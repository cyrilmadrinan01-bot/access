<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\LeaveApproval;

class LeaveApproval extends Model
{
    protected $table = 'leave_approvals';

    protected $fillable = [
        'leave_id',
        'approver_id',
        'status',
        'remarks',
    ];

    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
