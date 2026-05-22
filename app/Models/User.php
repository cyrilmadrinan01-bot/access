<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'empnum',
        'role_ids',
        'employeeStatus',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function devices(): BelongsToMany
    {
        //return $this->belongsToMany(Device::class, 'user_device');
        return $this->belongsToMany(Device::class, 'user_device', 'user_empnum', 'device_id', 'empnum', 'id')
                    ->withPivot('deviceIp')
                    ->withTimestamps();
    }

    protected static function booted()
    {
        static::created(function ($user) {
            if (!$user->hasAnyRole()) {
                $user->assignDefaultRole();
            }
        });
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'empnum', 'empnum');
    }

    public function assignDefaultRole()
    {
        if (!$this->employee) {
            $this->assignRole('employee');
            return;
        }

        match ($this->employee->employeeClass) {
            'Manager' => $this->assignRole('manager'),
            'HR' => $this->assignRole('hr'),
            'Payroll' => $this->assignRole('payroll'),
            default => $this->assignRole('employee'),
        };
    }

    public function leaveBalances()
    {
        return $this->hasMany(EmployeeLeaveBalance::class);
    }

    public function leaveAccruals()
    {
        return $this->hasMany(LeaveAccrual::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

}
