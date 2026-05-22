<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Benefit extends Model
{
    protected $fillable = [
        'name', 'frequency','taxable'
    ];

    public function benefit(): BelongsTo
    {
        return $this->belongsTo(EmployeeBenefit::class);
    }
}
