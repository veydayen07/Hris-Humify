<?php

namespace App\Services;

use App\Models\WorkLocation;
use Illuminate\Support\Collection;

class GeofenceService
{
    /**
     * @return array{valid: bool, location: ?WorkLocation, distance_m: ?int}
     */
    public function validate(float $latitude, float $longitude): array
    {
        /** @var Collection<int, WorkLocation> $locations */
        $locations = WorkLocation::query()->where('is_active', true)->orderBy('id')->get();

        $nearest = null;
        $nearestDistance = null;

        foreach ($locations as $location) {
            $distance = $this->distanceMeters(
                $latitude,
                $longitude,
                (float) $location->latitude,
                (float) $location->longitude,
            );

            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearest = $location;
                $nearestDistance = $distance;
            }

            if ($distance <= $location->radius_meters) {
                return [
                    'valid' => true,
                    'location' => $location,
                    'distance_m' => (int) round($distance),
                ];
            }
        }

        return [
            'valid' => false,
            'location' => $nearest,
            'distance_m' => $nearestDistance === null ? null : (int) round($nearestDistance),
        ];
    }

    public function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lngTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }
}
