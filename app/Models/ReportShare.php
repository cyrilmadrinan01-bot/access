<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportShare extends Model
{
    protected $fillable = [
        'report_id',
        'shared_to_user_id',
        'permission',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'shared_to_user_id');
    }
}
