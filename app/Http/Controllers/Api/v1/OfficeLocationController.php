<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OfficeLocationController extends Controller
{
    private const DEFAULT_LAT = -6.2087634;
    private const DEFAULT_LNG = 106.845599;
    private const DEFAULT_RADIUS = 100.0;
    private const DEFAULT_ADDRESS = 'Jl Nakula 6';
    private const DEFAULT_NAME = 'Humify Head Office';

    /**
     * Get current office location configuration.
     */
    public function show(): JsonResponse
    {
        $dept = Department::whereNotNull('description')
            ->where('description', '!=', '')
            ->first() ?: Department::first();

        $config = null;
        if ($dept && ! empty($dept->description)) {
            $config = json_decode($dept->description, true);
        }

        $lat = isset($config['latitude']) ? (float) $config['latitude'] : self::DEFAULT_LAT;
        $lng = isset($config['longitude']) ? (float) $config['longitude'] : self::DEFAULT_LNG;
        $radius = isset($config['radius_meters']) ? (float) $config['radius_meters'] : self::DEFAULT_RADIUS;
        $address = $config['address'] ?? self::DEFAULT_ADDRESS;
        $name = $config['name'] ?? self::DEFAULT_NAME;

        return response()->json([
            'success' => true,
            'data' => [
                'name' => (string) $name,
                'address' => (string) $address,
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
                'radius_meters' => (float) $radius,
                'updated_at' => $config['updated_at'] ?? null,
            ],
        ]);
    }

    /**
     * Update office location for all departments in a single query (Cara 2).
     * Accessible only by HR / Super Admin.
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'radius_meters' => ['nullable', 'numeric', 'min:10', 'max:5000'],
            'name' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        $lat = (float) $request->input('latitude');
        $lng = (float) $request->input('longitude');
        $radius = (float) $request->input('radius_meters', self::DEFAULT_RADIUS);
        $name = $request->input('name', self::DEFAULT_NAME);
        $address = $request->input('address', self::DEFAULT_ADDRESS);

        $payload = json_encode([
            'name' => $name,
            'address' => $address,
            'latitude' => $lat,
            'longitude' => $lng,
            'radius_meters' => $radius,
            'updated_at' => now('Asia/Jakarta')->toIso8601String(),
            'updated_by' => (string) $request->user()->id,
        ]);

        // Cara 2: Update seluruh departemen dalam 1 kali query database
        Department::query()->update(['description' => $payload]);

        return response()->json([
            'success' => true,
            'message' => 'Lokasi kantor berhasil disimpan dan disinkronkan ke seluruh departemen.',
            'data' => [
                'name' => $name,
                'address' => $address,
                'latitude' => $lat,
                'longitude' => $lng,
                'radius_meters' => $radius,
                'updated_at' => now('Asia/Jakarta')->toIso8601String(),
            ],
        ]);
    }
}
