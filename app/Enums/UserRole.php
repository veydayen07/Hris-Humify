<?php

namespace App\Enums;

enum UserRole: string
{
    case Employee = 'employee';
    case Manager = 'manager';
    case HrAdmin = 'hr_admin';
    case SuperAdmin = 'super_admin';

    public function isHrOrAbove(): bool
    {
        return in_array($this, [self::HrAdmin, self::SuperAdmin], true);
    }

    public function isManagerOrAbove(): bool
    {
        return in_array($this, [self::Manager, self::HrAdmin, self::SuperAdmin], true);
    }
}
