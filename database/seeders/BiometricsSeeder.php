<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BiometricsSeeder extends Seeder
{
    public function run(): void
    {
        $startDate = Carbon::create(2026, 2, 1);
        $endDate   = Carbon::create(2026, 4, 30);

        $users = DB::table('users')
            ->whereNotNull('empnum')
            ->pluck('empnum');

        $shiftcodes = DB::table('shiftcodes')->get();

        // Optional: map employees to shifts randomly
        $employeeShifts = [];
        foreach ($users as $empnum) {
            $employeeShifts[$empnum] = $shiftcodes->random();
        }

        $records = [];

        while ($startDate->lte($endDate)) {

            foreach ($users as $empnum) {

                $shift = $employeeShifts[$empnum];

                $workDays = explode('|', $shift->workDay);
                $dayName = $startDate->format('l');

                // Skip if not a workday
                if (!in_array($dayName, $workDays)) {
                    continue;
                }

                // Clock In
                $clockInTime = Carbon::parse($startDate->toDateString() . ' ' . $shift->shiftStart)
                    ->addMinutes(rand(-15, 10)); // early/late allowance

                $records[] = [
                    'empnum'     => $empnum,
                    'timeLog'    => $clockInTime,
                    'deviceIp'   => '192.168.50.' . rand(200, 250),
                    'dayName'    => $dayName,
                    'dated'      => $startDate->toDateString(),
                    'processed'  => 'Yes',
                    'logType'    => 'Clock In',
                    'retry_count'=> 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Clock Out (handle night shift)
                $clockOutBaseDate = $startDate->copy();

                if ($shift->shiftEnd < $shift->shiftStart) {
                    // Night shift → next day
                    $clockOutBaseDate->addDay();
                }

                $clockOutTime = Carbon::parse($clockOutBaseDate->toDateString() . ' ' . $shift->shiftEnd)
                    ->addMinutes(rand(-10, 20));

                $records[] = [
                    'empnum'     => $empnum,
                    'timeLog'    => $clockOutTime,
                    'deviceIp'   => '192.168.50.' . rand(200, 250),
                    'dayName'    => $clockOutBaseDate->format('l'),
                    'dated'      => $clockOutBaseDate->toDateString(),
                    'processed'  => 'Yes',
                    'logType'    => 'Clock Out',
                    'retry_count'=> 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $startDate->addDay();
        }

        // Insert in chunks (important for performance)
        foreach (array_chunk($records, 1000) as $chunk) {
            DB::table('biometrics')->insert($chunk);
        }
    }
}
