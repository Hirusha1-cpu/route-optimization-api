<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class AISummaryService
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key') ?? '';
    }

    public function generateSummary(array $orderedStops, float $totalDistance, float $totalDuration): string
    {
        // API key එකක් සෙට් කර නොමැති නම් සාමාන්‍ය basic summary එක ලබා දීම
        if (empty($this->apiKey)) {
            return $this->generateBasicSummary($orderedStops, $totalDistance, $totalDuration);
        }

        try {
            $stopsSummary = [];
            foreach ($orderedStops as $index => $stop) {
                $stopsSummary[] = "- Stop " . ($index + 1) . ": " . ($stop['customer_name'] ?? 'Customer') . " at " . ($stop['address'] ?? 'No Address');
            }

            $prompt = "Generate a short, professional 1-2 sentence delivery route summary for a driver based on these metrics:\n" .
                       "Stops Order:\n" . implode("\n", $stopsSummary) . "\n" .
                       "Total Route Distance: {$totalDistance} km\n" .
                       "Total Driving Duration: {$totalDuration} minutes\n\n" .
                       "Identify the route's geographic pattern (e.g., heading north first) and give a brief helpful tip for the driver.";

            // 💡 🚀 FIX 1 & 2: API Key එක URL parameter එකක් ලෙස යැවීම සහ Laravel Http Body එක නිවැරදි කිරීම
            $url = "https://googleapis.com{$this->apiKey}";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
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
                // Gemini API එකෙන් එන JSON response එකෙන් text එක නිවැරදිව ලබා ගැනීම
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
        
        if ($totalDistance > 50) {
            $summary .= "This is a long route, consider starting early. ";
        }

        $hasEarlyWindows = false;
        $hasLateWindows = false;
        foreach ($orderedStops as $stop) {
            if (!empty($stop['window_start'])) {
                $windowStart = Carbon::parse($stop['window_start']);
                if ($windowStart->hour < 10) {
                    $hasEarlyWindows = true;
                }
                if ($windowStart->hour > 16) {
                    $hasLateWindows = true;
                }
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
