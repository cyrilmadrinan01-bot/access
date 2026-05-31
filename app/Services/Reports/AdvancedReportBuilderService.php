<?php

namespace App\Services\Reports;

use App\Models\Report;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;

class AdvancedReportBuilderService
{
    public function generate(Report $report, DatabaseSchemaService $schema)
    {
        $baseTable = $report->module;
        $schemaName = $schema->schema();

        $query = DB::table("{$schemaName}.{$baseTable} as base");

        $schemaMap = $this->buildSchemaMap($schema);

        $this->applyJoins($query, $report->joins ?? [], $schemaMap, $baseTable);
        $this->applySelects($query, $report, $schemaMap, $baseTable);
        $this->applyFilters($query, $report->filters ?? [], $schemaMap, $baseTable);
        $this->applyGroups($query, $report->groups ?? [], $schemaMap, $baseTable);
        $this->applySorts($query, $report->sorts ?? [], $schemaMap, $baseTable);

        return $query->paginate(50);
    }

    /*
    |--------------------------------------------------------------------------
    | SCHEMA MAP
    |--------------------------------------------------------------------------
    */

    protected function buildSchemaMap(DatabaseSchemaService $schema): array
    {
        $map = [];

        foreach ($schema->modules() as $module) {
            foreach ($module['fields'] as $column) {
                $key = $column['table'] . '.' . $column['column'];

                $map[$key] = [
                    'table' => $column['table'],
                    'column' => $column['column'],
                ];
            }
        }

        return $map;
    }

    protected function isValidField(string $field, array $schemaMap): bool
    {
        return isset($schemaMap[$field]);
    }

    /*
    |--------------------------------------------------------------------------
    | FIELD NORMALIZER (CRITICAL FIX)
    |--------------------------------------------------------------------------
    */

    protected function resolveField(string $field, string $baseTable): string
    {
        [$table, $column] = explode('.', $field);

        return $table === $baseTable
            ? "base.{$column}"
            : "{$table}.{$column}";
    }

    /*
    |--------------------------------------------------------------------------
    | JOINS
    |--------------------------------------------------------------------------
    */

    protected function applyJoins(
        Builder $query,
        array $joins,
        array $schemaMap,
        string $baseTable
    ): void {
        foreach ($joins as $join) {
            $table = $join['table'] ?? null;
            $first = $join['first'] ?? null;
            $second = $join['second'] ?? null;

            if (!$table || !$first || !$second) {
                continue;
            }

            if (
                !$this->isValidField($first, $schemaMap) ||
                !$this->isValidField($second, $schemaMap)
            ) {
                continue;
            }

            $type = strtolower($join['type'] ?? 'left');

            $first = $this->resolveField($first, $baseTable);
            $second = $this->resolveField($second, $baseTable);

            $query->join($table, function ($joinQuery) use ($first, $second, $type, $table) {
                $joinQuery->on($first, '=', $second);
            }, null, null, $type);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SELECTS
    |--------------------------------------------------------------------------
    */

    protected function applySelects(
        Builder $query,
        Report $report,
        array $schemaMap,
        string $baseTable
    ): void {

        $selects = [];

        $groupFields = $report->groups ?? [];

        foreach ($report->columns ?? [] as $field) {

            if (!$this->isValidField($field, $schemaMap)) {
                continue;
            }

            $resolved = $this->resolveField($field, $baseTable);

            // 🚨 IMPORTANT RULE
            // If grouping is active, only allow grouped columns
            if (!empty($groupFields) && !in_array($field, $groupFields)) {
                continue;
            }

            $selects[] = "{$resolved} as " . str_replace('.', '__', $field);
        }

        foreach ($report->aggregates ?? [] as $agg) {

            $field = $agg['field'] ?? null;
            $alias = $agg['alias'] ?? null;
            $function = strtoupper($agg['function'] ?? 'SUM');

            if (!$field || !$alias) {
                continue;
            }

            if (!$this->isValidField($field, $schemaMap)) {
                continue;
            }

            if (!in_array($function, [
                'SUM',
                'AVG',
                'MIN',
                'MAX',
                'COUNT'
            ])) {
                continue;
            }

            $resolved = $this->resolveField($field, $baseTable);

            $selects[] = DB::raw(
                "{$function}({$resolved}) as {$alias}"
            );
        }

        if (!empty($selects)) {
            $query->select($selects);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FILTERS
    |--------------------------------------------------------------------------
    */

    protected function applyFilters(
        Builder $query,
        array $filters,
        array $schemaMap,
        string $baseTable
    ): void {
        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;

            if (!$this->isValidField($field, $schemaMap)) {
                continue;
            }

            $field = $this->resolveField($field, $baseTable);

            $operator = strtoupper($filter['operator'] ?? '=');
            $value = $filter['value'] ?? null;
            $boolean = strtolower($filter['boolean'] ?? 'and');

            $method = $boolean === 'or' ? 'orWhere' : 'where';

            switch ($operator) {
                case 'CONTAINS':
                    $query->{$method}($field, 'ILIKE', "%{$value}%");
                    break;

                case 'STARTS_WITH':
                    $query->{$method}($field, 'ILIKE', "{$value}%");
                    break;

                case 'ENDS_WITH':
                    $query->{$method}($field, 'ILIKE', "%{$value}");
                    break;

                case 'IN':
                    $values = is_array($value) ? $value : explode(',', $value);
                    $query->{$method . 'In'}($field, $values);
                    break;

                case 'BETWEEN':
                    if (is_array($value) && count($value) === 2) {
                        $query->{$method . 'Between'}($field, $value);
                    }
                    break;

                default:
                    $query->{$method}($field, $operator, $value);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GROUPS
    |--------------------------------------------------------------------------
    */

    protected function applyGroups(
        Builder $query,
        array $groups,
        array $schemaMap,
        string $baseTable
    ): void {
        $valid = [];

        foreach ($groups as $field) {
            if ($this->isValidField($field, $schemaMap)) {
                $valid[] = $this->resolveField($field, $baseTable);
            }
        }

        if (!empty($valid)) {
            $query->groupBy($valid);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SORTS
    |--------------------------------------------------------------------------
    */

    protected function applySorts(
        Builder $query,
        array $sorts,
        array $schemaMap,
        string $baseTable
    ): void {
        foreach ($sorts as $sort) {
            $field = $sort['field'] ?? null;

            if (!$this->isValidField($field, $schemaMap)) {
                continue;
            }

            $field = $this->resolveField($field, $baseTable);

            $dir = strtolower($sort['direction'] ?? 'asc');

            $query->orderBy(
                $field,
                in_array($dir, ['asc', 'desc']) ? $dir : 'asc'
            );
        }
    }
}