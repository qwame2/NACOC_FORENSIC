<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ForensicCase;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\User;

class RealtimeIncidentMapCaseSeeder extends Seeder
{
    public function run(): void
    {
        $officer = User::where('is_head', true)->first() ?? User::first();
        $officerId = $officer ? $officer->id : 1;

        // 1. Update existing case if present
        $existing = SingleSuspectCase::where('case_number', 'NCC-2026-5752')->first();
        if ($existing) {
            $existing->update([
                'raid_location' => 'Airport Residential Area, Accra',
                'latitude'      => 5.6052000,
                'longitude'     => -0.1668000,
            ]);
        }

        // 2. Case 1: East Legon, Accra (Single Suspect - Narcotics & Weapon)
        $case1Ref = 'NCC-2026-7821';
        if (!SingleSuspectCase::where('case_number', $case1Ref)->exists() && !MultipleSuspectCase::where('case_number', $case1Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case1Ref,
                'title'               => 'Republic vs Kwabena Osei (Operation Eagle Eye)',
                'unit_name'           => 'Crime Scene Support',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Det. Inspector Samuel Appiah',
                'submitting_officer'  => 'Det. Inspector Samuel Appiah',
                'suspect_name'        => 'Kwabena Osei',
                'suspect_alias'       => 'Pablo',
                'ghana_card'          => 'GHA-721948201-4',
                'gender'              => 'Male',
                'age'                 => 38,
                'nationality'         => 'Ghanaian',
                'address'             => 'Plot 44, Boundary Road, East Legon, Accra',
                'raid_location'       => 'East Legon Sector 4, Accra',
                'latitude'            => 5.6358000,
                'longitude'           => -0.1558000,
                'offense_description' => 'High-grade cocaine distribution and possession of uncustomed automatic weapon.',
                'holding_facility'    => 'NACOC High-Security Holding Facility, Accra',
                'priority'            => 'CRITICAL',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->format('Y-m-d'),
                'arrest_time'         => '04:15',
                'docket_data'         => [
                    'raid_location' => 'East Legon Sector 4, Accra',
                    'latitude'      => 5.6358000,
                    'longitude'     => -0.1558000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Kwabena Osei',
                            'suspect_alias'    => 'Pablo',
                            'ghana_card'       => 'GHA-721948201-4',
                            'gender'           => 'Male',
                            'age'              => 38,
                            'raid_location'    => 'East Legon Sector 4, Accra',
                            'latitude'         => 5.6358000,
                            'longitude'        => -0.1558000,
                            'role'             => 'Primary Suspect',
                            'holding_facility' => 'NACOC High-Security Holding Facility, Accra',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'High-Purity Cocaine Bricks',
                            'seizure_name'     => 'High-Purity Cocaine Bricks',
                            'seizure_quantity' => '25 Slabs',
                            'seizure_weight'   => '25.5',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'USD 750,000',
                            'seizure_location' => 'East Legon Sector 4, Accra',
                            'seizure_date'     => now()->format('Y-m-d'),
                            'seizure_time'     => '04:15',
                            'seizure_officer'  => 'Samuel Appiah',
                        ],
                        [
                            'item_number'      => 2,
                            'exhibit_category' => 'Weapons',
                            'commodity_name'   => 'Glock-19 9mm Tactical Handgun',
                            'seizure_name'     => 'Glock-19 9mm Tactical Handgun',
                            'seizure_quantity' => '1',
                            'seizure_weight'   => '0.85',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'GHS 18,000',
                            'seizure_location' => 'East Legon Sector 4, Accra',
                            'seizure_date'     => now()->format('Y-m-d'),
                            'seizure_time'     => '04:15',
                            'seizure_officer'  => 'Samuel Appiah',
                        ]
                    ]
                ]
            ]);
        }

        // 3. Case 2: Kumasi Kejetia / Adum (Multiple Suspects - Digital Devices & Encrypted Hard Drives)
        $case2Ref = 'NCC-2026-3104';
        if (!SingleSuspectCase::where('case_number', $case2Ref)->exists() && !MultipleSuspectCase::where('case_number', $case2Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case2Ref,
                'title'               => 'Republic vs Eric Boateng & Linda Siebia (Operation Golden Timber)',
                'unit_name'           => 'Digital & Physical Forensic Unit',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Senior Forensics Analyst Grey Essen',
                'submitting_officer'  => 'Senior Forensics Analyst Grey Essen',
                'suspect_name'        => 'Eric Boateng',
                'suspect_alias'       => 'Chief Tech',
                'ghana_card'          => 'GHA-819203194-2',
                'gender'              => 'Male',
                'age'                 => 34,
                'nationality'         => 'Ghanaian',
                'address'             => 'Adum Commercial Street, Kumasi',
                'raid_location'       => 'Kejetia Central Hub, Kumasi',
                'latitude'            => 6.6975000,
                'longitude'           => -1.6225000,
                'offense_description' => 'Cyber-narcotics syndicate operating encrypted payment gateways and illicit drop coordinates.',
                'holding_facility'    => 'Kumasi Central Police Command Holding Cell',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->format('Y-m-d'),
                'arrest_time'         => '11:30',
                'docket_data'         => [
                    'raid_location' => 'Kejetia Central Hub, Kumasi',
                    'latitude'      => 6.6975000,
                    'longitude'     => -1.6225000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Eric Boateng',
                            'suspect_alias'    => 'Chief Tech',
                            'ghana_card'       => 'GHA-819203194-2',
                            'gender'           => 'Male',
                            'age'              => 34,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Kejetia Central Hub, Kumasi',
                            'latitude'         => 6.6975000,
                            'longitude'        => -1.6225000,
                            'holding_facility' => 'Kumasi Central Police Command Holding Cell',
                        ],
                        [
                            'suspect_name'     => 'Linda Siebia',
                            'suspect_alias'    => 'Madam Cash',
                            'ghana_card'       => 'GHA-550912481-8',
                            'gender'           => 'Female',
                            'age'              => 29,
                            'role'             => 'Associate Suspect',
                            'raid_location'    => 'Kejetia Central Hub, Kumasi',
                            'latitude'         => 6.6975000,
                            'longitude'        => -1.6225000,
                            'holding_facility' => 'Kumasi Central Police Command Holding Cell',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Electronic Devices',
                            'commodity_name'   => 'Encrypted Apple iPhone 15 Pro Max',
                            'seizure_name'     => 'Encrypted Apple iPhone 15 Pro Max',
                            'seizure_quantity' => '1 Unit',
                            'seizure_value'    => 'GHS 24,000',
                            'seizure_location' => 'Kejetia Central Hub, Kumasi',
                            'seizure_date'     => now()->format('Y-m-d'),
                            'seizure_time'     => '11:30',
                            'seizure_officer'  => 'Grey Essen',
                        ],
                        [
                            'item_number'      => 2,
                            'exhibit_category' => 'Money',
                            'commodity_name'   => 'Laundered Currency Notes',
                            'seizure_name'     => 'Laundered Currency Notes',
                            'amount'           => 'GHS 78,500',
                            'seizure_quantity' => '78500',
                            'seizure_value'    => 'GHS 78,500',
                            'seizure_location' => 'Kejetia Central Hub, Kumasi',
                            'seizure_date'     => now()->format('Y-m-d'),
                            'seizure_time'     => '11:30',
                            'seizure_officer'  => 'Grey Essen',
                        ]
                    ]
                ]
            ]);
        }

        // 4. Case 3: Aflao Major Border Control Station (Volta Region)
        $case3Ref = 'NCC-2026-4490';
        if (!SingleSuspectCase::where('case_number', $case3Ref)->exists() && !MultipleSuspectCase::where('case_number', $case3Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case3Ref,
                'title'               => 'Republic vs Ibrahim Salifu (Operation Eastern Gate)',
                'unit_name'           => 'Evidence Management',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Chief Officer Diana Oduro',
                'submitting_officer'  => 'Chief Officer Diana Oduro',
                'suspect_name'        => 'Ibrahim Salifu',
                'suspect_alias'       => 'Shadow',
                'ghana_card'          => 'GHA-339210984-7',
                'gender'              => 'Male',
                'age'                 => 42,
                'nationality'         => 'Ghanaian',
                'address'             => 'Border Road Sector 2, Aflao',
                'raid_location'       => 'Aflao Major Border Control Station',
                'latitude'            => 6.1190000,
                'longitude'           => 1.1904000,
                'offense_description' => 'Cross-border concealment and importation of prohibited pharmaceutical opioids without regulatory permits.',
                'holding_facility'    => 'Aflao Border Security Cell Block',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->format('Y-m-d'),
                'arrest_time'         => '16:45',
                'docket_data'         => [
                    'raid_location' => 'Aflao Major Border Control Station',
                    'latitude'      => 6.1190000,
                    'longitude'     => 1.1904000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Ibrahim Salifu',
                            'suspect_alias'    => 'Shadow',
                            'ghana_card'       => 'GHA-339210984-7',
                            'gender'           => 'Male',
                            'age'              => 42,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Aflao Major Border Control Station',
                            'latitude'         => 6.1190000,
                            'longitude'        => 1.1904000,
                            'holding_facility' => 'Aflao Border Security Cell Block',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Illicit Tramadol Hydrochloride 225mg Cartons',
                            'seizure_name'     => 'Illicit Tramadol Hydrochloride 225mg Cartons',
                            'seizure_quantity' => '120 Cartons',
                            'seizure_weight'   => '180',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'GHS 420,000',
                            'seizure_location' => 'Aflao Major Border Control Station',
                            'seizure_date'     => now()->format('Y-m-d'),
                            'seizure_time'     => '16:45',
                            'seizure_officer'  => 'Diana Oduro',
                        ]
                    ]
                ]
            ]);
        }

        // 5. Case 4: Takoradi Harbour Port (Western Region)
        $case4Ref = 'NCC-2026-6215';
        if (!SingleSuspectCase::where('case_number', $case4Ref)->exists() && !MultipleSuspectCase::where('case_number', $case4Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case4Ref,
                'title'               => 'Republic vs Kofi Mensah (Operation Blue Coast)',
                'unit_name'           => 'Lab Analysis',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Forensic Chemist John Mensah',
                'submitting_officer'  => 'Forensic Chemist John Mensah',
                'suspect_name'        => 'Kofi Mensah',
                'suspect_alias'       => 'Skipper',
                'ghana_card'          => 'GHA-908123471-1',
                'gender'              => 'Male',
                'age'                 => 46,
                'nationality'         => 'Ghanaian',
                'address'             => 'Harbour View Quarters, Takoradi',
                'raid_location'       => 'Takoradi Harbour Container Terminal',
                'latitude'            => 4.8885000,
                'longitude'           => -1.7420000,
                'offense_description' => 'Maritime logistics concealment of bulk cannabis compressed parcels aboard foreign vessel.',
                'holding_facility'    => 'Takoradi Western Naval Command Security Cell',
                'priority'            => 'CRITICAL',
                'status'              => 'Under Multi-Unit Processing',
                'case_status'         => 'Under Multi-Unit Processing',
                'arrest_date'         => now()->format('Y-m-d'),
                'arrest_time'         => '08:20',
                'docket_data'         => [
                    'raid_location' => 'Takoradi Harbour Container Terminal',
                    'latitude'      => 4.8885000,
                    'longitude'     => -1.7420000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Kofi Mensah',
                            'suspect_alias'    => 'Skipper',
                            'ghana_card'       => 'GHA-908123471-1',
                            'gender'           => 'Male',
                            'age'              => 46,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Takoradi Harbour Container Terminal',
                            'latitude'         => 4.8885000,
                            'longitude'        => -1.7420000,
                            'holding_facility' => 'Takoradi Western Naval Command Security Cell',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Compressed Cannabis Sativa Parcels',
                            'seizure_name'     => 'Compressed Cannabis Sativa Parcels',
                            'seizure_quantity' => '14 Slabs',
                            'seizure_weight'   => '56',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'GHS 112,000',
                            'seizure_location' => 'Takoradi Harbour Container Terminal',
                            'seizure_date'     => now()->format('Y-m-d'),
                            'seizure_time'     => '08:20',
                            'seizure_officer'  => 'John Mensah',
                        ]
                    ]
                ]
            ]);
        }
    }
}
