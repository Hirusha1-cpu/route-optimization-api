<?php

namespace App\Services;

use Carbon\Carbon;

class RouteOptimizationService
{
    /**
     * Nearest-neighbor algorithm using OSRM Distance Matrix
     */
    public function nearestNeighbor(array $distanceMatrix, array $stops): array
    {
        $numStops = count($stops);
        if ($numStops === 0) {
            return [];
        }

        $visited = array_fill(0, $numStops + 1, false);
        $ordered = [];
        $current = 0; // Start point (Depot / Driver starting location)

        $visited[0] = true;

        for ($i = 0; $i < $numStops; $i++) {
            $nearest = -1;
            $nearestDistance = INF;

            for ($j = 1; $j <= $numStops; $j++) {
                if (!$visited[$j]) {
                    $distance = $distanceMatrix[$current][$j] ?? INF;
                    if ($distance < $nearestDistance) {
                        $nearestDistance = $distance;
                        $nearest = $j;
                    }
                }
            }

            if ($nearest === -1) {
                break;
            }

            $visited[$nearest] = true;
            $ordered[] = $stops[$nearest - 1]; // Adjust for index offset
            $current = $nearest;
        }

        return $ordered;
    }

    /**
     * Optimize route with real OSRM Durations and Soft Time Windows Constraint
     */
    public function optimizeWithTimeWindows(array $matrix, array $stops): array
    {
        $distanceMatrix = $matrix['distances'];
        $durationMatrix = $matrix['durations'];

        // 1. Get base order from nearest-neighbor heuristic
        $ordered = $this->nearestNeighbor($distanceMatrix, $stops);

        // 2. Check violations using real OSRM durations
        $violations = $this->checkTimeWindowViolations($ordered, $durationMatrix);
        
        // 3. Simple 2-opt swap to reduce time window violations if any exist
        if (!empty($violations) && count($ordered) > 2) {
            for ($i = 0; $i < count($ordered) - 1; $i++) {
                for ($j = $i + 1; $j < count($ordered); $j++) {
                    $swapped = $ordered;
                    $temp = $swapped[$i];
                    $swapped[$i] = $swapped[$j];
                    $swapped[$j] = $temp;

                    $newViolations = $this->checkTimeWindowViolations($swapped, $durationMatrix);
                    if (count($newViolations) < count($violations)) {
                        $ordered = $swapped;
                        $violations = $newViolations;
                        break 2;
                    }
                }
            }
        }

        return $ordered;
    }

    /**
     * 💡 Real OSRM duration values පාවිච්චි කර නිවැරදිම ETA එක ගණනය කිරීම
     */
    protected function checkTimeWindowViolations(array $orderedStops, array $durationMatrix): array
    {
        $violations = [];
        $currentTime = Carbon::today()->setHour(8)->setMinute(0); // Assume deliveries start at 8:00 AM
        $currentMatrixIndex = 0; // Start location index

        foreach ($orderedStops as $index => $stop) {
            // OSRM එකෙන් දෙන travel time (seconds) එක minutes වලට හැරවීම (+ 10 mins package handover time)
            $travelTimeSeconds = $durationMatrix[$currentMatrixIndex][$index + 1] ?? 0;
            $travelTimeMinutes = ceil($travelTimeSeconds / 60) + 10; 

            $arrivalTime = $currentTime->copy()->addMinutes($travelTimeMinutes);
            
            // Format check for soft windows (e.g., "09:00:00")
            $windowStart = Carbon::parse(Carbon::today()->toDateString() . ' ' . $stop['window_start']);
            $windowEnd = Carbon::parse(Carbon::today()->toDateString() . ' ' . $stop['window_end']);

            if ($arrivalTime->lt($windowStart) || $arrivalTime->gt($windowEnd)) {
                $violations[] = [
                    'stop_id' => $stop['id'] ?? $index,
                    'arrival' => $arrivalTime->toTimeString(),
                    'window' => $stop['window_start'] . ' - ' . $stop['window_end'],
                ];
            }

            // Next stop එකට යන්න කලින් current positions update කිරීම
            $currentTime = $arrivalTime;
            $currentMatrixIndex = $index + 1;
        }

        return $violations;
    }
}
