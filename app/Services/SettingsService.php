<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;

class SettingsService
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @return array{company_timezone: string, face_match_threshold: float, close_day_time: string}
     */
    public function get(): array
    {
        return [
            'company_timezone' => Setting::companyTimezone(),
            'face_match_threshold' => Setting::faceMatchThreshold(),
            'close_day_time' => Setting::closeDayTime(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{company_timezone: string, face_match_threshold: float, close_day_time: string}
     */
    public function update(User $actor, array $data): array
    {
        if (isset($data['company_timezone'])) {
            Setting::setValue('company_timezone', $data['company_timezone']);
        }

        if (isset($data['face_match_threshold'])) {
            Setting::setValue('face_match_threshold', (float) $data['face_match_threshold']);
        }

        if (isset($data['close_day_time'])) {
            Setting::setValue('close_day_time', $data['close_day_time']);
        }

        $this->auditLogger->log($actor, 'settings.updated', null, $data);

        return $this->get();
    }
}

