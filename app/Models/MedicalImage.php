<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalImage extends Model
{
    protected $fillable = ['medical_id', 'image_path', 'original_name'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function medical()
    {
        return $this->belongsTo(Medical::class);
    }

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }
}
