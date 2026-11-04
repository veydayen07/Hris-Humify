<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CloseAttendanceDayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:close-day {--date= : The target date to close (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close attendance for previous working day by marking absent records with pending review for employees with no records.';

    /**
     * Execute the console command.
     */
    public function handle(AttendanceService $attendanceService): int
    {
        $dateOption = $this->option('date');
        $targetDate = $dateOption ? CarbonImmutable::parse($dateOption) : null;

        $created = $attendanceService->closeDay($targetDate);

        $this->info("Attendance closed successfully. {$created} absent record(s) created.");

        return self::SUCCESS;
    }
}

