<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Picklist extends Model
{
    protected $fillable = [
        'type',
        'code',
        'label',
        'parent_code',
        'is_active',
        'sort_order',
    ];
}
