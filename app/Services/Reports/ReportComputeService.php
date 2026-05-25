<?php

namespace App\Services\Reports;

use App\Models\Timekeeping;
use App\Models\Employee;
use App\Models\PayrollRegister;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;

class ReportComputeService
{
    protected array $modules = [
        'employees' => Employee::class,
        'payroll' => PayrollRegister::class,
        'attendance' => Timekeeping::class,
    ];

    public function generate(Report $report)
    {
        $model = $this->modules[$report->module];

        /** @var Builder $query */
        $query = $model::query();

        if ($report->filters) {

            foreach ($report->filters as $filter) {

                $query->where(
                    $filter['field'],
                    $filter['operator'],
                    $filter['value']
                );
            }
        }

        if ($report->sorts) {

            foreach ($report->sorts as $sort) {

                $query->orderBy(
                    $sort['field'],
                    $sort['direction']
                );
            }
        }

        return $query
            ->select($report->columns)
            ->paginate(20);
    }
}