<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Driver;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $companyId = $request->user()->company_id;

        $stats = [
            'today_deliveries' => Delivery::whereDate('created_at', today())
                ->where('company_id', $companyId)
                ->count(),
            
            'pending_deliveries' => Delivery::where('status', 'pending')
                ->where('company_id', $companyId)
                ->count(),
            
            'assigned_deliveries' => Delivery::where('status', 'assigned')
                ->where('company_id', $companyId)
                ->count(),
            
            'in_transit_deliveries' => Delivery::where('status', 'in_transit')
                ->where('company_id', $companyId)
                ->count(),
            
            'delivered_today' => Delivery::whereDate('created_at', today())
                ->where('status', 'delivered')
                ->where('company_id', $companyId)
                ->count(),
            
            'failed_today' => Delivery::whereDate('created_at', today())
                ->where('status', 'failed')
                ->where('company_id', $companyId)
                ->count(),
            
            'active_drivers' => Driver::where('company_id', $companyId)
                ->whereHas('deliveries', function ($query) {
                    $query->whereIn('status', ['assigned', 'in_transit']);
                })
                ->count(),
            
            'total_drivers' => Driver::where('company_id', $companyId)->count(),
        ];

        return response()->json($stats);
    }

    public function auditLogs(Request $request)
    {
        $logs = \App\Models\AuditLog::where('company_id', $request->user()->company_id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($logs);
    }
}