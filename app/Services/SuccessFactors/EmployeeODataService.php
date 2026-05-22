<?php

namespace App\Services\SuccessFactors;

use App\Models\SfSyncState;
use Carbon\Carbon;
use RuntimeException;

class EmployeeODataService
{
    protected int $pageSize = 500;
    protected string $entity = 'PerPerson';

    public function __construct(
        protected SuccessFactorsClient $client
    ) {}

    /**
     * Fetch employees (CLEAN PerPerson objects)
     */
    public function fetchByPersonId(string $personId): array
    {
        $expand = implode(',', [
            'personalInfoNav',
            'employmentNav',
            'employmentNav/userNav',
            'employmentNav/jobInfoNav',
            'employmentNav/jobInfoNav/costCenterNav',
            'employmentNav/compInfoNav',
            'employmentNav/compInfoNav/empPayCompRecurringNav',
            'emailNav',
            'phoneNav',
            'homeAddressNavDEFLT',
            'employmentNav/jobInfoNav/emplStatusNav/picklistLabels',
            'employmentNav/jobInfoNav/employeeClassNav/picklistLabels',
            'employmentNav/jobInfoNav/customString30Nav',
        ]);

        $params = [
            '$expand' => $expand,
            '$format' => 'json',
            '$filter' => "personIdExternal eq '{$personId}'",
        ];

        $response = $this->client->get($this->entity, $params);

        return $this->normalizeResults($response);
    }

    /**
     * Normalize SF response → PerPerson[]
     */
    protected function normalizeResults(array $response): array
    {
        if (!isset($response['d']['results'])) {
            throw new RuntimeException('Invalid SF response structure');
        }

        return array_values(
            array_filter(
                $response['d']['results'],
                fn ($row) => isset($row['personIdExternal'])
            )
        );
    }

    public function fetchDeltaEmployees(): array
    {
        $companyCode = config('services.successfactors.company_code');

        $lastRun = SfSyncState::firstOrCreate(
            ['entity' => 'employee'],
            ['last_run_at' => now()->subHours(2)]
        );

        $from = Carbon::parse($lastRun->last_run_at)->subMinutes(5); // buffer
        $now  = now();

        /**
         * 🎯 SUCCESSFACTORS QUERY (VERY IMPORTANT)
         *
         * Includes:
         * - ACTIVE employees
         * - TERMINATED employees within last 7 days
         * - Filter by COMPANY CODE
         * - DELTA via lastModifiedDateTime
         */

        $expand = implode(',', [
            'personalInfoNav',
            'employmentNav',
            'employmentNav/userNav',
            'employmentNav/jobInfoNav',
            'employmentNav/jobInfoNav/costCenterNav',
            'employmentNav/compInfoNav',
            'employmentNav/compInfoNav/empPayCompRecurringNav',
            'emailNav',
            'phoneNav',
            'homeAddressNavDEFLT',
            'employmentNav/jobInfoNav/emplStatusNav/picklistLabels',
            'employmentNav/jobInfoNav/employeeClassNav/picklistLabels',
            'employmentNav/jobInfoNav/customString30Nav',
        ]);

        $filter = "
            (
                employmentNav/jobInfoNav/company eq '{$companyCode}'
            )
            and
            (
                lastModifiedDateTime ge datetime'2024-05-21T16:16:06'
            )
            and
            (
                employmentNav/jobInfoNav/emplStatusNav/picklistLabels/label eq 'Active'
            )
        ";
//lastModifiedDateTime ge datetime'{$from->format('Y-m-d\TH:i:s')}'
//                or
//                employmentNav/lastDateWorked ge datetime'{$now->copy()->subDays(7)->format('Y-m-d\TH:i:s')}'
        $params = [
            '$expand' => $expand,
            '$format' => 'json',
            '$filter' => preg_replace('/\s+/', ' ', trim($filter)),
            '$top'    => $this->pageSize,
        ];

        $response = $this->client->get($this->entity, $params);

        $results = $this->normalizeResults($response);

        // ✅ update sync timestamp AFTER successful fetch
        $lastRun->update(['last_run_at' => $now]);

        return $results;
    }

}
