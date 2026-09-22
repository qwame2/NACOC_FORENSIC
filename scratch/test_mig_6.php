<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$existingCases = DB::table('cases')->get();

foreach ($existingCases as $c) {
    echo "Processing case {$c->id} ({$c->case_number})...\n";
    $docket = json_decode($c->docket_data ?? '[]', true) ?: [];
    $suspects = $docket['suspects'] ?? [];
    $seizures = $docket['seizures'] ?? [];
    $associateExhibits = $docket['associate_exhibits'] ?? [];
    $allExhibits = array_merge($seizures, $associateExhibits);

    $isMulti = count($suspects) > 1 || !empty($c->parent_case_id) || (isset($docket['suspect_docket_mode']) && $docket['suspect_docket_mode'] === 'multiple');

    if ($isMulti) {
        $multiCaseId = DB::table('multiple_suspect_cases')->where('case_number', $c->case_number)->value('id');
        if (!$multiCaseId) {
            $multiCaseId = DB::table('multiple_suspect_cases')->insertGetId([
                'case_number'          => $c->case_number,
                'laboratory_number'    => $c->laboratory_number,
                'title'                => $c->title,
                'unit_name'            => $c->unit_name,
                'unit_id'              => $c->unit_id,
                'user_id'              => $c->user_id,
                'commanding_officer'   => $c->commanding_officer,
                'submitting_officer'   => $c->submitting_officer,
                'operation_code'       => $c->operation_code,
                'operation_code_name'  => $c->operation_code_name,
                'raid_location'        => $c->raid_location,
                'offense_description'  => $c->offense_description,
                'incident_date'        => $c->incident_date,
                'docket_summary'       => $c->exhibits_summary,
                'total_suspects_count' => count($suspects),
                'total_exhibits_count' => count($allExhibits),
                'priority'             => $c->priority ?? 'NORMAL',
                'status'               => $c->status ?? 'Approved',
                'hash_log'             => $c->hash_log,
                'created_at'           => $c->created_at ?? now(),
                'updated_at'           => $c->updated_at ?? now(),
            ]);
        }
        echo "Multi Case ID: $multiCaseId\n";

        if (empty($suspects)) {
            $suspects = [[
                'suspect_name'     => $c->suspect_name ?: 'Primary Suspect',
                'association_role' => 'Primary Suspect',
                'suspect_alias'    => $c->suspect_alias,
                'ghana_card'       => $c->ghana_card,
                'gender'           => $c->gender ?? 'Male',
                'dob'              => $c->dob,
                'age'              => $c->age,
                'height'           => $c->height,
                'height_unit'      => $c->height_unit ?? 'CM',
                'eye_color'        => $c->eye_color,
                'nationality'      => $c->nationality ?? 'Ghanaian',
                'pob'              => $c->pob,
                'phone'            => $c->phone,
                'address'          => $c->address,
                'marital_status'   => $c->marital_status,
                'children_count'   => $c->children_count ?? '0',
                'holding_facility'  => $c->holding_facility,
                'offense'          => $c->offense_description,
                'arrest_date'      => $c->arrest_date,
                'arrest_time'      => $c->arrest_time,
                'mugshot_front'    => $c->mugshot_front,
                'mugshot_left'     => $c->mugshot_left,
                'mugshot_right'    => $c->mugshot_right,
            ]];
        }

        $createdSuspectIds = [];
        foreach ($suspects as $sIdx => $s) {
            $roleRaw = strtolower($s['association_role'] ?? ($s['role'] ?? ''));
            $isCo = str_contains($roleRaw, 'co-') || ($sIdx === 1 && !str_contains($roleRaw, 'associate'));
            $isAssoc = str_contains($roleRaw, 'associate') || ($sIdx > 1 && !$isCo);
            $role = $isCo ? 'Co-Suspect' : ($isAssoc ? 'Associate Suspect' : 'Primary Suspect');
            $mug = $s['mugshots'] ?? [];

            try {
                $sId = DB::table('case_suspects')->insertGetId([
                    'multiple_suspect_case_id' => $multiCaseId,
                    'suspect_number'           => $sIdx + 1,
                    'role'                     => $role,
                    'associate_id'             => $s['associate_id'] ?? ($s['suspect_id'] ?? null),
                    'suspect_name'             => $s['suspect_name'] ?? ($s['name'] ?? ('Suspect #' . ($sIdx + 1))),
                    'suspect_alias'            => $s['suspect_alias'] ?? ($s['alias'] ?? ($sIdx === 0 ? $c->suspect_alias : null)),
                    'ghana_card'               => $s['ghana_card'] ?? ($sIdx === 0 ? $c->ghana_card : null),
                    'gender'                   => $s['gender'] ?? ($sIdx === 0 ? ($c->gender ?? 'Male') : 'Male'),
                    'dob'                      => $s['dob'] ?? ($sIdx === 0 ? $c->dob : null),
                    'age'                      => $s['age'] ?? ($sIdx === 0 ? $c->age : null),
                    'height'                   => $s['height'] ?? ($sIdx === 0 ? $c->height : null),
                    'height_unit'              => $s['height_unit'] ?? ($sIdx === 0 ? ($c->height_unit ?? 'CM') : 'CM'),
                    'eye_color'                => $s['eye_color'] ?? ($sIdx === 0 ? $c->eye_color : null),
                    'nationality'              => $s['nationality'] ?? ($sIdx === 0 ? ($c->nationality ?? 'Ghanaian') : 'Ghanaian'),
                    'pob'                      => $s['pob'] ?? ($sIdx === 0 ? $c->pob : null),
                    'phone'                    => $s['phone'] ?? ($sIdx === 0 ? $c->phone : null),
                    'address'                  => $s['address'] ?? ($sIdx === 0 ? $c->address : null),
                    'marital_status'           => $s['marital_status'] ?? ($sIdx === 0 ? $c->marital_status : null),
                    'has_children'             => !empty($s['children_count']) && $s['children_count'] !== '0',
                    'children_count'           => $s['children_count'] ?? '0',
                    'holding_facility'         => $s['holding_facility'] ?? ($sIdx === 0 ? $c->holding_facility : null),
                    'raid_location'            => $s['raid_location'] ?? ($sIdx === 0 ? $c->raid_location : null),
                    'offense_description'      => $s['offense'] ?? ($s['offense_description'] ?? ($sIdx === 0 ? $c->offense_description : null)),
                    'arrest_date'              => $s['arrest_date'] ?? ($sIdx === 0 ? $c->arrest_date : null),
                    'arrest_time'              => $s['arrest_time'] ?? ($sIdx === 0 ? $c->arrest_time : null),
                    'mugshot_front'            => $mug['front'] ?? ($mug['front-profile'] ?? ($s['mugshot_front'] ?? ($sIdx === 0 ? $c->mugshot_front : null))),
                    'mugshot_left'             => $mug['left-profile'] ?? ($mug['left'] ?? ($s['mugshot_left'] ?? ($sIdx === 0 ? $c->mugshot_left : null))),
                    'mugshot_right'            => $mug['right-profile'] ?? ($mug['right'] ?? ($s['mugshot_right'] ?? ($sIdx === 0 ? $c->mugshot_right : null))),
                    'created_at'               => $c->created_at ?? now(),
                    'updated_at'               => $c->updated_at ?? now(),
                ]);
                $createdSuspectIds[$sIdx] = $sId;
            } catch (\Exception $e) {
                echo "Error inserting suspect for case {$c->id}: " . $e->getMessage() . "\n";
            }
        }

        foreach ($allExhibits as $exIdx => $ex) {
            try {
                DB::table('exhibits')->insert([
                    'case_type'                 => 'multiple',
                    'multiple_suspect_case_id'  => $multiCaseId,
                    'suspect_id'                => $createdSuspectIds[0] ?? null,
                    'exhibit_number'            => $ex['exhibit_number'] ?? ('EXH-' . sprintf('%03d', $exIdx + 1)),
                    'exhibit_category'          => $ex['category'] ?? ($ex['exhibit_category'] ?? ($c->exhibit_category ?? 'Drugs')),
                    'commodity_name'            => $ex['seizure_name_drug'] ?? ($ex['seizure_name'] ?? ($ex['name'] ?? ($c->seizure_name_drug ?: $c->seizure_name))),
                    'seizure_quantity'          => $ex['seizure_quantity'] ?? ($ex['quantity'] ?? ($c->seizure_quantity ?? null)),
                    'seizure_packaging'         => $ex['seizure_packaging'] ?? ($c->seizure_packaging ?? null),
                    'seizure_weight'            => $ex['seizure_weight'] ?? ($ex['weight'] ?? ($c->seizure_weight ?? null)),
                    'seizure_unit'              => $ex['seizure_unit'] ?? ($ex['unit'] ?? ($c->seizure_unit ?? null)),
                    'seizure_value'             => $ex['seizure_value'] ?? ($c->seizure_value ?? null),
                    'seizure_concealment'        => $ex['seizure_concealment'] ?? ($c->seizure_concealment ?? null),
                    'seizure_date'              => $ex['seizure_date'] ?? ($c->seizure_date ?? null),
                    'seizure_time'              => $ex['seizure_time'] ?? ($c->seizure_time ?? null),
                    'seizure_location'          => $ex['seizure_location'] ?? ($c->seizure_location ?? null),
                    'seizure_officer'           => $ex['seizure_officer'] ?? ($c->seizure_officer ?? null),
                    'seizure_reason'            => $ex['seizure_reason'] ?? ($c->seizure_reason ?? null),
                    'veh_make'                  => $ex['veh_make'] ?? ($c->veh_make ?? null),
                    'veh_model'                 => $ex['veh_model'] ?? ($c->veh_model ?? null),
                    'veh_type'                  => $ex['veh_type'] ?? ($c->veh_type ?? null),
                    'veh_year'                  => $ex['veh_year'] ?? ($c->veh_year ?? null),
                    'veh_colour'                => $ex['veh_colour'] ?? ($c->veh_colour ?? null),
                    'veh_reg_no'                => $ex['veh_reg_no'] ?? ($c->veh_reg_no ?? null),
                    'veh_vin'                   => $ex['veh_vin'] ?? ($c->veh_vin ?? null),
                    'veh_engine_no'             => $ex['veh_engine_no'] ?? ($c->veh_engine_no ?? null),
                    'veh_country_reg'           => $ex['veh_country_reg'] ?? ($c->veh_country_reg ?? null),
                    'veh_owner'                 => $ex['veh_owner'] ?? ($c->veh_owner ?? null),
                    'veh_driver'                => $ex['veh_driver'] ?? ($c->veh_driver ?? null),
                    'wpn_model'                 => $ex['wpn_model'] ?? ($c->wpn_model ?? null),
                    'wpn_serial_no'             => $ex['wpn_serial_no'] ?? ($c->wpn_serial_no ?? null),
                    'wpn_calibre'               => $ex['wpn_calibre'] ?? ($c->wpn_calibre ?? null),
                    'wpn_colour'                => $ex['wpn_colour'] ?? ($c->wpn_colour ?? null),
                    'wpn_size'                  => $ex['wpn_size'] ?? ($c->wpn_size ?? null),
                    'dev_model'                 => $ex['dev_model'] ?? ($c->dev_model ?? null),
                    'dev_serial_no'             => $ex['dev_serial_no'] ?? ($c->dev_serial_no ?? null),
                    'dev_imei1'                 => $ex['dev_imei1'] ?? ($c->dev_imei1 ?? null),
                    'dev_imei2'                 => $ex['dev_imei2'] ?? ($c->dev_imei2 ?? null),
                    'dev_mac_address'           => $ex['dev_mac_address'] ?? ($c->dev_mac_address ?? null),
                    'evidence_photos'           => isset($ex['photos']) ? json_encode($ex['photos']) : json_encode($c->evidence_photos ?? []),
                    'created_at'                => $c->created_at ?? now(),
                    'updated_at'                => $c->updated_at ?? now(),
                ]);
            } catch (\Exception $e) {
                echo "Error inserting exhibit for case {$c->id}: " . $e->getMessage() . "\n";
            }
        }
    }
}
