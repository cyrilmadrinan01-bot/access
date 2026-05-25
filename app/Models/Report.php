<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'module',
        'columns',
        'joins',
        'filters',
        'sorts',
        'groups',
        'aggregates',
        'is_public',
    ];

    protected $casts = [
        'columns' => 'array',
        'joins' => 'array',
        'filters' => 'array',
        'sorts' => 'array',
        'groups' => 'array',
        'aggregates' => 'array',
        'is_public' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shares()
    {
        return $this->hasMany(ReportShare::class);
    }
}
