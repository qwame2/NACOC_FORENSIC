<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ForensicCase;
use App\Models\User;
use App\Models\Unit;

class CaseSeeder extends Seeder
{
    /**
     * Helper to read test images from public/test/ and return as base64 data URLs.
     */
    protected function getImageDataUri(string $filename): string
    {
        $path = public_path('test/' . $filename);
        if (!file_exists($path)) {
            return 'test/' . $filename;
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = match($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            'avif'        => 'image/avif',
            'jfif'        => 'image/jpeg',
            default       => 'image/jpeg'
        };

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    public function run(): void
    {
        $samUser = User::where('username', 'sam')->first() ?? User::first();
        $userId = $samUser ? $samUser->id : 1;

        $cssUnit = Unit::where('code', 'CSSU')->first();
        $unitId = $cssUnit ? $cssUnit->id : 1;

        // =========================================================================
        // CASE 1: Single Suspect Female Case (Linda Addo) — Uses Lady front, left, right + D1, D2 + GUN3
        // =========================================================================
        $ladyFront = $this->getImageDataUri('Lady front.jfif');
        $ladyLeft  = $this->getImageDataUri('lady left.avif');
        $ladyRight = $this->getImageDataUri('lady right.jpg');
        $d1Img     = $this->getImageDataUri('D1.jfif');
        $d2Img     = $this->getImageDataUri('D2.jpg');
        $gun3Img   = $this->getImageDataUri('GUN3.webp');

        $case1Ref = 'CSS-2026-9042';

        $case1Docket = [
            'full_name'      => 'LINDA ADDO',
            'suspects_count' => 1,
            'suspects' => [
                [
                    'suspect_number'   => 1,
                    'suspect_name'     => 'LINDA ADDO',
                    'suspect_alias'    => 'Queen Linda',
                    'ghana_card'       => 'GHA-8839201-9',
                    'gender'           => 'Female',
                    'dob'              => '1998-05-14',
                    'age'              => 28,
                    'height'           => '168',
                    'height_unit'      => 'CM',
                    'eye_color'        => 'Brown',
                    'nationality'      => 'Ghanaian',
                    'pob'              => 'Cantonments, Accra',
                    'phone'            => '+233 24 411 2233',
                    'address'          => 'House No. 42, Cantonments, Accra',
                    'marital_status'   => 'Single',
                    'has_children'     => false,
                    'children_count'   => '0',
                    'dependents'       => 'No Children',
                    'raid_location'    => 'Cantonments Luxury Apartment, Suite 4B',
                    'offense'          => 'Illegal Possession & Attempted Export of High-Purity Controlled Narcotics and Unlicensed Firearm',
                    'date_of_arrest'   => '2026-09-18',
                    'time_of_arrest'   => '11:30',
                    'holding_facility' => 'NACOC High-Security Holding Cell A',
                    'association_role' => 'Primary Suspect',
                    'mugshot_front'    => $ladyFront,
                    'mugshot_left'     => $ladyLeft,
                    'mugshot_right'    => $ladyRight,
                ]
            ],
            'seizures' => [
                [
                    'exhibit_number'     => 1,
                    'category'           => 'Drugs / Narcotics',
                    'seizure_name'       => 'Heroin (High-Purity Brown Powder)',
                    'seizure_name_drug'  => 'Heroin (High-Purity Brown Powder)',
                    'seizure_quantity'   => '12',
                    'seizure_packaging'  => 'Vacuum-Sealed Foil Slabs',
                    'seizure_weight'     => '12.50',
                    'seizure_unit'       => 'Kilograms (kg)',
                    'seizure_value'      => 'GHS 3,750,000',
                    'seizure_concealment'=> 'Hidden inside double-walled export luggage trunk',
                    'seizure_date'       => '2026-09-18',
                    'seizure_time'       => '11:30',
                    'seizure_location'   => 'Cantonments Luxury Apartment, Suite 4B',
                    'seizure_officer'    => 'DSP Kwame Mensah (SD-0042)',
                    'seizure_reason'     => 'Intelligence-led raid under NACOC Search & Seizure Warrant #2026-08',
                    'associated_person'  => 'LINDA ADDO',
                    'evidence_photos'    => [$d1Img, $d2Img],
                ],
                [
                    'exhibit_number'     => 2,
                    'category'           => 'Firearm / Ammunition / Weapon',
                    'seizure_name'       => 'Glock 19 Pistol',
                    'seizure_name_weapon'=> 'Glock 19 Semi-Automatic Pistol',
                    'seizure_quantity'   => '1',
                    'seizure_packaging'  => 'Hard-case pistol box',
                    'seizure_weight'     => '850',
                    'seizure_unit'       => 'Grams (g)',
                    'seizure_value'      => 'GHS 45,000',
                    'seizure_concealment'=> 'Concealed in bedside nightstand drawer',
                    'seizure_date'       => '2026-09-18',
                    'seizure_time'       => '11:35',
                    'seizure_location'   => 'Cantonments Luxury Apartment, Suite 4B',
                    'seizure_officer'    => 'Insp. Samuel Atta (SD-0112)',
                    'seizure_reason'     => 'Unlicensed firearm recovered during crime scene search',
                    'associated_person'  => 'LINDA ADDO',
                    'evidence_photos'    => [$gun3Img],
                    'wpn' => [
                        'model'           => 'Glock 19 Gen 5',
                        'serial_no'       => 'G-99201-ACCRA',
                        'calibre'         => '9x19mm Parabellum',
                        'colour'          => 'Matte Black',
                        'loaded_status'   => 'Loaded (15 rounds in magazine)',
                        'condition'       => 'Excellent / Fully Operational',
                    ]
                ]
            ],
            'unit_progress' => [
                'Crime Scene Support' => [
                    'status'     => 'COMPLETED',
                    'officer'    => 'DSP Kwame Mensah (SD-0042)',
                    'updated_at' => '2026-09-18 12:00:00',
                ],
                'Evidence Management' => [
                    'status'     => 'COMPLETED',
                    'officer'    => 'Insp. Samuel Atta (SD-0112)',
                    'updated_at' => '2026-09-18 14:15:00',
                ],
                'Lab Analysis' => [
                    'status'     => 'COMPLETED',
                    'officer'    => 'Samuel Atta',
                    'updated_at' => '2026-09-19 10:30:00',
                ],
                'Digital & Physical Forensics' => [
                    'status'     => 'PENDING',
                    'officer'    => null,
                    'updated_at' => null,
                ]
            ]
        ];

        ForensicCase::create([
            'case_number'                         => $case1Ref,
            'title'                               => 'Republic vs LINDA ADDO',
            'unit_name'                           => 'Crime Scene Support',
            'unit_id'                             => $unitId,
            'user_id'                             => $userId,
            'commanding_officer'                  => 'Cmdr. Crime Scene Support',
            'submitting_officer'                  => 'DSP Kwame Mensah (SD-0042)',
            'suspect_docket_mode'                 => 'SINGLE',
            'suspect_name'                        => 'LINDA ADDO',
            'suspect_alias'                       => 'Queen Linda',
            'ghana_card'                          => 'GHA-8839201-9',
            'gender'                              => 'Female',
            'dob'                                 => '1998-05-14',
            'age'                                 => 28,
            'height'                              => '168',
            'height_unit'                         => 'CM',
            'eye_color'                           => 'Brown',
            'nationality'                         => 'Ghanaian',
            'pob'                                 => 'Cantonments, Accra',
            'phone'                               => '+233 24 411 2233',
            'address'                             => 'House No. 42, Cantonments, Accra',
            'marital_status'                      => 'Single',
            'has_children'                        => false,
            'children_count'                      => '0',
            'dependents'                          => 'No Children',
            'holding_facility'                    => 'NACOC High-Security Holding Cell A',
            'operation_code'                      => 'Operation Shield',
            'operation_code_name'                 => 'Operation Shield',
            'raid_location'                       => 'Cantonments Luxury Apartment, Suite 4B',
            'offense_description'                 => 'Illegal Possession & Attempted Export of High-Purity Controlled Narcotics and Unlicensed Firearm',
            'arrest_date'                         => '2026-09-18',
            'arrest_time'                         => '11:30',
            'exhibit_category'                    => 'Drugs / Narcotics',
            'seizure_name'                        => 'Heroin (High-Purity Brown Powder)',
            'seizure_name_drug'                   => 'Heroin (High-Purity Brown Powder)',
            'seizure_quantity'                    => '12',
            'seizure_packaging'                   => 'Vacuum-Sealed Foil Slabs',
            'seizure_weight'                      => '12.50',
            'seizure_unit'                        => 'Kilograms (kg)',
            'seizure_value'                       => 'GHS 3,750,000',
            'seizure_concealment'                 => 'Hidden inside double-walled export luggage trunk',
            'seizure_date'                        => '2026-09-18',
            'seizure_time'                        => '11:30',
            'seizure_location'                    => 'Cantonments Luxury Apartment, Suite 4B',
            'seizure_officer'                     => 'DSP Kwame Mensah (SD-0042)',
            'seizure_reason'                      => 'Intelligence-led raid under NACOC Search & Seizure Warrant #2026-08',
            'exhibits_summary'                    => 'Item #1 [Drugs]: Qty: 12 | Pkg: Vacuum-Sealed Foil Slabs | Wt: 12.50 kg | Heroin',
            'valuation'                           => 'GHS 3,750,000',
            'evidence_photos'                     => [$d1Img, $d2Img, $gun3Img],
            'mugshot_front'                       => $ladyFront,
            'mugshot_left'                        => $ladyLeft,
            'mugshot_right'                       => $ladyRight,
            'presumptive_test_result'             => 'YES (Positive Colorimetric Violet Reaction)',
            'confirmatory_test_result'            => 'YES',
            'sample_weight_description'           => 'Heroin Slabs (Vacuum-Sealed)',
            'sample_weight_units'                 => '12 Foil Slabs',
            'sample_weight_gross'                 => '12.50 kg',
            'sample_weight_taken'                 => '25.0 g',
            'sample_weight_reserved'              => '12.475 kg',
            'confirmatory_substance_identification' => 'Diacetylmorphine (Heroin Hydrochloride)',
            'detected_substances'                 => ['Heroin (Diacetylmorphine)', 'Codeine', 'Morphine Traces'],
            'confirmatory_test_methods'           => 'Gas Chromatography-Mass Spectrometry (GC-MS) & FTIR Spectroscopy',
            'confirmatory_specifications'         => 'Purity Level: 88.5% High-Grade Heroin Powder',
            'laboratory_number'                   => 'LAB-2026-8801',
            'priority'                            => 'HIGH',
            'status'                              => 'Approved',
            'location'                            => 'Cantonments, Accra (House No. 42)',
            'description'                         => 'Offence: Narcotics & Firearm Trafficking | Raid Ref: Operation Shield | Custody: Holding Cell A',
            'docket_data'                         => $case1Docket,
        ]);

        // =========================================================================
        // CASE 2: Multi-Suspect Joint Syndicate Case (Kwame Mensah & Kofi Owusu)
        // Uses 01.jfif, 02.jpg, 03.jfif, 04.jfif, 05.jfif + D3.jpg + GUN4.webp
        // =========================================================================
        $m01 = $this->getImageDataUri('01.jfif');
        $m02 = $this->getImageDataUri('02.jpg');
        $m03 = $this->getImageDataUri('03.jfif');
        $m04 = $this->getImageDataUri('04.jfif');
        $m05 = $this->getImageDataUri('05.jfif');
        $d3Img   = $this->getImageDataUri('D3.jpg');
        $gun4Img = $this->getImageDataUri('GUN4.webp');

        $case2Ref = 'CSS-2026-8819';

        $case2Docket = [
            'full_name'      => 'KWAME MENSAH & KOFI OWUSU',
            'suspects_count' => 2,
            'suspects' => [
                [
                    'suspect_number'   => 1,
                    'suspect_name'     => 'KWAME MENSAH',
                    'suspect_alias'    => 'Chief Kwame',
                    'ghana_card'       => 'GHA-1029384-5',
                    'gender'           => 'Male',
                    'dob'              => '1988-03-22',
                    'age'              => 38,
                    'height'           => '175',
                    'height_unit'      => 'CM',
                    'eye_color'        => 'Black',
                    'nationality'      => 'Ghanaian',
                    'pob'              => 'Kumasi, Ashanti Region',
                    'phone'            => '+233 20 888 1122',
                    'address'          => 'House No. 14B, Spintex Road, Accra',
                    'marital_status'   => 'Married',
                    'has_children'     => true,
                    'children_count'   => '3',
                    'spouse_name'      => 'Abena Mensah',
                    'spouse_phone'     => '+233 20 888 3344',
                    'spouse_info'      => 'Abena Mensah (+233 20 888 3344)',
                    'dependents'       => '3 Kids',
                    'raid_location'    => 'Tema Port Gate 4, Container Terminal',
                    'offense'          => 'Organized Narcotics Trafficking & Illegal Importation of Assault Weapons',
                    'date_of_arrest'   => '2026-09-19',
                    'time_of_arrest'   => '15:45',
                    'holding_facility' => 'NACOC High-Security Holding Cell B',
                    'association_role' => 'Primary Suspect',
                    'mugshot_front'    => $m01,
                    'mugshot_left'     => $m02,
                    'mugshot_right'    => $m03,
                ],
                [
                    'suspect_number'   => 2,
                    'associate_id'     => 'ASC-001',
                    'suspect_id'       => 'ASC-001',
                    'suspect_name'     => 'KOFI OWUSU',
                    'suspect_alias'    => 'Kofi Cash',
                    'ghana_card'       => 'GHA-5544332-1',
                    'gender'           => 'Male',
                    'dob'              => '1992-09-10',
                    'age'              => 34,
                    'height'           => '180',
                    'height_unit'      => 'CM',
                    'eye_color'        => 'Dark Brown',
                    'nationality'      => 'Ghanaian',
                    'pob'              => 'Tema Community 2',
                    'phone'            => '+233 55 999 4455',
                    'address'          => 'Plot 8, Tema Community 5',
                    'marital_status'   => 'Single',
                    'has_children'     => false,
                    'children_count'   => '0',
                    'dependents'       => 'No Children',
                    'raid_location'    => 'Tema Port Gate 4, Container Terminal',
                    'offense'          => 'Co-Conspirator in Intercepted Port Contraband Shipment & Weapon Concealment',
                    'date_of_arrest'   => '2026-09-19',
                    'time_of_arrest'   => '15:45',
                    'holding_facility' => 'NACOC High-Security Holding Cell B',
                    'association_role' => 'Associate Suspect',
                    'is_associate'     => true,
                    'mugshot_front'    => $m04,
                    'mugshot_left'     => $m05,
                    'mugshot_right'    => $m01,
                ]
            ],
            'seizures' => [
                [
                    'exhibit_number'     => 1,
                    'category'           => 'Drugs / Narcotics',
                    'seizure_name'       => 'Cannabis Sativa (Compressed High-Grade Herb)',
                    'seizure_name_drug'  => 'Cannabis Sativa (Compressed High-Grade Herb)',
                    'seizure_quantity'   => '20',
                    'seizure_packaging'  => 'Jute Burlap Sacks',
                    'seizure_weight'     => '45.00',
                    'seizure_unit'       => 'Kilograms (kg)',
                    'seizure_value'      => 'GHS 1,800,000',
                    'seizure_concealment'=> 'Concealed inside hollowed-out timber export logs',
                    'seizure_date'       => '2026-09-19',
                    'seizure_time'       => '15:40',
                    'seizure_location'   => 'Tema Port Gate 4, Container Terminal',
                    'seizure_officer'    => 'Insp. Eric Ofori (SD-0089)',
                    'seizure_reason'     => 'Routine container freight inspection and K9 positive alert',
                    'associated_person'  => 'KWAME MENSAH & KOFI OWUSU',
                    'evidence_photos'    => [$d3Img],
                ],
                [
                    'exhibit_number'     => 2,
                    'category'           => 'Firearm / Ammunition / Weapon',
                    'seizure_name'       => 'AK-47 Assault Rifle',
                    'seizure_name_weapon'=> 'AK-47 Assault Rifle',
                    'seizure_quantity'   => '1',
                    'seizure_packaging'  => 'Wrapped in heavy oilcloth sheet',
                    'seizure_weight'     => '4.30',
                    'seizure_unit'       => 'Kilograms (kg)',
                    'seizure_value'      => 'GHS 65,000',
                    'seizure_concealment'=> 'Hidden under driver cabin floorboard of haulage truck',
                    'seizure_date'       => '2026-09-19',
                    'seizure_time'       => '15:50',
                    'seizure_location'   => 'Tema Port Gate 4, Container Terminal',
                    'seizure_officer'    => 'Det. Sgt. Ama Osei (SD-0204)',
                    'seizure_reason'     => 'Weapon search following driver cabin search',
                    'associated_person'  => 'KOFI OWUSU',
                    'associate_id'       => 'ASC-001',
                    'is_associate'       => true,
                    'evidence_photos'    => [$gun4Img],
                    'wpn' => [
                        'model'           => 'AK-47 Kalashnikov',
                        'serial_no'       => 'AK-47-8831-GH',
                        'calibre'         => '7.62x39mm Soviet',
                        'colour'          => 'Dark Metal & Wood Stock',
                        'loaded_status'   => 'Loaded (30 rounds in banana magazine)',
                        'condition'       => 'Operational / Modified Selector Switch',
                    ]
                ]
            ],
            'unit_progress' => [
                'Crime Scene Support' => [
                    'status'     => 'COMPLETED',
                    'officer'    => 'Insp. Eric Ofori (SD-0089)',
                    'updated_at' => '2026-09-19 16:30:00',
                ],
                'Evidence Management' => [
                    'status'     => 'COMPLETED',
                    'officer'    => 'Insp. Samuel Atta (SD-0112)',
                    'updated_at' => '2026-09-19 18:00:00',
                ],
                'Lab Analysis' => [
                    'status'     => 'COMPLETED',
                    'officer'    => 'John Mensah',
                    'updated_at' => '2026-09-20 09:15:00',
                ],
                'Digital & Physical Forensics' => [
                    'status'     => 'PENDING',
                    'officer'    => null,
                    'updated_at' => null,
                ]
            ]
        ];

        ForensicCase::create([
            'case_number'         => $case2Ref,
            'title'               => 'Republic vs KWAME MENSAH & KOFI OWUSU',
            'unit_name'           => 'Crime Scene Support',
            'unit_id'             => $unitId,
            'user_id'             => $userId,
            'commanding_officer'  => 'Cmdr. Crime Scene Support',
            'submitting_officer'  => 'Insp. Eric Ofori (SD-0089)',
            'suspect_docket_mode' => 'MULTIPLE',
            'suspect_name'        => 'KWAME MENSAH',
            'suspect_alias'       => 'Chief Kwame',
            'ghana_card'          => 'GHA-1029384-5',
            'gender'              => 'Male',
            'dob'                 => '1988-03-22',
            'age'                 => 38,
            'height'              => '175',
            'height_unit'         => 'CM',
            'eye_color'           => 'Black',
            'nationality'         => 'Ghanaian',
            'pob'                 => 'Kumasi, Ghana',
            'phone'               => '+233 20 888 1122',
            'address'             => 'House No. 14B, Spintex Road, Accra',
            'marital_status'      => 'Married',
            'has_children'        => true,
            'children_count'      => '3',
            'dependents'          => '3 Kids',
            'holding_facility'    => 'NACOC High-Security Holding Cell B',
            'operation_code'      => 'Port Guard',
            'operation_code_name' => 'Port Guard',
            'raid_location'       => 'Tema Port Gate 4, Container Terminal',
            'offense_description' => 'Organized Narcotics Trafficking & Illegal Importation of Assault Weapons in contravention of PNDC Law 236',
            'arrest_date'         => '2026-09-19',
            'arrest_time'         => '15:45',
            'exhibit_category'    => 'Drugs / Narcotics',
            'seizure_name'        => 'Cannabis Sativa (Compressed High-Grade Herb)',
            'seizure_name_drug'   => 'Cannabis Sativa (Compressed High-Grade Herb)',
            'seizure_quantity'    => '20',
            'seizure_packaging'   => 'Jute Burlap Sacks',
            'seizure_weight'      => '45.00',
            'seizure_unit'        => 'Kilograms (kg)',
            'seizure_value'       => 'GHS 1,800,000',
            'seizure_concealment' => 'Concealed inside hollowed-out timber export logs',
            'seizure_date'        => '2026-09-19',
            'seizure_time'        => '15:40',
            'seizure_location'    => 'Tema Port Gate 4, Container Terminal',
            'seizure_officer'     => 'Insp. Eric Ofori (SD-0089)',
            'seizure_reason'      => 'Routine container freight inspection and K9 contraband positive alert',
            'exhibits_summary'    => 'Item #1 [Drugs]: Qty: 20 | Pkg: Jute Sacks | Wt: 45.00 kg | Cannabis Sativa',
            'valuation'           => 'GHS 1,800,000',
            'evidence_photos'     => [$d3Img, $gun4Img],
            'mugshot_front'       => $m01,
            'mugshot_left'        => $m02,
            'mugshot_right'       => $m03,
            'presumptive_test_result'             => 'YES (Positive Colorimetric Reaction)',
            'confirmatory_test_result'            => 'YES',
            'sample_weight_description'           => 'Cannabis Sativa Herbal Material',
            'sample_weight_units'                 => '20 Jute Sacks',
            'sample_weight_gross'                 => '45.00 kg',
            'sample_weight_taken'                 => '50.0 g',
            'sample_weight_reserved'              => '44.95 kg',
            'confirmatory_substance_identification' => 'Delta-9-Tetrahydrocannabinol (THC)',
            'detected_substances'                 => ['Delta-9-THC', 'Cannabidiol (CBD)', 'Cannabinol (CBN)'],
            'confirmatory_test_methods'           => 'Gas Chromatography (GC-FID) & Thin Layer Chromatography (TLC)',
            'confirmatory_specifications'         => 'High THC Content Verified (Grade A Cannabis Sativa)',
            'laboratory_number'                   => 'LAB-2026-8820',
            'priority'                            => 'HIGH',
            'status'                              => 'Approved',
            'location'                            => 'Tema Port Gate 4, Container Terminal',
            'description'                         => 'Offence: Joint Narcotics & Weapon Importation | Raid Ref: Port Guard | Custody: Cell B',
            'docket_data'                         => $case2Docket,
        ]);

        $this->command->info("✅ Successfully seeded database with cases, suspects, exhibits and biometric images from public/test!");
    }
}
