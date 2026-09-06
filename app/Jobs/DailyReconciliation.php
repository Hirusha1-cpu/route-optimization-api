<?php

namespace App\Jobs;

use App\Models\Delivery;
use App\Models\CodLedger;
use App\Models\Driver;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DailyReconciliation implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $date = now()->subDay()->toDateString();

        $deliveries = Delivery::whereDate('created_at', $date)
            ->whereIn('status', ['delivered'])
            ->with('driver')
            ->get();

        $discrepancies = [];

        foreach ($deliveries->groupBy('driver_id') as $driverId => $driverDeliveries) {
            $driver = Driver::find($driverId);
            if (!$driver) continue;

            $expectedTotal = $driverDeliveries->sum('cod_amount');
            $actualTotal = CodLedger::where('driver_id', $driverId)
                ->whereDate('created_at', $date)
                ->sum('amount_collected');

            $discrepancy = $expectedTotal - $actualTotal;

            if (abs($discrepancy) > 0.01) {
                $discrepancies[] = [
                    'driver_id' => $driverId,
                    'driver_name' => $driver->name,
                    'expected' => $expectedTotal,
                    'actual' => $actualTotal,
                    'difference' => $discrepancy,
                ];
            }
        }

        // Log discrepancies
        if (!empty($discrepancies)) {
            AuditLog::record('reconciliation.discrepancy_found', 
                new \App\Models\Company(), 
                ['discrepancies' => $discrepancies]
            );

            Log::warning("Reconciliation discrepancies found", [
                'date' => $date,
                'count' => count($discrepancies),
                'details' => $discrepancies,
            ]);
        } else {
            Log::info("Reconciliation completed successfully", ['date' => $date]);
        }
    }
}