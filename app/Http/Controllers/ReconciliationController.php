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

        // 💡 1. Drivers ලගේ Expected COD Amounts එකම Query එකකින් Group කර ලබා ගැනීම
        // 🚨 FIX: සල්ලි බලාපොරොත්තු වෙන්නේ 'delivered' ඒවගෙන් පමණි.
        $deliveryQuery = Delivery::select('driver_id', DB::raw('SUM(cod_amount) as expected_total'))
            ->whereDate('created_at', $date)
            ->where('status', 'delivered')
            ->groupBy('driver_id');

        // 💡 2. Drivers ලා එකතු කළ ඇත්තම සල්ලි (Actual COD) Group කර ලබා ගැනීම
        $ledgerQuery = CodLedger::select('driver_id', DB::raw('SUM(amount_collected) as actual_total'))
            ->whereDate('created_at', $date)
            ->groupBy('driver_id');

        if ($request->driver_id) {
            $deliveryQuery->where('driver_id', $request->driver_id);
            $ledgerQuery->where('driver_id', $request->driver_id);
        }

        $expectedData = $deliveryQuery->pluck('expected_total', 'driver_id');
        $actualData = $ledgerQuery->pluck('actual_total', 'driver_id');

        // 💡 3. Drivers ලගේ විස්තර Eager Load කර එකවර ලබා ගැනීම
        $driverIds = collect($expectedData->keys()->merge($actualData->keys()))->unique();
        $drivers = Driver::whereIn('id', $driverIds)->get()->keyBy('id');

        $results = [];
        $discrepancies = [];

        foreach ($driverIds as $driverId) {
            $driver = $drivers->get($driverId);
            if (!$driver) continue;

            $expectedTotal = $expectedData->get($driverId, 0.00);
            $actualTotal = $actualData->get($driverId, 0.00);
            $discrepancy = $expectedTotal - $actualTotal;

            // Delivery Counts (delivered පමණක් නොව, failed ඇතුළු සියල්ල දැනගැනීමට)
            $deliveryCount = Delivery::where('driver_id', $driverId)
                ->whereDate('created_at', $date)
                ->whereIn('status', ['delivered', 'failed'])
                ->count();

            $results[] = [
                'driver_id' => $driverId,
                'driver_name' => $driver->name,
                'delivery_count' => $deliveryCount,
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

        AuditLog::record('reconciliation.daily', $request->user() ?? new \App\Models\Company(), [
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
        // 💡 Driver Profile Model එකේ 'calculatedWalletBalance' ලියා තිබිය යුතුය
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
