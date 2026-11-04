<?php

namespace App\Http\Resources;

use App\Models\WorkShift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkShift
 */
class WorkShiftResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'category' => $this->category->value,
            'is_overnight' => (bool) $this->is_overnight,
            'is_active' => (bool) $this->is_active,
            'is_configured' => $this->isConfigured(),
        ];
    }
}

