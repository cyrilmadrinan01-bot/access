<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class PayrollCutOff extends Model
{
    use HasFactory;

    protected $table = 'payroll_cut_offs';

    protected $fillable = [
        'cutOffStart',
        'cutOffEnd',
        'payrollDate',
        'current',
        'lockDate',
        'lockTime',
    ];

    protected $casts = [
        'cutOffStart' => 'date',
        'cutOffEnd'   => 'date',
        'payrollDate' => 'date',
        'lockDate'    => 'date',
        'lockTime'    => 'string', // just store TIME
    ];

    /**
     * Check if this payroll cut-off is locked.
     *
     * Returns true if lockDate + lockTime has passed.
     */
    public function getIsLockedAttribute(): bool
    {
        if (!$this->lockDate || !$this->lockTime) {
            return false;
        }

        // Combine lockDate + lockTime safely
        try {
            $lockDateTime = Carbon::parse("{$this->lockDate} {$this->lockTime}");
        } catch (\Exception $e) {
            // Invalid format in database → treat as unlocked
            return false;
        }

        return Carbon::now()->greaterThan($lockDateTime);
    }

    /**
     * Get all timekeeping corrections associated with this payroll cut-off.
     */
    public function timekeepingCorrections()
    {
        return $this->hasMany(TimekeepingCorrections::class, 'payroll_cut_off_id');
    }

    /**
     * Scope for current cut-off
     */
    public function scopeCurrent($query)
    {
        return $query->where('current', 'Yes');
    }

    /**
     * Scope for past cut-offs (locked or finished)
     */
    public function scopePast($query)
    {
        return $query->where('current', 'No')
                     ->whereNotNull('cutOffEnd')
                     ->whereDate('cutOffEnd', '<', now());
    }

    /**
     * Return lockDateTime as Carbon instance (nullable)
     */
    public function lockDateTime(): ?Carbon
    {
        if (!$this->lockDate || !$this->lockTime) {
            return null;
        }

        try {
            return Carbon::parse("{$this->lockDate} {$this->lockTime}");
        } catch (\Exception $e) {
            return null;
        }
    }
}
