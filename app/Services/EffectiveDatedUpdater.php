<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Models\EmployeeChangeLog;

class EffectiveDatedUpdater
{
    public function apply(
        string $modelClass,
        array $data,
        Carbon $effectiveDate,
        int $userId
    ): Model {
        /** @var Model|null $current */
        $current = $modelClass::where('employee_id', $data['employee_id'])
            ->whereNull('effective_end')
            ->first();

        // Close current record
        if ($current) {
            $current->update([
                'effective_end' => $effectiveDate->copy()->subDay(),
            ]);
        }

        // Create new effective-dated record
        /** @var Model $new */
        $new = $modelClass::create(array_merge($data, [
            'effective_start' => $effectiveDate,
        ]));

        // Audit log per field
        foreach ($data as $field => $value) {
            EmployeeChangeLog::create([
                'employee_id'   => $data['employee_id'],
                'domain'        => class_basename($modelClass),
                'field'         => $field,
                'old_value'     => $current?->$field,
                'new_value'     => $value,
                'effective_date'=> $effectiveDate,
                'is_future'     => $effectiveDate->isFuture(),
                'changed_by'    => $userId,
            ]);
        }

        return $new;
    }
}
