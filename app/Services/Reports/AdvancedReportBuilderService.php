<?php

namespace App\Services\Reports;

use App\Models\Report;
use Illuminate\Support\Facades\DB;

class AdvancedReportBuilderService
{
    public function generate(Report $report)
    {
        /*
        |--------------------------------------------------------------------------
        | BASE TABLE
        |--------------------------------------------------------------------------
        */

        $query = DB::table($report->module);

        /*
        |--------------------------------------------------------------------------
        | JOINS
        |--------------------------------------------------------------------------
        */

        foreach ($report->joins ?? [] as $join) {

            if (
                empty($join['table']) ||
                empty($join['first']) ||
                empty($join['second'])
            ) {
                continue;
            }

            $query->leftJoin(
                $join['table'],
                $join['first'],
                '=',
                $join['second']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SELECT COLUMNS
        |--------------------------------------------------------------------------
        */

        $selects = [];

        foreach ($report->columns ?? [] as $column) {

            /*
            |--------------------------------------------------------------------------
            | Convert:
            | employees.firstName
            |
            | INTO:
            | employees.firstName as employees_firstName
            |--------------------------------------------------------------------------
            */

            $alias = str_replace('.', '_', $column);

            $selects[] = DB::raw("
                {$column} as {$alias}
            ");
        }

        /*
        |--------------------------------------------------------------------------
        | AGGREGATES
        |--------------------------------------------------------------------------
        */

        foreach ($report->aggregates ?? [] as $aggregate) {

            $function = strtoupper($aggregate['function'] ?? 'SUM');

            $field = $aggregate['field'] ?? null;

            $alias = $aggregate['alias'] ?? null;

            if (!$field || !$alias) {
                continue;
            }

            $selects[] = DB::raw("
                {$function}({$field}) as {$alias}
            ");
        }

        /*
        |--------------------------------------------------------------------------
        | APPLY SELECT
        |--------------------------------------------------------------------------
        */

        if (count($selects)) {
            $query->select($selects);
        }

        /*
        |--------------------------------------------------------------------------
        | FILTERS
        |--------------------------------------------------------------------------
        */

        foreach ($report->filters ?? [] as $filter) {

            $field = $filter['field'] ?? null;
            $operator = strtoupper($filter['operator'] ?? '=');
            $value = $filter['value'] ?? null;
            $boolean = strtolower($filter['boolean'] ?? 'and');

            if (!$field) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | LIKE / CONTAINS
            |--------------------------------------------------------------------------
            */

            if ($operator === 'CONTAINS') {

                $method = $boolean === 'or'
                    ? 'orWhere'
                    : 'where';

                $query->{$method}(
                    $field,
                    'LIKE',
                    "%{$value}%"
                );

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | STANDARD FILTERS
            |--------------------------------------------------------------------------
            */

            if ($boolean === 'or') {

                $query->orWhere(
                    $field,
                    $operator,
                    $value
                );

            } else {

                $query->where(
                    $field,
                    $operator,
                    $value
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | GROUP BY
        |--------------------------------------------------------------------------
        */

        if (!empty($report->groups)) {
            $query->groupBy($report->groups);
        }

        /*
        |--------------------------------------------------------------------------
        | SORTING
        |--------------------------------------------------------------------------
        */

        foreach ($report->sorts ?? [] as $sort) {

            if (
                empty($sort['field']) ||
                empty($sort['direction'])
            ) {
                continue;
            }

            $query->orderBy(
                $sort['field'],
                $sort['direction']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | RETURN
        |--------------------------------------------------------------------------
        */

        return $query->paginate(50);
    }
}