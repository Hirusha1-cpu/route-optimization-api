<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Driver;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        // 💡 Global Scope එක (BelongsToCompany) active නිසා company_id manual filter කරන්න අවශ්‍ය නැත
        
        // Single Query එකකින් අද දවසේ සියලුම Counts සහ Status-wise Counts ලබාගැනීම
        $deliveryStats = Delivery::select(
            DB::raw("COUNT(*) as total_today"),
            DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending"),
            DB::raw("SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned"),
            DB::raw("SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) as in_transit"),
            DB::raw("SUM(CASE WHEN status = 'delivered' AND DATE(created_at) = '" . today()->toDateString() . "' THEN 1 ELSE 0 END) as delivered_today"),
            DB::raw("SUM(CASE WHEN status = 'failed' AND DATE(created_at) = '" . today()->toDateString() . "' THEN 1 ELSE 0 END) as failed_today")
        )->first();

        // Drivers ලා වෙනුවෙන් Run වන Queries
        $totalDrivers = Driver::count();
        $activeDrivers = Driver::whereHas('deliveries', function ($query) {
            $query->whereIn('status', ['assigned', 'in_transit']);
        })->count();

        return response()->json([
            'today_deliveries'      => (int) $deliveryStats->total_today,
            'pending_deliveries'    => (int) $deliveryStats->pending,
            'assigned_deliveries'   => (int) $deliveryStats->assigned,
            'in_transit_deliveries' => (int) $deliveryStats->in_transit,
            'delivered_today'       => (int) $deliveryStats->delivered_today,
            'failed_today'          => (int) $deliveryStats->failed_today,
            'active_drivers'        => $activeDrivers,
            'total_drivers'         => $totalDrivers,
        ]);
    }

    public function auditLogs(Request $request)
    {
        // 💡 Global Scope එක නිසා මෙතනත් where('company_id') අයින් කළ හැක.
        $logs = AuditLog::orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($logs);
    }
}
