<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSchemaService
{
    protected array $excluded = [
        'migrations',
        'cache',
        'jobs',
        'failed_jobs',
        'sessions',
        'password_reset_tokens',
    ];

    /*
    |--------------------------------------------------------------------------
    | GET ALL TABLES
    |--------------------------------------------------------------------------
    */

    public function tables(): array
    {
        $database = env('DB_DATABASE');

        $tables = DB::select("
            SELECT TABLE_NAME
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ?
        ", [$database]);

        return collect($tables)
            ->pluck('TABLE_NAME')
            ->filter(fn ($table) => !in_array($table, $this->excluded))
            ->values()
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | GET COLUMNS
    |--------------------------------------------------------------------------
    */

    public function columns(string $table): array
    {
        return collect(
            Schema::getColumnListing($table)
        )->map(function ($column) use ($table) {

            return [
                'field' => "{$table}.{$column}",
                'table' => $table,
                'column' => $column,
                'label' => str($column)
                    ->replace('_', ' ')
                    ->title()
                    ->toString(),
                'type' => Schema::getColumnType($table, $column),
            ];
        })->values()->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function relationships(string $table): array
    {
        $database = env('DB_DATABASE');

        $relationships = collect(DB::select("
            SELECT
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database, $table]))
        ->map(function ($relation) use ($table) {

            return [
                'table' => $relation->REFERENCED_TABLE_NAME,
                'first' => "{$table}.{$relation->COLUMN_NAME}",
                'second' => "{$relation->REFERENCED_TABLE_NAME}.{$relation->REFERENCED_COLUMN_NAME}",
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | AUTO empnum RELATIONS
        |--------------------------------------------------------------------------
        */

        foreach ($this->tables() as $otherTable) {

            if ($otherTable === $table) {
                continue;
            }

            $columns = Schema::getColumnListing($otherTable);

            if (in_array('empnum', $columns)) {

                $relationships->push([
                    'table' => $otherTable,
                    'first' => "{$table}.empnum",
                    'second' => "{$otherTable}.empnum",
                ]);
            }
        }

        return $relationships
            ->unique('table')
            ->values()
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | MODULES
    |--------------------------------------------------------------------------
    */

    public function modules(): array
    {
        $modules = [];

        foreach ($this->tables() as $table) {

            $modules[$table] = [

                'label' => str($table)
                    ->replace('_', ' ')
                    ->title()
                    ->toString(),

                'table' => $table,

                'columns' => $this->columns($table),

                'relationships' => $this->relationships($table),
            ];
        }

        return $modules;
    }
}