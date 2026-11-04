<?php

namespace App\Enums;

enum AttendanceAttemptOutcome: string
{
    case Success = 'success';
    case Failed = 'failed';
}
