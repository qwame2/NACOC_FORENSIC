<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ForensicCase;
use Illuminate\Support\Facades\DB;

/**
 * Seeder: MultiSuspectCaseSeeder
 *
 * Seeds a joint/multi-suspect case (Republic vs Esther & Jose) into the database
 * as a SINGLE ForensicCase record under ONE Case ID (NFC-2026-MULTI-001).
 * Both suspects' biodata and details are stored inside docket_data.suspects[].
 */
// class MultiSuspectCaseSeeder extends Seeder
// {
//     public function run(): void
//     {
//         $caseRef = 'NFC-2026-MULTI-001';
        $subRef  = 'NFC-2026-MULTI-001-S2';

        // Clean up old multi-row seed test records
        // ForensicCase::whereIn('case_number', [$caseRef, $subRef])->delete();

        // $title = 'Republic vs Esther Mensah & Jose Morales';
        // $unitProgress = [
        //     'Crime Scene Support' => [
        //         'status'     => 'COMPLETED',
        //         'officer'    => 'Insp. Kwame Acheampong',
        //         'updated_at' => now()->toDateTimeString(),
        //     ],
        //     'Evidence Management'          => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
        //     'Lab Analysis'                 => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
        //     'Digital & Physical Forensics' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
        // ];

        // $suspects = [
        //     [
        //         'suspect_number'   => 1,
        //         'suspect_name'     => 'Esther Mensah',
        //         'suspect_alias'    => 'Lady E',
        //         'ghana_card'       => 'GHA-7720481-2',
        //         'gender'           => 'Female',
        //         'dob'              => '1990-04-15',
        //         'age'              => 36,
        //         'height'           => '5\'5"',
        //         'eye_color'        => 'Brown',
        //         'nationality'      => 'Ghanaian',
        //         'pob'              => 'Kumasi, Ashanti Region, Ghana',
        //         'phone'            => '+233 24 555 7891',
        //         'address'          => 'House No. 14B, Spintex Road, Accra',
        //         'marital_status'   => 'Single',
        //         'has_children'     => false,
        //         'children_count'   => 0,
        //         'dependents'       => 'No Children',
        //         'raid_location'    => 'Kotoka International Airport, Terminal 3 Cargo Bay',
        //         'offense'          => 'Narcotics Trafficking — High-Purity Cocaine Import/Export',
        //         'date_of_arrest'   => '2026-09-12',
        //         'time_of_arrest'   => '14:45',
        //         'holding_facility' => 'NACOC High-Security Holding Cell Block A',
        //         'mugshot_front'    => null,
        //         'mugshot_left'     => null,
        //         'mugshot_right'    => null,
        //     ],
        //     [
        //         'suspect_number'   => 2,
        //         'suspect_name'     => 'Jose Morales',
        //         'suspect_alias'    => 'El Jefe',
        //         'ghana_card'       => 'ESP-9940021-7',
        //         'gender'           => 'Male',
        //         'dob'              => '1985-11-03',
        //         'age'              => 40,
        //         'height'           => '5\'11"',
        //         'eye_color'        => 'Dark Brown',
        //         'nationality'      => 'Spanish',
        //         'pob'              => 'Madrid, Spain',
        //         'phone'            => '+34 91 900 2233',
        //         'address'          => 'Room 412, Golden Tulip Hotel, Airport City, Accra',
        //         'marital_status'   => 'Married',
        //         'has_children'     => true,
        //         'children_count'   => 2,
        //         'dependents'       => '2 Kids',
        //         'spouse_name'      => 'Maria Morales',
        //         'spouse_phone'     => '+34 91 900 5566',
        //         'spouse_info'      => 'Spouse: Maria Morales — Madrid, Spain',
        //         'raid_location'    => 'Kotoka International Airport, Terminal 3 Cargo Bay',
        //         'offense'          => 'Narcotics Trafficking — High-Purity Cocaine Import/Export',
        //         'date_of_arrest'   => '2026-09-12',
        //         'time_of_arrest'   => '14:45',
        //         'holding_facility' => 'NACOC High-Security Holding Cell Block A',
        //         'mugshot_front'    => null,
        //         'mugshot_left'     => null,
        //         'mugshot_right'    => null,
        //     ],
        // ];

        // $docketData = [
        //     'full_name'       => 'Esther Mensah & Jose Morales',
        //     'suspects_count'  => 2,
        //     'unit_progress'   => $unitProgress,
        //     'suspects'        => $suspects,
        //     'seizures'        => [
        //         [
        //             'item_number'      => 1,
        //             'exhibit_category' => 'Narcotics',
        //             'seizure_name'     => 'Cocaine (High-Purity)',
        //             'seizure_name_drug' => 'Cocaine',
        //             'seizure_quantity' => '3',
        //             'seizure_packaging'=> 'Vacuum-Sealed Bricks',
        //             'seizure_weight'   => '4.85',
        //             'seizure_unit'     => 'kg',
        //             'seizure_value'    => 'GHS 2,400,000',
        //         ],
        //     ],
        // ];

//         $case = ForensicCase::create([
//             'case_number'         => $caseRef,
//             'title'               => $title,
//             'suspect_number'      => 1,
//             'parent_case_id'      => null,
//             'suspect_name'        => 'Esther Mensah & Jose Morales',
//             'suspect_alias'       => 'Lady E & El Jefe',
//             'ghana_card'          => 'GHA-7720481-2',
//             'gender'              => 'Female / Male',
//             'dob'                 => '1990-04-15',
//             'age'                 => 36,
//             'height'              => '5\'5"',
//             'eye_color'           => 'Brown',
//             'nationality'         => 'Ghanaian / Spanish',
//             'pob'                 => 'Kumasi, Ghana',
//             'phone'               => '+233 24 555 7891',
//             'address'             => 'House No. 14B, Spintex Road, Accra',
//             'marital_status'      => 'Single / Married',
//             'has_children'        => false,
//             'children_count'      => null,
//             'dependents'          => 'No Children',
//             'has_seizures'        => true,
//             'exhibit_category'    => 'Narcotics',
//             'seizure_name'        => 'Cocaine (High-Purity)',
//             'seizure_name_drug'   => 'Cocaine',
//             'seizure_quantity'    => '3',
//             'seizure_packaging'   => 'Vacuum-Sealed Bricks',
//             'seizure_weight'      => '4.85',
//             'seizure_unit'        => 'kg',
//             'seizure_value'       => 'GHS 2,400,000 (Estimated Street Value)',
//             'seizure_concealment' => 'Hidden in false-bottom suitcase',
//             'seizure_date'        => '2026-09-12',
//             'seizure_time'        => '14:30',
//             'seizure_location'    => 'Kotoka International Airport, Accra — Terminal 3 Cargo Bay',
//             'seizure_officer'     => 'Insp. Kwame Acheampong, Det. Sgt. Ama Osei',
//             'seizure_reason'      => 'Suspicious baggage flagged by X-Ray screening. K9 positive alert.',
//             'exhibits_summary'    => 'Item #1 [Narcotics]: Qty: 3 | Pkg: Vacuum-Sealed Bricks | Wt: 4.85 kg | Cocaine (High-Purity)',
//             'valuation'           => 'GHS 2,400,000 (Estimated Street Value)',
//             'raid_location'       => 'Kotoka International Airport, Terminal 3 Cargo Bay',
//             'offense_description' => 'Narcotics Trafficking — High-Purity Cocaine Import/Export in contravention of NACOC Act 1990 (PNDC Law 236)',
//             'arrest_date'         => '2026-09-12',
//             'arrest_time'         => '14:45',
//             'holding_facility'    => 'NACOC High-Security Holding Cell Block A',
//             'status'              => 'Under Multi-Unit Processing',
//             'priority'            => 'HIGH',
//             'location'            => 'Kotoka International Airport, Accra',
//             'unit_name'           => 'Crime Scene Support',
//             'commanding_officer'  => 'Cmdr. Crime Scene Support',
//             'submitting_officer'  => 'Insp. Kwame Acheampong',
//             'suspect_docket_mode' => 'MULTIPLE',
//             'operation_code'      => 'Cargo/Parcel',
//             'operation_code_name' => 'Cargo/Parcel',
//             'laboratory_number'   => 'Lab No.47/FS09/2026',
//             'hash_log'            => 'HASH-' . strtoupper(substr(md5('multi-seeder-single-001'), 0, 8)) . '-VERIFIED',
//             'incident_date'       => '2026-09-12',
//             'docket_data'         => $docketData,
//             'description'         => 'Joint Narcotics Trafficking — Cocaine Import via Cargo. Suspects: Esther Mensah & Jose Morales.',
//         ]);

//         $this->command->info("🎉 Multi-suspect single-case seed complete: '{$title}' stored under ONE Case ID ({$caseRef}) with 2 suspects in docket_data.");
//     }
// }
