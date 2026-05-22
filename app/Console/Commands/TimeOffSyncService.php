<?php

namespace App\Services\SuccessFactors;

use App\Models\Timekeeping;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TimeOffSyncService
{
    protected SuccessFactorsClient $client;

    public function __construct(SuccessFactorsClient $client)
    {
        $this->client = $client;
    }

    /**
     * Sync approved time off within a cutoff date range
     */
    public function syncApprovedTimeOff(Carbon $cutoffStart, Carbon $cutoffEnd): void
    {
        Log::info("Starting SF time off UPDATE ONLY from {$cutoffStart} to {$cutoffEnd}");

    $records = $this->fetchApprovedTimeOff($cutoffStart, $cutoffEnd);

    foreach ($records as $record) {
        $startDate = $this->parseSfDate($record['startDate']);
        $endDate   = $this->parseSfDate($record['endDate']);
        $empnum    = $record['userId'];
        $leaveCode = $record['timeTypeNav']['externalCode'] ?? null;
        $hours     = $record['quantity'] ?? 8;
        $reason    = $record['reason'] ?? '';

        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {

            $tk = Timekeeping::where('empnum', $empnum)
                ->where('dated', $date->toDateString())
                ->first();

            if (!$tk) {
                Log::info("Skipping empnum={$empnum} date={$date->toDateString()} — record does not exist");
                continue; // skip missing rows
            }

            // Update only leave-related fields
            $tk->typeCode     = 'onLeave';
            $tk->regHours     = $hours;
            $tk->leaveCode    = $leaveCode;
            $tk->reason       = 'Filed Leave';
            $tk->otherReason  = $reason;
            $tk->source       = 'SuccessFactors';

            $tk->save();

            Log::info("Updated leave: empnum={$empnum} date={$date->toDateString()} leaveCode={$leaveCode}");
        }
    }

    Log::info("SF time off UPDATE ONLY completed. Total records processed: " . count($records));
    }

    /**
     * Fetch approved time off from SF within date range
     */
    protected function fetchApprovedTimeOff(Carbon $start, Carbon $end): array
    {
        $response = $this->client->get('/odata/v2/EmployeeTime', [
            'select'  => 'userId,startDate,endDate,approvalStatus',
            '$expand' => 'timeTypeNav',
            '$filter' => sprintf(
                "approvalStatus eq 'APPROVED' and startDate ge datetime'%s' and startDate le datetime'%s'",
                $start->format('Y-m-d\TH:i:s'),
                $end->format('Y-m-d\TH:i:s')
            ),
        ]);

        return data_get($response, 'd.results', []);
    }

    /**
     * Parse SF /Date(1709596800000)/ into Carbon
     */
    protected function parseSfDate(string $sfDate): Carbon
    {
        if (preg_match('/\/Date\((\d+)\)\//', $sfDate, $matches)) {
            return Carbon::createFromTimestamp($matches[1] / 1000);
        }

        // fallback if already standard ISO string
        return Carbon::parse($sfDate);
    }
}
