<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\RouteModel;
use App\Models\Driver;
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
            'delivery_ids'   => ['required', 'array', 'min:2', 'max:20'],
            'delivery_ids.*' => ['exists:deliveries,id'],
            'driver_id'      => ['required', 'exists:drivers,id'],
            'start_lat'      => ['nullable', 'numeric'],
            'start_lng'      => ['nullable', 'numeric'],
        ]);

        $deliveries = Delivery::whereIn('id', $request->delivery_ids)
            ->where('status', 'pending')
            ->get();

        if ($deliveries->count() !== count($request->delivery_ids)) {
            return response()->json(['error' => 'Some deliveries are not pending or belong to another company'], 422);
        }

        $startLat = $request->start_lat ?? 6.9271;
        $startLng = $request->start_lng ?? 79.8612;

        $stops = $deliveries->map(function ($delivery) {
            return [
                'id'            => $delivery->id,
                'lat'           => (float) $delivery->lat,
                'lng'           => (float) $delivery->lng,
                'customer_name' => $delivery->customer_name,
                'address'       => $delivery->address,
                'window_start'  => $delivery->window_start,
                'window_end'    => $delivery->window_end,
                'cod_amount'    => $delivery->cod_amount,
            ];
        })->toArray();

        // දුර සහ කාලය Matrices දෙකම එකම API Request එකකින් ලබා ගැනීම
        $matrix = $this->osrm->getDistanceAndDurationMatrix($startLat, $startLng, $stops);

        // Algorithm එකට pass කරන්නේ optimized matrix mapping එකයි
        $orderedStops = $this->optimizer->optimizeWithTimeWindows($matrix, $stops);

        $totalDistance = 0;
        $totalDuration = 0;
        $orderedStopsList = [];
        $currentIndex = 0; // Depot / Start position in matrix

        foreach ($orderedStops as $index => $stop) {
            $matrixIndex = array_search($stop['id'], array_column($stops, 'id')) + 1;

            $distance = $matrix['distances'][$currentIndex][$matrixIndex] ?? 0;
            $duration = $matrix['durations'][$currentIndex][$matrixIndex] ?? 0;

            $totalDistance += $distance;
            $totalDuration += $duration;

            $orderedStopsList[] = [
                'stop_id'                => $stop['id'],
                'customer_name'          => $stop['customer_name'],
                'address'                => $stop['address'],
                'lat'                    => $stop['lat'],
                'lng'                    => $stop['lng'],
                'distance_from_prev_km'  => round($distance, 2),
                'duration_from_prev_min' => round($duration / 60, 2),
                'window_start'           => $stop['window_start'],
                'window_end'             => $stop['window_end'],
                'cod_amount'             => $stop['cod_amount'],
            ];

            $currentIndex = $matrixIndex;
        }

        $totalDurationMinutes = ceil($totalDuration / 60);

        // Gemini SDK එක හරහා AI summary එකක් ලබා ගැනීම
        $aiSummary = $this->ai->generateSummary($orderedStopsList, $totalDistance, $totalDurationMinutes);

        // 💡 🚀 FIX: use එක ඇතුළෙන් නොතිබූ $requestDeliveryIds ඉවත් කර $request පමණක් ඉතිරි කිරීම
        $route = DB::transaction(function () use ($request, $orderedStopsList, $totalDistance, $totalDurationMinutes, $aiSummary) {
            
            $newRoute = RouteModel::create([
                'driver_id'          => $request->driver_id,
                'date'               => now()->toDateString(),
                'ordered_stops'      => $orderedStopsList,
                'total_distance_km'  => round($totalDistance, 2),
                'total_duration_min' => $totalDurationMinutes,
                'ai_summary'         => $aiSummary,
            ]);

            // Bulk Update
            Delivery::whereIn('id', $request->delivery_ids)->update([
                'status'    => 'assigned',
                'driver_id' => $request->driver_id,
            ]);

            return $newRoute;
        });

        AuditLog::record('route.generated', $route, [
            'delivery_count' => count($deliveries),
            'total_distance' => round($totalDistance, 2),
            'total_duration' => $totalDurationMinutes,
        ]);

        return response()->json($route, 201);
    }

    public function assign(Request $request, RouteModel $route)
    {
        $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
        ]);

        $oldDriverId = $route->driver_id;

        DB::transaction(function () use ($request, $route) {
            $route->update(['driver_id' => $request->driver_id]);

            $deliveryIds = collect($route->ordered_stops)->pluck('stop_id');
            Delivery::whereIn('id', $deliveryIds)->update([
                'status'    => 'assigned',
                'driver_id' => $request->driver_id,
            ]);
        });

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
