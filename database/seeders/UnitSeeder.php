<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\UnitHead;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unitsData = [
            [
                'name' => 'Crime Scene Support',
                'code' => 'CSSU',
                'category' => 'FIELD OPS',
            ],
            [
                'name' => 'Evidence Management',
                'code' => 'EMU',
                'category' => 'CUSTODY',
            ],
            [
                'name' => 'Lab Analysis',
                'code' => 'CFLU',
                'category' => 'LAB',
            ],
            [
                'name' => 'Digital & Physical Forensic Unit',
                'code' => 'DPFU',
                'category' => 'CYBER & TECH',
            ],
        ];

        foreach ($unitsData as $data) {
            Unit::updateOrCreate(
                ['name' => $data['name']],
                [
                    'code' => $data['code'],
                    'category' => $data['category'],
                ]
            );
        }
    }
}
