<?php

namespace App\Http\Resources;

use App\Models\WorkLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkLocation
 */
class WorkLocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'radius_meters' => (int) $this->radius_meters,
            'is_active' => (bool) $this->is_active,
        ];
    }
}

