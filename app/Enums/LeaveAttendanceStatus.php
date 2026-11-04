<?php

namespace App\Enums;

enum LeaveAttendanceStatus: string
{
    case OnLeave = 'on_leave';
    case SickLeave = 'sick_leave';
    case AuthorizedAbsence = 'authorized_absence';

    public function toAttendanceStatus(): AttendanceStatus
    {
        return match ($this) {
            self::OnLeave => AttendanceStatus::OnLeave,
            self::SickLeave => AttendanceStatus::SickLeave,
            self::AuthorizedAbsence => AttendanceStatus::AuthorizedAbsence,
        };
    }
}
