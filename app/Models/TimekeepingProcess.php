<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimekeepingProcess extends Model
{
    protected $fillable = ['payroll_cut_offs_id',
            'empnum','reg',
            'nsd_reg',
            'overtime_reg',
            'overtime_nsd_reg',
            'overtime_lh',
            'overtime_sh',
            'overtime_lhrd',
            'overtime_shrd',
            'overtime_rd',
            'overtime_nsd_lh',
            'overtime_nsd_sh',
            'overtime_nsd_lhrd',
            'overtime_nsd_shrd',
            'overtime_nsd_rd',
            'late_reg',
            'undertime',
            'absent',
            'adjusted_hours',
            'adjusted_nsd',
            'adjusted_ot_hours',
            'adjusted_ot_nsd',
            'overtime_lh_8',
            'overtime_lh_12',
            'overtime_sh_8',
            'overtime_sh_12',
            'overtime_lhrd_8',
            'overtime_lhrd_12',
            'overtime_shrd_8',
            'overtime_shrd_12',
            'overtime_rd_8',
            'overtime_rd_12'];
}
