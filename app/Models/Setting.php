<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting.{$key}", 60, fn () => static::query()->where('key', $key)->first());

        if ($setting === null) {
            return $default;
        }

        return $setting->value['value'] ?? $setting->value ?? $default;
    }

    public static function setValue(string $key, mixed $value): self
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => ['value' => $value]],
        );

        Cache::forget("setting.{$key}");

        return $setting;
    }

    public static function companyTimezone(): string
    {
        return (string) static::getValue('company_timezone', config('humify.company_timezone', 'Asia/Jakarta'));
    }

    public static function faceMatchThreshold(): float
    {
        return (float) static::getValue('face_match_threshold', config('humify.face_match_threshold', 80));
    }

    public static function closeDayTime(): string
    {
        return (string) static::getValue('close_day_time', config('humify.close_day_time', '01:00'));
    }
}
