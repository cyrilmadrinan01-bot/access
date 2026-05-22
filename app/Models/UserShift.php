<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class UserShift extends Model
{
    protected $fillable = [
        'employee_id', 'shiftcode_id', 'effective_date', 'end_date', 'changed_by', 'reason'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shiftCode()
    {
        return $this->belongsTo(ShiftCode::class, 'shiftcode_id');
    }

    // Scope to get active shift for a date
    public function scopeActiveForDate($query, $date)
    {
        return $query->whereDate('effective_date', '<=', $date)
                     ->where(function ($q) use ($date) {
                         $q->whereNull('end_date')
                           ->orWhereDate('end_date', '>=', $date);
                     })
                     ->latest('effective_date');
    }
}