<?php

namespace App\Enums;

enum AttendanceAttemptType: string
{
    case CheckIn = 'check_in';
    case CheckOut = 'check_out';
}
