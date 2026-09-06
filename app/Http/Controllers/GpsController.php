<?php

namespace App\Http\Controllers;

use App\Models\GpsLog;
use App\Models\Driver;
use App\Events\DriverLocationUpdated;
use Illuminate\Http\Request;

class GpsController extends Controller
{
    public function ping(Request $request)
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $driverId = $request->user()->driver_id;

        $gpsLog = GpsLog::create([
            'driver_id' => $driverId,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'logged_at' => now(),
        ]);

        // Broadcast to admin dashboard
        broadcast(new DriverLocationUpdated(
            $driverId,
            $request->lat,
            $request->lng,
            $request->user()->company_id
        ));

        return response()->json([
            'message' => 'Location updated',
            'logged_at' => $gpsLog->logged_at,
        ]);
    }

    public function history(Request $request)
    {
        $driverId = $request->user()->driver_id;

        $logs = GpsLog::where('driver_id', $driverId)
            ->orderBy('logged_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json($logs);
    }
}