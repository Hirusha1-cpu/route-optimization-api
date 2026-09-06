<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AISummaryService
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    public function generateSummary(array $orderedStops, float $totalDistance, float $totalDuration): string
    {
        // If no API key, return a basic summary
        if (!$this->apiKey) {
            return $this->generateBasicSummary($orderedStops, $totalDistance, $totalDuration);
        }

        try {
            $stopsSummary = [];
            foreach ($orderedStops as $index => $stop) {
                $stopsSummary[] = "{$index + 1}. {$stop['customer_name']} at {$stop['address']}";
            }

            $prompt = "Generate a short, professional delivery route summary for a driver. " .
                       "Stops:\n" . implode("\n", $stopsSummary) . "\n" .
                       "Total distance: {$totalDistance} km\n" .
                       "Total duration: {$totalDuration} minutes\n\n" .
                       "Provide 1-2 sentences about the route order, what to watch out for, " .
                       "and any tips for the driver.";

            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent", [
                'key' => $this->apiKey,
            ], [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 
                       $this->generateBasicSummary($orderedStops, $totalDistance, $totalDuration);
            }

            return $this->generateBasicSummary($orderedStops, $totalDistance, $totalDuration);
        } catch (\Exception $e) {
            return $this->generateBasicSummary($orderedStops, $totalDistance, $totalDuration);
        }
    }

    protected function generateBasicSummary(array $orderedStops, float $totalDistance, float $totalDuration): string
    {
        $stopCount = count($orderedStops);
        $start = $orderedStops[0] ?? null;
        $end = $orderedStops[$stopCount - 1] ?? null;

        $summary = "This route covers {$stopCount} delivery stops. ";
        
        if ($start && $end) {
            $summary .= "Starting at {$start['customer_name']} and ending at {$end['customer_name']}. ";
        }

        $summary .= "Total distance is {$totalDistance} km with estimated duration of {$totalDuration} minutes. ";
        
        // Add basic tips
        if ($totalDistance > 50) {
            $summary .= "This is a long route, consider starting early. ";
        }

        // Check for time windows
        $hasEarlyWindows = false;
        $hasLateWindows = false;
        foreach ($orderedStops as $stop) {
            $windowStart = \Carbon\Carbon::parse($stop['window_start']);
            if ($windowStart->hour < 10) {
                $hasEarlyWindows = true;
            }
            if ($windowStart->hour > 16) {
                $hasLateWindows = true;
            }
        }

        if ($hasEarlyWindows && $hasLateWindows) {
            $summary .= "Mixed time windows - plan to deliver early morning stops first, afternoon stops later. ";
        } elseif ($hasEarlyWindows) {
            $summary .= "Has early morning time windows - prioritize these stops first. ";
        } elseif ($hasLateWindows) {
            $summary .= "Has afternoon time windows - take your time in the morning. ";
        }

        return $summary;
    }
}