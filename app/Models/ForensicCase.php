<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ForensicCase extends Model
{
    use HasFactory;

    /**
     * Disable direct DB table usage since table 'cases' has been dropped.
     */
    protected $table = null;

    protected $guarded = [];

    public $underlyingRecord = null;

    protected $casts = [
        'docket_data' => 'array',
        'evidence_photos' => 'array',
        'detected_substances' => 'array',
        'has_children' => 'boolean',
        'has_seizures' => 'boolean',
    ];

    /**
     * Helper to construct a unified ForensicCase object from SingleSuspectCase or MultipleSuspectCase
     */
    public static function wrapRecord($record): ?self
    {
        if (!$record) return null;

        if ($record instanceof SingleSuspectCase && $record->status === 'Promoted') {
            $promotedMulti = MultipleSuspectCase::where('case_number', $record->case_number)->first();
            if ($promotedMulti) {
                return self::wrapRecord($promotedMulti);
            }
        }

        $instance = new self();
        $instance->underlyingRecord = $record;
        $isMulti = $record instanceof MultipleSuspectCase || isset($record->total_suspects_count);

        $instance->id = $record->id;
        $instance->case_number = $record->case_number;
        $rawLabNo = $record->laboratory_number ?? null;
        if (empty($rawLabNo) || (!str_starts_with(trim($rawLabNo), 'Lab No.'))) {
            $cDate = $record->created_at ?? now();
            $m = is_object($cDate) ? $cDate->format('m') : date('m', strtotime((string)$cDate));
            $y = is_object($cDate) ? $cDate->format('Y') : date('Y', strtotime((string)$cDate));
            $randNum = rand(10, 99);
            $normalizedLabNo = "Lab No. {$randNum}/FS{$m}/{$y}";
            $instance->laboratory_number = $normalizedLabNo;
            try {
                $record->laboratory_number = $normalizedLabNo;
                $record->save();
            } catch (\Throwable $e) {}
        } else {
            $instance->laboratory_number = $rawLabNo;
        }
        $instance->title = $record->title;
        $instance->unit_name = $record->unit_name;
        $instance->unit_id = $record->unit_id;
        $instance->user_id = $record->user_id;
        $instance->commanding_officer = $record->commanding_officer ?? null;
        $instance->submitting_officer = $record->submitting_officer ?? null;
        $instance->operation_code = $record->operation_code ?? null;
        $instance->operation_code_name = $record->operation_code_name ?? null;
        $instance->raid_location = $record->raid_location ?? null;
        $instance->offense_description = $record->offense_description ?? null;
        $instance->incident_date = $record->incident_date ?? null;
        $instance->priority = $record->priority ?? 'NORMAL';
        $instance->status = $record->status ?? 'Approved';
        $instance->case_status = $record->case_status ?? 'Under Investigation';
        $instance->controller_user_id = $record->controller_user_id ?? ($record->user_id ?? null);
        $instance->hash_log = $record->hash_log ?? null;
        $instance->created_at = $record->created_at;
        $instance->updated_at = $record->updated_at;

        // Set user & unit relations if loaded or available
        $instance->setRelation('user', $record->user ?? null);
        $instance->setRelation('unit', $record->unit ?? null);

        // Fetch Exhibits
        $exhibits = isset($record->exhibits) ? $record->exhibits : (
            $isMulti 
                ? Exhibit::where('multiple_suspect_case_id', $record->id)->get()
                : Exhibit::where('single_suspect_case_id', $record->id)->get()
        );
        $firstExhibit = $exhibits->first();

        $recDocket = is_array($record->docket_data) ? $record->docket_data : (json_decode($record->docket_data ?? '[]', true) ?: []);
        $instance->docket_data = $recDocket;

        $exCatLower = strtolower($firstExhibit->exhibit_category ?? ($record->exhibit_category ?? ''));
        $isDrugCat = false;
        $drugKws = ['drug', 'narcotic', 'substance', 'cannabis', 'cocaine', 'heroin', 'meth', 'tramadol', 'weed', 'hemp', 'hashish', 'ephedrine', 'precursor', 'opioid', 'fentanyl'];
        foreach ($drugKws as $dkw) {
            if (str_contains($exCatLower, $dkw)) { $isDrugCat = true; break; }
        }

        $instance->exhibit_category = $firstExhibit->exhibit_category ?? ($record->exhibit_category ?? null);
        $instance->seizure_name = $firstExhibit->commodity_name ?? ($record->seizure_name ?? null);
        $instance->seizure_name_drug = ($firstExhibit && $isDrugCat) ? $firstExhibit->commodity_name : ($record->seizure_name_drug ?? ($isDrugCat ? ($record->seizure_name ?? null) : null));

        $instance->seizure_quantity = $firstExhibit->seizure_quantity ?? null;
        $instance->seizure_packaging = $firstExhibit->seizure_packaging ?? null;
        $instance->seizure_weight = $firstExhibit->seizure_weight ?? null;
        $instance->seizure_unit = $firstExhibit->seizure_unit ?? null;
        $instance->seizure_value = $firstExhibit->seizure_value ?? null;
        $instance->seizure_concealment = $firstExhibit->seizure_concealment ?? ($recDocket['seizures'][0]['seizure_concealment'] ?? null);
        $instance->seizure_date = $firstExhibit->seizure_date ?? ($record->incident_date ?? ($recDocket['seizures'][0]['seizure_date'] ?? null));
        $instance->seizure_time = $firstExhibit->seizure_time ?? ($recDocket['seizures'][0]['seizure_time'] ?? null);
        $instance->seizure_location = $firstExhibit->seizure_location ?? ($record->raid_location ?? ($recDocket['seizures'][0]['seizure_location'] ?? null));
        $instance->seizure_officer = $firstExhibit->seizure_officer ?? ($record->seizure_officer ?? ($recDocket['seizure_officer'] ?? ($recDocket['seizures'][0]['seizure_officer'] ?? null)));
        $instance->seizure_reason = $firstExhibit->seizure_reason ?? ($record->seizure_reason ?? ($recDocket['seizure_reason'] ?? ($recDocket['seizures'][0]['seizure_reason'] ?? null)));
        $instance->veh_make = $firstExhibit->veh_make ?? null;
        $instance->veh_model = $firstExhibit->veh_model ?? null;
        $instance->wpn_model = $firstExhibit->wpn_model ?? null;
        $instance->dev_model = $firstExhibit->dev_model ?? null;
        // Always ensure evidence_photos is an array (never a JSON string)
        $recDocket = is_array($record->docket_data) ? $record->docket_data : (json_decode($record->docket_data ?? '[]', true) ?: []);
        $rawPhotos = ($firstExhibit && !empty($firstExhibit->evidence_photos))
            ? $firstExhibit->evidence_photos
            : ($record->evidence_photos ?? ($recDocket['evidence_photos'] ?? []));
        $instance->evidence_photos = is_array($rawPhotos) ? $rawPhotos : (json_decode($rawPhotos ?? '[]', true) ?: []);

        // Fetch Suspects
        if ($isMulti) {
            $suspects = isset($record->suspects) ? $record->suspects : CaseSuspect::where('multiple_suspect_case_id', $record->id)->orderBy('suspect_number')->get();
            $primarySuspect = $suspects->where('role', 'Primary Suspect')->first() ?: $suspects->first();
        } else {
            $primarySuspect = $record;
            $suspects = collect([$record]);
        }

        if ($primarySuspect) {
            $instance->suspect_name = $primarySuspect->suspect_name ?? null;
            $instance->suspect_alias = $primarySuspect->suspect_alias ?? null;
            $instance->ghana_card = $primarySuspect->ghana_card ?? null;
            $instance->gender = $primarySuspect->gender ?? null;
            $instance->dob = $primarySuspect->dob ?? null;
            $instance->age = $primarySuspect->age ?? null;
            $instance->height = $primarySuspect->height ?? null;
            $instance->height_unit = $primarySuspect->height_unit ?? 'CM';
            $instance->eye_color = $primarySuspect->eye_color ?? null;
            $instance->nationality = $primarySuspect->nationality ?? 'Ghanaian';
            $instance->pob = $primarySuspect->pob ?? null;
            $instance->phone = $primarySuspect->phone ?? null;
            $instance->address = $primarySuspect->address ?? null;
            $instance->marital_status = $primarySuspect->marital_status ?? null;
            $instance->has_children = $primarySuspect->has_children ?? false;
            $instance->children_count = $primarySuspect->children_count ?? '0';
            $instance->spouse_info = $primarySuspect->spouse_info ?? null;
            $instance->spouse_name = $primarySuspect->spouse_name ?? null;
            $instance->spouse_phone = $primarySuspect->spouse_phone ?? null;
            $instance->partner_name = $primarySuspect->partner_name ?? null;
            $instance->partner_phone = $primarySuspect->partner_phone ?? null;
            $instance->partner_info = $primarySuspect->partner_info ?? null;
            $instance->dependents = $primarySuspect->dependents ?? null;
            $instance->holding_facility = $primarySuspect->holding_facility ?? null;
            $instance->arrest_date = $primarySuspect->arrest_date ?? null;
            $instance->arrest_time = $primarySuspect->arrest_time ?? null;
            $instance->mugshot_front = $primarySuspect->mugshot_front ?? null;
            $instance->mugshot_left = $primarySuspect->mugshot_left ?? null;
            $instance->mugshot_right = $primarySuspect->mugshot_right ?? null;
        }

        // Fetch Laboratory Results
        $labResults = isset($record->labResults) ? $record->labResults : (
            $isMulti
                ? LaboratoryExaminationResult::where('multiple_suspect_case_id', $record->id)->get()
                : LaboratoryExaminationResult::where('single_suspect_case_id', $record->id)->get()
        );
        $primaryLab = $labResults->first();

        if ($primaryLab) {
            $instance->presumptive_test_result = $primaryLab->presumptive_result ?? null;
            $confRes = $primaryLab->confirmatory_result ?? null;
            $instance->confirmatory_test_result = (!empty($confRes) && strtoupper(trim((string)$confRes)) === 'NO') ? 'NO' : 'YES';
            $instance->sample_weight_description = $primaryLab->sample_weight_description ?? null;
            $instance->sample_weight_units = $primaryLab->sample_weight_units ?? null;
            $instance->sample_weight_gross = $primaryLab->sample_weight_gross ?? null;
            $instance->sample_weight_taken = $primaryLab->sample_weight_taken ?? null;
            $instance->sample_weight_reserved = $primaryLab->sample_weight_reserved ?? null;
            $instance->confirmatory_substance_identification = $primaryLab->substance_identification ?? null;
            $instance->detected_substances = $primaryLab->detected_substances ?? [];
            $instance->confirmatory_test_methods = $primaryLab->test_methods ?? null;
            $instance->confirmatory_specifications = $primaryLab->specifications ?? null;
            $instance->confirmatory_remark = $primaryLab->remark ?? null;
            $instance->remark = $primaryLab->remark ?? null;
            $instance->laboratory_analytical_findings = $primaryLab->findings ?? null;
        } else {
            $instance->confirmatory_test_result = null;
        }

        // Build docket_data array dynamically
        $suspectsArray = [];
        foreach ($suspects as $sIdx => $s) {
            $assocId = $s->associate_id ?? ($sIdx > 0 ? ('ASC-' . str_pad($sIdx, 3, '0', STR_PAD_LEFT)) : null);
            $sRole = strtolower(trim($s->role ?? ''));
            $isPrimaryRole = $sRole === 'primary suspect' || $sRole === 'primary' || ($sIdx === 0 && empty($s->role));
            $isAssocSuspect = !$isPrimaryRole;

            $suspectsArray[] = [
                'id'               => $s->id ?? null,
                'associate_id'     => $assocId,
                'suspect_id'       => $assocId,
                'suspect_number'   => $sIdx + 1,
                'suspect_name'     => $s->suspect_name ?? null,
                'suspect_alias'    => $s->suspect_alias ?? 'N/A',
                'ghana_card'       => $s->ghana_card ?? null,
                'gender'           => $s->gender ?? 'Male',
                'dob'              => $s->dob ?? null,
                'age'              => $s->age ?? null,
                'height'           => $s->height ?? null,
                'eye_color'        => $s->eye_color ?? null,
                'nationality'      => $s->nationality ?? 'Ghanaian',
                'pob'              => $s->pob ?? null,
                'phone'            => $s->phone ?? null,
                'address'          => $s->address ?? null,
                'marital_status'   => $s->marital_status ?? null,
                'has_children'     => !empty($s->has_children),
                'children_count'   => $s->children_count ?? '0',
                'spouse_info'      => $s->spouse_info ?? null,
                'holding_facility' => $s->holding_facility ?? null,
                'offense'          => $s->offense_description ?? $instance->offense_description,
                'date_of_arrest'   => $s->arrest_date ?? null,
                'time_of_arrest'   => $s->arrest_time ?? null,
                'association_role' => $s->role ?? ($sIdx === 0 ? 'Primary Suspect' : 'Associate Suspect'),
                'role'             => $s->role ?? ($sIdx === 0 ? 'Primary Suspect' : 'Associate Suspect'),
                'is_associate'     => $isAssocSuspect,
                'mugshot_front'    => $s->mugshot_front ?? null,
                'mugshot_left'     => $s->mugshot_left ?? null,
                'mugshot_right'    => $s->mugshot_right ?? null,
            ];
        }

        // Build a comma-joined string of all suspect names for joint exhibit labels
        $allSuspectNames = $suspectsArray ? implode(', ', array_filter(array_column($suspectsArray, 'suspect_name'))) : ($instance->suspect_name ?? '');

        $seizuresArray = [];
        foreach ($exhibits as $exIdx => $ex) {
            // Determine if this exhibit's suspect is an associate-role suspect
            $exSuspect = $ex->suspect;
            $exSuspectRole = strtolower(trim($exSuspect->role ?? ''));
            $isAssoc = $exSuspect ? ($exSuspect->suspect_number > 1 || ($exSuspectRole !== 'primary suspect' && $exSuspectRole !== 'primary')) : false;

            // For multi-suspect cases, null suspect_id means jointly owned by all suspects
            $associatedPerson = $exSuspect
                ? $exSuspect->suspect_name
                : ($isMulti ? $allSuspectNames : ($instance->suspect_name ?? 'Primary Suspect'));

            $seizuresArray[] = [
                'id'                 => $ex->id ?? ($recDocket['seizures'][$exIdx]['id'] ?? null),
                'category'           => $ex->exhibit_category,
                'exhibit_type'       => $ex->exhibit_type ?? ($recDocket['seizures'][$exIdx]['exhibit_type'] ?? null),
                'drug_classification'=> $ex->drug_classification ?? ($recDocket['seizures'][$exIdx]['drug_classification'] ?? null),
                'seizure_name'       => $ex->commodity_name,
                'seizure_name_drug'  => str_contains(strtolower($ex->exhibit_category ?? ''), 'drug') ? $ex->commodity_name : null,
                'exhibit_number'     => $ex->exhibit_number ?? ('EXH-' . sprintf('%02d', $exIdx + 1)),
                'seizure_quantity'   => $ex->seizure_quantity,
                'seizure_packaging'  => $ex->seizure_packaging,
                'seizure_weight'     => $ex->seizure_weight,
                'seizure_unit'       => $ex->seizure_unit,
                'seizure_description'=> $recDocket['seizures'][$exIdx]['seizure_description'] ?? ($recDocket['seizures'][$exIdx]['description'] ?? null),
                'seizure_value'      => $ex->seizure_value,
                'seizure_concealment'=> $ex->seizure_concealment ?? ($recDocket['seizures'][$exIdx]['seizure_concealment'] ?? null),
                'seizure_date'       => $ex->seizure_date ?? ($recDocket['seizures'][$exIdx]['seizure_date'] ?? null),
                'seizure_time'       => $ex->seizure_time ?? ($recDocket['seizures'][$exIdx]['seizure_time'] ?? null),
                'seizure_location'   => $ex->seizure_location ?? ($recDocket['seizures'][$exIdx]['seizure_location'] ?? null),
                'seizure_officer'    => $ex->seizure_officer ?? ($recDocket['seizures'][$exIdx]['seizure_officer'] ?? ($record->seizure_officer ?? null)),
                'seizure_reason'     => $ex->seizure_reason ?? ($recDocket['seizures'][$exIdx]['seizure_reason'] ?? ($record->seizure_reason ?? null)),
                'associated_person'  => $associatedPerson,
                'associate_id'       => $ex->suspect ? $ex->suspect->associate_id : null,
                'is_associate'       => $isAssoc,
                'ammunition_details' => is_array($ex->ammunition_details) ? $ex->ammunition_details : (json_decode($ex->ammunition_details ?? '[]', true) ?: ($recDocket['seizures'][$exIdx]['ammunition_details'] ?? null)),
                'has_saved_lab'      => $ex->labResult ? $ex->labResult->has_saved : false,
                'presumptive_result' => $ex->labResult ? $ex->labResult->presumptive_result : null,
                'confirmatory_result' => $ex->labResult ? $ex->labResult->confirmatory_result : null,
                'substance_identification' => $ex->labResult ? $ex->labResult->substance_identification : null,
                'sample_taken'        => $ex->labResult ? $ex->labResult->sample_weight_taken : null,
                'sample_weight_taken' => $ex->labResult ? $ex->labResult->sample_weight_taken : null,
                'sample_reserved'     => $ex->labResult ? $ex->labResult->sample_weight_reserved : null,
                'sample_weight_reserved' => $ex->labResult ? $ex->labResult->sample_weight_reserved : null,
                'evidence_photos'    => (is_array($ex->evidence_photos) && !empty($ex->evidence_photos)) 
                    ? $ex->evidence_photos 
                    : ((!empty($ex->evidence_photos) && is_string($ex->evidence_photos) && json_decode($ex->evidence_photos, true))
                        ? json_decode($ex->evidence_photos, true)
                        : ($exIdx === 0 ? ($instance->evidence_photos ?? []) : [])),
            ];
        }

        $labExaminationsList = [];
        foreach ($labResults as $lr) {
            $labExaminationsList[] = [
                'suspect_name'             => $lr->suspect ? $lr->suspect->suspect_name : ($instance->suspect_name ?? 'Primary Suspect'),
                'laboratory_number'        => $lr->laboratory_number,
                'exam_reference'           => (!empty($lr->exam_reference) && str_starts_with(trim($lr->exam_reference), 'GSA-FM-T')) ? $lr->exam_reference : ('GSA-FM-T' . rand(10, 99) . '-F'),
                'analyst'                  => $lr->analyst,
                'date_received'            => $lr->date_received,
                'date_started'             => $lr->date_started,
                'date_completed'           => $lr->date_completed,
                'presumptive_result'       => $lr->presumptive_result,
                'confirmatory_result'      => $lr->confirmatory_result,
                'sample_weight_description'=> $lr->sample_weight_description,
                'sample_weight_units'      => $lr->sample_weight_units,
                'sample_weight_gross'      => $lr->sample_weight_gross,
                'sample_weight_taken'      => $lr->sample_weight_taken,
                'sample_weight_reserved'   => $lr->sample_weight_reserved,
                'substance_identification' => $lr->substance_identification,
                'detected_substances'      => $lr->detected_substances,
                'test_methods'             => $lr->test_methods,
                'specifications'           => $lr->specifications,
                'remark'                   => $lr->remark,
                'findings'                 => $lr->findings,
                'has_saved'                => $lr->has_saved,
            ];
        }

        $assocLabsMap = [];
        $assocLabs = AssociateLabExamination::where('case_id', $record->id)->get();
        foreach ($assocLabs as $al) {
            $assocLabsMap[$al->suspect_name] = [
                'suspect_name'              => $al->suspect_name,
                'laboratory_number'         => $al->laboratory_number,
                'exam_reference'            => (!empty($al->exam_reference) && str_starts_with(trim($al->exam_reference), 'GSA-FM-T')) ? $al->exam_reference : ('GSA-FM-T' . rand(10, 99) . '-F'),
                'exhibit_number'            => $al->exhibit_number,
                'analyst'                   => $al->analyst,
                'date_received'             => $al->date_received,
                'date_started'              => $al->date_started,
                'date_completed'            => $al->date_completed,
                'presumptive_result'        => $al->presumptive_result,
                'confirmatory_result'       => $al->confirmatory_result,
                'sample_weight'             => [
                    'description'      => $al->sample_weight_description,
                    'units_packaging'  => $al->sample_weight_units,
                    'gross_weight'     => $al->sample_weight_gross,
                    'sample_taken'     => $al->sample_weight_taken,
                    'reserved_samples' => $al->sample_weight_reserved,
                ],
                'sample_weight_description' => $al->sample_weight_description,
                'sample_weight_units'       => $al->sample_weight_units,
                'sample_weight_gross'       => $al->sample_weight_gross,
                'sample_weight_taken'       => $al->sample_weight_taken,
                'sample_weight_reserved'    => $al->sample_weight_reserved,
                'substance_identification'  => $al->substance_identification,
                'detected_substances'       => $al->detected_substances,
                'test_methods'              => $al->test_methods,
                'specifications'            => $al->specifications,
                'remark'                    => $al->remark,
                'findings'                  => $al->findings,
                'has_saved'                 => $al->has_saved,
            ];
        }

        // Determine primary suspect name for lab examination header
        $primarySuspectLabel = $primarySuspect ? $primarySuspect->suspect_name : ($instance->suspect_name ?? null);

        $hasSavedLabWrap = ($primaryLab && $primaryLab->has_saved)
            || count(array_filter($labExaminationsList, fn($l) => !empty($l['has_saved']))) > 0
            || count(array_filter($assocLabsMap, fn($l) => !empty($l['has_saved']))) > 0
            || count(array_filter($seizuresArray, fn($s) => !empty($s['has_saved_lab']))) > 0;
        
        $reqUnitsWrap = $instance->getWorkflowUnits();
        $unitProgressWrap = [];
        foreach ($reqUnitsWrap as $rU) {
            if ($rU === 'Lab Analysis') {
                $unitProgressWrap[$rU] = [
                    'status'     => $hasSavedLabWrap ? 'COMPLETED' : 'PENDING',
                    'officer'    => $hasSavedLabWrap && $primaryLab ? $primaryLab->analyst : null,
                    'updated_at' => $hasSavedLabWrap && $primaryLab ? (is_object($primaryLab->updated_at) ? $primaryLab->updated_at->toDateTimeString() : (string)$primaryLab->updated_at) : null,
                    'notes'      => $hasSavedLabWrap ? 'Submitted via Laboratory Analysis & Case Registration Workspace' : null,
                ];
            } elseif ($rU === 'Crime Scene Support') {
                $unitProgressWrap[$rU] = [
                    'status'     => 'COMPLETED',
                    'officer'    => $instance->commanding_officer ?? ($instance->submitting_officer ?? 'Crime Scene Officer'),
                    'updated_at' => is_object($instance->created_at) ? $instance->created_at->toDateTimeString() : (string)$instance->created_at,
                    'notes'      => 'Submitted via Crime Scene Support & Field Operations Workspace',
                ];
            } else {
                $unitProgressWrap[$rU] = [
                    'status'     => 'PENDING',
                    'officer'    => null,
                    'updated_at' => null,
                    'notes'      => null,
                ];
            }
        }

        if (is_array($recDocket) && !empty($recDocket['unit_progress'])) {
            foreach ($recDocket['unit_progress'] as $uK => $uV) {
                if (in_array($uK, $reqUnitsWrap)) {
                    if ($uK === 'Lab Analysis' && !$hasSavedLabWrap) {
                        $unitProgressWrap[$uK] = [
                            'status'     => 'PENDING',
                            'officer'    => null,
                            'updated_at' => null,
                            'notes'      => null,
                        ];
                    } else {
                        $unitProgressWrap[$uK] = $uV;
                    }
                }
            }
        }

        $instance->docket_data = [
            'suspects' => $suspectsArray,
            'seizures' => $seizuresArray,
            'evidence_photos' => is_array($instance->evidence_photos) ? $instance->evidence_photos : [],
            'lab_examination' => $primaryLab ? [
                'has_saved'                => $primaryLab->has_saved,
                'exhibit_owner_name'       => $primarySuspectLabel,
                'analyst'                  => $primaryLab->analyst,
                'presumptive_result'       => $primaryLab->presumptive_result,
                'confirmatory_result'      => (!empty($primaryLab->confirmatory_result) && strtoupper(trim((string)$primaryLab->confirmatory_result)) === 'NO') ? 'NO' : 'YES',
                'substance_identification' => $primaryLab->substance_identification,
                'sample_weight_gross'      => $primaryLab->sample_weight_gross,
                'sample_weight_description'=> $primaryLab->sample_weight_description,
                'sample_weight_units'      => $primaryLab->sample_weight_units,
                'sample_weight_taken'      => $primaryLab->sample_weight_taken,
                'sample_weight_reserved'   => $primaryLab->sample_weight_reserved,
                'findings'                 => $primaryLab->findings,
            ] : [
                'has_saved'                => false,
                'exhibit_owner_name'       => $primarySuspectLabel,
                'confirmatory_result'      => 'YES',
            ],
            'lab_examinations_list' => $labExaminationsList,
            'associate_lab_examinations' => $assocLabsMap,
            'unit_progress' => $unitProgressWrap,
        ];


        return $instance;
    }

    /**
     * Get the User model who currently controls this case's status.
     */
    public function getControllerUser(): ?User
    {
        $ctrlId = $this->controller_user_id ?? $this->user_id;
        if ($ctrlId) {
            $user = User::find($ctrlId);
            if ($user) return $user;
        }
        if (!empty($this->unit_name)) {
            $head = User::where('unit', 'LIKE', '%' . $this->unit_name . '%')->where('is_head', true)->first();
            if ($head) return $head;
        }
        return User::where('is_head', true)->first();
    }

    /**
     * Check if a given user is the current Case Status Controller.
     */
    public function isController($user): bool
    {
        if (!$user) return false;
        $ctrlUser = $this->getControllerUser();
        return $ctrlUser && (int)$ctrlUser->id === (int)$user->id;
    }

    /**
     * Get active pending handover for this case if any.
     */
    public function getActiveHandover(): ?CaseHandover
    {
        if (empty($this->case_number)) return null;
        return CaseHandover::where('case_number', $this->case_number)
            ->where('status', 'Pending Acknowledgement')
            ->latest()
            ->first();
    }

    /**
     * Get audit logs for this case.
     */
    public function getAuditLogs()
    {
        if (empty($this->case_number)) return collect();
        return AuditLog::where('case_number', $this->case_number)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a fresh instance of the model.
     */
    public function fresh($with = [])
    {
        if ($this->underlyingRecord) {
            $refreshed = $this->underlyingRecord->fresh($with);
            if ($refreshed) {
                return self::wrapRecord($refreshed);
            }
        }
        if (!empty($this->case_number)) {
            $byNum = self::findByRef($this->case_number);
            if ($byNum) return $byNum;
        }
        return self::findByRef($this->id);
    }

    /**
     * Reload the current model instance with fresh data from the database.
     */
    public function refresh()
    {
        if ($this->underlyingRecord) {
            $this->underlyingRecord->refresh();
            $refreshed = self::wrapRecord($this->underlyingRecord);
            if ($refreshed) {
                foreach (get_object_vars($refreshed) as $key => $value) {
                    $this->$key = $value;
                }
            }
        }
        return $this;
    }

    /**
     * Save changes back to the underlying record.
     */
    public function save(array $options = [])
    {
        if (!$this->underlyingRecord) {
            return false;
        }

        if ($this->underlyingRecord instanceof SingleSuspectCase) {
            $fillable = (new SingleSuspectCase)->getFillable();
            $data = [];
            foreach ($fillable as $col) {
                if (array_key_exists($col, $this->attributes)) {
                    $data[$col] = $this->attributes[$col];
                } elseif (isset($this->$col) || property_exists($this, $col)) {
                    $data[$col] = $this->$col;
                }
            }
            if (!empty($data)) {
                $this->underlyingRecord->update($data);
            }

            $ex = $this->underlyingRecord->exhibits()->first();
            if ($ex) {
                $exData = [];
                if (isset($this->attributes['exhibit_category']) || isset($this->exhibit_category)) $exData['exhibit_category'] = $this->attributes['exhibit_category'] ?? $this->exhibit_category;
                if (isset($this->attributes['seizure_name']) || isset($this->seizure_name)) $exData['commodity_name'] = $this->attributes['seizure_name'] ?? $this->seizure_name;
                if (isset($this->attributes['seizure_name_drug']) || isset($this->seizure_name_drug)) $exData['commodity_name'] = $this->attributes['seizure_name_drug'] ?? $this->seizure_name_drug;
                if (isset($this->attributes['seizure_quantity']) || isset($this->seizure_quantity)) $exData['seizure_quantity'] = $this->attributes['seizure_quantity'] ?? $this->seizure_quantity;
                if (isset($this->attributes['seizure_weight']) || isset($this->seizure_weight)) $exData['seizure_weight'] = $this->attributes['seizure_weight'] ?? $this->seizure_weight;
                if (isset($this->attributes['seizure_unit']) || isset($this->seizure_unit)) $exData['seizure_unit'] = $this->attributes['seizure_unit'] ?? $this->seizure_unit;
                if (array_key_exists('evidence_photos', $this->attributes) || isset($this->evidence_photos) || property_exists($this, 'evidence_photos')) {
                    $exData['evidence_photos'] = $this->attributes['evidence_photos'] ?? ($this->evidence_photos ?? []);
                }
                if (!empty($exData)) $ex->update($exData);
            }

            $lab = $this->underlyingRecord->labResults()->first();
            if ($lab) {
                $labData = [];
                if (isset($this->attributes['presumptive_test_result'])) $labData['presumptive_result'] = $this->attributes['presumptive_test_result'];
                if (isset($this->attributes['confirmatory_test_result'])) $labData['confirmatory_result'] = $this->attributes['confirmatory_test_result'];
                if (isset($this->attributes['sample_weight_description'])) $labData['sample_weight_description'] = $this->attributes['sample_weight_description'];
                if (isset($this->attributes['sample_weight_gross'])) $labData['sample_weight_gross'] = $this->attributes['sample_weight_gross'];
                if (isset($this->attributes['confirmatory_substance_identification'])) $labData['substance_identification'] = $this->attributes['confirmatory_substance_identification'];
                if (isset($this->attributes['detected_substances'])) $labData['detected_substances'] = $this->attributes['detected_substances'];
                if (!empty($labData)) {
                    $labData['has_saved'] = true;
                    $lab->update($labData);
                }
            }
        } elseif ($this->underlyingRecord instanceof MultipleSuspectCase) {
            $fillable = (new MultipleSuspectCase)->getFillable();
            $data = [];
            foreach ($fillable as $col) {
                if (array_key_exists($col, $this->attributes)) {
                    $data[$col] = $this->attributes[$col];
                } elseif (isset($this->$col) || property_exists($this, $col)) {
                    $data[$col] = $this->$col;
                }
            }
            if (!empty($data)) {
                $this->underlyingRecord->update($data);
            }

            $primarySuspect = $this->underlyingRecord->suspects()->where('role', 'Primary Suspect')->first() ?: $this->underlyingRecord->suspects()->first();
            if ($primarySuspect) {
                $spFillable = (new CaseSuspect)->getFillable();
                $spData = [];
                foreach ($spFillable as $col) {
                    if (array_key_exists($col, $this->attributes)) {
                        $spData[$col] = $this->attributes[$col];
                    } elseif (isset($this->$col) || property_exists($this, $col)) {
                        $spData[$col] = $this->$col;
                    }
                }
                if (!empty($spData)) {
                    $primarySuspect->update($spData);
                }
            }

            $ex = $this->underlyingRecord->exhibits()->first();
            if ($ex) {
                $exData = [];
                if (array_key_exists('evidence_photos', $this->attributes) || isset($this->evidence_photos) || property_exists($this, 'evidence_photos')) {
                    $exData['evidence_photos'] = $this->attributes['evidence_photos'] ?? ($this->evidence_photos ?? []);
                }
                if (!empty($exData)) {
                    $ex->update($exData);
                }
            }
        }

        return true;
    }

    /**
     * Create a new single or multiple suspect case record depending on payload.
     */
    public static function create(array $attributes = [])
    {
        $docketData = is_array($attributes['docket_data'] ?? null)
            ? $attributes['docket_data']
            : (json_decode($attributes['docket_data'] ?? '{}', true) ?: []);

        $suspects = $docketData['suspects'] ?? [];
        $isMulti = count($suspects) > 1;
        $caseRef = $attributes['case_number'] ?? ('NFC-' . date('Y') . '-' . rand(1000, 9999));

        if ($isMulti) {
            $multi = MultipleSuspectCase::create([
                'case_number'          => $caseRef,
                'laboratory_number'    => $attributes['laboratory_number'] ?? null,
                'title'                => $attributes['title'] ?? 'Multiple Suspect Docket File',
                'unit_name'            => $attributes['unit_name'] ?? 'Crime Scene Support',
                'unit_id'              => $attributes['unit_id'] ?? null,
                'user_id'              => $attributes['user_id'] ?? null,
                'commanding_officer'   => $attributes['commanding_officer'] ?? null,
                'submitting_officer'   => $attributes['submitting_officer'] ?? null,
                'operation_code'       => $attributes['operation_code'] ?? null,
                'operation_code_name'  => $attributes['operation_code_name'] ?? null,
                'raid_location'        => $attributes['raid_location'] ?? ($attributes['location'] ?? null),
                'offense_description'  => $attributes['offense_description'] ?? ($attributes['description'] ?? null),
                'incident_date'        => $attributes['incident_date'] ?? now(),
                'docket_summary'       => $attributes['exhibits_summary'] ?? null,
                'total_suspects_count' => count($suspects),
                'total_exhibits_count' => count($docketData['seizures'] ?? []),
                'priority'             => $attributes['priority'] ?? 'HIGH',
                'status'               => $attributes['status'] ?? 'Approved',
                'case_status'          => $attributes['case_status'] ?? 'Under Investigation',
                'controller_user_id'   => $attributes['controller_user_id'] ?? ($attributes['user_id'] ?? auth()->id()),
                'hash_log'             => $attributes['hash_log'] ?? ('HASH-' . strtoupper(substr(md5(uniqid()), 0, 8)) . '-VERIFIED'),
            ]);

            $createdSuspects = [];
            foreach ($suspects as $sIdx => $s) {
                $cs = CaseSuspect::create([
                    'multiple_suspect_case_id' => $multi->id,
                    'suspect_number'           => $sIdx + 1,
                    'role'                     => $s['association_role'] ?? ($s['role'] ?? ($sIdx === 0 ? 'Primary Suspect' : 'Associate Suspect')),
                    'associate_id'             => $s['associate_id'] ?? ($s['suspect_id'] ?? ($sIdx > 0 ? ('ASC-' . str_pad($sIdx, 3, '0', STR_PAD_LEFT)) : null)),
                    'suspect_name'             => $s['suspect_name'] ?? ($attributes['suspect_name'] ?? 'Unnamed Suspect'),
                    'suspect_alias'            => $s['suspect_alias'] ?? null,
                    'ghana_card'               => $s['ghana_card'] ?? null,
                    'gender'                   => $s['gender'] ?? ($attributes['gender'] ?? 'Male'),
                    'dob'                      => $s['dob'] ?? null,
                    'age'                      => isset($s['age']) && $s['age'] !== '' ? (int)$s['age'] : null,
                    'height'                   => $s['height'] ?? null,
                    'eye_color'                => $s['eye_color'] ?? null,
                    'nationality'              => $s['nationality'] ?? ($attributes['nationality'] ?? 'Ghanaian'),
                    'phone'                    => $s['phone'] ?? null,
                    'address'                  => $s['address'] ?? null,
                    'holding_facility'         => $s['holding_facility'] ?? ($s['facility'] ?? ($attributes['holding_facility'] ?? null)),
                    'raid_location'            => $s['raid_location'] ?? ($s['location'] ?? ($attributes['raid_location'] ?? null)),
                    'offense_description'      => $s['offense'] ?? ($s['offense_description'] ?? ($attributes['offense_description'] ?? null)),
                    'arrest_date'              => $s['date_of_arrest'] ?? ($s['arrest_date'] ?? ($attributes['arrest_date'] ?? null)),
                    'arrest_time'              => $s['time_of_arrest'] ?? ($s['arrest_time'] ?? ($attributes['arrest_time'] ?? null)),
                    'mugshot_front'            => $s['mugshot_front'] ?? ($sIdx === 0 ? ($attributes['mugshot_front'] ?? null) : null),
                    'mugshot_left'             => $s['mugshot_left'] ?? ($sIdx === 0 ? ($attributes['mugshot_left'] ?? null) : null),
                    'mugshot_right'            => $s['mugshot_right'] ?? ($sIdx === 0 ? ($attributes['mugshot_right'] ?? null) : null),
                ]);
                $createdSuspects[] = $cs;
            }

            $seizures = $docketData['seizures'] ?? [];
            $hasExplicitTopLevelExhibit = !empty($attributes['seizure_name_drug']) || !empty($attributes['seizure_name']) || !empty($attributes['commodity_name']);
            if (empty($seizures)) {
                if ($hasExplicitTopLevelExhibit) {
                    $ex = Exhibit::create([
                        'case_type'               => 'MULTIPLE',
                        'multiple_suspect_case_id'=> $multi->id,
                        'suspect_id'              => $createdSuspects[0]->id ?? null,
                        'exhibit_number'          => 1,
                        'exhibit_category'        => $attributes['exhibit_category'] ?? 'Drugs',
                        'commodity_name'          => $attributes['seizure_name_drug'] ?? ($attributes['seizure_name'] ?? ($attributes['commodity_name'] ?? 'Exhibits')),
                        'seizure_quantity'        => $attributes['seizure_quantity'] ?? null,
                        'seizure_weight'          => $attributes['seizure_weight'] ?? null,
                        'seizure_unit'            => $attributes['seizure_unit'] ?? null,
                        'seizure_packaging'       => $attributes['seizure_packaging'] ?? ($attributes['packaging_type'] ?? null),
                        'seizure_value'           => $attributes['seizure_value'] ?? ($attributes['valuation'] ?? null),
                        'seizure_concealment'     => $attributes['seizure_concealment'] ?? null,
                        'seizure_date'            => $attributes['seizure_date'] ?? null,
                        'seizure_time'            => $attributes['seizure_time'] ?? null,
                        'seizure_location'        => $attributes['seizure_location'] ?? null,
                        'seizure_officer'         => $attributes['seizure_officer'] ?? null,
                        'seizure_reason'          => $attributes['seizure_reason'] ?? null,
                        'veh_make'                => $attributes['veh_make'] ?? ($attributes['vehicle_make'] ?? ($attributes['vehicle_make_model'] ?? null)),
                        'veh_model'               => $attributes['veh_model'] ?? ($attributes['vehicle_model'] ?? ($attributes['vehicle_make_model'] ?? null)),
                        'veh_type'                => $attributes['veh_type'] ?? ($attributes['vehicle_type'] ?? null),
                        'veh_year'                => $attributes['veh_year'] ?? ($attributes['vehicle_year'] ?? null),
                        'veh_colour'              => $attributes['veh_colour'] ?? ($attributes['vehicle_colour'] ?? ($attributes['vehicle_color_type'] ?? null)),
                        'veh_reg_no'              => $attributes['veh_reg_no'] ?? ($attributes['vehicle_reg_no'] ?? ($attributes['vehicle_registration'] ?? null)),
                        'veh_vin'                 => $attributes['veh_vin'] ?? ($attributes['vehicle_vin'] ?? ($attributes['vehicle_chassis_vin'] ?? null)),
                        'veh_engine_no'           => $attributes['veh_engine_no'] ?? ($attributes['engine_number'] ?? null),
                        'veh_country_reg'         => $attributes['veh_country_reg'] ?? ($attributes['country_of_registration'] ?? null),
                        'veh_owner'               => $attributes['veh_owner'] ?? ($attributes['vehicle_owner'] ?? null),
                        'veh_driver'              => $attributes['veh_driver'] ?? ($attributes['vehicle_driver'] ?? null),
                        'wpn_model'               => $attributes['wpn_model'] ?? ($attributes['weapon_model'] ?? ($attributes['weapon_type'] ?? null)),
                        'wpn_serial_no'           => $attributes['wpn_serial_no'] ?? ($attributes['weapon_serial'] ?? null),
                        'wpn_calibre'             => $attributes['wpn_calibre'] ?? ($attributes['weapon_calibre'] ?? null),
                        'wpn_colour'              => $attributes['wpn_colour'] ?? ($attributes['weapon_colour'] ?? null),
                        'wpn_size'                => $attributes['wpn_size'] ?? ($attributes['weapon_size'] ?? null),
                        'dev_model'               => $attributes['dev_model'] ?? ($attributes['device_model'] ?? null),
                        'dev_serial_no'           => $attributes['dev_serial_no'] ?? ($attributes['device_serial'] ?? null),
                        'dev_imei1'               => $attributes['dev_imei1'] ?? ($attributes['imei_1'] ?? null),
                        'dev_imei2'               => $attributes['dev_imei2'] ?? ($attributes['imei_2'] ?? null),
                        'dev_mac_address'         => $attributes['dev_mac_address'] ?? ($attributes['mac_address'] ?? null),
                        'ammunition_details'      => $attributes['ammunition_details'] ?? null,
                        'evidence_photos'         => $attributes['evidence_photos'] ?? [],
                    ]);
                }
            } else {
                $hasCreatedLabFromSeizure = false;
                $labDataFromDocket = $docketData['lab_examination'] ?? [];
                foreach ($seizures as $sIdx => $sz) {
                    $ownerName = $sz['associated_person'] ?? ($sz['suspect_ownership'] ?? ($sz['suspect_name'] ?? null));
                    $ownerAssocId = $sz['associate_id'] ?? ($sz['suspect_id'] ?? null);
                    $matchedSuspect = null;

                    $isJointOwner = ($ownerName && (str_contains(strtolower($ownerName), 'all suspect') || str_contains($ownerName, ',') || str_contains(strtolower($ownerName), 'joint')))
                                    || (!empty($sz['suspect_ownership']) && str_contains(strtolower($sz['suspect_ownership']), 'all suspect'));

                    if ($ownerName && !$isJointOwner) {
                        foreach ($createdSuspects as $cs) {
                            if (strtolower(trim($cs->suspect_name)) === strtolower(trim($ownerName))) {
                                $matchedSuspect = $cs;
                                break;
                            }
                        }
                    }
                    if (!$matchedSuspect && $ownerAssocId && !$isJointOwner) {
                        foreach ($createdSuspects as $cs) {
                            if ($cs->associate_id === $ownerAssocId) {
                                $matchedSuspect = $cs;
                                break;
                            }
                        }
                    }
                    // Do NOT fall back to index-based matching for joint/unowned exhibits
                    // suspect_id = null means the exhibit is jointly owned by all suspects

                    $ex = Exhibit::create([
                        'case_type'               => 'MULTIPLE',
                        'multiple_suspect_case_id'=> $multi->id,
                        'suspect_id'              => $matchedSuspect ? $matchedSuspect->id : null,
                        'exhibit_number'          => $sz['exhibit_number'] ?? ($sIdx + 1),
                        'exhibit_category'        => $sz['category'] ?? ($attributes['exhibit_category'] ?? 'Drugs'),
                        'exhibit_type'            => $sz['exhibit_type'] ?? ($attributes['exhibit_type'] ?? null),
                        'drug_classification'     => $sz['drug_classification'] ?? ($attributes['drug_classification'] ?? null),
                        'commodity_name'          => $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? ($attributes['seizure_name'] ?? 'Exhibits')),
                        'seizure_quantity'        => $sz['seizure_quantity'] ?? null,
                        'seizure_weight'          => $sz['seizure_weight'] ?? null,
                        'seizure_unit'            => $sz['seizure_unit'] ?? null,
                        'seizure_packaging'       => $sz['seizure_packaging'] ?? ($attributes['seizure_packaging'] ?? null),
                        'seizure_value'           => $sz['seizure_value'] ?? ($sz['valuation'] ?? ($attributes['seizure_value'] ?? ($attributes['valuation'] ?? null))),
                        'seizure_concealment'     => $sz['seizure_concealment'] ?? ($attributes['seizure_concealment'] ?? null),
                        'seizure_date'            => $sz['seizure_date'] ?? ($attributes['seizure_date'] ?? null),
                        'seizure_time'            => $sz['seizure_time'] ?? ($attributes['seizure_time'] ?? null),
                        'seizure_location'        => $sz['seizure_location'] ?? ($attributes['seizure_location'] ?? null),
                        'seizure_officer'         => $sz['seizure_officer'] ?? ($attributes['seizure_officer'] ?? null),
                        'seizure_reason'          => $sz['seizure_reason'] ?? ($attributes['seizure_reason'] ?? null),
                        'veh_make'                => $sz['veh_make'] ?? ($sz['veh']['make'] ?? ($sz['vehicle_make'] ?? ($sz['vehicle_make_model'] ?? ($attributes['veh_make'] ?? null)))),
                        'veh_model'               => $sz['veh_model'] ?? ($sz['veh']['model'] ?? ($sz['vehicle_model'] ?? ($sz['vehicle_make_model'] ?? ($attributes['veh_model'] ?? null)))),
                        'veh_type'                => $sz['veh_type'] ?? ($sz['veh']['type'] ?? ($sz['vehicle_type'] ?? ($attributes['veh_type'] ?? null))),
                        'veh_year'                => $sz['veh_year'] ?? ($sz['veh']['year'] ?? ($sz['vehicle_year'] ?? ($attributes['veh_year'] ?? null))),
                        'veh_colour'              => $sz['veh_colour'] ?? ($sz['veh']['colour'] ?? ($sz['vehicle_colour'] ?? ($sz['vehicle_color_type'] ?? ($attributes['veh_colour'] ?? null)))),
                        'veh_reg_no'              => $sz['veh_reg_no'] ?? ($sz['veh']['reg_no'] ?? ($sz['vehicle_reg_no'] ?? ($sz['vehicle_registration'] ?? ($attributes['veh_reg_no'] ?? null)))),
                        'veh_vin'                 => $sz['veh_vin'] ?? ($sz['veh']['vin'] ?? ($sz['vehicle_vin'] ?? ($sz['vehicle_chassis_vin'] ?? ($attributes['veh_vin'] ?? null)))),
                        'veh_engine_no'           => $sz['veh_engine_no'] ?? ($sz['veh']['engine_no'] ?? ($sz['engine_number'] ?? ($attributes['veh_engine_no'] ?? null))),
                        'veh_country_reg'         => $sz['veh_country_reg'] ?? ($sz['veh']['country_reg'] ?? ($sz['country_of_registration'] ?? ($attributes['veh_country_reg'] ?? null))),
                        'veh_owner'               => $sz['veh_owner'] ?? ($sz['veh']['owner'] ?? ($sz['vehicle_owner'] ?? ($attributes['veh_owner'] ?? null))),
                        'veh_driver'              => $sz['veh_driver'] ?? ($sz['veh']['driver'] ?? ($sz['vehicle_driver'] ?? ($attributes['veh_driver'] ?? null))),
                        'wpn_model'               => $sz['wpn_model'] ?? ($sz['wpn']['model'] ?? ($sz['weapon_model'] ?? ($sz['weapon_type'] ?? ($sz['seizure_name_weapon'] ?? ($attributes['wpn_model'] ?? null))))),
                        'wpn_serial_no'           => $sz['wpn_serial_no'] ?? ($sz['wpn']['serial_no'] ?? ($sz['weapon_serial'] ?? ($attributes['wpn_serial_no'] ?? null))),
                        'wpn_calibre'             => $sz['wpn_calibre'] ?? ($sz['wpn']['calibre'] ?? ($sz['weapon_calibre'] ?? ($attributes['wpn_calibre'] ?? null))),
                        'wpn_colour'              => $sz['wpn_colour'] ?? ($sz['wpn']['colour'] ?? ($sz['weapon_colour'] ?? ($attributes['wpn_colour'] ?? null))),
                        'wpn_size'                => $sz['wpn_size'] ?? ($sz['wpn']['size'] ?? ($sz['weapon_size'] ?? ($attributes['wpn_size'] ?? null))),
                        'dev_model'               => $sz['dev_model'] ?? ($sz['device_model'] ?? ($attributes['dev_model'] ?? null)),
                        'dev_serial_no'           => $sz['dev_serial_no'] ?? ($sz['device_serial'] ?? ($attributes['dev_serial_no'] ?? null)),
                        'dev_imei1'               => $sz['dev_imei1'] ?? ($sz['imei_1'] ?? ($attributes['dev_imei1'] ?? null)),
                        'dev_imei2'               => $sz['dev_imei2'] ?? ($sz['imei_2'] ?? ($attributes['dev_imei2'] ?? null)),
                        'dev_mac_address'         => $sz['dev_mac_address'] ?? ($sz['mac_address'] ?? ($attributes['dev_mac_address'] ?? null)),
                        'ammunition_details'      => $sz['ammunition_details'] ?? ($attributes['ammunition_details'] ?? null),
                        'evidence_photos'         => !empty($sz['evidence_photos']) ? $sz['evidence_photos'] : ($sIdx === 0 ? ($attributes['evidence_photos'] ?? ($docketData['evidence_photos'] ?? [])) : []),
                    ]);

                    if (!empty($sz['has_saved_lab'])) {
                        $hasCreatedLabFromSeizure = true;
                        LaboratoryExaminationResult::create([
                            'case_type'               => 'MULTIPLE',
                            'multiple_suspect_case_id'=> $multi->id,
                            'suspect_id'              => $matchedSuspect ? $matchedSuspect->id : ($createdSuspects[0]->id ?? null),
                            'exhibit_id'              => $ex->id,
                            'laboratory_number'       => $sz['laboratory_number'] ?? ($multi->laboratory_number ?? ($labDataFromDocket['laboratory_number'] ?? null)),
                            'presumptive_result'      => $sz['presumptive_result'] ?? ($labDataFromDocket['presumptive_result'] ?? null),
                            'confirmatory_result'     => $sz['confirmatory_result'] ?? ($labDataFromDocket['confirmatory_result'] ?? 'YES'),
                            'substance_identification'=> $sz['substance_identification'] ?? ($labDataFromDocket['substance_identification'] ?? ($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? null))),
                            'sample_weight_description'=> $sz['sample_description'] ?? ($labDataFromDocket['sample_weight']['description'] ?? ($labDataFromDocket['sample_weight_description'] ?? null)),
                            'sample_weight_units'     => $sz['sample_units'] ?? ($labDataFromDocket['sample_weight']['units_packaging'] ?? ($labDataFromDocket['sample_weight_units'] ?? null)),
                            'sample_weight_gross'     => $sz['sample_gross'] ?? ($labDataFromDocket['sample_weight']['gross_weight'] ?? ($labDataFromDocket['sample_weight_gross'] ?? null)),
                            'sample_weight_taken'     => $sz['sample_taken'] ?? ($sz['sample_weight_taken'] ?? ($labDataFromDocket['sample_weight_taken'] ?? ($labDataFromDocket['sample_taken'] ?? ($labDataFromDocket['sample_weight']['sample_taken'] ?? null)))),
                            'sample_weight_reserved'  => $sz['sample_reserved'] ?? ($sz['sample_weight_reserved'] ?? ($labDataFromDocket['sample_weight_reserved'] ?? ($labDataFromDocket['sample_reserved'] ?? ($labDataFromDocket['sample_weight']['reserved_samples'] ?? null)))),
                            'detected_substances'     => $sz['detected_substances'] ?? ($labDataFromDocket['detected_substances'] ?? []),
                            'test_methods'            => $sz['test_methods'] ?? ($labDataFromDocket['test_methods'] ?? null),
                            'specifications'          => $sz['specifications'] ?? ($labDataFromDocket['specifications'] ?? null),
                            'remark'                  => $sz['remark'] ?? ($labDataFromDocket['remark'] ?? null),
                            'findings'                => $sz['findings'] ?? ($labDataFromDocket['findings'] ?? null),
                            'analyst'                 => $sz['analyst'] ?? ($labDataFromDocket['analyst'] ?? null),
                            'has_saved'               => true,
                        ]);
                    }
                }
            }

            $labDataFromDocket = $docketData['lab_examination'] ?? [];
            if (empty($hasCreatedLabFromSeizure) && (!empty($attributes['presumptive_test_result']) || !empty($attributes['confirmatory_test_result']) || !empty($labDataFromDocket))) {
                LaboratoryExaminationResult::create([
                    'case_type'               => 'MULTIPLE',
                    'multiple_suspect_case_id'=> $multi->id,
                    'exhibit_id'              => $ex->id ?? null,
                    'laboratory_number'       => $multi->laboratory_number,
                    'analyst'                 => $attributes['analyst'] ?? ($labDataFromDocket['analyst'] ?? null),
                    'presumptive_result'      => $attributes['presumptive_test_result'] ?? ($labDataFromDocket['presumptive_result'] ?? null),
                    'confirmatory_result'     => $attributes['confirmatory_test_result'] ?? ($labDataFromDocket['confirmatory_result'] ?? 'YES'),
                    'sample_weight_description'=> $attributes['sample_weight_description'] ?? ($labDataFromDocket['sample_weight_description'] ?? ($labDataFromDocket['sample_weight']['description'] ?? null)),
                    'sample_weight_units'     => $attributes['sample_weight_units'] ?? ($labDataFromDocket['sample_weight_units'] ?? ($labDataFromDocket['sample_weight']['units_packaging'] ?? null)),
                    'sample_weight_gross'     => $attributes['sample_weight_gross'] ?? ($labDataFromDocket['sample_weight_gross'] ?? ($labDataFromDocket['sample_weight']['gross_weight'] ?? null)),
                    'sample_weight_taken'     => $attributes['sample_weight_taken'] ?? ($attributes['sample_taken'] ?? ($labDataFromDocket['sample_weight_taken'] ?? ($labDataFromDocket['sample_taken'] ?? ($labDataFromDocket['sample_weight']['sample_taken'] ?? null)))),
                    'sample_weight_reserved'  => $attributes['sample_weight_reserved'] ?? ($attributes['sample_reserved'] ?? ($labDataFromDocket['sample_weight_reserved'] ?? ($labDataFromDocket['sample_reserved'] ?? ($labDataFromDocket['sample_weight']['reserved_samples'] ?? null)))),
                    'substance_identification'=> $attributes['confirmatory_substance_identification'] ?? ($labDataFromDocket['substance_identification'] ?? null),
                    'test_methods'            => $attributes['confirmatory_test_methods'] ?? ($labDataFromDocket['test_methods'] ?? null),
                    'specifications'          => $attributes['confirmatory_specifications'] ?? ($labDataFromDocket['specifications'] ?? null),
                    'remark'                  => $attributes['confirmatory_remark'] ?? ($attributes['remark'] ?? ($labDataFromDocket['remark'] ?? null)),
                    'findings'                => $attributes['laboratory_analytical_findings'] ?? ($labDataFromDocket['findings'] ?? null),
                    'has_saved'               => true,
                ]);
            }

            if (!empty($docketData['associate_lab_examinations'])) {
                foreach ($docketData['associate_lab_examinations'] as $assocName => $assocLab) {
                    \App\Models\AssociateLabExamination::updateOrCreate(
                        [
                            'case_id'      => $multi->id,
                            'suspect_name' => $assocName,
                        ],
                        [
                            'laboratory_number'         => $assocLab['laboratory_number'] ?? $multi->laboratory_number,
                            'exam_reference'            => $assocLab['exam_reference'] ?? null,
                            'exhibit_number'            => $assocLab['exhibit_number'] ?? null,
                            'lab_unit'                  => $assocLab['lab_unit'] ?? null,
                            'analyst'                   => $assocLab['analyst'] ?? null,
                            'date_received'             => $assocLab['date_received'] ?? null,
                            'date_started'              => $assocLab['date_started'] ?? null,
                            'date_completed'            => $assocLab['date_completed'] ?? null,
                            'presumptive_result'        => $assocLab['presumptive_result'] ?? null,
                            'confirmatory_result'       => $assocLab['confirmatory_result'] ?? null,
                            'sample_weight_description' => $assocLab['sample_weight']['description'] ?? ($assocLab['sample_weight_description'] ?? null),
                            'sample_weight_units'       => $assocLab['sample_weight']['units_packaging'] ?? ($assocLab['sample_weight_units'] ?? null),
                            'sample_weight_gross'       => $assocLab['sample_weight']['gross_weight'] ?? ($assocLab['sample_weight_gross'] ?? null),
                            'sample_weight_taken'       => $assocLab['sample_weight']['sample_taken'] ?? ($assocLab['sample_weight_taken'] ?? null),
                            'sample_weight_reserved'   => $assocLab['sample_weight']['reserved_samples'] ?? ($assocLab['sample_weight_reserved'] ?? null),
                            'substance_identification'  => $assocLab['substance_identification'] ?? null,
                            'detected_substances'       => $assocLab['detected_substances'] ?? [],
                            'test_methods'              => $assocLab['test_methods'] ?? null,
                            'specifications'            => $assocLab['specifications'] ?? null,
                            'remark'                    => $assocLab['remark'] ?? null,
                            'findings'                  => $assocLab['findings'] ?? null,
                            'has_saved'                 => true,
                        ]
                    );
                }
            }

            return self::wrapRecord($multi->fresh(['suspects', 'exhibits', 'labResults']));
        }

        // Single suspect case creation
        $single = SingleSuspectCase::create([
            'case_number'          => $caseRef,
            'laboratory_number'    => $attributes['laboratory_number'] ?? null,
            'title'                => $attributes['title'] ?? 'Single Suspect Docket File',
            'unit_name'            => $attributes['unit_name'] ?? 'Crime Scene Support',
            'unit_id'              => $attributes['unit_id'] ?? null,
            'user_id'              => $attributes['user_id'] ?? null,
            'commanding_officer'   => $attributes['commanding_officer'] ?? null,
            'submitting_officer'   => $attributes['submitting_officer'] ?? null,
            'suspect_name'         => $attributes['suspect_name'] ?? ($suspects[0]['suspect_name'] ?? 'Unnamed Suspect'),
            'suspect_alias'        => $attributes['suspect_alias'] ?? ($suspects[0]['suspect_alias'] ?? null),
            'ghana_card'           => $attributes['ghana_card'] ?? ($suspects[0]['ghana_card'] ?? null),
            'gender'               => $attributes['gender'] ?? ($suspects[0]['gender'] ?? 'Male'),
            'dob'                  => $attributes['dob'] ?? ($suspects[0]['dob'] ?? null),
            'age'                  => isset($attributes['age']) && $attributes['age'] !== '' ? (int)$attributes['age'] : (isset($suspects[0]['age']) && $suspects[0]['age'] !== '' ? (int)$suspects[0]['age'] : null),
            'height'               => $attributes['height'] ?? ($suspects[0]['height'] ?? null),
            'height_unit'          => $attributes['height_unit'] ?? ($suspects[0]['height_unit'] ?? 'CM'),
            'eye_color'            => $attributes['eye_color'] ?? ($suspects[0]['eye_color'] ?? null),
            'nationality'          => $attributes['nationality'] ?? ($suspects[0]['nationality'] ?? null),
            'pob'                  => $attributes['place_of_birth'] ?? ($attributes['pob'] ?? ($suspects[0]['place_of_birth'] ?? ($suspects[0]['pob'] ?? null))),
            'phone'                => $attributes['phone'] ?? ($suspects[0]['phone'] ?? null),
            'address'              => $attributes['address'] ?? ($suspects[0]['address'] ?? null),
            'marital_status'       => $attributes['marital_status'] ?? ($suspects[0]['marital_status'] ?? null),
            'has_children'         => !empty($attributes['has_children']) || ((int)($attributes['children_count'] ?? 0) > 0) || !empty($suspects[0]['has_children']) || ((int)($suspects[0]['children_count'] ?? 0) > 0),
            'children_count'       => isset($attributes['children_count']) && $attributes['children_count'] !== '' ? (int)$attributes['children_count'] : null,
            'spouse_info'          => $attributes['spouse_info'] ?? null,
            'spouse_name'          => $attributes['spouse_name'] ?? null,
            'spouse_phone'         => $attributes['spouse_phone'] ?? null,
            'partner_name'         => $attributes['partner_name'] ?? null,
            'partner_phone'        => $attributes['partner_phone'] ?? null,
            'partner_info'         => $attributes['partner_info'] ?? null,
            'dependents'           => $attributes['dependents'] ?? null,
            'holding_facility'     => $attributes['holding_facility'] ?? null,
            'operation_code'       => $attributes['operation_code'] ?? null,
            'operation_code_name'  => $attributes['operation_code_name'] ?? null,
            'raid_location'        => $attributes['raid_location'] ?? ($attributes['location'] ?? null),
            'offense_description'  => $attributes['offense_description'] ?? ($attributes['description'] ?? null),
            'arrest_date'          => $attributes['arrest_date'] ?? null,
            'arrest_time'          => $attributes['arrest_time'] ?? null,
            'incident_date'        => $attributes['incident_date'] ?? now(),
            'mugshot_front'        => $attributes['mugshot_front'] ?? null,
            'mugshot_left'         => $attributes['mugshot_left'] ?? null,
            'mugshot_right'        => $attributes['mugshot_right'] ?? null,
            'priority'             => $attributes['priority'] ?? 'HIGH',
            'status'               => $attributes['status'] ?? 'Approved',
            'case_status'          => $attributes['case_status'] ?? 'Under Investigation',
            'controller_user_id'   => $attributes['controller_user_id'] ?? ($attributes['user_id'] ?? auth()->id()),
            'hash_log'             => $attributes['hash_log'] ?? ('HASH-' . strtoupper(substr(md5(uniqid()), 0, 8)) . '-VERIFIED'),
        ]);

        $seizures = $docketData['seizures'] ?? [];
        $hasExplicitTopLevelExhibitSingle = !empty($attributes['seizure_name_drug']) || !empty($attributes['seizure_name']) || !empty($attributes['commodity_name']);
        if (empty($seizures)) {
            if ($hasExplicitTopLevelExhibitSingle) {
                $ex = Exhibit::create([
                    'case_type'             => 'SINGLE',
                    'single_suspect_case_id'=> $single->id,
                    'exhibit_number'        => 1,
                    'exhibit_category'      => $attributes['exhibit_category'] ?? 'Drugs',
                    'commodity_name'        => $attributes['seizure_name_drug'] ?? ($attributes['seizure_name'] ?? ($attributes['commodity_name'] ?? 'Exhibits')),
                    'seizure_quantity'      => $attributes['seizure_quantity'] ?? null,
                    'seizure_weight'        => $attributes['seizure_weight'] ?? null,
                    'seizure_unit'          => $attributes['seizure_unit'] ?? null,
                    'seizure_packaging'     => $attributes['seizure_packaging'] ?? ($attributes['packaging_type'] ?? null),
                    'seizure_value'         => $attributes['seizure_value'] ?? ($attributes['valuation'] ?? null),
                    'seizure_concealment'   => $attributes['seizure_concealment'] ?? null,
                    'seizure_date'          => $attributes['seizure_date'] ?? null,
                    'seizure_time'          => $attributes['seizure_time'] ?? null,
                    'seizure_location'      => $attributes['seizure_location'] ?? null,
                    'seizure_officer'       => $attributes['seizure_officer'] ?? null,
                    'seizure_reason'        => $attributes['seizure_reason'] ?? null,
                    'veh_make'              => $attributes['veh_make'] ?? ($attributes['vehicle_make'] ?? ($attributes['vehicle_make_model'] ?? null)),
                    'veh_model'             => $attributes['veh_model'] ?? ($attributes['vehicle_model'] ?? ($attributes['vehicle_make_model'] ?? null)),
                    'veh_type'              => $attributes['veh_type'] ?? ($attributes['vehicle_type'] ?? null),
                    'veh_year'              => $attributes['veh_year'] ?? ($attributes['vehicle_year'] ?? null),
                    'veh_colour'            => $attributes['veh_colour'] ?? ($attributes['vehicle_colour'] ?? ($attributes['vehicle_color_type'] ?? null)),
                    'veh_reg_no'            => $attributes['veh_reg_no'] ?? ($attributes['vehicle_reg_no'] ?? ($attributes['vehicle_registration'] ?? null)),
                    'veh_vin'               => $attributes['veh_vin'] ?? ($attributes['vehicle_vin'] ?? ($attributes['vehicle_chassis_vin'] ?? null)),
                    'veh_engine_no'         => $attributes['veh_engine_no'] ?? ($attributes['engine_number'] ?? null),
                    'veh_country_reg'       => $attributes['veh_country_reg'] ?? ($attributes['country_of_registration'] ?? null),
                    'veh_owner'             => $attributes['veh_owner'] ?? ($attributes['vehicle_owner'] ?? null),
                    'veh_driver'            => $attributes['veh_driver'] ?? ($attributes['vehicle_driver'] ?? null),
                    'wpn_model'             => $attributes['wpn_model'] ?? ($attributes['weapon_model'] ?? ($attributes['weapon_type'] ?? null)),
                    'wpn_serial_no'         => $attributes['wpn_serial_no'] ?? ($attributes['weapon_serial'] ?? null),
                    'wpn_calibre'           => $attributes['wpn_calibre'] ?? ($attributes['weapon_calibre'] ?? null),
                    'wpn_colour'            => $attributes['wpn_colour'] ?? ($attributes['weapon_colour'] ?? null),
                    'wpn_size'              => $attributes['wpn_size'] ?? ($attributes['weapon_size'] ?? null),
                    'dev_model'             => $attributes['dev_model'] ?? ($attributes['device_model'] ?? null),
                    'dev_serial_no'         => $attributes['dev_serial_no'] ?? ($attributes['device_serial'] ?? null),
                    'dev_imei1'             => $attributes['dev_imei1'] ?? ($attributes['imei_1'] ?? null),
                    'dev_imei2'             => $attributes['dev_imei2'] ?? ($attributes['imei_2'] ?? null),
                    'dev_mac_address'       => $attributes['dev_mac_address'] ?? ($attributes['mac_address'] ?? null),
                    'ammunition_details'    => $attributes['ammunition_details'] ?? null,
                    'evidence_photos'       => $attributes['evidence_photos'] ?? [],
                ]);
            }
        } else {
            foreach ($seizures as $sIdx => $sz) {
                $ex = Exhibit::create([
                    'case_type'             => 'SINGLE',
                    'single_suspect_case_id'=> $single->id,
                    'exhibit_number'        => $sIdx + 1,
                    'exhibit_category'      => $sz['category'] ?? ($attributes['exhibit_category'] ?? 'Drugs'),
                    'exhibit_type'          => $sz['exhibit_type'] ?? ($attributes['exhibit_type'] ?? null),
                    'drug_classification'   => $sz['drug_classification'] ?? ($attributes['drug_classification'] ?? null),
                    'commodity_name'        => $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? ($attributes['seizure_name'] ?? 'Exhibits')),
                    'seizure_quantity'      => $sz['seizure_quantity'] ?? null,
                    'seizure_weight'        => $sz['seizure_weight'] ?? null,
                    'seizure_unit'          => $sz['seizure_unit'] ?? null,
                    'seizure_packaging'     => $sz['seizure_packaging'] ?? ($attributes['seizure_packaging'] ?? null),
                    'seizure_value'         => $sz['seizure_value'] ?? ($sz['valuation'] ?? ($attributes['seizure_value'] ?? ($attributes['valuation'] ?? null))),
                    'seizure_concealment'   => $sz['seizure_concealment'] ?? ($attributes['seizure_concealment'] ?? null),
                    'seizure_date'          => $sz['seizure_date'] ?? ($attributes['seizure_date'] ?? null),
                    'seizure_time'          => $sz['seizure_time'] ?? ($attributes['seizure_time'] ?? null),
                    'seizure_location'      => $sz['seizure_location'] ?? ($attributes['seizure_location'] ?? null),
                    'seizure_officer'       => $sz['seizure_officer'] ?? ($attributes['seizure_officer'] ?? null),
                    'seizure_reason'        => $sz['seizure_reason'] ?? ($attributes['seizure_reason'] ?? null),
                    'veh_make'              => $sz['veh_make'] ?? ($sz['veh']['make'] ?? ($sz['vehicle_make'] ?? ($sz['vehicle_make_model'] ?? ($attributes['veh_make'] ?? null)))),
                    'veh_model'             => $sz['veh_model'] ?? ($sz['veh']['model'] ?? ($sz['vehicle_model'] ?? ($sz['vehicle_make_model'] ?? ($attributes['veh_model'] ?? null)))),
                    'veh_type'              => $sz['veh_type'] ?? ($sz['veh']['type'] ?? ($sz['vehicle_type'] ?? ($attributes['veh_type'] ?? null))),
                    'veh_year'              => $sz['veh_year'] ?? ($sz['veh']['year'] ?? ($sz['vehicle_year'] ?? ($attributes['veh_year'] ?? null))),
                    'veh_colour'            => $sz['veh_colour'] ?? ($sz['veh']['colour'] ?? ($sz['vehicle_colour'] ?? ($sz['vehicle_color_type'] ?? ($attributes['veh_colour'] ?? null)))),
                    'veh_reg_no'            => $sz['veh_reg_no'] ?? ($sz['veh']['reg_no'] ?? ($sz['vehicle_reg_no'] ?? ($sz['vehicle_registration'] ?? ($attributes['veh_reg_no'] ?? null)))),
                    'veh_vin'               => $sz['veh_vin'] ?? ($sz['veh']['vin'] ?? ($sz['vehicle_vin'] ?? ($sz['vehicle_chassis_vin'] ?? ($attributes['veh_vin'] ?? null)))),
                    'veh_engine_no'         => $sz['veh_engine_no'] ?? ($sz['veh']['engine_no'] ?? ($sz['engine_number'] ?? ($attributes['veh_engine_no'] ?? null))),
                    'veh_country_reg'       => $sz['veh_country_reg'] ?? ($sz['veh']['country_reg'] ?? ($sz['country_of_registration'] ?? ($attributes['veh_country_reg'] ?? null))),
                    'veh_owner'             => $sz['veh_owner'] ?? ($sz['veh']['owner'] ?? ($sz['vehicle_owner'] ?? ($attributes['veh_owner'] ?? null))),
                    'veh_driver'            => $sz['veh_driver'] ?? ($sz['veh']['driver'] ?? ($sz['vehicle_driver'] ?? ($attributes['veh_driver'] ?? null))),
                    'wpn_model'             => $sz['wpn_model'] ?? ($sz['wpn']['model'] ?? ($sz['weapon_model'] ?? ($sz['weapon_type'] ?? ($sz['seizure_name_weapon'] ?? ($attributes['wpn_model'] ?? null))))),
                    'wpn_serial_no'         => $sz['wpn_serial_no'] ?? ($sz['wpn']['serial_no'] ?? ($sz['weapon_serial'] ?? ($attributes['wpn_serial_no'] ?? null))),
                    'wpn_calibre'           => $sz['wpn_calibre'] ?? ($sz['wpn']['calibre'] ?? ($sz['weapon_calibre'] ?? ($attributes['wpn_calibre'] ?? null))),
                    'wpn_colour'            => $sz['wpn_colour'] ?? ($sz['wpn']['colour'] ?? ($sz['weapon_colour'] ?? ($attributes['wpn_colour'] ?? null))),
                    'wpn_size'              => $sz['wpn_size'] ?? ($sz['wpn']['size'] ?? ($sz['weapon_size'] ?? ($attributes['wpn_size'] ?? null))),
                    'dev_model'             => $sz['dev_model'] ?? ($sz['device_model'] ?? ($attributes['dev_model'] ?? null)),
                    'dev_serial_no'         => $sz['dev_serial_no'] ?? ($sz['device_serial'] ?? ($attributes['dev_serial_no'] ?? null)),
                    'dev_imei1'             => $sz['dev_imei1'] ?? ($sz['imei_1'] ?? ($attributes['dev_imei1'] ?? null)),
                    'dev_imei2'             => $sz['dev_imei2'] ?? ($sz['imei_2'] ?? ($attributes['dev_imei2'] ?? null)),
                    'dev_mac_address'       => $sz['dev_mac_address'] ?? ($sz['mac_address'] ?? ($attributes['dev_mac_address'] ?? null)),
                    'ammunition_details'    => $sz['ammunition_details'] ?? ($attributes['ammunition_details'] ?? null),
                    'evidence_photos'       => !empty($sz['evidence_photos']) ? $sz['evidence_photos'] : ($sIdx === 0 ? ($attributes['evidence_photos'] ?? ($docketData['evidence_photos'] ?? [])) : []),
                ]);
            }
        }

        $labDataFromDocket = $docketData['lab_examination'] ?? [];
        if (!empty($attributes['presumptive_test_result']) || !empty($attributes['confirmatory_test_result']) || !empty($labDataFromDocket)) {
            // Derive weight fields from the exhibit when not provided
            $labSwDesc  = $attributes['sample_weight_description'] ?? ($labDataFromDocket['sample_weight_description'] ?? ($labDataFromDocket['sample_weight']['description'] ?? null));
            $labSwUnits = $attributes['sample_weight_units'] ?? ($labDataFromDocket['sample_weight_units'] ?? ($labDataFromDocket['sample_weight']['units_packaging'] ?? null));
            $labSwGross = $attributes['sample_weight_gross'] ?? ($labDataFromDocket['sample_weight_gross'] ?? ($labDataFromDocket['sample_weight']['gross_weight'] ?? null));
            if (empty($labSwDesc) || empty($labSwGross) || empty($labSwUnits)) {
                if (isset($ex) && $ex) {
                    if (empty($labSwDesc))  $labSwDesc  = $ex->commodity_name ?? null;
                    if (empty($labSwUnits)) $labSwUnits = trim(($ex->seizure_quantity ?? '') . ' ' . ($ex->seizure_packaging ?? '')) ?: null;
                    if (empty($labSwGross)) $labSwGross = trim(($ex->seizure_weight ?? '') . ' ' . ($ex->seizure_unit ?? '')) ?: null;
                }
            }
            LaboratoryExaminationResult::create([
                'case_type'               => 'SINGLE',
                'single_suspect_case_id'  => $single->id,
                'exhibit_id'              => $ex->id ?? null,
                'laboratory_number'       => $single->laboratory_number,
                'analyst'                 => $attributes['analyst'] ?? ($labDataFromDocket['analyst'] ?? null),
                'presumptive_result'      => $attributes['presumptive_test_result'] ?? ($labDataFromDocket['presumptive_result'] ?? null),
                'confirmatory_result'     => $attributes['confirmatory_test_result'] ?? ($labDataFromDocket['confirmatory_result'] ?? 'YES'),
                'sample_weight_description'=> $labSwDesc,
                'sample_weight_units'     => $labSwUnits,
                'sample_weight_gross'     => $labSwGross,
                'sample_weight_taken'     => $attributes['sample_weight_taken'] ?? ($attributes['sample_taken'] ?? ($labDataFromDocket['sample_weight_taken'] ?? ($labDataFromDocket['sample_taken'] ?? ($labDataFromDocket['sample_weight']['sample_taken'] ?? null)))),
                'sample_weight_reserved'  => $attributes['sample_weight_reserved'] ?? ($attributes['sample_reserved'] ?? ($labDataFromDocket['sample_weight_reserved'] ?? ($labDataFromDocket['sample_reserved'] ?? ($labDataFromDocket['sample_weight']['reserved_samples'] ?? null)))),
                'substance_identification'=> $attributes['confirmatory_substance_identification'] ?? ($labDataFromDocket['substance_identification'] ?? null),
                'test_methods'            => $attributes['confirmatory_test_methods'] ?? ($labDataFromDocket['test_methods'] ?? null),
                'specifications'          => $attributes['confirmatory_specifications'] ?? ($labDataFromDocket['specifications'] ?? null),
                'remark'                  => $attributes['confirmatory_remark'] ?? ($attributes['remark'] ?? ($labDataFromDocket['remark'] ?? null)),
                'findings'                => $attributes['laboratory_analytical_findings'] ?? ($labDataFromDocket['findings'] ?? null),
                'has_saved'               => true,
            ]);
        }

        return self::wrapRecord($single->fresh(['exhibits', 'labResults']));
    }

    /**
     * Batch insert records into single_suspect_cases table.
     */
    public static function insert(array $records)
    {
        $singleData = [];
        foreach ($records as $r) {
            $singleData[] = [
                'case_number' => $r['case_number'] ?? ('NFC-' . rand(1000, 9999)),
                'title' => $r['title'] ?? 'Bulk Insert Case',
                'unit_name' => $r['unit_name'] ?? 'Crime Scene Support',
                'status' => $r['status'] ?? 'Pending Approval',
                'suspect_name' => $r['suspect_name'] ?? 'Default Suspect',
                'priority' => $r['priority'] ?? 'HIGH',
                'hash_log' => 'HASH-' . strtoupper(substr(md5(uniqid()), 0, 8)) . '-VERIFIED',
                'created_at' => $r['created_at'] ?? now(),
                'updated_at' => $r['updated_at'] ?? now(),
            ];
        }
        return SingleSuspectCase::insert($singleData);
    }

    /**
     * Find a case by ID or case_number
     */
    public static function find($id)
    {
        return self::findByRef($id);
    }

    /**
     * Query builder helper for where clauses
     */
    public static function where($column, $operator = null, $value = null)
    {
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('single_suspect_cases')) {
            if (\Illuminate\Support\Facades\Schema::hasTable('cases')) {
                $raw = \Illuminate\Support\Facades\DB::table('cases')->where($column, $operator, $value)->get()->map(function($row) {
                    $inst = new self();
                    foreach ((array)$row as $k => $v) {
                        $inst->$k = $v;
                    }
                    return $inst;
                });
                return new class($raw) {
                    protected $items;
                    public function __construct($items) { $this->items = $items; }
                    public function first() { return $this->items->first(); }
                    public function get() { return $this->items; }
                    public function count() { return $this->items->count(); }
                    public function exists() { return $this->items->isNotEmpty(); }
                    public function orderBy($col, $dir = 'asc') { return $this; }
                    public function update(array $values) { return 0; }
                    public function orWhere($c, $o = null, $v = null) { return $this; }
                };
            }
            return new class(collect()) {
                public function first() { return null; }
                public function get() { return collect(); }
                public function count() { return 0; }
                public function exists() { return false; }
                public function orderBy($col, $dir = 'asc') { return $this; }
                public function update(array $values) { return 0; }
                public function orWhere($c, $o = null, $v = null) { return $this; }
            };
        }

        $singles = SingleSuspectCase::where($column, $operator, $value)->where('status', '!=', 'Promoted')->get();
        $multiples = MultipleSuspectCase::where($column, $operator, $value)->get();

        $collection = collect();
        foreach ($singles as $s) {
            $collection->push(self::wrapRecord($s));
        }
        foreach ($multiples as $m) {
            $collection->push(self::wrapRecord($m));
        }
        $collection = $collection->unique('case_number')->values();

        return new class($collection, $column, $operator, $value) {
            protected $items;
            protected $col;
            protected $op;
            protected $val;

            public function __construct($items, $col, $op, $val) {
                $this->items = $items;
                $this->col = $col;
                $this->op = $op;
                $this->val = $val;
            }

            public function first() { return $this->items->first(); }
            public function get() { return $this->items; }
            public function count() { return $this->items->count(); }
            public function exists() { return $this->items->isNotEmpty(); }
            public function orderBy($col, $dir = 'asc') {
                $sorted = $dir === 'desc' ? $this->items->sortByDesc($col) : $this->items->sortBy($col);
                return new static($sorted->values(), $this->col, $this->op, $this->val);
            }

            public function update(array $values) {
                $c1 = SingleSuspectCase::where($this->col, $this->op, $this->val)->update($values);
                $c2 = MultipleSuspectCase::where($this->col, $this->op, $this->val)->update($values);
                return $c1 + $c2;
            }

            public function orWhere($col, $op = null, $val = null) {
                if (func_num_args() == 2) {
                    $val = $op;
                    $op = '=';
                }
                $s2 = SingleSuspectCase::where($col, $op, $val)->get();
                $m2 = MultipleSuspectCase::where($col, $op, $val)->get();
                foreach ($s2 as $s) {
                    if (!$this->items->contains('id', $s->id)) {
                        $this->items->push(ForensicCase::wrapRecord($s));
                    }
                }
                foreach ($m2 as $m) {
                    if (!$this->items->contains('id', $m->id)) {
                        $this->items->push(ForensicCase::wrapRecord($m));
                    }
                }
                return $this;
            }
        };
    }

    /**
     * Query builder helper for whereIn clauses
     */
    public static function whereIn($column, array $values)
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('single_suspect_cases')) {
            return new class(collect()) {
                public function first() { return null; }
                public function get() { return collect(); }
                public function count() { return 0; }
                public function exists() { return false; }
                public function update(array $u) { return 0; }
            };
        }

        $singles = SingleSuspectCase::whereIn($column, $values)->where('status', '!=', 'Promoted')->get();
        $multiples = MultipleSuspectCase::whereIn($column, $values)->get();

        $collection = collect();
        foreach ($singles as $s) {
            $collection->push(self::wrapRecord($s));
        }
        foreach ($multiples as $m) {
            $collection->push(self::wrapRecord($m));
        }
        $collection = $collection->unique('case_number')->values();

        return new class($collection, $column, $values) {
            protected $items;
            protected $col;
            protected $vals;

            public function __construct($items, $col, $vals) {
                $this->items = $items;
                $this->col = $col;
                $this->vals = $vals;
            }

            public function first() { return $this->items->first(); }
            public function get() { return $this->items; }
            public function count() { return $this->items->count(); }
            public function exists() { return $this->items->isNotEmpty(); }

            public function update(array $updateValues) {
                $c1 = SingleSuspectCase::whereIn($this->col, $this->vals)->update($updateValues);
                $c2 = MultipleSuspectCase::whereIn($this->col, $this->vals)->update($updateValues);
                return $c1 + $c2;
            }
        };
    }

    /**
     * Helper to return all cases.
     */
    public static function all($columns = ['*'])
    {
        return self::allCases();
    }

    /**
     * Helper to wrap with relations.
     */
    public static function with($relations)
    {
        return new class {
            public function orderBy($column, $direction = 'asc') {
                return $this;
            }
            public function get() {
                return ForensicCase::allCases();
            }
        };
    }

    /**
     * Get all cases ordered by created_at desc
     */
    public static function allCases(): Collection
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('single_suspect_cases')) {
            if (\Illuminate\Support\Facades\Schema::hasTable('cases')) {
                return \Illuminate\Support\Facades\DB::table('cases')->get()->map(function($row) {
                    $inst = new self();
                    foreach ((array)$row as $k => $v) {
                        $inst->$k = $v;
                    }
                    return $inst;
                });
            }
            return collect();
        }

        $singles = SingleSuspectCase::with(['user', 'unit', 'exhibits', 'labResults'])->where('status', '!=', 'Promoted')->get();
        $multiples = MultipleSuspectCase::with(['user', 'unit', 'suspects', 'exhibits', 'labResults'])->get();

        $all = collect();
        foreach ($singles as $s) {
            $all->push(self::wrapRecord($s));
        }
        foreach ($multiples as $m) {
            $all->push(self::wrapRecord($m));
        }

        return $all->unique('case_number')->sortByDesc('created_at')->values();
    }

    /**
     * Find a case by case_number or ID
     */
    public static function findByRef($ref): ?self
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('single_suspect_cases')) {
            if (\Illuminate\Support\Facades\Schema::hasTable('cases')) {
                $row = \Illuminate\Support\Facades\DB::table('cases')
                    ->where('id', is_numeric($ref) ? (int)$ref : 0)
                    ->orWhere('case_number', (string)$ref)
                    ->first();
                if ($row) {
                    $inst = new self();
                    foreach ((array)$row as $k => $v) {
                        $inst->$k = $v;
                    }
                    return $inst;
                }
            }
            return null;
        }

        if (is_numeric($ref)) {
            $multi = MultipleSuspectCase::with(['user', 'unit', 'suspects', 'exhibits', 'labResults'])->find($ref);
            if ($multi) return self::wrapRecord($multi);

            $single = SingleSuspectCase::with(['user', 'unit', 'exhibits', 'labResults'])->where('status', '!=', 'Promoted')->find($ref);
            if ($single) return self::wrapRecord($single);
        }

        $multi = MultipleSuspectCase::with(['user', 'unit', 'suspects', 'exhibits', 'labResults'])->where('case_number', $ref)->first();
        if ($multi) return self::wrapRecord($multi);

        $single = SingleSuspectCase::with(['user', 'unit', 'exhibits', 'labResults'])->where('case_number', $ref)->where('status', '!=', 'Promoted')->first();
        if ($single) return self::wrapRecord($single);

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EXHIBIT CLASSIFICATION & WORKFLOW ROUTING
    // All routing is driven exclusively by the actual exhibit categories
    // linked to this case. Unit name, creator, and case title are NOT used.
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Keyword sets for exhibit classification.
     */
    private static array $DRUG_KEYWORDS = [
        'drug', 'narcotic', 'opioid', 'cocaine', 'heroin', 'cannabis', 'marijuana',
        'weed', 'hashish', 'hash', 'meth', 'methamphetamine', 'fentanyl', 'tramadol',
        'codeine', 'morphine', 'opium', 'amphetamine', 'mdma', 'ecstasy', 'hemp',
        'ephedrine', 'precursor', 'substance',
    ];

    private static array $ELECTRONIC_KEYWORDS = [
        'mobile', 'phone', 'smartphone', 'laptop', 'computer', 'tablet', 'ipad',
        'hard drive', 'harddrive', 'hdd', 'ssd', 'usb', 'flash drive', 'flashdrive',
        'memory card', 'memorycard', 'electronic', 'digital device', 'device',
        'iphone', 'android', 'pc', 'desktop',
    ];

    private static array $CRIME_SCENE_MANDATORY_KEYWORDS = [
        // Weapons & ammunition
        'weapon', 'firearm', 'gun', 'pistol', 'rifle', 'shotgun', 'revolver',
        'knife', 'blade', 'machete', 'cutlass', 'sword', 'dagger',
        'ammunition', 'ammo', 'bullet', 'cartridge', 'shell', 'explosive', 'grenade',
        // Gold / precious metals / money
        'gold', 'precious', 'silver', 'diamond', 'gemstone', 'jewel', 'bullion',
        'currency', 'cash', 'money', 'banknote', 'note', 'coin',
        // Vehicles & Vessels
        'vehicle', 'vehicles', 'veh', 'car', 'truck', 'automobile', 'vessel', 'zonda',
    ];

    /**
     * Scan every exhibit linked to this case and return a set of classification flags.
     *
     * @return array{has_drug: bool, has_electronic: bool, has_crime_scene_mandatory: bool}
     */
    public function classifyExhibits(): array
    {
        $flags = [
            'has_drug'                  => false,
            'has_electronic'            => false,
            'has_crime_scene_mandatory' => false,
        ];

        // Helper closure: classify a single text value against a keyword list
        $matches = static function (string $text, array $keywords): bool {
            $lower = strtolower($text);
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) return true;
            }
            return false;
        };

        // Helper: classify one exhibit or lab result array item
        $classifyItem = function (array $ex) use (&$flags, $matches): void {
            // The exhibit and lab result fields we check
            $texts = array_filter([
                $ex['exhibit_category'] ?? ($ex['category'] ?? null),
                $ex['commodity_name']   ?? ($ex['seizure_name'] ?? ($ex['name'] ?? ($ex['description'] ?? ($ex['type'] ?? null)))),
                $ex['seizure_name_drug'] ?? null,
                $ex['substance_identification'] ?? null,
                $ex['presumptive_result'] ?? null,
                $ex['confirmatory_result'] ?? null,
                $ex['sample_weight_description'] ?? null,
                $ex['remarks'] ?? ($ex['remark'] ?? null),
            ]);

            // Drug shortcut: non-empty seizure_name_drug field means drug exhibit
            if (!empty($ex['seizure_name_drug'])) {
                $flags['has_drug'] = true;
            }

            foreach ($texts as $text) {
                if (!is_string($text) || $text === '') continue;
                if (!$flags['has_drug'] && $matches($text, self::$DRUG_KEYWORDS)) {
                    $flags['has_drug'] = true;
                }
                if (!$flags['has_crime_scene_mandatory'] && $matches($text, self::$CRIME_SCENE_MANDATORY_KEYWORDS)) {
                    $flags['has_crime_scene_mandatory'] = true;
                }
            }

            foreach ($texts as $text) {
                if (!is_string($text) || $text === '') continue;
                if (!$flags['has_electronic'] && $matches($text, self::$ELECTRONIC_KEYWORDS)) {
                    $lowerText = strtolower($text);
                    // Disambiguate pharmaceutical tablets (e.g. Tramadol Tablets, MDMA Tablets) from electronic tablets (e.g. iPad, Android Tablet)
                    $isPharmTablet = false;
                    if (preg_match('/\btablets?\b/i', $text) && !preg_match('/\b(tablet\s*pc|tablet\s*computer|android\s*tablet|ipad|graphics\s*tablet)\b/i', $text)) {
                        $isDrugContext = $flags['has_drug'] || $matches($lowerText, self::$DRUG_KEYWORDS) || str_contains($lowerText, 'drug') || str_contains($lowerText, 'narcotic') || str_contains($lowerText, 'capsule') || str_contains($lowerText, 'dose') || str_contains($lowerText, 'mg') || str_contains($lowerText, 'gram');
                        if ($isDrugContext) {
                            $isPharmTablet = true;
                        }
                    }

                    if (!$isPharmTablet) {
                        $flags['has_electronic'] = true;
                    }
                }
            }
        };

        // 1. Eloquent Exhibit records
        if (isset($this->underlyingRecord) && isset($this->underlyingRecord->exhibits)) {
            foreach ($this->underlyingRecord->exhibits as $exRecord) {
                $classifyItem([
                    'exhibit_category' => $exRecord->exhibit_category ?? null,
                    'commodity_name'   => $exRecord->commodity_name ?? null,
                    'seizure_name_drug'=> $exRecord->seizure_name_drug ?? null,
                ]);
            }
        }

        // Direct DB query for exhibits if case ID is present
        if (!empty($this->id)) {
            $dbExhibits = Exhibit::where('multiple_suspect_case_id', $this->id)
                ->orWhere('single_suspect_case_id', $this->id)
                ->get();
            foreach ($dbExhibits as $exRecord) {
                $classifyItem([
                    'exhibit_category' => $exRecord->exhibit_category ?? null,
                    'commodity_name'   => $exRecord->commodity_name ?? null,
                    'seizure_name_drug'=> $exRecord->seizure_name_drug ?? null,
                ]);
            }
        }

        // 2. Docket data (seizures, lab examinations, associate labs)
        $docketData = is_array($this->docket_data)
            ? $this->docket_data
            : (json_decode($this->docket_data ?? '[]', true) ?: []);

        $docketExhibits = array_merge(
            $docketData['seizures']            ?? [],
            $docketData['associated_seizures'] ?? [],
            $docketData['associate_exhibits']  ?? [],
        );
        foreach ($docketExhibits as $ex) {
            $classifyItem((array) $ex);
        }

        if (!empty($docketData['lab_examination'])) {
            $classifyItem((array) $docketData['lab_examination']);
        }

        if (!empty($docketData['lab_examinations_list']) && is_array($docketData['lab_examinations_list'])) {
            foreach ($docketData['lab_examinations_list'] as $lExam) {
                $classifyItem((array) $lExam);
            }
        }

        if (!empty($docketData['associate_lab_examinations']) && is_array($docketData['associate_lab_examinations'])) {
            foreach ($docketData['associate_lab_examinations'] as $alExam) {
                $classifyItem((array) $alExam);
            }
        }

        // 3. Linked Laboratory Examination DB Records
        if (!empty($this->id)) {
            $labRecords = LaboratoryExaminationResult::where('multiple_suspect_case_id', $this->id)
                ->orWhere('single_suspect_case_id', $this->id)
                ->get();
            foreach ($labRecords as $labRec) {
                $classifyItem([
                    'substance_identification' => $labRec->substance_identification ?? null,
                    'presumptive_result'       => $labRec->presumptive_result ?? null,
                    'confirmatory_result'      => $labRec->confirmatory_result ?? null,
                    'sample_weight_description'=> $labRec->sample_weight_description ?? null,
                    'remark'                   => $labRec->remark ?? null,
                ]);
            }

            $assocLabs = AssociateLabExamination::where('case_id', $this->id)->get();
            foreach ($assocLabs as $alRec) {
                $classifyItem([
                    'substance_identification' => $alRec->substance_identification ?? null,
                    'presumptive_result'       => $alRec->presumptive_result ?? null,
                    'confirmatory_result'      => $alRec->confirmatory_result ?? null,
                    'sample_weight_description'=> $alRec->sample_weight_description ?? null,
                    'remark'                   => $alRec->remark ?? null,
                ]);
            }
        }

        // 4. Top-level case fields
        $classifyItem([
            'exhibit_category'          => $this->exhibit_category  ?? ($this->underlyingRecord->exhibit_category ?? null),
            'commodity_name'            => $this->seizure_name       ?? ($this->underlyingRecord->seizure_name ?? null),
            'seizure_name_drug'         => $this->seizure_name_drug  ?? ($this->underlyingRecord->seizure_name_drug ?? null),
            'substance_identification'  => $this->confirmatory_substance_identification ?? ($this->underlyingRecord->confirmatory_substance_identification ?? null),
            'presumptive_result'        => $this->presumptive_test_result ?? ($this->underlyingRecord->presumptive_test_result ?? null),
            'sample_weight_description' => $this->sample_weight_description ?? ($this->underlyingRecord->sample_weight_description ?? null),
        ]);

        return $flags;
    }

    /**
     * Check if this case is classified as a Drug Case.
     * Delegates to classifyExhibits().
     */
    public function isDrugCase(): bool
    {
        $flags = $this->classifyExhibits();
        return (bool) ($flags['has_drug'] ?? false);
    }

    /**
     * Build the ordered list of workflow units required for this case.
     * Preserves existing/completed workflow stages to guarantee workflow persistence.
     *
     * Pipeline order:
     *   [Crime Scene Support]          — when exhibit mandates it or stage already recorded
     *   [Lab Analysis]                 — when a drug exhibit exists
     *   [Digital & Physical Forensics] — when an electronic device exhibit exists
     *   Evidence Management            — always present (last specialist stage before HOF)
     */
    public function getWorkflowUnits(): array
    {
        $flags = $this->classifyExhibits();

        $docketData = is_array($this->docket_data)
            ? $this->docket_data
            : (json_decode($this->docket_data ?? '[]', true) ?: []);

        $unitProgress = $docketData['unit_progress'] ?? [];

        $includeLab = !empty($flags['has_drug']);

        // Check if Crime Scene Support is required or already present/completed
        $hasCssInUnitProgress = isset($unitProgress['Crime Scene Support']);
        $includeCss = !empty($flags['has_crime_scene_mandatory']) || $hasCssInUnitProgress || str_starts_with($this->case_number ?? '', 'CSS-');

        // Check if Digital & Physical Forensics is required
        $includeDigital = !empty($flags['has_electronic']);

        $units = [];

        if ($includeCss) {
            $units[] = 'Crime Scene Support';
        }

        if ($includeLab) {
            $units[] = 'Lab Analysis';
        }

        if ($includeDigital) {
            $units[] = 'Digital & Physical Forensics';
        }

        // Evidence Management is always present as the final specialist stage
        $units[] = 'Evidence Management';

        return $units;
    }

    /**
     * Retrieve all formatted suspect records for this case.
     */
    public function getSuspectsList(): array
    {
        $docketData = is_array($this->docket_data)
            ? $this->docket_data
            : (json_decode($this->docket_data ?? '[]', true) ?: []);

        $list = [];

        // 1. If multi-suspect case in DB, query CaseSuspect model
        if ($this->underlyingRecord instanceof MultipleSuspectCase) {
            $dbSuspects = CaseSuspect::where('multiple_suspect_case_id', $this->id)
                ->orderBy('suspect_number')
                ->get();

            if ($dbSuspects->count() > 0) {
                foreach ($dbSuspects as $idx => $s) {
                    $list[] = [
                        'id'                     => $s->id,
                        'suspect_number'         => $s->suspect_number,
                        'suspect_name'           => $s->suspect_name,
                        'suspect_alias'          => $s->suspect_alias ?? 'N/A',
                        'role'                   => $s->role ?? ($idx === 0 ? 'Primary Suspect' : 'Associate Suspect'),
                        'association_role'       => $s->role ?? ($idx === 0 ? 'Primary Suspect' : 'Associate Suspect'),
                        'associate_id'           => $s->associate_id,
                        'suspect_disposition'    => $s->suspect_disposition ?? 'Active — Under Investigation',
                        'disposition_reason'     => $s->disposition_reason,
                        'disposition_updated_at' => $s->disposition_updated_at,
                        'disposition_updated_by' => $s->disposition_updated_by,
                        'ghana_card'             => $s->ghana_card,
                        'gender'                 => $s->gender,
                        'dob'                    => $s->dob,
                        'phone'                  => $s->phone,
                        'holding_facility'       => $s->holding_facility ?? ($this->holding_facility ?? null),
                        'raid_location'          => $s->raid_location ?? ($this->raid_location ?? null),
                        'offense_description'    => $s->offense_description ?? ($this->offense_description ?? null),
                        'date_of_arrest'         => $s->arrest_date ?? ($this->arrest_date ?? null),
                        'arrest_date'            => $s->arrest_date ?? ($this->arrest_date ?? null),
                        'time_of_arrest'         => $s->arrest_time ?? ($this->arrest_time ?? null),
                        'arrest_time'            => $s->arrest_time ?? ($this->arrest_time ?? null),
                        'mugshot_front'          => $s->mugshot_front,
                        'is_associate'           => !empty($s->associate_id) || strtolower($s->role ?? '') === 'associate suspect' || strtolower($s->role ?? '') === 'co-suspect',
                    ];
                }
                return $list;
            }
        }

        // 2. Check docket_data['suspects']
        $docketSuspects = $docketData['suspects'] ?? [];
        if (is_array($docketSuspects) && count($docketSuspects) > 0) {
            foreach ($docketSuspects as $idx => $s) {
                if (empty($s['suspect_name'])) continue;
                $role = $s['association_role'] ?? ($s['role'] ?? ($idx === 0 ? 'Primary Suspect' : 'Associate Suspect'));
                $list[] = [
                    'id'                     => $s['id'] ?? null,
                    'suspect_number'         => $idx + 1,
                    'suspect_name'           => $s['suspect_name'],
                    'suspect_alias'          => $s['suspect_alias'] ?? 'N/A',
                    'role'                   => $role,
                    'association_role'       => $role,
                    'associate_id'           => $s['associate_id'] ?? null,
                    'suspect_disposition'    => $s['suspect_disposition'] ?? 'Active — Under Investigation',
                    'disposition_reason'     => $s['disposition_reason'] ?? null,
                    'disposition_updated_at' => $s['disposition_updated_at'] ?? null,
                    'disposition_updated_by' => $s['disposition_updated_by'] ?? null,
                    'ghana_card'             => $s['ghana_card'] ?? null,
                    'gender'                 => $s['gender'] ?? null,
                    'dob'                    => $s['dob'] ?? null,
                    'phone'                  => $s['phone_number'] ?? ($s['phone'] ?? null),
                    'holding_facility'       => $s['holding_facility'] ?? ($s['facility'] ?? ($this->holding_facility ?? null)),
                    'raid_location'          => $s['raid_location'] ?? ($s['location'] ?? ($this->raid_location ?? null)),
                    'offense_description'    => $s['offense_description'] ?? ($s['offense'] ?? ($this->offense_description ?? null)),
                    'date_of_arrest'         => $s['date_of_arrest'] ?? ($s['arrest_date'] ?? ($this->arrest_date ?? null)),
                    'arrest_date'            => $s['arrest_date'] ?? ($s['date_of_arrest'] ?? ($this->arrest_date ?? null)),
                    'time_of_arrest'         => $s['time_of_arrest'] ?? ($s['arrest_time'] ?? ($this->arrest_time ?? null)),
                    'arrest_time'            => $s['arrest_time'] ?? ($s['time_of_arrest'] ?? ($this->arrest_time ?? null)),
                    'mugshot_front'          => $s['mugshot_front'] ?? null,
                    'is_associate'           => !empty($s['associate_id']) || (!empty($s['is_associate']) && $s['is_associate'] !== false) || strtolower($role) !== 'primary suspect',
                ];
            }
            if (!empty($list)) return $list;
        }

        // 3. Fallback to single suspect on case model
        $primaryDisp = $this->suspect_disposition ?? ($this->underlyingRecord->suspect_disposition ?? 'Active — Under Investigation');
        $list[] = [
            'id'                     => $this->id,
            'suspect_number'         => 1,
            'suspect_name'           => $this->suspect_name ?? $this->title,
            'suspect_alias'          => $this->suspect_alias ?? 'N/A',
            'role'                   => 'Primary Suspect',
            'association_role'       => 'Primary Suspect',
            'associate_id'           => null,
            'suspect_disposition'    => $primaryDisp,
            'disposition_reason'     => $this->disposition_reason ?? ($this->underlyingRecord->disposition_reason ?? null),
            'disposition_updated_at' => $this->disposition_updated_at ?? ($this->underlyingRecord->disposition_updated_at ?? null),
            'disposition_updated_by' => $this->disposition_updated_by ?? ($this->underlyingRecord->disposition_updated_by ?? null),
            'ghana_card'             => $this->ghana_card ?? null,
            'gender'                 => $this->gender ?? null,
            'dob'                    => $this->dob ?? null,
            'phone'                  => $this->phone ?? null,
            'holding_facility'       => $this->holding_facility ?? null,
            'raid_location'          => $this->raid_location ?? null,
            'offense_description'    => $this->offense_description ?? null,
            'date_of_arrest'         => $this->arrest_date ?? null,
            'arrest_date'            => $this->arrest_date ?? null,
            'time_of_arrest'         => $this->arrest_time ?? null,
            'arrest_time'            => $this->arrest_time ?? null,
            'mugshot_front'          => $this->mugshot_front ?? null,
            'is_associate'           => false,
        ];

        return $list;
    }

    public function getActiveSuspects(): array
    {
        return array_values(array_filter($this->getSuspectsList(), function($s) {
            return ($s['suspect_disposition'] ?? 'Active — Under Investigation') === 'Active — Under Investigation';
        }));
    }

    public function getClearedSuspects(): array
    {
        return array_values(array_filter($this->getSuspectsList(), function($s) {
            return ($s['suspect_disposition'] ?? 'Active — Under Investigation') !== 'Active — Under Investigation';
        }));
    }

    /**
     * Retrieve specific exhibit records linked to a suspect (by DB ID, associate ID, or suspect name).
     * Enforces strict suspect-to-exhibit relationship and prevents cross-contamination.
     */
    public function getExhibitsForSuspect($suspectName, $suspectDbId = null, $associateId = null, $suspectRole = null): array
    {
        $matchedExhibits = [];
        $seenKeys = [];

        $cleanTargetName = strtolower(trim($suspectName ?? ''));
        $cleanAssocId   = !empty($associateId) ? strtolower(trim($associateId)) : null;
        $cleanRole      = !empty($suspectRole) ? strtolower(trim($suspectRole)) : '';
        $isPrimaryRole  = !empty($cleanRole) && str_contains($cleanRole, 'primary');

        // 1. Relational Exhibit records from 'exhibits' DB table
        if (!empty($this->id)) {
            $dbExhibits = Exhibit::where('multiple_suspect_case_id', $this->id)
                ->orWhere('single_suspect_case_id', $this->id)
                ->get();

            foreach ($dbExhibits as $ex) {
                $exSuspectId = $ex->suspect_id;
                $exOwners    = array_filter([
                    $ex->suspect ? $ex->suspect->suspect_name : null,
                    $ex->veh_owner ?? null,
                    $ex->wpn_owner ?? null,
                    $ex->dev_owner ?? null,
                    $ex->seizure_officer ?? null,
                    $ex->associated_person ?? null,
                    $ex->associated_suspect ?? null,
                ]);

                $isMatch = false;

                // Explicit DB Foreign Key match
                if (!empty($exSuspectId) && !empty($suspectDbId) && (int)$exSuspectId === (int)$suspectDbId) {
                    $isMatch = true;
                }

                // Name / Owner String matching
                if (!$isMatch && !empty($cleanTargetName)) {
                    foreach ($exOwners as $ownerStr) {
                        $cleanOwner = strtolower(trim($ownerStr));
                        if ($cleanOwner === $cleanTargetName || str_contains($cleanOwner, $cleanTargetName) || str_contains($cleanTargetName, $cleanOwner)) {
                            $isMatch = true;
                            break;
                        }
                    }
                }

                // Fallback matching for single-suspect case or Primary Suspect when exhibit has no explicit suspect_id or specific owner assigned to someone else
                if (!$isMatch && empty($exSuspectId)) {
                    $hasOtherSpecificOwner = false;
                    $suspectsList = $this->getSuspectsList();
                    $otherSuspectNames = [];
                    foreach ($suspectsList as $sItem) {
                        $sNameClean = strtolower(trim($sItem['suspect_name'] ?? ''));
                        if (!empty($sNameClean) && $sNameClean !== $cleanTargetName) {
                            $otherSuspectNames[] = $sNameClean;
                        }
                    }

                    foreach ($exOwners as $ownerStr) {
                        $cleanOwner = strtolower(trim($ownerStr));
                        if (empty($cleanOwner)) continue;
                        foreach ($otherSuspectNames as $otherName) {
                            if ($cleanOwner === $otherName || str_contains($cleanOwner, $otherName)) {
                                $hasOtherSpecificOwner = true;
                                break 2;
                            }
                        }
                    }

                    if (!$hasOtherSpecificOwner) {
                        if ($isPrimaryRole || count($suspectsList) <= 1) {
                            $isMatch = true;
                        }
                    }
                }

                // Reject if suspect_id belongs to ANOTHER suspect (unless explicitly shared in owner string)
                if ($isMatch && !empty($exSuspectId) && !empty($suspectDbId) && (int)$exSuspectId !== (int)$suspectDbId) {
                    $isExplicitlyShared = false;
                    foreach ($exOwners as $ownerStr) {
                        if (str_contains(strtolower($ownerStr), $cleanTargetName)) {
                            $isExplicitlyShared = true;
                            break;
                        }
                    }
                    if (!$isExplicitlyShared) {
                        $isMatch = false;
                    }
                }

                $exNo = $ex->exhibit_number;
                if (!empty($exNo)) {
                    $seenKeys['all_db_ex_no_' . strtolower(trim($exNo))] = true;
                }
                if (!empty($ex->exhibit_category) && !empty($ex->commodity_name)) {
                    $catNameKey = strtolower(trim($ex->exhibit_category)) . '_' . strtolower(trim($ex->commodity_name));
                    $seenKeys['all_db_catname_' . $catNameKey] = true;
                }

                if ($isMatch) {
                    if (!empty($exNo)) {
                        $seenKeys['processed_ex_no_' . strtolower(trim($exNo))] = true;
                    }
                    $catNameKey = strtolower(trim($ex->exhibit_category ?? '')) . '_' . strtolower(trim($ex->commodity_name ?? ''));
                    $seenKeys['processed_catname_' . $catNameKey] = true;

                    $key = 'db_' . $ex->id;
                    if (!isset($seenKeys[$key])) {
                        $seenKeys[$key] = true;
                        $arr = $ex->toArray();
                        $arr['category'] = $ex->exhibit_category;
                        $arr['seizure_name'] = $ex->commodity_name;
                        $arr['weapon_model'] = $ex->wpn_model;
                        $arr['weapon_serial_number'] = $ex->wpn_serial_no;
                        $arr['weapon_calibre'] = $ex->wpn_calibre;
                        $arr['weapon_color'] = $ex->wpn_colour;
                        $arr['weapon_size_length'] = $ex->wpn_size;

                        // Map vehicle attributes
                        $arr['veh_make']        = $ex->veh_make;
                        $arr['veh_model']       = $ex->veh_model;
                        $arr['veh_type']        = $ex->veh_type;
                        $arr['veh_year']        = $ex->veh_year;
                        $arr['veh_colour']      = $ex->veh_colour;
                        $arr['veh_reg_no']      = $ex->veh_reg_no;
                        $arr['veh_vin']         = $ex->veh_vin;
                        $arr['veh_engine_no']   = $ex->veh_engine_no;
                        $arr['veh_country_reg'] = $ex->veh_country_reg;
                        $arr['veh_owner']       = $ex->veh_owner;
                        $arr['veh_driver']      = $ex->veh_driver;

                        $matchedExhibits[] = $arr;
                    }
                }
            }
        }

        // 2. JSON Docket Data exhibit arrays
        $dData = is_array($this->docket_data)
            ? $this->docket_data
            : (json_decode($this->docket_data ?? '[]', true) ?: []);

        $allDocketExhibits = array_merge(
            $dData['associate_exhibits']  ?? [],
            $dData['associated_seizures'] ?? [],
            $dData['seizures']            ?? []
        );

        foreach ($allDocketExhibits as $dIdx => $sz) {
            if (!is_array($sz)) continue;

            $cat  = $sz['category'] ?? ($sz['exhibit_category'] ?? '');
            $name = $sz['seizure_name'] ?? ($sz['commodity_name'] ?? ($sz['name'] ?? ($sz['seizure_name_drug'] ?? '')));
            $exNo = $sz['exhibit_number'] ?? null;

            if (!empty($exNo) && (isset($seenKeys['processed_ex_no_' . strtolower(trim($exNo))]) || isset($seenKeys['all_db_ex_no_' . strtolower(trim($exNo))]))) {
                continue; // Authoritatively handled by DB Exhibit record
            }
            $catNameKey = strtolower(trim($cat)) . '_' . strtolower(trim($name));
            if (!empty($cat) && !empty($name) && (isset($seenKeys['processed_catname_' . $catNameKey]) || isset($seenKeys['all_db_catname_' . $catNameKey]))) {
                continue; // Authoritatively handled by DB Exhibit record
            }

            $szOwner = strtolower(trim($sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['associated_suspect'] ?? ($sz['suspect_ownership'] ?? '')))));
            $szSuspectId = $sz['suspect_id'] ?? null;
            $szAssocId   = $sz['associate_id'] ?? null;

            $isMatch = false;

            // DB / Numeric ID match
            if (!empty($szSuspectId) && !empty($suspectDbId) && (int)$szSuspectId === (int)$suspectDbId) {
                $isMatch = true;
            }

            // Associate ID match (ONLY if owner name matches or is empty/generic)
            if (!$isMatch && !empty($szAssocId) && !empty($cleanAssocId) && strtolower(trim($szAssocId)) === $cleanAssocId) {
                if (empty($szOwner) || $szOwner === 'associate suspect' || $szOwner === 'associate' || $szOwner === $cleanTargetName || str_contains($szOwner, $cleanTargetName)) {
                    $isMatch = true;
                }
            }

            // Name match
            if (!$isMatch && !empty($cleanTargetName) && !empty($szOwner)) {
                if ($szOwner === $cleanTargetName || str_contains($szOwner, $cleanTargetName) || str_contains($cleanTargetName, $szOwner)) {
                    $isMatch = true;
                }
            }

            // Shared / Joint exhibit check
            if (!$isMatch && !empty($szOwner)) {
                if (str_contains($szOwner, 'all suspect') || str_contains($szOwner, 'joint')) {
                    $isMatch = true;
                }
            }

            // Fallback matching for docket exhibits without explicit suspect ID
            if (!$isMatch && empty($szSuspectId)) {
                if (empty($szOwner) || $szOwner === 'primary suspect' || $szOwner === 'primary' || $szOwner === 'unassigned' || $szOwner === 'n/a') {
                    if ($isPrimaryRole || count($this->getSuspectsList()) <= 1) {
                        $isMatch = true;
                    }
                }
            }

            // Reject if exhibit explicitly belongs to a DIFFERENT suspect name or DB ID
            if ($isMatch && !empty($szOwner) && $szOwner !== 'primary suspect' && $szOwner !== 'associate suspect' && $szOwner !== 'joint suspects' && !str_contains($szOwner, 'all suspect')) {
                if ($szOwner !== $cleanTargetName && !str_contains($szOwner, $cleanTargetName)) {
                    $isMatch = false;
                }
            }
            if ($isMatch && !empty($szSuspectId) && !empty($suspectDbId) && (int)$szSuspectId !== (int)$suspectDbId) {
                if (empty($szOwner) || ($szOwner !== $cleanTargetName && !str_contains($szOwner, $cleanTargetName))) {
                    $isMatch = false;
                }
            }

            if ($isMatch) {
                $cat  = $sz['category'] ?? ($sz['exhibit_category'] ?? '');
                $name = $sz['seizure_name'] ?? ($sz['commodity_name'] ?? ($sz['name'] ?? ($sz['seizure_name_drug'] ?? '')));
                $key  = 'docket_' . strtolower(trim($cat)) . '_' . strtolower(trim($name)) . '_' . ($sz['exhibit_number'] ?? $dIdx);

                if (!isset($seenKeys[$key])) {
                    $seenKeys[$key] = true;
                    $matchedExhibits[] = $sz;
                }
            }
        }

        // 3. Check docket_data['suspects'] for suspect-level exhibit arrays
        $suspectsInDocket = $dData['suspects'] ?? [];
        if (is_array($suspectsInDocket)) {
            foreach ($suspectsInDocket as $sDocket) {
                if (!is_array($sDocket)) continue;
                $sNameInDocket = strtolower(trim($sDocket['suspect_name'] ?? ''));
                $sAssocIdInDocket = strtolower(trim($sDocket['associate_id'] ?? ''));

                $isSuspectMatch = false;
                if (!empty($cleanTargetName) && !empty($sNameInDocket) && ($sNameInDocket === $cleanTargetName || str_contains($sNameInDocket, $cleanTargetName) || str_contains($cleanTargetName, $sNameInDocket))) {
                    $isSuspectMatch = true;
                }
                if (!$isSuspectMatch && !empty($cleanAssocId) && !empty($sAssocIdInDocket) && $sAssocIdInDocket === $cleanAssocId) {
                    $isSuspectMatch = true;
                }

                if ($isSuspectMatch) {
                    $sExhibits = array_merge(
                        $sDocket['exhibits'] ?? [],
                        $sDocket['seizures'] ?? [],
                        $sDocket['associated_seizures'] ?? [],
                        $sDocket['associate_exhibits'] ?? []
                    );
                    foreach ($sExhibits as $sExIdx => $sEx) {
                        if (!is_array($sEx)) continue;
                        $cat  = $sEx['category'] ?? ($sEx['exhibit_category'] ?? 'Associate Exhibit');
                        $name = $sEx['seizure_name'] ?? ($sEx['commodity_name'] ?? ($sEx['name'] ?? 'Exhibit Item'));
                        $key  = 's_docket_' . strtolower(trim($cat)) . '_' . strtolower(trim($name)) . '_' . ($sEx['exhibit_number'] ?? $sExIdx);
                        if (!isset($seenKeys[$key])) {
                            $seenKeys[$key] = true;
                            $matchedExhibits[] = $sEx;
                        }
                    }
                }
            }
        }

        return $matchedExhibits;
    }

    /**
     * Retrieve laboratory examination results linked to a specific suspect.
     */
    public function getLabResultsForSuspect($suspectName, $suspectDbId = null, $associateId = null): array
    {
        $results = [];
        $seenKeys = [];
        $cleanTargetName = strtolower(trim($suspectName ?? ''));
        $cleanAssocId   = !empty($associateId) ? strtolower(trim($associateId)) : null;

        // 1. Relational LaboratoryExaminationResult DB records
        if (!empty($this->id)) {
            $labRecords = LaboratoryExaminationResult::where('multiple_suspect_case_id', $this->id)
                ->orWhere('single_suspect_case_id', $this->id)
                ->get();

            foreach ($labRecords as $lr) {
                $isMatch = false;
                if (!empty($lr->suspect_id) && !empty($suspectDbId) && (int)$lr->suspect_id === (int)$suspectDbId) {
                    $isMatch = true;
                }
                if (!$isMatch && !empty($cleanTargetName)) {
                    $exRecord = $lr->exhibit;
                    if ($exRecord) {
                        $exSuspectId = $exRecord->suspect_id;
                        if (!empty($exSuspectId) && !empty($suspectDbId) && (int)$exSuspectId === (int)$suspectDbId) {
                            $isMatch = true;
                        }
                    }
                }
                if ($isMatch) {
                    $key = 'db_lab_' . $lr->id;
                    if (!isset($seenKeys[$key])) {
                        $seenKeys[$key] = true;
                        $results[] = $lr->toArray();
                    }
                }
            }
        }

        // 2. AssociateLabExamination DB records
        if (!empty($this->id)) {
            $assocLabs = AssociateLabExamination::where('case_id', $this->id)->get();
            foreach ($assocLabs as $al) {
                $isMatch = false;
                if (!empty($cleanTargetName) && strtolower(trim($al->suspect_name)) === $cleanTargetName) {
                    $isMatch = true;
                }
                if (!$isMatch && !empty($cleanAssocId) && !empty($al->associate_id) && strtolower(trim($al->associate_id)) === $cleanAssocId) {
                    $isMatch = true;
                }
                if ($isMatch) {
                    $key = 'assoc_lab_' . $al->id;
                    if (!isset($seenKeys[$key])) {
                        $seenKeys[$key] = true;
                        $results[] = $al->toArray();
                    }
                }
            }
        }

        return $results;
    }
}

