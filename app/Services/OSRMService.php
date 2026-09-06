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
            // Default speed 30 km/h
            $distance = $this->haversineDistance($lat1, $lng1, $lat2, $lng2);
            return ($distance / 30) * 3600; // seconds
        }

        $data = $response->json();
        return $data['routes'][0]['legs'][0]['duration']; // seconds
    }

    public function getDistanceMatrix(float $startLat, float $startLng, array $stops): array
    {
        $coordinates = [];
        
        // Add start location
        $coordinates[] = "{$startLng},{$startLat}";
        
        // Add all stops
        foreach ($stops as $stop) {
            $coordinates[] = "{$stop['lng']},{$stop['lat']}";
        }

        $coordinatesString = implode(';', $coordinates);

        $response = Http::get("{$this->baseUrl}/table/v1/driving/{$coordinatesString}");

        if ($response->failed()) {
            return $this->fallbackDistanceMatrix($startLat, $startLng, $stops);
        }

        $data = $response->json();
        
        // Extract distances
        $matrix = [];
        foreach ($data['distances'] as $i => $row) {
            $matrix[$i] = [];
            foreach ($row as $j => $distance) {
                $matrix[$i][$j] = $distance / 1000; // Convert to km
            }
        }

        return $matrix;
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

    protected function fallbackDistanceMatrix(float $startLat, float $startLng, array $stops): array
    {
        $allPoints = array_merge([['lat' => $startLat, 'lng' => $startLng]], $stops);
        $matrix = [];

        foreach ($allPoints as $i => $point1) {
            $matrix[$i] = [];
            foreach ($allPoints as $j => $point2) {
                $matrix[$i][$j] = $this->haversineDistance(
                    $point1['lat'], $point1['lng'],
                    $point2['lat'], $point2['lng']
                );
            }
        }

        return $matrix;
    }
}