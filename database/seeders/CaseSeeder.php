<?php

namespace Database\Seeders;

use App\Models\ForensicCase;
use App\Models\User;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class CaseSeeder extends Seeder
{
    /**
     * Seed initial unit head cases into the database.
     */
    public function run(): void
    {
        $forensicHead = User::where('is_head', true)->first();

        $cases = [
            [
                'case_number' => 'FSD-2026-8842',
                'title' => 'Suspected Organic Powder Specimen (3.40 kg)',
                'unit_name' => 'Evidence Management',
                'commanding_officer' => 'Dr. Evelyn Osei',
                'submitting_officer' => 'Insp. Eric Ofori',
                'suspect_name' => 'Kwame Ofori',
                'priority' => 'HIGH',
                'status' => 'Awaiting Sign-off',
                'location' => 'Kotoka Int. Airport (Cargo Village)',
                'description' => 'Suspected Cocaine / Opioid compound batch submitted for spectroscopic chemical profiling.',
                'exhibits_summary' => '5 Sealed Evidence Tamper Bags',
                'hash_log' => 'HASH-FSD-8842-VERIFIED',
                'incident_date' => now()->subDays(2),
            ],
            [
                'case_number' => 'FSD-2026-8839',
                'title' => 'Encrypted NVMe SSD Storage Drive',
                'unit_name' => 'Digital & Physical Forensics',
                'commanding_officer' => 'Eng. Michael Mensah',
                'submitting_officer' => 'Eng. Michael Mensah',
                'suspect_name' => 'Kojo Antwi',
                'priority' => 'HIGH',
                'status' => 'Awaiting Sign-off',
                'location' => 'Greater Accra Harbour Hub',
                'description' => 'Bit-Stream Image Checksum #DIG-884 extracted from seized suspect laptop.',
                'exhibits_summary' => '1 Encase Bit-Stream Drive Image',
                'hash_log' => 'HASH-DIG-8839-VERIFIED',
                'incident_date' => now()->subDays(4),
            ],
            [
                'case_number' => 'NFC-2026-0892',
                'title' => 'Cocaine Concealment & Export Attempt',
                'unit_name' => 'Crime Scene Support',
                'commanding_officer' => 'Cmdr. J. Annan',
                'submitting_officer' => 'Insp. Emmanuel Mensah',
                'suspect_name' => 'Kwame Ofori',
                'priority' => 'CRITICAL',
                'status' => 'Lab Testing',
                'location' => 'Tema Port Terminal 3',
                'description' => 'Cargo Container #NACOC-9921 intercepted with false-bottom concealment.',
                'exhibits_summary' => '12 Compressed Powder Blocks',
                'hash_log' => 'HASH-NFC-0892-VERIFIED',
                'incident_date' => now()->subDays(4),
            ],
            [
                'case_number' => 'NFC-2026-0885',
                'title' => 'Methamphetamine Clandestine Lab Raid',
                'unit_name' => 'Evidence Management',
                'commanding_officer' => 'Dr. Evelyn Osei',
                'submitting_officer' => 'Chief Insp. R. Quarshie',
                'suspect_name' => 'Yaw Mensah',
                'priority' => 'CRITICAL',
                'status' => 'Awaiting Sign-off',
                'location' => 'Spintex Industrial Area',
                'description' => 'Raid on clandestine facility yielding chemical precursors and synthesis equipment.',
                'exhibits_summary' => '8 Chemical Precursor Drums',
                'hash_log' => 'HASH-NFC-0885-VERIFIED',
                'incident_date' => now()->subDays(9),
            ],
            [
                'case_number' => 'NFC-2026-0874',
                'title' => 'Ephedrine Precursor Interception',
                'unit_name' => 'Crime Scene Support',
                'commanding_officer' => 'Cmdr. J. Annan',
                'submitting_officer' => 'Alhassan Dauda',
                'suspect_name' => 'Alhassan Dauda',
                'priority' => 'NORMAL',
                'status' => 'Lab Testing',
                'location' => 'Aflao Border Post',
                'description' => 'Border check-point seizure of undeclared pharmaceutical precursor shipment.',
                'exhibits_summary' => '15 Sealed Containers',
                'hash_log' => 'HASH-NFC-0874-VERIFIED',
                'incident_date' => now()->subDays(14),
            ],
        ];

        foreach ($cases as $caseData) {
            $unit = Unit::where('name', $caseData['unit_name'])->first();

            ForensicCase::firstOrCreate(
                ['case_number' => $caseData['case_number']],
                array_merge($caseData, [
                    'user_id' => $forensicHead ? $forensicHead->id : null,
                    'unit_id' => $unit ? $unit->id : null,
                ])
            );
        }
    }
}
