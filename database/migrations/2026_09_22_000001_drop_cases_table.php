<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        // 1. Perform safety backfill of any remaining records in cases table into relational tables
        $existingCases = DB::table('cases')->get();

        foreach ($existingCases as $c) {
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

                if (DB::table('case_suspects')->where('multiple_suspect_case_id', $multiCaseId)->count() === 0) {
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

                        DB::table('case_suspects')->insert([
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
                    }
                }
            } else {
                $singleCaseId = DB::table('single_suspect_cases')->where('case_number', $c->case_number)->value('id');
                if (!$singleCaseId) {
                    DB::table('single_suspect_cases')->insert([
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
            }
        }

        // 2. Drop foreign key constraint on associate_lab_examinations if present
        if (Schema::hasTable('associate_lab_examinations')) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE associate_lab_examinations DROP CONSTRAINT IF EXISTS associate_lab_examinations_case_id_foreign');
            } else {
                try {
                    Schema::table('associate_lab_examinations', function (Blueprint $table) {
                        $table->dropForeign(['case_id']);
                    });
                } catch (\Throwable $e) {
                    // Ignore if constraint already dropped
                }
            }
        }

        // 3. Safely drop table cases
        Schema::dropIfExists('cases');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
