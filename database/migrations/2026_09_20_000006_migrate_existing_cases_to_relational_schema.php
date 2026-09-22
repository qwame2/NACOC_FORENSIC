<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('cases')) {
            return;
        }

        $existingCases = DB::table('cases')->get();

        foreach ($existingCases as $c) {
            $docket = json_decode($c->docket_data ?? '[]', true) ?: [];
            $suspects = $docket['suspects'] ?? [];
            $seizures = $docket['seizures'] ?? [];
            $associateExhibits = $docket['associate_exhibits'] ?? [];
            $allExhibits = array_merge($seizures, $associateExhibits);

            $isMulti = count($suspects) > 1 || !empty($c->parent_case_id) || (isset($docket['suspect_docket_mode']) && $docket['suspect_docket_mode'] === 'multiple');

            if ($isMulti) {
                // Check if multiple_suspect_cases already exists for this case_number
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

                // Migrate Suspects
                $createdSuspectIds = [];
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

                foreach ($suspects as $sIdx => $s) {
                    $roleRaw = strtolower($s['association_role'] ?? ($s['role'] ?? ''));
                    $isCo = str_contains($roleRaw, 'co-') || ($sIdx === 1 && !str_contains($roleRaw, 'associate'));
                    $isAssoc = str_contains($roleRaw, 'associate') || ($sIdx > 1 && !$isCo);
                    $role = $isCo ? 'Co-Suspect' : ($isAssoc ? 'Associate Suspect' : 'Primary Suspect');

                    $mug = $s['mugshots'] ?? [];

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
                }

                // Migrate Exhibits
                foreach ($allExhibits as $exIdx => $ex) {
                    $ownerName = $ex['associated_person'] ?? ($ex['suspect_name'] ?? ($ex['owner_name'] ?? null));
                    $matchedSuspectId = null;
                    if (!empty($ownerName)) {
                        foreach ($suspects as $sIdx => $sp) {
                            $spN = $sp['suspect_name'] ?? null;
                            if ($spN && (str_contains(strtolower($spN), strtolower($ownerName)) || str_contains(strtolower($ownerName), strtolower($spN)))) {
                                $matchedSuspectId = $createdSuspectIds[$sIdx] ?? null;
                                break;
                            }
                        }
                    }
                    if (!$matchedSuspectId) {
                        $matchedSuspectId = $createdSuspectIds[0] ?? null;
                    }

                    DB::table('exhibits')->insert([
                        'case_type'                 => 'multiple',
                        'multiple_suspect_case_id'  => $multiCaseId,
                        'suspect_id'                => $matchedSuspectId,
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
                }

                // Migrate Associate Lab Exams
                $assocLabs = DB::table('associate_lab_examinations')->where('case_id', $c->id)->get();
                foreach ($assocLabs as $al) {
                    $matchedSuspectId = null;
                    if (!empty($al->suspect_name)) {
                        foreach ($suspects as $sIdx => $sp) {
                            $spN = $sp['suspect_name'] ?? null;
                            if ($spN && (str_contains(strtolower($spN), strtolower($al->suspect_name)) || str_contains(strtolower($al->suspect_name), strtolower($spN)))) {
                                $matchedSuspectId = $createdSuspectIds[$sIdx] ?? null;
                                break;
                            }
                        }
                    }

                    DB::table('laboratory_examination_results')->insert([
                        'case_type'                 => 'multiple',
                        'multiple_suspect_case_id'  => $multiCaseId,
                        'suspect_id'                => $matchedSuspectId,
                        'laboratory_number'         => $al->laboratory_number ?: $c->laboratory_number,
                        'exam_reference'            => $al->exam_reference,
                        'analyst'                   => $al->analyst,
                        'date_received'             => $al->date_received,
                        'date_started'              => $al->date_started,
                        'date_completed'            => $al->date_completed,
                        'presumptive_result'        => $al->presumptive_result,
                        'confirmatory_result'       => $al->confirmatory_result ?? 'YES',
                        'sample_weight_description' => $al->sample_weight_description,
                        'sample_weight_units'       => $al->sample_weight_units,
                        'sample_weight_gross'       => $al->sample_weight_gross,
                        'sample_weight_taken'       => $al->sample_weight_taken,
                        'sample_weight_reserved'    => $al->sample_weight_reserved,
                        'substance_identification'  => $al->substance_identification,
                        'detected_substances'       => is_array($al->detected_substances) ? json_encode($al->detected_substances) : $al->detected_substances,
                        'test_methods'              => $al->test_methods,
                        'specifications'            => $al->specifications,
                        'findings'                  => $al->findings,
                        'has_saved'                 => $al->has_saved ?? true,
                        'created_at'                => $al->created_at ?? now(),
                        'updated_at'                => $al->updated_at ?? now(),
                    ]);
                }

                // If no associate lab exams were found in associate_lab_examinations table, check main docket lab_examination
                if ($assocLabs->isEmpty() && (!empty($c->confirmatory_substance_identification) || !empty($c->presumptive_test_result))) {
                    DB::table('laboratory_examination_results')->insert([
                        'case_type'                 => 'multiple',
                        'multiple_suspect_case_id'  => $multiCaseId,
                        'suspect_id'                => $createdSuspectIds[0] ?? null,
                        'laboratory_number'         => $c->laboratory_number,
                        'presumptive_result'        => $c->presumptive_test_result,
                        'confirmatory_result'       => $c->confirmatory_test_result ?? 'YES',
                        'sample_weight_description' => $c->sample_weight_description ?: ($c->seizure_name_drug ?: $c->seizure_name),
                        'sample_weight_units'       => $c->sample_weight_units ?: $c->seizure_quantity,
                        'sample_weight_gross'       => $c->sample_weight_gross ?: $c->seizure_weight,
                        'sample_weight_taken'       => $c->sample_weight_taken,
                        'sample_weight_reserved'    => $c->sample_weight_reserved,
                        'substance_identification'  => $c->confirmatory_substance_identification ?: ($c->seizure_name_drug ?: $c->seizure_name),
                        'detected_substances'       => is_array($c->detected_substances) ? json_encode($c->detected_substances) : $c->detected_substances,
                        'test_methods'              => $c->confirmatory_test_methods,
                        'specifications'            => $c->confirmatory_specifications,
                        'findings'                  => $c->laboratory_analytical_findings,
                        'has_saved'                 => true,
                        'created_at'                => $c->created_at ?? now(),
                        'updated_at'                => $c->updated_at ?? now(),
                    ]);
                }

            } else {
                // Single Suspect Case Migration
                $singleCaseId = DB::table('single_suspect_cases')->where('case_number', $c->case_number)->value('id');

                if (!$singleCaseId) {
                    $singleCaseId = DB::table('single_suspect_cases')->insertGetId([
                        'case_number'          => $c->case_number,
                        'laboratory_number'    => $c->laboratory_number,
                        'title'                => $c->title,
                        'unit_name'            => $c->unit_name,
                        'unit_id'              => $c->unit_id,
                        'user_id'              => $c->user_id,
                        'commanding_officer'   => $c->commanding_officer,
                        'submitting_officer'   => $c->submitting_officer,
                        'suspect_name'         => $c->suspect_name ?: 'Primary Suspect',
                        'suspect_alias'        => $c->suspect_alias,
                        'ghana_card'           => $c->ghana_card,
                        'gender'               => $c->gender ?? 'Male',
                        'dob'                  => $c->dob,
                        'age'                  => $c->age,
                        'height'               => $c->height,
                        'height_unit'          => $c->height_unit ?? 'CM',
                        'eye_color'            => $c->eye_color,
                        'nationality'          => $c->nationality ?? 'Ghanaian',
                        'pob'                  => $c->pob,
                        'phone'                => $c->phone,
                        'address'              => $c->address,
                        'marital_status'       => $c->marital_status,
                        'has_children'         => $c->has_children ?? false,
                        'children_count'       => $c->children_count ?? '0',
                        'spouse_info'          => $c->spouse_info,
                        'spouse_name'          => $c->spouse_name,
                        'spouse_phone'         => $c->spouse_phone,
                        'partner_name'         => $c->partner_name,
                        'partner_phone'        => $c->partner_phone,
                        'partner_info'         => $c->partner_info,
                        'dependents'           => $c->dependents,
                        'holding_facility'     => $c->holding_facility,
                        'operation_code'       => $c->operation_code,
                        'operation_code_name'  => $c->operation_code_name,
                        'raid_location'        => $c->raid_location,
                        'offense_description'  => $c->offense_description,
                        'arrest_date'          => $c->arrest_date,
                        'arrest_time'          => $c->arrest_time,
                        'incident_date'        => $c->incident_date,
                        'mugshot_front'        => $c->mugshot_front,
                        'mugshot_left'         => $c->mugshot_left,
                        'mugshot_right'        => $c->mugshot_right,
                        'priority'             => $c->priority ?? 'NORMAL',
                        'status'               => $c->status ?? 'Approved',
                        'hash_log'             => $c->hash_log,
                        'created_at'           => $c->created_at ?? now(),
                        'updated_at'           => $c->updated_at ?? now(),
                    ]);
                }

                // Migrate Exhibits for single-suspect case
                DB::table('exhibits')->insert([
                    'case_type'               => 'single',
                    'single_suspect_case_id'  => $singleCaseId,
                    'exhibit_number'          => $c->exhibit_number ?? 'EXH-01',
                    'exhibit_category'        => $c->exhibit_category ?? 'Drugs',
                    'commodity_name'          => $c->seizure_name_drug ?: ($c->seizure_name ?: $c->exhibits_summary),
                    'seizure_quantity'        => $c->seizure_quantity,
                    'seizure_packaging'       => $c->seizure_packaging,
                    'seizure_weight'          => $c->seizure_weight,
                    'seizure_unit'            => $c->seizure_unit,
                    'seizure_value'           => $c->seizure_value,
                    'seizure_concealment'      => $c->seizure_concealment,
                    'seizure_date'            => $c->seizure_date,
                    'seizure_time'            => $c->seizure_time,
                    'seizure_location'        => $c->seizure_location,
                    'seizure_officer'         => $c->seizure_officer,
                    'seizure_reason'          => $c->seizure_reason,
                    'veh_make'                => $c->veh_make,
                    'veh_model'               => $c->veh_model,
                    'veh_type'                => $c->veh_type,
                    'veh_year'                => $c->veh_year,
                    'veh_colour'              => $c->veh_colour,
                    'veh_reg_no'              => $c->veh_reg_no,
                    'veh_vin'                 => $c->veh_vin,
                    'veh_engine_no'           => $c->veh_engine_no,
                    'veh_country_reg'         => $c->veh_country_reg,
                    'veh_owner'               => $c->veh_owner,
                    'veh_driver'              => $c->veh_driver,
                    'wpn_model'               => $c->wpn_model,
                    'wpn_serial_no'           => $c->wpn_serial_no,
                    'wpn_calibre'             => $c->wpn_calibre,
                    'wpn_colour'              => $c->wpn_colour,
                    'wpn_size'                => $c->wpn_size,
                    'dev_model'               => $c->dev_model,
                    'dev_serial_no'           => $c->dev_serial_no,
                    'dev_imei1'               => $c->dev_imei1,
                    'dev_imei2'               => $c->dev_imei2,
                    'dev_mac_address'         => $c->dev_mac_address,
                    'evidence_photos'         => is_array($c->evidence_photos) ? json_encode($c->evidence_photos) : $c->evidence_photos,
                    'created_at'              => $c->created_at ?? now(),
                    'updated_at'              => $c->updated_at ?? now(),
                ]);

                // Migrate Lab Results for single-suspect case
                if (!empty($c->confirmatory_substance_identification) || !empty($c->presumptive_test_result)) {
                    DB::table('laboratory_examination_results')->insert([
                        'case_type'                 => 'single',
                        'single_suspect_case_id'    => $singleCaseId,
                        'laboratory_number'         => $c->laboratory_number,
                        'presumptive_result'        => $c->presumptive_test_result,
                        'confirmatory_result'       => $c->confirmatory_test_result ?? 'YES',
                        'sample_weight_description' => $c->sample_weight_description ?: ($c->seizure_name_drug ?: $c->seizure_name),
                        'sample_weight_units'       => $c->sample_weight_units ?: $c->seizure_quantity,
                        'sample_weight_gross'       => $c->sample_weight_gross ?: $c->seizure_weight,
                        'sample_weight_taken'       => $c->sample_weight_taken,
                        'sample_weight_reserved'    => $c->sample_weight_reserved,
                        'substance_identification'  => $c->confirmatory_substance_identification ?: ($c->seizure_name_drug ?: $c->seizure_name),
                        'detected_substances'       => is_array($c->detected_substances) ? json_encode($c->detected_substances) : $c->detected_substances,
                        'test_methods'              => $c->confirmatory_test_methods,
                        'specifications'            => $c->confirmatory_specifications,
                        'findings'                  => $c->laboratory_analytical_findings,
                        'has_saved'                 => true,
                        'created_at'                => $c->created_at ?? now(),
                        'updated_at'                => $c->updated_at ?? now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('laboratory_examination_results')->truncate();
        DB::table('exhibits')->truncate();
        DB::table('case_suspects')->truncate();
        DB::table('multiple_suspect_cases')->truncate();
        DB::table('single_suspect_cases')->truncate();
    }
};
