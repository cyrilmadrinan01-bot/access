<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SfSyncState extends Model
{
    protected $fillable = ['entity', 'last_synced_at'];
}
