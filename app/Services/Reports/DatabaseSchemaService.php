<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

class DatabaseSchemaService
{
    /**
     * Tables you never want exposed to report builder
     */
    protected array $excluded = [
        'migrations',
        'jobs',
        'failed_jobs',
        'cache',
        'sessions',
        'password_reset_tokens',
    ];

    /**
     * Schema name (can be changed via env)
     */
    protected string $schema;

    public function __construct()
    {
        $this->schema = config('database.connections.pgsql.schema', 'access');
    }

    /*
    |--------------------------------------------------------------------------
    | TABLES (CACHED)
    |--------------------------------------------------------------------------
    */

    public function tables(): array
    {
        return Cache::remember('report_builder_tables_' . $this->schema, 60, function () {

            return collect(DB::select("
                SELECT tablename
                FROM pg_tables
                WHERE schemaname = ?
            ", [$this->schema]))
                ->pluck('tablename')
                ->reject(fn ($table) => in_array($table, $this->excluded))
                ->values()
                ->toArray();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | COLUMNS (SAFE + NORMALIZED)
    |--------------------------------------------------------------------------
    */

    public function columns(string $table): array
    {
        $columns = Schema::getColumnListing($table);

        return collect($columns)->map(function ($column) use ($table) {

            $type = Schema::getColumnType($table, $column);

            return [
                // FULL FIELD IDENTIFIER (USED BY REPORT BUILDER)
                'field' => "{$table}.{$column}",

                'table' => $table,
                'column' => $column,

                // UI LABEL
                'label' => $this->humanize($column),

                // DATA TYPE (important for filter builder)
                'type' => $type,

                // UI HELPERS
                'group' => $this->humanize($table),

                // useful for frontend filtering
                'is_numeric' => in_array($type, [
                    'smallint',
                    'integer',
                    'bigint',
                    'decimal',
                    'float',
                    'numeric',
                    'real',
                    'double',
                    'double precision',
                ]),
                'is_text' => in_array($type, ['string', 'text']),
                'is_date' => in_array($type, ['date', 'datetime', 'timestamp']),
            ];
        })->values()->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS (POSTGRES CORRECT VERSION)
    |--------------------------------------------------------------------------
    */

    public function relationships(string $table): array
    {
        $sql = "
            SELECT
                kcu.column_name,
                ccu.table_name AS foreign_table,
                ccu.column_name AS foreign_column
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
                ON tc.constraint_name = kcu.constraint_name
            JOIN information_schema.constraint_column_usage ccu
                ON ccu.constraint_name = tc.constraint_name
            WHERE tc.constraint_type = 'FOREIGN KEY'
              AND tc.table_name = ?
              AND tc.table_schema = ?
        ";

        $rows = DB::select($sql, [$table, $this->schema]);

        return collect($rows)->map(function ($row) use ($table) {

            return [
                'label' => "{$table} → {$row->foreign_table}",

                'table' => $row->foreign_table,

                // JOIN definition (used by builder engine)
                'first' => "{$table}.{$row->column_name}",
                'second' => "{$row->foreign_table}.{$row->foreign_column}",

                // UI helper
                'type' => 'foreign_key',
            ];
        })->values()->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | MODULES (UI READY STRUCTURE)
    |--------------------------------------------------------------------------
    */

    public function modules(): array
    {
        return Cache::remember('report_builder_modules_' . $this->schema, 60, function () {

            $modules = [];

            foreach ($this->tables() as $table) {

                $modules[$table] = [
                    'key' => $table,

                    // Human readable name
                    'label' => $this->humanize($table),

                    'table' => $table,

                    // fields grouped for UI
                    'fields' => $this->columns($table),

                    // available joins
                    'relationships' => $this->relationships($table),
                ];
            }

            return $modules;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    protected function humanize(string $value): string
    {
        return str($value)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    /*
    |--------------------------------------------------------------------------
    | EXTRA: FIELD LOOKUP (USED BY REPORT ENGINE)
    |--------------------------------------------------------------------------
    */

    public function allFields(): array
    {
        $fields = [];

        foreach ($this->modules() as $module) {
            foreach ($module['fields'] as $field) {
                $fields[$field['field']] = $field;
            }
        }

        return $fields;
    }

    public function schema(): string
    {
        return $this->schema;
    }
}