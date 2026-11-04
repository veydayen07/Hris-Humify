<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case LeftEarly = 'left_early';
    case AuthorizedAbsence = 'authorized_absence';
    case SickLeave = 'sick_leave';
    case OnLeave = 'on_leave';
    case Absent = 'absent';
    case DayOff = 'day_off';

    public function isPresentLike(): bool
    {
        return in_array($this, [self::Present, self::Late, self::LeftEarly], true);
    }

    public function isLeaveLike(): bool
    {
        return in_array($this, [self::OnLeave, self::SickLeave, self::AuthorizedAbsence], true);
    }
}
