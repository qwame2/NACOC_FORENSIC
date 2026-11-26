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
                'priority'            => 'HIGH',
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
                'priority'            => 'HIGH',
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

        // 6. Case 5: East Legon Phase 2, Accra (Second case in East Legon to rank #1 Hotspot)
        $case5Ref = 'NCC-2026-9812';
        if (!SingleSuspectCase::where('case_number', $case5Ref)->exists() && !MultipleSuspectCase::where('case_number', $case5Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case5Ref,
                'title'               => 'Republic vs Dennis Appiah (Operation Falcon Swift)',
                'unit_name'           => 'Digital Forensics',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Det. Sgt. Florence Mensah',
                'submitting_officer'  => 'Det. Sgt. Florence Mensah',
                'suspect_name'        => 'Dennis Appiah',
                'suspect_alias'       => 'Crypto Don',
                'ghana_card'          => 'GHA-882019472-8',
                'gender'              => 'Male',
                'age'                 => 34,
                'nationality'         => 'Ghanaian',
                'address'             => 'House 18, Mensah Wood Street, East Legon, Accra',
                'raid_location'       => 'East Legon Phase 2, Accra',
                'latitude'            => 5.6372000,
                'longitude'           => -0.1542000,
                'offense_description' => 'Cyber narcotics laundering, encrypted VoIP routing, and synthetic opioid distribution ring.',
                'holding_facility'    => 'NACOC Special Operations Holding Cell, Accra',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->format('Y-m-d'),
                'arrest_time'         => '11:45',
                'docket_data'         => [
                    'raid_location' => 'East Legon Phase 2, Accra',
                    'latitude'      => 5.6372000,
                    'longitude'     => -0.1542000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Dennis Appiah',
                            'suspect_alias'    => 'Crypto Don',
                            'ghana_card'       => 'GHA-882019472-8',
                            'gender'           => 'Male',
                            'age'              => 34,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'East Legon Phase 2, Accra',
                            'latitude'         => 5.6372000,
                            'longitude'        => -0.1542000,
                            'holding_facility' => 'NACOC Special Operations Holding Cell, Accra',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Digital Forensics',
                            'commodity_name'   => 'Encrypted Hard Wallets & Ledger Devices',
                            'seizure_name'     => 'Encrypted Hard Wallets & Ledger Devices',
                            'seizure_quantity' => '3 Devices',
                            'seizure_weight'   => '0.45',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'USD 140,000',
                            'seizure_location' => 'East Legon Phase 2, Accra',
                            'seizure_date'     => now()->format('Y-m-d'),
                            'seizure_time'     => '11:45',
                            'seizure_officer'  => 'Florence Mensah',
                        ]
                    ]
                ]
            ]);
        }

        // 7. Case 6: Tamale Central Interchange, Northern Region
        $case6Ref = 'NCC-2026-1052';
        if (!SingleSuspectCase::where('case_number', $case6Ref)->exists() && !MultipleSuspectCase::where('case_number', $case6Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case6Ref,
                'title'               => 'Republic vs Abdul-Rahman Yakubu (Operation Northern Shield)',
                'unit_name'           => 'Evidence Management',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Det. Inspector Alhasan Mumuni',
                'submitting_officer'  => 'Det. Inspector Alhasan Mumuni',
                'suspect_name'        => 'Abdul-Rahman Yakubu',
                'suspect_alias'       => 'Alhaji Red',
                'ghana_card'          => 'GHA-110294812-3',
                'gender'              => 'Male',
                'age'                 => 49,
                'nationality'         => 'Ghanaian',
                'address'             => 'Lamashegu Commercial District, Tamale',
                'raid_location'       => 'Tamale Central Interchange, Northern Region',
                'latitude'            => 9.4008000,
                'longitude'           => -0.8393000,
                'offense_description' => 'Interception of cross-regional transport hauling bulk unauthorized synthetic opioids and psychotropics.',
                'holding_facility'    => 'Tamale Regional Police Command Security Cells',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->subDays(1)->format('Y-m-d'),
                'arrest_time'         => '14:10',
                'docket_data'         => [
                    'raid_location' => 'Tamale Central Interchange, Northern Region',
                    'latitude'      => 9.4008000,
                    'longitude'     => -0.8393000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Abdul-Rahman Yakubu',
                            'suspect_alias'    => 'Alhaji Red',
                            'ghana_card'       => 'GHA-110294812-3',
                            'gender'           => 'Male',
                            'age'              => 49,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Tamale Central Interchange, Northern Region',
                            'latitude'         => 9.4008000,
                            'longitude'        => -0.8393000,
                            'holding_facility' => 'Tamale Regional Police Command Security Cells',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Illicit Codeine & Ephedrine Blisters',
                            'seizure_name'     => 'Illicit Codeine & Ephedrine Blisters',
                            'seizure_quantity' => '85 Cartons',
                            'seizure_weight'   => '125',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'GHS 310,000',
                            'seizure_location' => 'Tamale Central Interchange, Northern Region',
                            'seizure_date'     => now()->subDays(1)->format('Y-m-d'),
                            'seizure_time'     => '14:10',
                            'seizure_officer'  => 'Alhasan Mumuni',
                        ]
                    ]
                ]
            ]);
        }

        // 8. Case 7: Paga International Border Post, Upper East Region (Multiple Suspects)
        $case7Ref = 'NCC-2026-2819';
        if (!SingleSuspectCase::where('case_number', $case7Ref)->exists() && !MultipleSuspectCase::where('case_number', $case7Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case7Ref,
                'title'               => 'Republic vs Oumarou Traore & Seidu Bamba (Operation Sahel Watch)',
                'unit_name'           => 'Crime Scene Support',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Chief Inspector Anthony Agbeko',
                'submitting_officer'  => 'Chief Inspector Anthony Agbeko',
                'suspect_name'        => 'Oumarou Traore',
                'suspect_alias'       => 'Le Passeur',
                'ghana_card'          => 'GHA-990142851-9',
                'gender'              => 'Male',
                'age'                 => 37,
                'nationality'         => 'Burkinabe',
                'address'             => 'Paga Border Zone, Kasena-Nankana West',
                'raid_location'       => 'Paga Border Control Point, Upper East Region',
                'latitude'            => 10.9829000,
                'longitude'           => -1.1130000,
                'offense_description' => 'Transnational narcotics corridor operation: smuggling compressed heroin slabs hidden in vehicular door panels.',
                'holding_facility'    => 'Navrongo Central Prison Holding Block',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->subDays(2)->format('Y-m-d'),
                'arrest_time'         => '06:30',
                'docket_data'         => [
                    'raid_location' => 'Paga Border Control Point, Upper East Region',
                    'latitude'      => 10.9829000,
                    'longitude'     => -1.1130000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Oumarou Traore',
                            'suspect_alias'    => 'Le Passeur',
                            'ghana_card'       => 'GHA-990142851-9',
                            'gender'           => 'Male',
                            'age'              => 37,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Paga Border Control Point, Upper East Region',
                            'latitude'         => 10.9829000,
                            'longitude'        => -1.1130000,
                            'holding_facility' => 'Navrongo Central Prison Holding Block',
                        ],
                        [
                            'suspect_name'     => 'Seidu Bamba',
                            'suspect_alias'    => 'Courier Seidu',
                            'ghana_card'       => 'GHA-441209581-2',
                            'gender'           => 'Male',
                            'age'              => 31,
                            'role'             => 'Transporter',
                            'raid_location'    => 'Paga Border Control Point, Upper East Region',
                            'latitude'         => 10.9829000,
                            'longitude'        => -1.1130000,
                            'holding_facility' => 'Navrongo Central Prison Holding Block',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Compressed White Heroin Slabs',
                            'seizure_name'     => 'Compressed White Heroin Slabs',
                            'seizure_quantity' => '12 Slabs',
                            'seizure_weight'   => '12.4',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'USD 480,000',
                            'seizure_location' => 'Paga Border Control Point, Upper East Region',
                            'seizure_date'     => now()->subDays(2)->format('Y-m-d'),
                            'seizure_time'     => '06:30',
                            'seizure_officer'  => 'Anthony Agbeko',
                        ]
                    ]
                ]
            ]);
        }

        // 9. Case 8: Sunyani Municipality, Bono Region
        $case8Ref = 'NCC-2026-3940';
        if (!SingleSuspectCase::where('case_number', $case8Ref)->exists() && !MultipleSuspectCase::where('case_number', $case8Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case8Ref,
                'title'               => 'Republic vs Kwaku Antwi (Operation Timberline)',
                'unit_name'           => 'Lab Analysis',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Forensic Chemist Beatrice Danquah',
                'submitting_officer'  => 'Forensic Chemist Beatrice Danquah',
                'suspect_name'        => 'Kwaku Antwi',
                'suspect_alias'       => 'Agya Koo',
                'ghana_card'          => 'GHA-519284102-4',
                'gender'              => 'Male',
                'age'                 => 43,
                'nationality'         => 'Ghanaian',
                'address'             => 'Fiapre Road, Sunyani',
                'raid_location'       => 'Sunyani Municipality, Bono Region',
                'latitude'            => 7.3399000,
                'longitude'           => -2.3268000,
                'offense_description' => 'Unlicensed stockpiling of chemical precursors and processing of synthetic cannabinoid blends.',
                'holding_facility'    => 'Sunyani Central Prison Holding Facility',
                'priority'            => 'LOW',
                'status'              => 'Under Multi-Unit Processing',
                'case_status'         => 'Under Multi-Unit Processing',
                'arrest_date'         => now()->subDays(3)->format('Y-m-d'),
                'arrest_time'         => '10:15',
                'docket_data'         => [
                    'raid_location' => 'Sunyani Municipality, Bono Region',
                    'latitude'      => 7.3399000,
                    'longitude'     => -2.3268000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Kwaku Antwi',
                            'suspect_alias'    => 'Agya Koo',
                            'ghana_card'       => 'GHA-519284102-4',
                            'gender'           => 'Male',
                            'age'              => 43,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Sunyani Municipality, Bono Region',
                            'latitude'         => 7.3399000,
                            'longitude'        => -2.3268000,
                            'holding_facility' => 'Sunyani Central Prison Holding Facility',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Acetic Anhydride & Chemical Precursors',
                            'seizure_name'     => 'Acetic Anhydride & Chemical Precursors',
                            'seizure_quantity' => '45 Liters',
                            'seizure_weight'   => '48',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'GHS 145,000',
                            'seizure_location' => 'Sunyani Municipality, Bono Region',
                            'seizure_date'     => now()->subDays(3)->format('Y-m-d'),
                            'seizure_time'     => '10:15',
                            'seizure_officer'  => 'Beatrice Danquah',
                        ]
                    ]
                ]
            ]);
        }

        // 10. Case 9: Cape Coast Kotokuraba Sector, Central Region
        $case9Ref = 'NCC-2026-4712';
        if (!SingleSuspectCase::where('case_number', $case9Ref)->exists() && !MultipleSuspectCase::where('case_number', $case9Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case9Ref,
                'title'               => 'Republic vs Ekow Arthur (Operation Coastal Net)',
                'unit_name'           => 'Crime Scene Support',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Inspector Kwesi Hagan',
                'submitting_officer'  => 'Inspector Kwesi Hagan',
                'suspect_name'        => 'Ekow Arthur',
                'suspect_alias'       => 'Fisherman',
                'ghana_card'          => 'GHA-778201948-5',
                'gender'              => 'Male',
                'age'                 => 40,
                'nationality'         => 'Ghanaian',
                'address'             => 'Kotokuraba Market Road, Cape Coast',
                'raid_location'       => 'Kotokuraba Commercial Hub, Cape Coast',
                'latitude'            => 5.1054000,
                'longitude'           => -1.2466000,
                'offense_description' => 'Maritime coastal distribution hub using commercial fish vans for nationwide drug conveyance.',
                'holding_facility'    => 'Cape Coast Ankaful Maximum Security Annex',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->subDays(4)->format('Y-m-d'),
                'arrest_time'         => '09:00',
                'docket_data'         => [
                    'raid_location' => 'Kotokuraba Commercial Hub, Cape Coast',
                    'latitude'      => 5.1054000,
                    'longitude'     => -1.2466000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Ekow Arthur',
                            'suspect_alias'    => 'Fisherman',
                            'ghana_card'       => 'GHA-778201948-5',
                            'gender'           => 'Male',
                            'age'              => 40,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Kotokuraba Commercial Hub, Cape Coast',
                            'latitude'         => 5.1054000,
                            'longitude'        => -1.2466000,
                            'holding_facility' => 'Cape Coast Ankaful Maximum Security Annex',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Compressed Cannabis Cakes',
                            'seizure_name'     => 'Compressed Cannabis Cakes',
                            'seizure_quantity' => '30 Bundles',
                            'seizure_weight'   => '68',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'GHS 136,000',
                            'seizure_location' => 'Kotokuraba Commercial Hub, Cape Coast',
                            'seizure_date'     => now()->subDays(4)->format('Y-m-d'),
                            'seizure_time'     => '09:00',
                            'seizure_officer'  => 'Kwesi Hagan',
                        ]
                    ]
                ]
            ]);
        }

        // 11. Case 10: Koforidua Central Sector, Eastern Region
        $case10Ref = 'NCC-2026-5831';
        if (!SingleSuspectCase::where('case_number', $case10Ref)->exists() && !MultipleSuspectCase::where('case_number', $case10Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case10Ref,
                'title'               => 'Republic vs Maxwell Ofori (Operation Ridge Runner)',
                'unit_name'           => 'Digital Forensics',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Forensic Examiner Kwaku Boateng',
                'submitting_officer'  => 'Forensic Examiner Kwaku Boateng',
                'suspect_name'        => 'Maxwell Ofori',
                'suspect_alias'       => 'Matrix',
                'ghana_card'          => 'GHA-629103847-1',
                'gender'              => 'Male',
                'age'                 => 32,
                'nationality'         => 'Ghanaian',
                'address'             => 'Jackson Park Road, Koforidua',
                'raid_location'       => 'Koforidua Central Sector, Eastern Region',
                'latitude'            => 6.0941000,
                'longitude'           => -0.2591000,
                'offense_description' => 'Operating clandestine communication relay node, GSM SIM box array, and laundering crypto illicit funds.',
                'holding_facility'    => 'Koforidua Central Police Holding Cells',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->subDays(2)->format('Y-m-d'),
                'arrest_time'         => '16:00',
                'docket_data'         => [
                    'raid_location' => 'Koforidua Central Sector, Eastern Region',
                    'latitude'      => 6.0941000,
                    'longitude'     => -0.2591000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Maxwell Ofori',
                            'suspect_alias'    => 'Matrix',
                            'ghana_card'       => 'GHA-629103847-1',
                            'gender'           => 'Male',
                            'age'              => 32,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Koforidua Central Sector, Eastern Region',
                            'latitude'         => 6.0941000,
                            'longitude'        => -0.2591000,
                            'holding_facility' => 'Koforidua Central Police Holding Cells',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Electronic Devices',
                            'commodity_name'   => '64-Port Telecommunications SIM Bank Rig',
                            'seizure_name'     => '64-Port Telecommunications SIM Bank Rig',
                            'seizure_quantity' => '2 Units',
                            'seizure_value'    => 'GHS 62,000',
                            'seizure_location' => 'Koforidua Central Sector, Eastern Region',
                            'seizure_date'     => now()->subDays(2)->format('Y-m-d'),
                            'seizure_time'     => '16:00',
                            'seizure_officer'  => 'Kwaku Boateng',
                        ]
                    ]
                ]
            ]);
        }

        // 12. Case 11: Elubo International Border Post, Western Region
        $case11Ref = 'NCC-2026-6644';
        if (!SingleSuspectCase::where('case_number', $case11Ref)->exists() && !MultipleSuspectCase::where('case_number', $case11Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case11Ref,
                'title'               => 'Republic vs Jean-Pierre Kouassi (Operation Ivory Gate)',
                'unit_name'           => 'Evidence Management',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Det. Sgt. David Appiah',
                'submitting_officer'  => 'Det. Sgt. David Appiah',
                'suspect_name'        => 'Jean-Pierre Kouassi',
                'suspect_alias'       => 'Le Patron',
                'ghana_card'          => 'GHA-801293749-6',
                'gender'              => 'Male',
                'age'                 => 44,
                'nationality'         => 'Ivorian',
                'address'             => 'Elubo Border Enclave, Western Region',
                'raid_location'       => 'Elubo International Border Post, Western Region',
                'latitude'            => 5.2847000,
                'longitude'           => -2.7761000,
                'offense_description' => 'Cross-border narcotics trafficking via heavy vehicle haulage compartments spanning Abidjan-Takoradi route.',
                'holding_facility'    => 'Half Assini District Police Command Holding Facility',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->subDays(1)->format('Y-m-d'),
                'arrest_time'         => '17:40',
                'docket_data'         => [
                    'raid_location' => 'Elubo International Border Post, Western Region',
                    'latitude'      => 5.2847000,
                    'longitude'     => -2.7761000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Jean-Pierre Kouassi',
                            'suspect_alias'    => 'Le Patron',
                            'ghana_card'       => 'GHA-801293749-6',
                            'gender'           => 'Male',
                            'age'              => 44,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Elubo International Border Post, Western Region',
                            'latitude'         => 5.2847000,
                            'longitude'        => -2.7761000,
                            'holding_facility' => 'Half Assini District Police Command Holding Facility',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'High-Purity Cocaine Bricks',
                            'seizure_name'     => 'High-Purity Cocaine Bricks',
                            'seizure_quantity' => '18 Bricks',
                            'seizure_weight'   => '18.2',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'USD 540,000',
                            'seizure_location' => 'Elubo International Border Post, Western Region',
                            'seizure_date'     => now()->subDays(1)->format('Y-m-d'),
                            'seizure_time'     => '17:40',
                            'seizure_officer'  => 'David Appiah',
                        ]
                    ]
                ]
            ]);
        }

        // 13. Case 12: Wa Central District, Upper West Region
        $case12Ref = 'NCC-2026-7318';
        if (!SingleSuspectCase::where('case_number', $case12Ref)->exists() && !MultipleSuspectCase::where('case_number', $case12Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case12Ref,
                'title'               => 'Republic vs Sumaila Adams (Operation Savannah Hawk)',
                'unit_name'           => 'Lab Analysis',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Analyst George Gyasi',
                'submitting_officer'  => 'Analyst George Gyasi',
                'suspect_name'        => 'Sumaila Adams',
                'suspect_alias'       => 'Cobra',
                'ghana_card'          => 'GHA-409128374-0',
                'gender'              => 'Male',
                'age'                 => 36,
                'nationality'         => 'Ghanaian',
                'address'             => 'Wa Central Commercial Area, Upper West Region',
                'raid_location'       => 'Wa Central District, Upper West Region',
                'latitude'            => 10.0601000,
                'longitude'           => -2.5099000,
                'offense_description' => 'Unlawful possession and distribution of refined crystalline methamphetamine packages.',
                'holding_facility'    => 'Wa Regional Police Headquarters Holding Cell',
                'priority'            => 'LOW',
                'status'              => 'Under Multi-Unit Processing',
                'case_status'         => 'Under Multi-Unit Processing',
                'arrest_date'         => now()->subDays(5)->format('Y-m-d'),
                'arrest_time'         => '11:20',
                'docket_data'         => [
                    'raid_location' => 'Wa Central District, Upper West Region',
                    'latitude'      => 10.0601000,
                    'longitude'     => -2.5099000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Sumaila Adams',
                            'suspect_alias'    => 'Cobra',
                            'ghana_card'       => 'GHA-409128374-0',
                            'gender'           => 'Male',
                            'age'              => 36,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Wa Central District, Upper West Region',
                            'latitude'         => 10.0601000,
                            'longitude'        => -2.5099000,
                            'holding_facility' => 'Wa Regional Police Headquarters Holding Cell',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Methamphetamine Crystals',
                            'seizure_name'     => 'Methamphetamine Crystals',
                            'seizure_quantity' => '3 Packets',
                            'seizure_weight'   => '3.2',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'GHS 280,000',
                            'seizure_location' => 'Wa Central District, Upper West Region',
                            'seizure_date'     => now()->subDays(5)->format('Y-m-d'),
                            'seizure_time'     => '11:20',
                            'seizure_officer'  => 'George Gyasi',
                        ]
                    ]
                ]
            ]);
        }

        // 14. Case 13: Ho Civic Hub, Volta Region
        $case13Ref = 'NCC-2026-8195';
        if (!SingleSuspectCase::where('case_number', $case13Ref)->exists() && !MultipleSuspectCase::where('case_number', $case13Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case13Ref,
                'title'               => 'Republic vs Prosper Dzreke (Operation Volta Crest)',
                'unit_name'           => 'Crime Scene Support',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Chief Inspector Sena Amegashie',
                'submitting_officer'  => 'Chief Inspector Sena Amegashie',
                'suspect_name'        => 'Prosper Dzreke',
                'suspect_alias'       => 'Volta Don',
                'ghana_card'          => 'GHA-301928475-2',
                'gender'              => 'Male',
                'age'                 => 41,
                'nationality'         => 'Ghanaian',
                'address'             => 'Civic Center Road, Ho',
                'raid_location'       => 'Ho Civic Hub, Volta Region',
                'latitude'            => 6.6111000,
                'longitude'           => 0.4703000,
                'offense_description' => 'Regional logistics transit hub distributing bulk contraband pharmaceuticals and unprescribed depressants.',
                'holding_facility'    => 'Ho Central Police Command Cell Block',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->subDays(3)->format('Y-m-d'),
                'arrest_time'         => '15:10',
                'docket_data'         => [
                    'raid_location' => 'Ho Civic Hub, Volta Region',
                    'latitude'      => 6.6111000,
                    'longitude'     => 0.4703000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Prosper Dzreke',
                            'suspect_alias'    => 'Volta Don',
                            'ghana_card'       => 'GHA-301928475-2',
                            'gender'           => 'Male',
                            'age'              => 41,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Ho Civic Hub, Volta Region',
                            'latitude'         => 6.6111000,
                            'longitude'        => 0.4703000,
                            'holding_facility' => 'Ho Central Police Command Cell Block',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Pharmaceutical Tramadol & Diazepam Blister Packs',
                            'seizure_name'     => 'Pharmaceutical Tramadol & Diazepam Blister Packs',
                            'seizure_quantity' => '32000 Tablets',
                            'seizure_weight'   => '42',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'GHS 190,000',
                            'seizure_location' => 'Ho Civic Hub, Volta Region',
                            'seizure_date'     => now()->subDays(3)->format('Y-m-d'),
                            'seizure_time'     => '15:10',
                            'seizure_officer'  => 'Sena Amegashie',
                        ]
                    ]
                ]
            ]);
        }

        // 15. Case 14: Tema Port Quay 2, Greater Accra (Multiple Suspects)
        $case14Ref = 'NCC-2026-9023';
        if (!SingleSuspectCase::where('case_number', $case14Ref)->exists() && !MultipleSuspectCase::where('case_number', $case14Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case14Ref,
                'title'               => 'Republic vs Christopher Adjetey & Nana Poku (Operation Meridian Strike)',
                'unit_name'           => 'Crime Scene Support',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Chief Det. Inspector Raymond Tagoe',
                'submitting_officer'  => 'Chief Det. Inspector Raymond Tagoe',
                'suspect_name'        => 'Christopher Adjetey',
                'suspect_alias'       => 'Port Captain',
                'ghana_card'          => 'GHA-291038472-7',
                'gender'              => 'Male',
                'age'                 => 45,
                'nationality'         => 'Ghanaian',
                'address'             => 'Community 1 Port Enclave, Tema',
                'raid_location'       => 'Tema Port Quay 2, Tema',
                'latitude'            => 5.6471000,
                'longitude'           => 0.0076000,
                'offense_description' => 'Maritime container cargo tampering: concealing 40kg cocaine inside industrial refrigeration units imported from South America.',
                'holding_facility'    => 'Tema Regional Police Command High-Security Cell',
                'priority'            => 'HIGH',
                'status'              => 'Under Multi-Unit Processing',
                'case_status'         => 'Under Multi-Unit Processing',
                'arrest_date'         => now()->subDays(1)->format('Y-m-d'),
                'arrest_time'         => '05:40',
                'docket_data'         => [
                    'raid_location' => 'Tema Port Quay 2, Tema',
                    'latitude'      => 5.6471000,
                    'longitude'     => 0.0076000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Christopher Adjetey',
                            'suspect_alias'    => 'Port Captain',
                            'ghana_card'       => 'GHA-291038472-7',
                            'gender'           => 'Male',
                            'age'              => 45,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Tema Port Quay 2, Tema',
                            'latitude'         => 5.6471000,
                            'longitude'        => 0.0076000,
                            'holding_facility' => 'Tema Regional Police Command High-Security Cell',
                        ],
                        [
                            'suspect_name'     => 'Nana Poku',
                            'suspect_alias'    => 'Logistics Chief',
                            'ghana_card'       => 'GHA-102938475-4',
                            'gender'           => 'Male',
                            'age'              => 39,
                            'role'             => 'Clearance Agent',
                            'raid_location'    => 'Tema Port Quay 2, Tema',
                            'latitude'         => 5.6471000,
                            'longitude'        => 0.0076000,
                            'holding_facility' => 'Tema Regional Police Command High-Security Cell',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Pure Cocaine Compressed Bricks',
                            'seizure_name'     => 'Pure Cocaine Compressed Bricks',
                            'seizure_quantity' => '40 Bricks',
                            'seizure_weight'   => '40.0',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'USD 1,200,000',
                            'seizure_location' => 'Tema Port Quay 2, Tema',
                            'seizure_date'     => now()->subDays(1)->format('Y-m-d'),
                            'seizure_time'     => '05:40',
                            'seizure_officer'  => 'Raymond Tagoe',
                        ]
                    ]
                ]
            ]);
        }

        // 16. Case 15: Techiman Market Hub, Bono East Region
        $case15Ref = 'NCC-2026-9541';
        if (!SingleSuspectCase::where('case_number', $case15Ref)->exists() && !MultipleSuspectCase::where('case_number', $case15Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case15Ref,
                'title'               => 'Republic vs Haruna Danladi (Operation Crossroads)',
                'unit_name'           => 'Evidence Management',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Sgt. Emmanuel Donkor',
                'submitting_officer'  => 'Sgt. Emmanuel Donkor',
                'suspect_name'        => 'Haruna Danladi',
                'suspect_alias'       => 'Mallam',
                'ghana_card'          => 'GHA-849102938-1',
                'gender'              => 'Male',
                'age'                 => 48,
                'nationality'         => 'Ghanaian',
                'address'             => 'Techiman Central Market Road, Bono East',
                'raid_location'       => 'Techiman Market Hub, Bono East Region',
                'latitude'            => 7.5833000,
                'longitude'           => -1.9333000,
                'offense_description' => 'Transit freight truck carrying concealed narcotics hidden beneath agricultural cargo consignment.',
                'holding_facility'    => 'Techiman Divisional Police Cell Block',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->subDays(2)->format('Y-m-d'),
                'arrest_time'         => '13:20',
                'docket_data'         => [
                    'raid_location' => 'Techiman Market Hub, Bono East Region',
                    'latitude'      => 7.5833000,
                    'longitude'     => -1.9333000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Haruna Danladi',
                            'suspect_alias'    => 'Mallam',
                            'ghana_card'       => 'GHA-849102938-1',
                            'gender'           => 'Male',
                            'age'              => 48,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Techiman Market Hub, Bono East Region',
                            'latitude'         => 7.5833000,
                            'longitude'        => -1.9333000,
                            'holding_facility' => 'Techiman Divisional Police Cell Block',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Compressed Cannabis Slabs',
                            'seizure_name'     => 'Compressed Cannabis Slabs',
                            'seizure_quantity' => '80 Slabs',
                            'seizure_weight'   => '120',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'GHS 240,000',
                            'seizure_location' => 'Techiman Market Hub, Bono East Region',
                            'seizure_date'     => now()->subDays(2)->format('Y-m-d'),
                            'seizure_time'     => '13:20',
                            'seizure_officer'  => 'Emmanuel Donkor',
                        ]
                    ]
                ]
            ]);
        }

        // 17. Case 16: Kumasi Adum Commercial Street (Ashanti Region - Second Case)
        $case16Ref = 'NCC-2026-3115';
        if (!SingleSuspectCase::where('case_number', $case16Ref)->exists() && !MultipleSuspectCase::where('case_number', $case16Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case16Ref,
                'title'               => 'Republic vs Kwabena Frimpong (Operation Ashanti Crest)',
                'unit_name'           => 'Digital Forensics',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Senior Forensics Analyst Grey Essen',
                'submitting_officer'  => 'Senior Forensics Analyst Grey Essen',
                'suspect_name'        => 'Kwabena Frimpong',
                'suspect_alias'       => 'Ashanti Chief',
                'ghana_card'          => 'GHA-771920384-2',
                'gender'              => 'Male',
                'age'                 => 39,
                'nationality'         => 'Ghanaian',
                'address'             => 'Adum Commercial Street, Kumasi',
                'raid_location'       => 'Kumasi Adum Commercial Street, Ashanti Region',
                'latitude'            => 6.6920000,
                'longitude'           => -1.6250000,
                'offense_description' => 'Cyber narcotics laundering, crypto swap terminal operations, and encrypted messaging network.',
                'holding_facility'    => 'Kumasi Central Police Command Holding Cell',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->subDays(1)->format('Y-m-d'),
                'arrest_time'         => '10:00',
                'docket_data'         => [
                    'raid_location' => 'Kumasi Adum Commercial Street, Ashanti Region',
                    'latitude'      => 6.6920000,
                    'longitude'     => -1.6250000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Kwabena Frimpong',
                            'suspect_alias'    => 'Ashanti Chief',
                            'ghana_card'       => 'GHA-771920384-2',
                            'gender'           => 'Male',
                            'age'              => 39,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Kumasi Adum Commercial Street, Ashanti Region',
                            'latitude'         => 6.6920000,
                            'longitude'        => -1.6250000,
                            'holding_facility' => 'Kumasi Central Police Command Holding Cell',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Electronic Devices',
                            'commodity_name'   => 'Encrypted Crypto Mining & Ledger Hub',
                            'seizure_name'     => 'Encrypted Crypto Mining & Ledger Hub',
                            'seizure_quantity' => '2 Units',
                            'seizure_value'    => 'USD 38,000',
                            'seizure_location' => 'Kumasi Adum Commercial Street, Ashanti Region',
                            'seizure_date'     => now()->subDays(1)->format('Y-m-d'),
                            'seizure_time'     => '10:00',
                            'seizure_officer'  => 'Grey Essen',
                        ]
                    ]
                ]
            ]);
        }

        // 18. Case 17: Kejetia Central Transit, Kumasi (Ashanti Region - Third Case, making Kumasi Top Hotspot)
        $case17Ref = 'NCC-2026-3128';
        if (!SingleSuspectCase::where('case_number', $case17Ref)->exists() && !MultipleSuspectCase::where('case_number', $case17Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case17Ref,
                'title'               => 'Republic vs Yaw Mensah (Operation Golden Core)',
                'unit_name'           => 'Crime Scene Support',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Det. Inspector Isaac Donkor',
                'submitting_officer'  => 'Det. Inspector Isaac Donkor',
                'suspect_name'        => 'Yaw Mensah',
                'suspect_alias'       => 'Kumasi Rider',
                'ghana_card'          => 'GHA-661092837-5',
                'gender'              => 'Male',
                'age'                 => 33,
                'nationality'         => 'Ghanaian',
                'address'             => 'Kejetia Market Terminal, Kumasi',
                'raid_location'       => 'Kejetia Central Hub, Kumasi',
                'latitude'            => 6.6980000,
                'longitude'           => -1.6210000,
                'offense_description' => 'Regional distribution depot of adulterated narcotics and synthetic pharmaceutical compounds.',
                'holding_facility'    => 'Kumasi Central Police Command Holding Cell',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->format('Y-m-d'),
                'arrest_time'         => '07:15',
                'docket_data'         => [
                    'raid_location' => 'Kejetia Central Hub, Kumasi',
                    'latitude'      => 6.6980000,
                    'longitude'     => -1.6210000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Yaw Mensah',
                            'suspect_alias'    => 'Kumasi Rider',
                            'ghana_card'       => 'GHA-661092837-5',
                            'gender'           => 'Male',
                            'age'              => 33,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Kejetia Central Hub, Kumasi',
                            'latitude'         => 6.6980000,
                            'longitude'        => -1.6210000,
                            'holding_facility' => 'Kumasi Central Police Command Holding Cell',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'High-Concentration Heroin Packs',
                            'seizure_name'     => 'High-Concentration Heroin Packs',
                            'seizure_quantity' => '6 Packs',
                            'seizure_weight'   => '6.5',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'USD 210,000',
                            'seizure_location' => 'Kejetia Central Hub, Kumasi',
                            'seizure_date'     => now()->format('Y-m-d'),
                            'seizure_time'     => '07:15',
                            'seizure_officer'  => 'Isaac Donkor',
                        ]
                    ]
                ]
            ]);
        }

        // 19. Case 18: Tema Container Terminal Quay 1 (Greater Accra - Second Tema Case)
        $case18Ref = 'NCC-2026-9035';
        if (!SingleSuspectCase::where('case_number', $case18Ref)->exists() && !MultipleSuspectCase::where('case_number', $case18Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case18Ref,
                'title'               => 'Republic vs Benjamin Ashitey (Operation Atlantic Net)',
                'unit_name'           => 'Evidence Management',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Chief Det. Inspector Raymond Tagoe',
                'submitting_officer'  => 'Chief Det. Inspector Raymond Tagoe',
                'suspect_name'        => 'Benjamin Ashitey',
                'suspect_alias'       => 'Dock Master',
                'ghana_card'          => 'GHA-552910384-9',
                'gender'              => 'Male',
                'age'                 => 47,
                'nationality'         => 'Ghanaian',
                'address'             => 'Harbour Commercial Gate, Tema',
                'raid_location'       => 'Tema Port Quay 1, Tema',
                'latitude'            => 5.6480000,
                'longitude'           => 0.0090000,
                'offense_description' => 'Customs documentation fraud and container seal breach for maritime narcotics import.',
                'holding_facility'    => 'Tema Regional Police Command High-Security Cell',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->subDays(1)->format('Y-m-d'),
                'arrest_time'         => '12:30',
                'docket_data'         => [
                    'raid_location' => 'Tema Port Quay 1, Tema',
                    'latitude'      => 5.6480000,
                    'longitude'     => 0.0090000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Benjamin Ashitey',
                            'suspect_alias'    => 'Dock Master',
                            'ghana_card'       => 'GHA-552910384-9',
                            'gender'           => 'Male',
                            'age'              => 47,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Tema Port Quay 1, Tema',
                            'latitude'         => 5.6480000,
                            'longitude'        => 0.0090000,
                            'holding_facility' => 'Tema Regional Police Command High-Security Cell',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Refined Cocaine Paste Parcels',
                            'seizure_name'     => 'Refined Cocaine Paste Parcels',
                            'seizure_quantity' => '15 Parcels',
                            'seizure_weight'   => '15.0',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'USD 450,000',
                            'seizure_location' => 'Tema Port Quay 1, Tema',
                            'seizure_date'     => now()->subDays(1)->format('Y-m-d'),
                            'seizure_time'     => '12:30',
                            'seizure_officer'  => 'Raymond Tagoe',
                        ]
                    ]
                ]
            ]);
        }

        // 20. Case 19: Tema Fishing Harbour Industrial Sector (Greater Accra - Third Tema Case, making Tema Joint #1)
        $case19Ref = 'NCC-2026-9048';
        if (!SingleSuspectCase::where('case_number', $case19Ref)->exists() && !MultipleSuspectCase::where('case_number', $case19Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case19Ref,
                'title'               => 'Republic vs Joseph Tetteh (Operation Maritime Shield)',
                'unit_name'           => 'Lab Analysis',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Forensic Chemist Beatrice Danquah',
                'submitting_officer'  => 'Forensic Chemist Beatrice Danquah',
                'suspect_name'        => 'Joseph Tetteh',
                'suspect_alias'       => 'Skipper Joe',
                'ghana_card'          => 'GHA-441920485-6',
                'gender'              => 'Male',
                'age'                 => 52,
                'nationality'         => 'Ghanaian',
                'address'             => 'Fishing Harbour Wharf, Tema',
                'raid_location'       => 'Tema Fishing Harbour, Tema',
                'latitude'            => 5.6390000,
                'longitude'           => 0.0120000,
                'offense_description' => 'Maritime vessel fuel tanks modified for cross-border oceanic delivery of narcotic contraband.',
                'holding_facility'    => 'Tema Regional Police Command High-Security Cell',
                'priority'            => 'HIGH',
                'status'              => 'Under Multi-Unit Processing',
                'case_status'         => 'Under Multi-Unit Processing',
                'arrest_date'         => now()->format('Y-m-d'),
                'arrest_time'         => '04:50',
                'docket_data'         => [
                    'raid_location' => 'Tema Fishing Harbour, Tema',
                    'latitude'      => 5.6390000,
                    'longitude'     => 0.0120000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Joseph Tetteh',
                            'suspect_alias'    => 'Skipper Joe',
                            'ghana_card'       => 'GHA-441920485-6',
                            'gender'           => 'Male',
                            'age'              => 52,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Tema Fishing Harbour, Tema',
                            'latitude'         => 5.6390000,
                            'longitude'        => 0.0120000,
                            'holding_facility' => 'Tema Regional Police Command High-Security Cell',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Oceanic Cocaine Packages',
                            'seizure_name'     => 'Oceanic Cocaine Packages',
                            'seizure_quantity' => '22 Packs',
                            'seizure_weight'   => '22.0',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'USD 660,000',
                            'seizure_location' => 'Tema Fishing Harbour, Tema',
                            'seizure_date'     => now()->format('Y-m-d'),
                            'seizure_time'     => '04:50',
                            'seizure_officer'  => 'Beatrice Danquah',
                        ]
                    ]
                ]
            ]);
        }

        // 21. Case 20: Takoradi Port Western Gateway (Western Region - Second Case)
        $case20Ref = 'NCC-2026-6228';
        if (!SingleSuspectCase::where('case_number', $case20Ref)->exists() && !MultipleSuspectCase::where('case_number', $case20Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case20Ref,
                'title'               => 'Republic vs Paa Kwesi Biney (Operation Oil City Guard)',
                'unit_name'           => 'Crime Scene Support',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Forensic Chemist John Mensah',
                'submitting_officer'  => 'Forensic Chemist John Mensah',
                'suspect_name'        => 'Paa Kwesi Biney',
                'suspect_alias'       => 'Western Navigator',
                'ghana_card'          => 'GHA-992019483-3',
                'gender'              => 'Male',
                'age'                 => 43,
                'nationality'         => 'Ghanaian',
                'address'             => 'Poison Wharf Road, Takoradi',
                'raid_location'       => 'Takoradi Harbour Western Gateway, Takoradi',
                'latitude'            => 4.8910000,
                'longitude'           => -1.7450000,
                'offense_description' => 'Offshore transit offloading and storage of high-grade marijuana and narcotic concentrates.',
                'holding_facility'    => 'Takoradi Western Naval Command Security Cell',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->subDays(2)->format('Y-m-d'),
                'arrest_time'         => '18:10',
                'docket_data'         => [
                    'raid_location' => 'Takoradi Harbour Western Gateway, Takoradi',
                    'latitude'      => 4.8910000,
                    'longitude'     => -1.7450000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Paa Kwesi Biney',
                            'suspect_alias'    => 'Western Navigator',
                            'ghana_card'       => 'GHA-992019483-3',
                            'gender'           => 'Male',
                            'age'              => 43,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Takoradi Harbour Western Gateway, Takoradi',
                            'latitude'         => 4.8910000,
                            'longitude'        => -1.7450000,
                            'holding_facility' => 'Takoradi Western Naval Command Security Cell',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Compressed Cannabis Slabs',
                            'seizure_name'     => 'Compressed Cannabis Slabs',
                            'seizure_quantity' => '35 Slabs',
                            'seizure_weight'   => '70.0',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'GHS 140,000',
                            'seizure_location' => 'Takoradi Harbour Western Gateway, Takoradi',
                            'seizure_date'     => now()->subDays(2)->format('Y-m-d'),
                            'seizure_time'     => '18:10',
                            'seizure_officer'  => 'John Mensah',
                        ]
                    ]
                ]
            ]);
        }

        // 22. Case 21: Tamale Central Market Sector (Northern Region - Second Case)
        $case21Ref = 'NCC-2026-1065';
        if (!SingleSuspectCase::where('case_number', $case21Ref)->exists() && !MultipleSuspectCase::where('case_number', $case21Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case21Ref,
                'title'               => 'Republic vs Salifu Mohammed (Operation Northern Express)',
                'unit_name'           => 'Crime Scene Support',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Det. Inspector Alhasan Mumuni',
                'submitting_officer'  => 'Det. Inspector Alhasan Mumuni',
                'suspect_name'        => 'Salifu Mohammed',
                'suspect_alias'       => 'Courier Salifu',
                'ghana_card'          => 'GHA-881920394-1',
                'gender'              => 'Male',
                'age'                 => 38,
                'nationality'         => 'Ghanaian',
                'address'             => 'Aboabo Commercial Sector, Tamale',
                'raid_location'       => 'Tamale Central Market Sector, Northern Region',
                'latitude'            => 9.4030000,
                'longitude'           => -0.8410000,
                'offense_description' => 'Inland narcotics forwarding station distributing bulk tramadol and codeine syrups.',
                'holding_facility'    => 'Tamale Regional Police Command Security Cells',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->subDays(1)->format('Y-m-d'),
                'arrest_time'         => '08:45',
                'docket_data'         => [
                    'raid_location' => 'Tamale Central Market Sector, Northern Region',
                    'latitude'      => 9.4030000,
                    'longitude'     => -0.8410000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Salifu Mohammed',
                            'suspect_alias'    => 'Courier Salifu',
                            'ghana_card'       => 'GHA-881920394-1',
                            'gender'           => 'Male',
                            'age'              => 38,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Tamale Central Market Sector, Northern Region',
                            'latitude'         => 9.4030000,
                            'longitude'        => -0.8410000,
                            'holding_facility' => 'Tamale Regional Police Command Security Cells',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Illicit Tramadol 250mg Tablets',
                            'seizure_name'     => 'Illicit Tramadol 250mg Tablets',
                            'seizure_quantity' => '25 Cartons',
                            'seizure_weight'   => '50.0',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'GHS 125,000',
                            'seizure_location' => 'Tamale Central Market Sector, Northern Region',
                            'seizure_date'     => now()->subDays(1)->format('Y-m-d'),
                            'seizure_time'     => '08:45',
                            'seizure_officer'  => 'Alhasan Mumuni',
                        ]
                    ]
                ]
            ]);
        }

        // 23. Case 22: Paga Customs & Border Transit (Upper East Region - Second Case)
        $case22Ref = 'NCC-2026-2832';
        if (!SingleSuspectCase::where('case_number', $case22Ref)->exists() && !MultipleSuspectCase::where('case_number', $case22Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case22Ref,
                'title'               => 'Republic vs Boureima Compaore (Operation Sahara Shield)',
                'unit_name'           => 'Evidence Management',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Chief Inspector Anthony Agbeko',
                'submitting_officer'  => 'Chief Inspector Anthony Agbeko',
                'suspect_name'        => 'Boureima Compaore',
                'suspect_alias'       => 'Le Routier',
                'ghana_card'          => 'GHA-339102948-4',
                'gender'              => 'Male',
                'age'                 => 45,
                'nationality'         => 'Burkinabe',
                'address'             => 'Paga Commercial Border Zone, Upper East',
                'raid_location'       => 'Paga Customs Transit Station, Upper East Region',
                'latitude'            => 10.9840000,
                'longitude'           => -1.1120000,
                'offense_description' => 'Heavy transport lorry smuggling compressed opioid cargo across northern frontier.',
                'holding_facility'    => 'Navrongo Central Prison Holding Block',
                'priority'            => 'HIGH',
                'status'              => 'Under Investigation',
                'case_status'         => 'Under Investigation',
                'arrest_date'         => now()->subDays(2)->format('Y-m-d'),
                'arrest_time'         => '14:20',
                'docket_data'         => [
                    'raid_location' => 'Paga Customs Transit Station, Upper East Region',
                    'latitude'      => 10.9840000,
                    'longitude'     => -1.1120000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Boureima Compaore',
                            'suspect_alias'    => 'Le Routier',
                            'ghana_card'       => 'GHA-339102948-4',
                            'gender'           => 'Male',
                            'age'              => 45,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Paga Customs Transit Station, Upper East Region',
                            'latitude'         => 10.9840000,
                            'longitude'        => -1.1120000,
                            'holding_facility' => 'Navrongo Central Prison Holding Block',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Bulk Opioid Blisters',
                            'seizure_name'     => 'Bulk Opioid Blisters',
                            'seizure_quantity' => '18 Cartons',
                            'seizure_weight'   => '36.0',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'GHS 90,000',
                            'seizure_location' => 'Paga Customs Transit Station, Upper East Region',
                            'seizure_date'     => now()->subDays(2)->format('Y-m-d'),
                            'seizure_time'     => '14:20',
                            'seizure_officer'  => 'Anthony Agbeko',
                        ]
                    ]
                ]
            ]);
        }

        // 24. Case 23: Aflao Border Crossing Sector 2 (Volta Region - Second Case)
        $case23Ref = 'NCC-2026-4498';
        if (!SingleSuspectCase::where('case_number', $case23Ref)->exists() && !MultipleSuspectCase::where('case_number', $case23Ref)->exists()) {
            ForensicCase::create([
                'case_number'         => $case23Ref,
                'title'               => 'Republic vs Mawuli Klutse (Operation East Coast Watch)',
                'unit_name'           => 'Lab Analysis',
                'user_id'             => $officerId,
                'arresting_officer'   => 'Chief Officer Diana Oduro',
                'submitting_officer'  => 'Chief Officer Diana Oduro',
                'suspect_name'        => 'Mawuli Klutse',
                'suspect_alias'       => 'Lome Runner',
                'ghana_card'          => 'GHA-229103847-8',
                'gender'              => 'Male',
                'age'                 => 35,
                'nationality'         => 'Ghanaian',
                'address'             => 'Border Barrier Road, Aflao',
                'raid_location'       => 'Aflao Border Crossing Sector 2, Volta Region',
                'latitude'            => 6.1210000,
                'longitude'           => 1.1920000,
                'offense_description' => 'Cross-border courier smuggling synthetic psychoactive compounds concealed in personal effects.',
                'holding_facility'    => 'Aflao Border Security Cell Block',
                'priority'            => 'HIGH',
                'status'              => 'Under Multi-Unit Processing',
                'case_status'         => 'Under Multi-Unit Processing',
                'arrest_date'         => now()->subDays(1)->format('Y-m-d'),
                'arrest_time'         => '16:00',
                'docket_data'         => [
                    'raid_location' => 'Aflao Border Crossing Sector 2, Volta Region',
                    'latitude'      => 6.1210000,
                    'longitude'     => 1.1920000,
                    'suspects'      => [
                        [
                            'suspect_name'     => 'Mawuli Klutse',
                            'suspect_alias'    => 'Lome Runner',
                            'ghana_card'       => 'GHA-229103847-8',
                            'gender'           => 'Male',
                            'age'              => 35,
                            'role'             => 'Primary Suspect',
                            'raid_location'    => 'Aflao Border Crossing Sector 2, Volta Region',
                            'latitude'         => 6.1210000,
                            'longitude'        => 1.1920000,
                            'holding_facility' => 'Aflao Border Security Cell Block',
                        ]
                    ],
                    'seizures' => [
                        [
                            'item_number'      => 1,
                            'exhibit_category' => 'Drugs',
                            'commodity_name'   => 'Synthetic Amphetamine Crystals',
                            'seizure_name'     => 'Synthetic Amphetamine Crystals',
                            'seizure_quantity' => '4 Packets',
                            'seizure_weight'   => '4.0',
                            'seizure_unit'     => 'KG',
                            'seizure_value'    => 'GHS 160,000',
                            'seizure_location' => 'Aflao Border Crossing Sector 2, Volta Region',
                            'seizure_date'     => now()->subDays(1)->format('Y-m-d'),
                            'seizure_time'     => '16:00',
                            'seizure_officer'  => 'Diana Oduro',
                        ]
                    ]
                ]
            ]);
        }
    }
}
