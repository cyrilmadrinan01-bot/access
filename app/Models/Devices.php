<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devices extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_device');
    }
}
