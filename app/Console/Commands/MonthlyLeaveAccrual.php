<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\LeaveType;
use App\Services\LeaveAccrualService;

class MonthlyLeaveAccrual extends Command
{
    //protected $signature = 'leave:monthly-accrual';
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monthly-leave-accrual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monthly leave credit accrual';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::where('is_active', 1)->get();
        $leaveTypes = LeaveType::whereIn('code', ['SL', 'VL'])->get();

        foreach ($users as $user) {
            foreach ($leaveTypes as $type) {
                LeaveAccrualService::accrue(
                    $user->id,
                    $type->id,
                    1.25,
                    'Monthly',
                    'Monthly auto accrual'
                );
            }
        }
    }
}
