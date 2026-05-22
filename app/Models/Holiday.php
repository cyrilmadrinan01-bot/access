<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'type',
        'day_type_code',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Determine the day_type_code based on holiday type and rest day
     */
    public static function determineDayTypeCode(string $type, bool $isRestDay): string
    {
        if ($isRestDay) {
            return $type === 'Legal' ? 'LHRD' : 'SHRD';
        }

        return 'Holiday';
    }
}
