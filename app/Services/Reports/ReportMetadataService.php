<?php

namespace App\Services\Reports;

class ReportMetadataService
{
    public function modules(): array
    {
        return config('reportables');
    }

    public function module(string $module): array
    {
        return config("reportables.{$module}");
    }
}