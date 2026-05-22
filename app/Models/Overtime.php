<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Overtime extends Model implements AuditableContract
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'timekeeping_id',
        'empnum',
        'overtimeDate',
        'startTime',
        'endTime',
        'hours',
        'status',
        'reasons',
        'reject_resaon',
        'updated_by',
        'approved_by',
        'is_adjustment',
        'adjusted_from_id',
        'meal_eligible'
    ];

    protected $casts = [
        'startTime' => 'datetime',
        'endTime'   => 'datetime',
    ];

    public function timekeeping()
    {
        return $this->belongsTo(Timekeeping::class);
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by', 'empnum');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'updated_by', 'empnum');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'empnum', 'empnum');
    }
}
 