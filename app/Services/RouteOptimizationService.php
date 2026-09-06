<?php

namespace App\Services;

class RouteOptimizationService
{
    /**
     * Nearest-neighbor algorithm for route optimization
     * This is a heuristic, not optimal (TSP is NP-hard)
     */
    public function nearestNeighbor(array $matrix, array $stops): array
    {
        $numStops = count($stops);
        if ($numStops === 0) {
            return [];
        }

        $visited = array_fill(0, $numStops, false);
        $ordered = [];
        $current = 0; // Start at index 0 (after start point in matrix)

        // Mark start point as visited (index 0 in matrix)
        $visited[0] = true;

        for ($i = 0; $i < $numStops; $i++) {
            $nearest = -1;
            $nearestDistance = INF;

            for ($j = 1; $j <= $numStops; $j++) {
                if (!$visited[$j]) {
                    $distance = $matrix[$current][$j] ?? INF;
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
            $ordered[] = $stops[$nearest - 1]; // Adjust for matrix offset
            $current = $nearest;
        }

        return $ordered;
    }

    /**
     * Optimize route with time window consideration (soft constraint)
     */
    public function optimizeWithTimeWindows(array $matrix, array $stops): array
    {
        // First get base order from nearest-neighbor
        $ordered = $this->nearestNeighbor($matrix, $stops);

        // Check if any time windows are violated
        // If violated, try to reorder to fix (simple 2-opt style swap)
        $violations = $this->checkTimeWindowViolations($ordered);
        
        if (!empty($violations) && count($ordered) > 2) {
            // Try simple swap to fix violations
            for ($i = 0; $i < count($ordered) - 1; $i++) {
                for ($j = $i + 1; $j < count($ordered); $j++) {
                    // Swap and check if violations improve
                    $swapped = $ordered;
                    $temp = $swapped[$i];
                    $swapped[$i] = $swapped[$j];
                    $swapped[$j] = $temp;

                    $newViolations = $this->checkTimeWindowViolations($swapped);
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

    protected function checkTimeWindowViolations(array $orderedStops): array
    {
        $violations = [];
        $currentTime = now();

        foreach ($orderedStops as $index => $stop) {
            // Estimate arrival time (simplified)
            $arrivalTime = $currentTime->copy()->addMinutes($index * 15);
            $windowStart = \Carbon\Carbon::parse($stop['window_start']);
            $windowEnd = \Carbon\Carbon::parse($stop['window_end']);

            if ($arrivalTime < $windowStart || $arrivalTime > $windowEnd) {
                $violations[] = [
                    'stop_index' => $index,
                    'arrival' => $arrivalTime->toDateTimeString(),
                    'window_start' => $windowStart->toDateTimeString(),
                    'window_end' => $windowEnd->toDateTimeString(),
                ];
            }
        }

        return $violations;
    }
}