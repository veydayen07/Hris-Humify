<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

class CompanyClock
{
    public function timezone(): string
    {
        return Setting::companyTimezone();
    }

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC');
    }

    public function nowInCompanyTz(): CarbonImmutable
    {
        return $this->now()->timezone($this->timezone());
    }

    public function attendanceDate(?CarbonInterface $moment = null): CarbonImmutable
    {
        $local = ($moment ?? $this->now())->timezone($this->timezone());

        return $local->startOfDay();
    }

    public function toUtc(CarbonInterface $moment): CarbonImmutable
    {
        return CarbonImmutable::parse($moment)->utc();
    }

    public function isWorkingDay(CarbonInterface $date): bool
    {
        $dayOfWeek = (int) $date->timezone($this->timezone())->dayOfWeek;

        return $dayOfWeek >= CarbonInterface::MONDAY && $dayOfWeek <= CarbonInterface::FRIDAY;
    }

    public function isWeekend(CarbonInterface $date): bool
    {
        return ! $this->isWorkingDay($date);
    }

    /**
     * @return list<CarbonImmutable>
     */
    public function workingDaysBetween(CarbonInterface $start, CarbonInterface $end): array
    {
        $period = CarbonPeriod::create(
            $start->timezone($this->timezone())->startOfDay(),
            $end->timezone($this->timezone())->startOfDay(),
        );

        $days = [];

        foreach ($period as $day) {
            $immutable = CarbonImmutable::instance($day)->timezone($this->timezone())->startOfDay();

            if ($this->isWorkingDay($immutable)) {
                $days[] = $immutable;
            }
        }

        return $days;
    }

    public function countWorkingDays(CarbonInterface $start, CarbonInterface $end): float
    {
        return (float) count($this->workingDaysBetween($start, $end));
    }

    public function shiftDateTime(CarbonInterface $attendanceDate, string $time, bool $nextDay = false): CarbonImmutable
    {
        $localDate = $attendanceDate->timezone($this->timezone())->toDateString();
        $local = CarbonImmutable::parse("{$localDate} {$time}", $this->timezone());

        if ($nextDay) {
            $local = $local->addDay();
        }

        return $local->utc();
    }
}
