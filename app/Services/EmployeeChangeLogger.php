<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeChangeLogger
{
    /**
     * Log differences between old and new data
     */
    public static function logChanges(
        int $employeeId,
        array $oldData,
        array $newData,
        string $domain,
        Carbon|string|null $effectiveDate = null,
        bool $isFuture = false
    ): void {
        $userId = Auth::id() ?? 1;
        $effectiveDate = $effectiveDate instanceof Carbon
            ? $effectiveDate->toDateString()
            : ($effectiveDate ?? now()->toDateString());

        $logs = [];

        foreach ($newData as $field => $newValue) {

            $oldValue = $oldData[$field] ?? null;

            // normalize values
            $old = is_array($oldValue) ? json_encode($oldValue) : (string) $oldValue;
            $new = is_array($newValue) ? json_encode($newValue) : (string) $newValue;

            if ($old !== $new) {
                $logs[] = [
                    'employee_id'    => $employeeId,
                    'domain'         => $domain,
                    'field'          => $field,
                    'old_value'      => $old,
                    'new_value'      => $new,
                    'effective_date' => $effectiveDate,
                    'is_future'      => $isFuture,
                    'changed_by'     => $userId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }
        }

        if (!empty($logs)) {
            DB::table('employee_change_logs')->insert($logs);
        }
    }
}