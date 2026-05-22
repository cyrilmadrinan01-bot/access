<?php
namespace App\Services;

//use App\Models\PayrollProcessStatus;
use Illuminate\Support\Facades\DB;

class PayrollStepService
{
    public static function start($cutoffId, $step)
    {
        DB::table('payroll_process_statuses')->updateOrInsert(
            ['cutoff_id' => $cutoffId, 'step' => $step],
            ['status' => 'processing', 'message' => null, 'updated_at' => now()]
        );

        broadcast(new \App\Events\PayrollStepUpdated($cutoffId, $step, 'processing'));
    }

    public static function complete($cutoffId, $step)
    {
        DB::table('payroll_process_statuses')
            ->where('cutoff_id', $cutoffId)
            ->where('step', $step)
            ->update(['status' => 'completed', 'updated_at' => now()]);

        broadcast(new \App\Events\PayrollStepUpdated($cutoffId, $step, 'completed'));
    }

    public static function fail($cutoffId, $step, $error)
    {
        DB::table('payroll_process_statuses')
            ->where('cutoff_id', $cutoffId)
            ->where('step', $step)
            ->update([
                'status' => 'failed',
                'message' => $error,
                'updated_at' => now()
            ]);

        broadcast(new \App\Events\PayrollStepUpdated($cutoffId, $step, 'failed'));
    }

    public static function reset($cutoffId, $step)
    {
        DB::table('payroll_process_statuses')
            ->where('cutoff_id', $cutoffId)
            ->where('step', $step)
            ->update(['status' => 'pending']);
    }
}

/*
class PayrollStepService
{
    public static function complete($cutoffId, $step)
    {
        PayrollProcessStatus::updateOrCreate(
            ['cutoff_id' => $cutoffId, 'step' => $step],
            [
                'status' => 'completed',
                'processed_at' => now()
            ]
        );
    }

    public static function skip($cutoffId, $step)
    {
        PayrollProcessStatus::updateOrCreate(
            ['cutoff_id' => $cutoffId, 'step' => $step],
            [
                'status' => 'skipped',
                'processed_at' => now()
            ]
        );
    }

    public static function getSteps($cutoffId)
    {
        $steps = [
            'timekeeping',
            'other_income',
            'deduction',
            'medical',
            'sss',
            'pagibig',
            'philhealth',
            'payroll',
            'bank',
            'payslip',
        ];

        $records = PayrollProcessStatus::where('cutoff_id', $cutoffId)->get()->keyBy('step');

        $result = [];

        foreach ($steps as $step) {
            $result[$step] = isset($records[$step]) &&
                in_array($records[$step]->status, ['completed', 'skipped']);
        }

        return $result;
    }
}
    */
