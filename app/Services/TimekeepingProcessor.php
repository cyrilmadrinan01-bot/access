<?php

namespace App\Services;

use App\Models\PayrollCutOff;
use App\Models\Timekeeping;
use App\Models\TimekeepingProcess;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class TimekeepingProcessor
{
    public function process(PayrollCutOff $cutoff): void
    {
        logger()->warning('TIMEKEEPING PROCESS START', [
            'cutoff_id' => $cutoff->id,
            'time' => now()->toDateTimeString(),
            //'force' => $forceReprocess
        ]);

        // 🔒 Payroll Lock Protection
        if ($cutoff->is_locked ?? false) {
            throw new \RuntimeException('Payroll cutoff is locked.');
        }

        if ($cutoff->timekeeping_processed_at) { 
            logger()->warning('TIMEKEEPING ALREADY PROCESSED', [ 'cutoff_id' => $cutoff->id, ]); 
            return; 
        }

        //if ($cutoff->timekeeping_processed_at && !$forceReprocess) {
        //    logger()->warning('TIMEKEEPING ALREADY PROCESSED');
        //    return;
        //}

        DB::transaction(function () use ($cutoff) {

           // if ($forceReprocess) {
           //     TimekeepingProcess::where('payroll_cut_offs_id', $cutoff->id)->delete();
           // }

            $timekeepings = Timekeeping::with('shiftcode')
                ->where('payrollDate', $cutoff->payrollDate)
                ->get()
                ->groupBy('empnum');

            if ($timekeepings->isEmpty()) {
                return;
            }

            // ✅ PRELOAD EMPLOYEES (NO N+1)
            $employees = Employee::whereIn('empnum', $timekeepings->keys())
                ->get()
                ->keyBy('empnum');

            foreach ($timekeepings as $empnum => $records) {

                $employee = $employees[$empnum] ?? null;
                $standardHours = (int) ($employee->standard_hours ?? 40);
                $dailyThreshold = $standardHours == 48 ? 12 : 8;

                $summary = $this->emptySummary();

                foreach ($records as $tk) {
                    $this->applyTimekeeping($tk, $summary, $dailyThreshold);
                }

                // ======================================
                // ✅ PAYROLL ADJUSTMENTS (SQL SUM FAST)
                // ======================================
                $adjustmentTotals = DB::table('payroll_adjustments')
                    ->where('payroll_cut_off_id', $cutoff->id)
                    ->where('empnum', $empnum)
                    ->whereNotNull('approved_at')
                    ->selectRaw('
                        COALESCE(SUM(adjusted_hours - late - undertime),0) as net_hours,
                        COALESCE(SUM(adjusted_nsd),0) as nsd
                    ')
                    ->first();

                $summary['adjusted_hours'] += max(0, (float) $adjustmentTotals->net_hours);
                $summary['adjusted_nsd'] += (float) $adjustmentTotals->nsd;

                // ======================================
                // ✅ OVERTIME ADJUSTMENTS (SQL SUM FAST)
                // ======================================
                $otAdjustmentTotals = DB::table('overtimes')
                    ->where('payroll_cut_off_id', $cutoff->id)
                    ->where('empnum', $empnum)
                    ->where('status', 'Approved')
                    ->where('is_adjustment', 1)
                    ->selectRaw('
                        COALESCE(SUM(hours),0) as hours,
                        COALESCE(SUM(nsd),0) as nsd
                    ')
                    ->first();

                $summary['adjusted_ot_hours'] += (float) $otAdjustmentTotals->hours;
                $summary['adjusted_ot_nsd'] += (float) $otAdjustmentTotals->nsd;
                //$summary['adjusted_meal'] += (float) ($otAdjustmentTotals->adjusted_meal * 50 ?? 0);

                TimekeepingProcess::updateOrCreate(
                    [
                        'payroll_cut_offs_id' => $cutoff->id,
                        'empnum' => $empnum,
                    ],
                    $summary
                );
            }

            $cutoff->update([
                'timekeeping_processed_at' => now(),
            ]);
        });
    }

    protected function applyTimekeeping($tk, array &$s, int $dailyThreshold): void
    {
        $type = strtoupper((string) $tk->typeCode);
        $leave = strtoupper((string) $tk->leaveCode);
        $dayType = strtoupper(trim($tk->dayType));

        if ($type === 'ABSENT' || $leave === 'LOA') {
            $regHours = $tk->shift?->regHours ?? 0;
            $s['absent'] += (float) $regHours;
            return;
        }

        if ($type === 'ON LEAVE' && $leave !== 'LOA') {
            return;
        }

        $reg = max(0, (float) ($tk->regHours ?? 0));
        $nsd = (float) ($tk->nsd ?? 0);
        $ot  = (float) ($tk->overtime ?? 0);
        $otNsd = (float) ($tk->overtime_nsd ?? 0);

        $regularOt = min($ot, $dailyThreshold);
        $excessOt  = max(0, $ot - $dailyThreshold);

        switch ($dayType) {

            case 'REG':
                $s['reg'] += $reg;
                $s['nsd_reg'] += $nsd;
                $s['overtime_reg'] += $regularOt;
                $s['overtime_nsd_reg'] += $otNsd;
                break;

            case 'LH':
                $s['overtime_lh'] += $regularOt;
                $this->applyExcess($s, 'lh', $excessOt, $dailyThreshold);
                $s['overtime_nsd_lh'] += $otNsd;
                break;

            case 'SH':
                $s['overtime_sh'] += $regularOt;
                $this->applyExcess($s, 'sh', $excessOt, $dailyThreshold);
                $s['overtime_nsd_sh'] += $otNsd;
                break;

            case 'RD':
                $s['overtime_rd'] += $regularOt;
                $this->applyExcess($s, 'rd', $excessOt, $dailyThreshold);
                $s['overtime_nsd_rd'] += $otNsd;
                break;

            case 'LHRD':
                $s['overtime_lhrd'] += $regularOt;
                $this->applyExcess($s, 'lhrd', $excessOt, $dailyThreshold);
                $s['overtime_nsd_lhrd'] += $otNsd;
                break;

            case 'SHRD':
                $s['overtime_shrd'] += $regularOt;
                $this->applyExcess($s, 'shrd', $excessOt, $dailyThreshold);
                $s['overtime_nsd_shrd'] += $otNsd;
                break;
        }

        $s['late_reg'] += (float) ($tk->late ?? 0);
        $s['undertime'] += (float) ($tk->undertime ?? 0);
        //$s['meal'] += (float) ($tk->meal * 50 ?? 0);
        logger()->info('LATE CHECK', [
    'empnum' => $tk->empnum,
    'date' => $tk->dated,
    'late_raw' => $tk->getAttributes()['late'] ?? null,
    'late_casted' => $reg,
]);
    }

    protected function applyExcess(array &$s, string $type, float $excess, int $threshold): void
    {
        if ($threshold == 8) {
            $s["overtime_{$type}_8"] += $excess;
        } else {
            $s["overtime_{$type}_12"] += $excess;
        }
    }

    protected function emptySummary(): array
    {
        return [
            'reg' => 0,
            'nsd_reg' => 0,
            'overtime_reg' => 0,
            'overtime_lh' => 0,
            'overtime_sh' => 0,
            'overtime_lhrd' => 0,
            'overtime_shrd' => 0,
            'overtime_rd' => 0,
            'overtime_nsd_reg' => 0,
            'overtime_nsd_lh' => 0,
            'overtime_nsd_sh' => 0,
            'overtime_nsd_lhrd' => 0,
            'overtime_nsd_shrd' => 0,
            'overtime_nsd_rd' => 0,
            'late_reg' => 0,
            'undertime' => 0,
            'absent' => 0,
            'adjusted_hours' => 0,
            'adjusted_nsd' => 0,
            'adjusted_ot_hours' => 0,
            'adjusted_ot_nsd' => 0,
            'overtime_lh_8' => 0,
            'overtime_lh_12' => 0,
            'overtime_sh_8' => 0,
            'overtime_sh_12' => 0,
            'overtime_lhrd_8' => 0,
            'overtime_lhrd_12' => 0,
            'overtime_shrd_8' => 0,
            'overtime_shrd_12' => 0,
            'overtime_rd_8' => 0,
            'overtime_rd_12' => 0,
        ];
    }
}
