<?php

namespace App\Traits;

use App\Services\EmployeeChangeLogger;

trait LogsEmployeeChanges
{
    protected function logEmployeeChanges($employeeId, $old, $new, $domain, $effectiveDate = null, $isFuture = false)
    {
        EmployeeChangeLogger::logChanges(
            $employeeId,
            $old,
            $new,
            $domain,
            $effectiveDate,
            $isFuture
        );
    }
}