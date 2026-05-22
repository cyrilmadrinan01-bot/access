<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['code' => 'SL', 'name' => 'Sick Leave'],
            ['code' => 'VL', 'name' => 'Vacation Leave'],
            ['code' => 'BL', 'name' => 'Birthday Leave'],
            ['code' => 'ML', 'name' => 'Maternity Leave'],
            ['code' => 'PL', 'name' => 'Paternity Leave'],
            ['code' => 'EL', 'name' => 'Emergency Leave'],
            ['code' => 'MC', 'name' => 'Magna Carta Leave'],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}
