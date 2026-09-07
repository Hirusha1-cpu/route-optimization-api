<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OSRMService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.osrm.url', 'http://router.project-osrm.org');
    }

    public function getDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $response = Http::get("{$this->baseUrl}/route/v1/driving/{$lng1},{$lat1};{$lng2},{$lat2}", [
            'overview' => 'false',
        ]);

        if ($response->failed()) {
            return $this->haversineDistance($lat1, $lng1, $lat2, $lng2);
        }

        $data = $response->json();
        return $data['routes'][0]['legs'][0]['distance'] / 1000; // Convert to km
    }

    public function getDuration(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $response = Http::get("{$this->baseUrl}/route/v1/driving/{$lng1},{$lat1};{$lng2},{$lat2}", [
            'overview' => 'false',
        ]);

        if ($response->failed()) {
            $distance = $this->haversineDistance($lat1, $lng1, $lat2, $lng2);
            return ($distance / 30) * 3600; // Default speed 30 km/h -> seconds
        }

        $data = $response->json();
        return $data['routes'][0]['legs'][0]['duration']; // seconds
    }

    /**
     * 💡 Distance සහ Duration Matrices දෙකම එකවර ලබා ගැනීම
     */
    public function getDistanceAndDurationMatrix(float $startLat, float $startLng, array $stops): array
    {
        $coordinates = [];
        $coordinates[] = "{$startLng},{$startLat}";
        
        foreach ($stops as $stop) {
            $coordinates[] = "{$stop['lng']},{$stop['lat']}";
        }

        $coordinatesString = implode(';', $coordinates);

        // 💡 OSRM එකට annotations=distance,duration යැවීමෙන් matrices දෙකම එකවර ලැබේ
        $response = Http::get("{$this->baseUrl}/table/v1/driving/{$coordinatesString}", [
            'annotations' => 'distance,duration'
        ]);

        if ($response->failed()) {
            return $this->fallbackMatrix($startLat, $startLng, $stops);
        }

        $data = $response->json();
        
        $distances = [];
        $durations = [];

        foreach ($data['distances'] as $i => $row) {
            $distances[$i] = [];
            $durations[$i] = [];
            foreach ($row as $j => $val) {
                $distances[$i][$j] = $val / 1000; // Convert to km
                $durations[$i][$j] = $data['durations'][$i][$j]; // seconds
            }
        }

        return [
            'distances' => $distances,
            'durations' => $durations,
        ];
    }

    protected function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    /**
     * 💡 Fallback එකකදී දුර සහ ගතවන කාලය (30km/h වේගයෙන්) දෙකම ගණනය කිරීම
     */
    protected function fallbackMatrix(float $startLat, float $startLng, array $stops): array
    {
        $allPoints = array_merge([['lat' => $startLat, 'lng' => $startLng]], $stops);
        $distances = [];
        $durations = [];

        foreach ($allPoints as $i => $point1) {
            $distances[$i] = [];
            $durations[$i] = [];
            foreach ($allPoints as $j => $point2) {
                $dist = $this->haversineDistance(
                    $point1['lat'], $point1['lng'],
                    $point2['lat'], $point2['lng']
                );
                $distances[$i][$j] = $dist;
                $durations[$i][$j] = ($dist / 30) * 3600; // 30 km/h baseline speed
            }
        }

        return [
            'distances' => $distances,
            'durations' => $durations,
        ];
    }
}
