<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Device extends Model
{
    protected $table = 'device';

    protected $fillable = [
        'deviceName',
        'deviceType',
        'deviceIp',
        'location',
        'status',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    
    public function users(): BelongsToMany
    {
        //return $this->belongsToMany(User::class, 'user_device');
        return $this->belongsToMany(User::class, 'user_device', 'device_id', 'user_empnum', 'id', 'empnum')
                    ->withPivot('deviceIp')
                    ->withTimestamps();
    }
}
