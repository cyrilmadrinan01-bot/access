<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = ['name', 'code', 'status']; // adjust as needed

    // Scope to get only active leave types
    public function scopeActive($query)
    {
        return $query->where('status', 'active'); // assumes you have a 'status' column
    }

    public function balances()
    {
        return $this->hasMany(EmployeeLeaveBalance::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id', 'id');
    }
}
