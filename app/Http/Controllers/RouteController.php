<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Driver;
use App\Models\RouteModel;
use App\Models\AuditLog;
use App\Services\OSRMService;
use App\Services\RouteOptimizationService;
use App\Services\AISummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    protected OSRMService $osrm;
    protected RouteOptimizationService $optimizer;
    protected AISummaryService $ai;

    public function __construct(
        OSRMService $osrm,
        RouteOptimizationService $optimizer,
        AISummaryService $ai
    ) {
        $this->osrm = $osrm;
        $this->optimizer = $optimizer;
        $this->ai = $ai;
    }

    public function generate(Request $request)
    {
        $request->validate([
            'delivery_ids' => ['required', 'array', 'min:2', 'max:20'],
            'delivery_ids.*' => ['exists:deliveries,id'],
            'driver_id' => ['required', 'exists:drivers,id'],
            'start_lat' => ['nullable', 'numeric'],
            'start_lng' => ['nullable', 'numeric'],
        ]);

        $deliveries = Delivery::whereIn('id', $request->delivery_ids)
            ->where('status', 'pending')
            ->get();

        if ($deliveries->count() !== count($request->delivery_ids)) {
            return response()->json(['error' => 'Some deliveries are not pending'], 422);
        }

        $driver = Driver::find($request->driver_id);

        // Get driver start location or default
        $startLat = $request->start_lat ?? 6.9271;
        $startLng = $request->start_lng ?? 79.8612;

        // Get coordinates for all stops
        $stops = $deliveries->map(function ($delivery) {
            return [
                'id' => $delivery->id,
                'lat' => $delivery->lat,
                'lng' => $delivery->lng,
                'customer_name' => $delivery->customer_name,
                'address' => $delivery->address,
                'window_start' => $delivery->window_start,
                'window_end' => $delivery->window_end,
                'cod_amount' => $delivery->cod_amount,
            ];
        })->toArray();

        // Get distance matrix from OSRM
        $matrix = $this->osrm->getDistanceMatrix($startLat, $startLng, $stops);

        // Optimize route using nearest-neighbor
        $orderedStops = $this->optimizer->nearestNeighbor($matrix, $stops);

        // Calculate total distance and duration
        $totalDistance = 0;
        $totalDuration = 0;
        $orderedStopsList = [];

        $prevLat = $startLat;
        $prevLng = $startLng;

        foreach ($orderedStops as $index => $stop) {
            $distance = $this->osrm->getDistance($prevLat, $prevLng, $stop['lat'], $stop['lng']);
            $duration = $this->osrm->getDuration($prevLat, $prevLng, $stop['lat'], $stop['lng']);

            $totalDistance += $distance;
            $totalDuration += $duration;

            $orderedStopsList[] = [
                'stop_id' => $stop['id'],
                'customer_name' => $stop['customer_name'],
                'address' => $stop['address'],
                'lat' => $stop['lat'],
                'lng' => $stop['lng'],
                'distance_from_prev_km' => round($distance, 2),
                'duration_from_prev_min' => round($duration / 60, 2),
                'window_start' => $stop['window_start'],
                'window_end' => $stop['window_end'],
                'cod_amount' => $stop['cod_amount'],
            ];

            $prevLat = $stop['lat'];
            $prevLng = $stop['lng'];
        }

        // Generate AI summary
        $aiSummary = $this->ai->generateSummary($orderedStopsList, $totalDistance, $totalDuration);

        // Save route
        $route = RouteModel::create([
            'company_id' => $request->user()->company_id,
            'driver_id' => $request->driver_id,
            'date' => now()->toDateString(),
            'ordered_stops' => $orderedStopsList,
            'total_distance_km' => round($totalDistance, 2),
            'total_duration_min' => round($totalDuration / 60),
            'ai_summary' => $aiSummary,
        ]);

        // Update deliveries status to assigned
        foreach ($deliveries as $delivery) {
            $delivery->update([
                'status' => 'assigned',
                'driver_id' => $request->driver_id,
            ]);
        }

        AuditLog::record('route.generated', $route, [
            'delivery_count' => count($deliveries),
            'total_distance' => $totalDistance,
            'total_duration' => $totalDuration,
        ]);

        return response()->json($route, 201);
    }

    public function assign(Request $request, RouteModel $route)
    {
        $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
        ]);

        $oldDriverId = $route->driver_id;
        $route->update(['driver_id' => $request->driver_id]);

        // Update all deliveries to assigned status
        $deliveryIds = collect($route->ordered_stops)->pluck('stop_id');
        Delivery::whereIn('id', $deliveryIds)->update([
            'status' => 'assigned',
            'driver_id' => $request->driver_id,
        ]);

        AuditLog::record('route.assigned', $route, [
            'old_driver_id' => $oldDriverId,
            'new_driver_id' => $request->driver_id,
        ]);

        return response()->json($route);
    }

    public function index(Request $request)
    {
        $routes = RouteModel::with('driver')
            ->when($request->date, function ($query, $date) {
                return $query->whereDate('date', $date);
            })
            ->paginate(20);

        return response()->json($routes);
    }

    public function show(RouteModel $route)
    {
        return response()->json($route->load('driver'));
    }
}