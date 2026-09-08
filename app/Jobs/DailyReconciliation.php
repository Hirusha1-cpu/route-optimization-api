<?php

namespace App\Jobs;

use App\Models\Delivery;
use App\Models\CodLedger;
use App\Models\Driver;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DailyReconciliation implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // 💡 ඊයේ දවසේ ඩේටා පිරික්සීම
        $date = now()->subDay()->toDateString();

        // 💡 🚀 FIX 1: සියලුම සමාගම් වල Drivers ලගේ Expected COD Amounts එකවර ලබා ගැනීම (Global Scope එක Bypass කර)
        $expectedData = Delivery::withoutGlobalScopes()
            ->select('company_id', 'driver_id', DB::raw('SUM(cod_amount) as expected_total'))
            ->whereDate('created_at', $date)
            ->where('status', 'delivered')
            ->groupBy('company_id', 'driver_id')
            ->get()
            ->groupBy('company_id');

        // 💡 🚀 FIX 2: සියලුම සමාගම් වල Drivers ලා එකතු කළ ඇත්තම සල්ලි (Actual COD) එකවර ලබා ගැනීම
        $actualData = CodLedger::withoutGlobalScopes()
            ->select('driver_id', DB::raw('SUM(amount_collected) as actual_total'))
            ->whereDate('created_at', $date)
            ->groupBy('driver_id')
            ->pluck('actual_total', 'driver_id');

        // 💡 🚀 FIX 3: Drivers ලගේ Profile විස්තර එකවර ලබා ගැනීම
        $driverIds = collect($expectedData->values()->collapse()->pluck('driver_id'))
            ->merge($actualData->keys())
            ->unique();
            
        $drivers = Driver::withoutGlobalScopes()->whereIn('id', $driverIds)->get()->keyBy('id');

        // එක් එක් Company එක අනුව වෙන් වෙන් වශයෙන් පිරික්සීම (Multi-Tenancy Safety)
        foreach ($expectedData as $companyId => $companyDeliveries) {
            $companyDiscrepancies = [];

            foreach ($companyDeliveries as $deliveryRow) {
                $driverId = $deliveryRow->driver_id;
                $driver = $drivers->get($driverId);
                if (!$driver) continue;

                $expectedTotal = (float) $deliveryRow->expected_total;
                $actualTotal = (float) $actualData->get($driverId, 0.00);
                $discrepancy = $expectedTotal - $actualTotal;

                if (abs($discrepancy) > 0.01) {
                    $companyDiscrepancies[] = [
                        'driver_id'   => $driverId,
                        'driver_name' => $driver->name,
                        'expected'    => round($expectedTotal, 2),
                        'actual'      => round($actualTotal, 2),
                        'difference'  => round($discrepancy, 2),
                    ];
                }
            }

            // 💡 🚀 FIX 4: Audit Logs සටහන් කරන්නේ අදාළ Company ID එකට අනුකූලව පමණි
            if (!empty($companyDiscrepancies)) {
                
                // AuditLog සේවාවට company context එක ලබා දීම
                $company = \App\Models\Company::withoutGlobalScopes()->find($companyId);
                if ($company) {
                    AuditLog::record('reconciliation.discrepancy_found', $company, [
                        'date' => $date,
                        'discrepancies' => $companyDiscrepancies,
                        'actor' => 'system_cron'
                    ]);
                }

                Log::warning("Reconciliation discrepancies found for Company ID: {$companyId}", [
                    'date'    => $date,
                    'count'   => count($companyDiscrepancies),
                    'details' => $companyDiscrepancies,
                ]);
            } else {
                Log::info("Reconciliation completed successfully for Company ID: {$companyId}", ['date' => $date]);
            }
        }
    }
}
