<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\CodLedger;
use App\Models\Driver;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReconciliationController extends Controller
{
    public function daily(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
        ]);

        $date = $request->date;

        $query = Delivery::whereDate('created_at', $date)
            ->whereIn('status', ['delivered', 'failed']);

        if ($request->driver_id) {
            $query->where('driver_id', $request->driver_id);
        }

        $deliveries = $query->with('driver')->get();

        $results = [];
        $discrepancies = [];

        foreach ($deliveries->groupBy('driver_id') as $driverId => $driverDeliveries) {
            $driver = Driver::find($driverId);
            if (!$driver) continue;

            $expectedTotal = $driverDeliveries->sum('cod_amount');

            $actualTotal = CodLedger::where('driver_id', $driverId)
                ->whereDate('created_at', $date)
                ->sum('amount_collected');

            $discrepancy = $expectedTotal - $actualTotal;

            $results[] = [
                'driver_id' => $driverId,
                'driver_name' => $driver->name,
                'delivery_count' => $driverDeliveries->count(),
                'expected_total' => round($expectedTotal, 2),
                'actual_collected' => round($actualTotal, 2),
                'discrepancy' => round($discrepancy, 2),
                'has_discrepancy' => abs($discrepancy) > 0.01,
            ];

            if (abs($discrepancy) > 0.01) {
                $discrepancies[] = [
                    'driver_id' => $driverId,
                    'driver_name' => $driver->name,
                    'amount' => round($discrepancy, 2),
                ];
            }
        }

        // Log reconciliation
        AuditLog::record('reconciliation.daily', $request->user(), [
            'date' => $date,
            'discrepancies' => $discrepancies,
        ]);

        return response()->json([
            'date' => $date,
            'results' => $results,
            'discrepancies' => $discrepancies,
            'total_discrepancy' => array_sum(array_column($discrepancies, 'amount')),
        ]);
    }

    public function driverWallet(Driver $driver)
    {
        $balance = $driver->calculatedWalletBalance();

        return response()->json([
            'driver_id' => $driver->id,
            'name' => $driver->name,
            'wallet_balance' => round($balance, 2),
            'collections' => $driver->codLedgerEntries()
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }
}