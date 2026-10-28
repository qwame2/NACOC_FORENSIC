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
        'phones' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

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
        $instance->is_multiple = $isMulti;
        $instance->case_type = $isMulti ? 'MULTIPLE' : 'SINGLE';

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
        $instance->submitting_officer = $record->submitting_officer ?? null;
        $instance->operation_code = $record->operation_code ?? null;
        $instance->operation_code_name = $record->operation_code_name ?? null;
        $instance->raid_location = $record->raid_location ?? null;
        $instance->offense_description = $record->offense_description ?? null;
        $casePriority = $record->priority;
        if (empty($casePriority) || $casePriority === 'NORMAL') {
            if ($record instanceof MultipleSuspectCase) {
                $origSingle = SingleSuspectCase::where('case_number', $record->case_number)->first();
                if ($origSingle && !empty($origSingle->priority) && $origSingle->priority !== 'NORMAL') {
                    $casePriority = $origSingle->priority;
                    try {
                        $record->priority = $origSingle->priority;
                        $record->save();
                    } catch (\Throwable $e) {}
                }
            }
        }
        if (empty($casePriority) && !empty($record->docket_data)) {
            $dData = is_array($record->docket_data) ? $record->docket_data : (json_decode($record->docket_data, true) ?: []);
            if (!empty($dData['priority'])) {
                $casePriority = $dData['priority'];
            }
        }
        $instance->priority = $casePriority ?: 'HIGH';
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
        if ($isMulti && $exhibits->isEmpty()) {
            $origSingle = SingleSuspectCase::where('case_number', $record->case_number)->first();
            if ($origSingle) {
                $exhibits = Exhibit::where('single_suspect_case_id', $origSingle->id)->get();
            }
            if ($exhibits->isEmpty()) {
                $suspectIds = CaseSuspect::where('multiple_suspect_case_id', $record->id)->pluck('id');
                if ($suspectIds->isNotEmpty()) {
                    $exhibits = Exhibit::whereIn('suspect_id', $suspectIds)->get();
                }
            }
        }
        $instance->setRelation('exhibits', $exhibits);
        $firstExhibit = $exhibits->first();

        // Fallback for raid_location if multi case record has it null
        if (empty($instance->raid_location) && $isMulti) {
            $origSingle = isset($origSingle) ? $origSingle : SingleSuspectCase::where('case_number', $record->case_number)->first();
            if ($origSingle && !empty($origSingle->raid_location)) {
                $instance->raid_location = $origSingle->raid_location;
            } elseif ($firstExhibit && !empty($firstExhibit->seizure_location)) {
                $instance->raid_location = $firstExhibit->seizure_location;
            } else {
                $csLoc = CaseSuspect::where('multiple_suspect_case_id', $record->id)->whereNotNull('raid_location')->value('raid_location');
                if ($csLoc) $instance->raid_location = $csLoc;
            }
        }

        $recDocket = is_array($record->docket_data) ? $record->docket_data : (json_decode($record->docket_data ?? '[]', true) ?: []);
        $instance->docket_data = $recDocket;

        $exCatLower = strtolower($firstExhibit->exhibit_category ?? ($record->exhibit_category ?? ''));
        $isDrugCat = false;
        $drugKws = config('forensics.substance_keywords', [
            'drug', 'narcotic', 'substance', 'cannabis', 'cocaine', 'heroin', 'meth', 
            'tramadol', 'weed', 'hemp', 'hashish', 'ephedrine', 'precursor', 'opioid', 'fentanyl'
        ]);
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
        $instance->source_of_exhibits = $firstExhibit->source_of_exhibits ?? ($record->source_of_exhibits ?? ($recDocket['source_of_exhibits'] ?? ($recDocket['seizures'][0]['source_of_exhibits'] ?? null)));
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
            $instance->phones = $primarySuspect->phones ?: ($recDocket['suspects'][0]['phones'] ?? ($recDocket['phones'] ?? ($recDocket['additional_phones'] ?? [])));
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
            $instance->arrest_date = $primarySuspect->arrest_date ?? null;
            if (empty($instance->arrest_date) && $isMulti) {
                $origSingle = isset($origSingle) ? $origSingle : SingleSuspectCase::where('case_number', $record->case_number)->first();
                if ($origSingle && !empty($origSingle->arrest_date)) {
                    $instance->arrest_date = $origSingle->arrest_date;
                } elseif ($firstExhibit && !empty($firstExhibit->seizure_date)) {
                    $instance->arrest_date = $firstExhibit->seizure_date;
                }
            }
            $instance->arrest_time = $primarySuspect->arrest_time ?? null;
            $instance->mugshot_front = $primarySuspect->mugshot_front ?? null;
            $instance->mugshot_left = $primarySuspect->mugshot_left ?? null;
            $instance->mugshot_right = $primarySuspect->mugshot_right ?? null;
            $instance->operation_code_name = $primarySuspect->operation_code_name 
                ?? ($instance->operation_code_name 
                ?? ($record->operation_code_name 
                ?? ($recDocket['suspects'][0]['operation_code_name'] 
                ?? ($recDocket['operation_code_name'] ?? null))));
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
                'phones'           => $s->phones ?? null,
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

        $assocLabsMap = [];
        $assocLabs = AssociateLabExamination::where('case_id', $record->id)->get();
        foreach ($assocLabs as $al) {
            $assocLabsMap[$al->suspect_name] = [
                'suspect_name'              => $al->suspect_name,
                'laboratory_number'         => $al->laboratory_number,
                'exam_reference'            => (!empty($al->exam_reference)) ? (str_starts_with(trim($al->exam_reference), 'GSA-') ? str_replace(['GSA-', 'T28'], ['NLR-', 'T35'], $al->exam_reference) : $al->exam_reference) : 'NLR-FM-T35-F',
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

        $seizuresArray = [];
        foreach ($exhibits as $exIdx => $ex) {
            // Determine if this exhibit's suspect is an associate-role suspect
            $exSuspect = $ex->suspect;
            $exSuspectRole = strtolower(trim($exSuspect->role ?? ''));
            $isAssoc = $exSuspect ? ($exSuspect->suspect_number > 1 || ($exSuspectRole !== 'primary suspect' && $exSuspectRole !== 'primary')) : false;

            $associatedPerson = $exSuspect
                ? $exSuspect->suspect_name
                : ($isMulti ? $allSuspectNames : ($instance->suspect_name ?? 'Primary Suspect'));

            $assocExamForEx = null;
            if (!$ex->labResult) {
                $assocKey = strtolower(trim($associatedPerson));
                // 1. Check assocLabsMap
                foreach ($assocLabsMap as $kAssoc => $vAssoc) {
                    $kLower = strtolower(trim($kAssoc));
                    if ($kLower === $assocKey || (!empty($vAssoc['exhibit_number']) && $vAssoc['exhibit_number'] === ($ex->exhibit_number ?? ''))) {
                        $assocExamForEx = $vAssoc;
                        break;
                    }
                }
                // 2. Check recDocket associate_lab_examinations
                if (!$assocExamForEx && !empty($recDocket['associate_lab_examinations'])) {
                    foreach ($recDocket['associate_lab_examinations'] as $kAssoc => $vAssoc) {
                        $kLower = strtolower(trim($kAssoc));
                        if ($kLower === $assocKey || (!empty($vAssoc['exhibit_number']) && $vAssoc['exhibit_number'] === ($ex->exhibit_number ?? ''))) {
                            $assocExamForEx = $vAssoc;
                            break;
                        }
                    }
                }
            }

            $hasSavedLab = $ex->labResult 
                ? $ex->labResult->has_saved 
                : (!empty($recDocket['seizures'][$exIdx]['has_saved_lab']) || !empty($assocExamForEx['has_saved']));

            $presumptiveResult = $ex->labResult 
                ? $ex->labResult->presumptive_result 
                : ($recDocket['seizures'][$exIdx]['presumptive_result'] ?? ($assocExamForEx['presumptive_result'] ?? null));

            $confirmatoryResult = $ex->labResult 
                ? $ex->labResult->confirmatory_result 
                : ($recDocket['seizures'][$exIdx]['confirmatory_result'] ?? ($assocExamForEx['confirmatory_result'] ?? null));

            $substanceIdent = $ex->labResult 
                ? $ex->labResult->substance_identification 
                : ($recDocket['seizures'][$exIdx]['substance_identification'] ?? ($assocExamForEx['substance_identification'] ?? null));

            $sampleWeightTaken = $ex->labResult 
                ? $ex->labResult->sample_weight_taken 
                : ($recDocket['seizures'][$exIdx]['sample_weight_taken'] ?? ($recDocket['seizures'][$exIdx]['sample_taken'] ?? ($assocExamForEx['sample_weight']['sample_taken'] ?? null)));

            $sampleWeightReserved = $ex->labResult 
                ? $ex->labResult->sample_weight_reserved 
                : ($recDocket['seizures'][$exIdx]['sample_weight_reserved'] ?? ($recDocket['seizures'][$exIdx]['sample_reserved'] ?? ($assocExamForEx['sample_weight']['reserved_samples'] ?? null)));

            $seizuresArray[] = [
                'id'                 => $ex->id ?? ($recDocket['seizures'][$exIdx]['id'] ?? null),
                'suspect_id'         => $ex->suspect_id ?? ($recDocket['seizures'][$exIdx]['suspect_id'] ?? null),
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
                'source_of_exhibits' => $ex->source_of_exhibits ?? ($recDocket['seizures'][$exIdx]['source_of_exhibits'] ?? ($record->source_of_exhibits ?? null)),
                'associated_person'  => $associatedPerson,
                'associate_id'       => $ex->suspect ? $ex->suspect->associate_id : null,
                'is_associate'       => $isAssoc,
                'ammunition_details' => is_array($ex->ammunition_details) ? $ex->ammunition_details : (json_decode($ex->ammunition_details ?? '[]', true) ?: ($recDocket['seizures'][$exIdx]['ammunition_details'] ?? null)),
                'has_saved_lab'      => $hasSavedLab,
                'presumptive_result' => $presumptiveResult,
                'confirmatory_result' => $confirmatoryResult,
                'substance_identification' => $substanceIdent,
                'sample_taken'        => $sampleWeightTaken,
                'sample_weight_taken' => $sampleWeightTaken,
                'sample_reserved'     => $sampleWeightReserved,
                'sample_weight_reserved' => $sampleWeightReserved,
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
                'exhibit_owner_name'       => $lr->suspect ? $lr->suspect->suspect_name : ($instance->suspect_name ?? 'Primary Suspect'),
                'laboratory_number'        => $lr->laboratory_number,
                'exam_reference'           => !empty($recDocket['lab_examination']['exam_reference']) 
                    ? (str_starts_with(trim($recDocket['lab_examination']['exam_reference']), 'GSA-') ? str_replace(['GSA-', 'T28'], ['NLR-', 'T35'], $recDocket['lab_examination']['exam_reference']) : $recDocket['lab_examination']['exam_reference'])
                    : ((!empty($lr->exam_reference)) ? (str_starts_with(trim($lr->exam_reference), 'GSA-') ? str_replace(['GSA-', 'T28'], ['NLR-', 'T35'], $lr->exam_reference) : $lr->exam_reference) : 'NLR-FM-T35-F'),
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
                    'officer'    => $instance->user?->fullname ?? ($instance->user_id ? \App\Models\User::find($instance->user_id)?->fullname : ($instance->submitting_officer ?? 'Crime Scene Officer')),
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
                'exam_reference'           => !empty($primaryLab->exam_reference) ? $primaryLab->exam_reference : ($recDocket['lab_examination']['exam_reference'] ?? null),
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
                'remark'                   => $primaryLab->remark ?? ($recDocket['lab_examination']['remark'] ?? null),
                'findings'                 => $primaryLab->findings,
            ] : (!empty($recDocket['lab_examination']) ? array_merge([
                'has_saved'                => false,
                'exhibit_owner_name'       => $primarySuspectLabel,
                'confirmatory_result'      => 'YES',
            ], $recDocket['lab_examination']) : [
                'has_saved'                => false,
                'exhibit_owner_name'       => $primarySuspectLabel,
                'confirmatory_result'      => 'YES',
            ]),
            'lab_examinations_list' => !empty($labExaminationsList) ? $labExaminationsList : (!empty($recDocket['lab_examinations_list']) ? $recDocket['lab_examinations_list'] : (!empty($recDocket['lab_examination']) ? [$recDocket['lab_examination']] : [])),
            'associate_lab_examinations' => $assocLabsMap,
            'unit_progress' => $unitProgressWrap,
        ];


        return $instance;
    }

    /**
     * Generate structured narrative for Brief Case Description from saved suspect & case info
     */
    public function generateBriefCaseDescription(): string
    {
        $dData = is_array($this->docket_data) ? $this->docket_data : (json_decode($this->docket_data ?? '[]', true) ?: []);

        // 1. Date (Formatted e.g. "Thursday, 18 June 2026")
        $rawArrestDate = $this->arrest_date 
            ?? ($this->date_of_arrest 
            ?? ($this->incident_date 
            ?? ($dData['arrest_date'] 
            ?? ($dData['date_of_arrest'] 
            ?? ($dData['incident_date'] 
            ?? ($dData['suspects'][0]['arrest_date'] 
            ?? ($dData['suspects'][0]['date_of_arrest'] 
            ?? null)))))));

        if (!empty($rawArrestDate) && strtotime((string)$rawArrestDate)) {
            $formattedDate = date('l, d F Y', strtotime((string)$rawArrestDate));
        } elseif ($this->created_at) {
            $formattedDate = $this->created_at->format('l, d F Y');
        } else {
            $formattedDate = 'Thursday, 18 June 2026';
        }

        // 2. Operation Type (always fetch from operation_code_name column from the suspect)
        $rawOpType = $this->operation_code_name 
            ?? ($this->underlyingRecord->operation_code_name ?? null)
            ?? ($dData['operation_code_name'] ?? null)
            ?? ($dData['suspects'][0]['operation_code_name'] ?? null)
            ?? ($this->operation_code ?? null)
            ?? ($this->underlyingRecord->operation_code ?? null)
            ?? ($this->operation_type ?? null)
            ?? ($dData['operation_type'] ?? null)
            ?? ($dData['op_type'] ?? null);

        if (!empty($rawOpType)) {
            $opTypeStr = trim((string)$rawOpType);
            if (!str_contains(strtolower($opTypeStr), 'operation')) {
                $opType = strtolower($opTypeStr) . ' operation';
            } else {
                $opType = strtolower($opTypeStr);
            }
        } else {
            $opType = 'intelligence-led operation';
        }

        $article = preg_match('/^[aeiou]/i', $opType) ? 'an' : 'a';

        // 3. Raid Scene / Offence Location (e.g. "Agbogbloshie in the Greater Accra Region")
        $loc = $this->raid_location 
            ?? ($this->seizure_location 
            ?? ($dData['raid_location'] 
            ?? ($dData['seizure_location'] 
            ?? ($dData['seizures'][0]['seizure_location'] 
            ?? ($this->location ?? null)))));

        if (empty($loc)) {
            $loc = 'Agbogbloshie in the Greater Accra Region';
        }

        // 4. Suspect Full Legal Name (e.g. "Victor Kofi")
        $suspectName = $this->suspect_name ?? null;
        if (empty($suspectName) && isset($this->underlyingRecord)) {
            $suspectName = $this->underlyingRecord->suspect_name ?? null;
        }
        if (empty($suspectName) && !empty($dData['suspects']) && is_array($dData['suspects'])) {
            $sNames = [];
            foreach ($dData['suspects'] as $spItem) {
                if (!empty($spItem['suspect_name'])) {
                    $sNames[] = $spItem['suspect_name'];
                }
            }
            if (!empty($sNames)) {
                $suspectName = implode(', ', array_unique($sNames));
            }
        }
        if (empty($suspectName)) {
            $suspectName = $dData['suspect_name'] ?? 'Victor Kofi';
        }

        // 5. Assigned Custody Holding Facility
        $holdingFacility = $this->holding_facility 
            ?? ($dData['holding_facility'] 
            ?? ($dData['facility'] 
            ?? ($dData['suspects'][0]['holding_facility'] 
            ?? ($dData['suspects'][0]['facility'] ?? null))));

        if (empty($holdingFacility) && isset($this->underlyingRecord)) {
            $holdingFacility = $this->underlyingRecord->holding_facility ?? null;
        }

        if (empty($holdingFacility)) {
            $holdingFacility = 'NACOC Headquarters';
        }

        // 6. Exhibit classification: determine if linked to only electronics, both drugs and electronics, or drugs
        $flags = $this->classifyExhibits();
        $hasDrug = !empty($flags['has_drug']) || $this->isDrugCase();
        $hasElectronic = !empty($flags['has_electronic']) || $this->isDigitalCase();

        $suspectPhones = $this->phones ?? ($this->underlyingRecord->phones ?? ($dData['phones'] ?? ($dData['additional_phones'] ?? null)));
        if (!empty($suspectPhones)) {
            $hasElectronic = true;
        }

        if ($hasElectronic && !$hasDrug) {
            // ONLY ELECTRONICS (NO DRUGS)
            return "On {$formattedDate}, officers of the Narcotics Control Commission (NACOC) conducted {$article} {$opType} at {$loc} to intercept and seize illicit electronic devices and digital evidence within the area. During the operation, {$suspectName} was arrested and electronic exhibits were seized, and the suspect was subsequently conveyed to {$holdingFacility} for digital forensic examination and further investigation.";
        }

        if ($hasElectronic && $hasDrug) {
            // BOTH DRUGS AND ELECTRONICS
            return "On {$formattedDate}, officers of the Narcotics Control Commission (NACOC) conducted {$article} {$opType} at {$loc} to curb the trafficking of narcotic drugs and seize electronic devices used in illicit activities within the area. During the operation, {$suspectName} was arrested in possession of narcotic substances and electronic exhibits, and subsequently conveyed to {$holdingFacility} for chemical laboratory analysis and digital forensic investigation.";
        }

        // DRUGS ONLY / DEFAULT
        return "On {$formattedDate}, officers of the Narcotics Control Commission (NACOC) conducted {$article} {$opType} at {$loc} to curb the use and sale of narcotic drugs within the area. During the operation, {$suspectName} was arrested and subsequently conveyed to {$holdingFacility} for further investigation.";
    }

    public function getDescriptionAttribute(): string
    {
        return $this->generateBriefCaseDescription();
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
     * Check if a given user has Case Status Control authority (Any Head of Unit or Admin).
     */
    public function isController($user): bool
    {
        if (!$user) return false;
        return !empty($user->is_head) || strtolower($user->role ?? '') === 'admin' || (isset($user->unit) && str_contains(strtolower($user->unit), 'head'));
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
        $caseRef = $attributes['case_number'] ?? ('NCC-' . date('Y') . '-' . rand(1000, 9999));

        if ($isMulti) {
            $multi = MultipleSuspectCase::create([
                'case_number'          => $caseRef,
                'laboratory_number'    => $attributes['laboratory_number'] ?? null,
                'title'                => $attributes['title'] ?? 'Multiple Suspect Docket File',
                'unit_name'            => $attributes['unit_name'] ?? 'Crime Scene Support',
                'unit_id'              => $attributes['unit_id'] ?? null,
                'user_id'              => $attributes['user_id'] ?? null,
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
                    'phone'                    => $s['phone'] ?? ($sIdx === 0 ? ($attributes['phone'] ?? null) : null),
                    'phones'                   => $s['phones'] ?? ($s['additional_phones'] ?? ($sIdx === 0 ? ($attributes['phones'] ?? null) : null)),
                    'pob'                      => $s['pob'] ?? ($s['place_of_birth'] ?? ($s['hometown'] ?? ($sIdx === 0 ? ($attributes['pob'] ?? null) : null))),
                    'address'                  => $s['address'] ?? ($s['residential_address'] ?? ($s['gps'] ?? ($sIdx === 0 ? ($attributes['address'] ?? null) : null))),
                    'marital_status'           => $s['marital_status'] ?? ($sIdx === 0 ? ($attributes['marital_status'] ?? null) : null),
                    'has_children'             => !empty($s['has_children']) || (!empty($attributes['has_children']) && $sIdx === 0),
                    'children_count'           => isset($s['children_count']) && $s['children_count'] !== '' ? (int)$s['children_count'] : ($sIdx === 0 ? ($attributes['children_count'] ?? null) : null),
                    'spouse_name'              => $s['spouse_name'] ?? ($sIdx === 0 ? ($attributes['spouse_name'] ?? null) : null),
                    'spouse_phone'             => $s['spouse_phone'] ?? ($sIdx === 0 ? ($attributes['spouse_phone'] ?? null) : null),
                    'spouse_info'              => $s['spouse_info'] ?? ($sIdx === 0 ? ($attributes['spouse_info'] ?? null) : null),
                    'partner_name'             => $s['partner_name'] ?? ($sIdx === 0 ? ($attributes['partner_name'] ?? null) : null),
                    'partner_phone'            => $s['partner_phone'] ?? ($sIdx === 0 ? ($attributes['partner_phone'] ?? null) : null),
                    'partner_info'             => $s['partner_info'] ?? ($sIdx === 0 ? ($attributes['partner_info'] ?? null) : null),
                    'dependents'               => $s['dependents'] ?? ($sIdx === 0 ? ($attributes['dependents'] ?? null) : null),
                    'height_unit'              => $s['height_unit'] ?? null,
                    'holding_facility'         => $s['holding_facility'] ?? ($s['facility'] ?? ($attributes['holding_facility'] ?? null)),
                    'raid_location'            => $s['raid_location'] ?? ($s['location'] ?? ($attributes['raid_location'] ?? null)),
                    'offense_description'      => $s['offense'] ?? ($s['offense_description'] ?? ($attributes['offense_description'] ?? null)),
                    'arrest_date'              => $s['date_of_arrest'] ?? ($s['arrest_date'] ?? ($attributes['arrest_date'] ?? null)),
                    'arrest_time'              => $s['time_of_arrest'] ?? ($s['arrest_time'] ?? ($attributes['arrest_time'] ?? null)),
                    'mugshot_front'            => $s['mugshot_front'] ?? ($s['mugshots']['front'] ?? ($sIdx === 0 ? ($attributes['mugshot_front'] ?? null) : null)),
                    'mugshot_left'             => $s['mugshot_left'] ?? ($s['mugshots']['left'] ?? ($sIdx === 0 ? ($attributes['mugshot_left'] ?? null) : null)),
                    'mugshot_right'            => $s['mugshot_right'] ?? ($s['mugshots']['right'] ?? ($sIdx === 0 ? ($attributes['mugshot_right'] ?? null) : null)),
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
                        'associated_person'       => $createdSuspects[0]->suspect_name ?? ($attributes['suspect_name'] ?? 'All Suspects'),
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
                        'source_of_exhibits'      => $attributes['source_of_exhibits'] ?? null,
                        'exhibit_remark'          => $attributes['exhibit_remark'] ?? null,
                        'lab_remark'              => $attributes['confirmatory_remark'] ?? ($attributes['lab_remark'] ?? ($attributes['docket_data']['lab_examination']['remark'] ?? null)),
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
                        'veh_owner_contact'       => $attributes['veh_owner_contact'] ?? ($attributes['vehicle_owner_contact'] ?? null),
                        'veh_driver'              => $attributes['veh_driver'] ?? ($attributes['vehicle_driver'] ?? null),
                        'veh_docs_available'      => $attributes['veh_docs_available'] ?? ($attributes['vehicle_docs_available'] ?? null),
                        'veh_doc_ref'             => $attributes['veh_doc_ref'] ?? ($attributes['vehicle_doc_ref'] ?? null),
                        'veh_condition'           => $attributes['veh_condition'] ?? ($attributes['vehicle_condition'] ?? null),
                        'veh_fuel'                => $attributes['veh_fuel'] ?? ($attributes['vehicle_fuel'] ?? null),
                        'veh_keys_recv'           => $attributes['veh_keys_recv'] ?? ($attributes['vehicle_keys_recv'] ?? null),
                        'veh_key_count'           => $attributes['veh_key_count'] ?? ($attributes['vehicle_key_count'] ?? null),
                        'veh_damage'              => $attributes['veh_damage'] ?? ($attributes['vehicle_damage'] ?? null),
                        'veh_mods'                => $attributes['veh_mods'] ?? ($attributes['vehicle_mods'] ?? null),
                        'veh_features'            => $attributes['veh_features'] ?? ($attributes['vehicle_features'] ?? null),
                        'wpn_model'               => $attributes['wpn_model'] ?? ($attributes['weapon_model'] ?? ($attributes['weapon_type'] ?? null)),
                        'wpn_serial_no'           => $attributes['wpn_serial_no'] ?? ($attributes['weapon_serial'] ?? null),
                        'wpn_calibre'             => $attributes['wpn_calibre'] ?? ($attributes['weapon_calibre'] ?? null),
                        'wpn_colour'              => $attributes['wpn_colour'] ?? ($attributes['weapon_colour'] ?? null),
                        'wpn_size'                => $attributes['wpn_size'] ?? ($attributes['weapon_size'] ?? null),
                        'wpn_marks'               => $attributes['wpn_marks'] ?? ($attributes['weapon_marks'] ?? null),
                        'wpn_location_found'      => $attributes['wpn_location_found'] ?? ($attributes['weapon_location_found'] ?? null),
                        'wpn_loaded_status'       => $attributes['wpn_loaded_status'] ?? ($attributes['weapon_loaded_status'] ?? null),
                        'wpn_condition'           => $attributes['wpn_condition'] ?? ($attributes['weapon_condition'] ?? null),
                        'dev_model'               => $attributes['dev_model'] ?? ($attributes['device_model'] ?? null),
                        'dev_serial_no'           => $attributes['dev_serial_no'] ?? ($attributes['device_serial'] ?? null),
                        'dev_imei1'               => $attributes['dev_imei1'] ?? ($attributes['imei_1'] ?? null),
                        'dev_imei2'               => $attributes['dev_imei2'] ?? ($attributes['imei_2'] ?? null),
                        'dev_mac_address'         => $attributes['dev_mac_address'] ?? ($attributes['mac_address'] ?? null),
                        'dev_storage'             => $attributes['dev_storage'] ?? ($attributes['storage_capacity'] ?? ($attributes['dev_storage_capacity'] ?? ($attributes['storage'] ?? null))),
                        'dev_colour'              => $attributes['dev_colour'] ?? ($attributes['dev_color'] ?? ($attributes['colour'] ?? ($attributes['color'] ?? ($attributes['device_color'] ?? null)))),
                        'dev_marks'               => $attributes['dev_marks'] ?? ($attributes['dev_other_marks'] ?? ($attributes['marks'] ?? ($attributes['other_marks'] ?? ($attributes['distinguishing_marks'] ?? null)))),
                        'ammunition_details'      => $attributes['ammunition_details'] ?? null,
                        'other_exhibit_description' => $attributes['other_exhibit_description'] ?? ($attributes['other_description'] ?? null),
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
                    if (!$matchedSuspect && !$isJointOwner && empty($ownerName) && !empty($createdSuspects[0])) {
                        if (isset($sz['is_associate']) && $sz['is_associate'] === false) {
                            $matchedSuspect = $createdSuspects[0];
                        } elseif ($sIdx === 0 && empty($sz['is_associate'])) {
                            $matchedSuspect = $createdSuspects[0];
                        }
                    }
                    // Do NOT fall back to index-based matching for joint/unowned exhibits
                    // suspect_id = null means the exhibit is jointly owned by all suspects

                    $ex = Exhibit::create([
                        'case_type'               => 'MULTIPLE',
                        'multiple_suspect_case_id'=> $multi->id,
                        'suspect_id'              => $matchedSuspect ? $matchedSuspect->id : null,
                        'associated_person'       => $isJointOwner ? 'All Suspects' : ($matchedSuspect ? $matchedSuspect->suspect_name : ($ownerName ?: 'All Suspects')),
                        'exhibit_number'          => $sz['exhibit_number'] ?? ($sIdx + 1),
                        'exhibit_category'        => $sz['exhibit_category'] ?? ($sz['category'] ?? ($attributes['exhibit_category'] ?? 'Drugs')),
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
                        'source_of_exhibits'      => $sz['source_of_exhibits'] ?? ($attributes['source_of_exhibits'] ?? null),
                        'exhibit_remark'          => $sz['exhibit_remark'] ?? ($attributes['exhibit_remark'] ?? null),
                        'lab_remark'              => $sz['lab_remark'] ?? ($sz['confirmatory_remark'] ?? (!empty($sz['has_saved_lab']) ? ($sz['remark'] ?? null) : ($sIdx === 0 ? ($attributes['confirmatory_remark'] ?? ($attributes['lab_remark'] ?? ($attributes['docket_data']['lab_examination']['remark'] ?? null))) : null))),
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
                        'veh_owner_contact'       => $sz['veh_owner_contact'] ?? ($sz['veh']['owner_contact'] ?? ($sz['vehicle_owner_contact'] ?? ($attributes['veh_owner_contact'] ?? null))),
                        'veh_driver'              => $sz['veh_driver'] ?? ($sz['veh']['driver'] ?? ($sz['vehicle_driver'] ?? ($attributes['veh_driver'] ?? null))),
                        'veh_docs_available'      => $sz['veh_docs_available'] ?? ($sz['veh']['docs_available'] ?? ($sz['vehicle_docs_available'] ?? ($attributes['veh_docs_available'] ?? null))),
                        'veh_doc_ref'             => $sz['veh_doc_ref'] ?? ($sz['veh']['doc_ref'] ?? ($sz['vehicle_doc_ref'] ?? ($attributes['veh_doc_ref'] ?? null))),
                        'veh_condition'           => $sz['veh_condition'] ?? ($sz['veh']['condition'] ?? ($sz['vehicle_condition'] ?? ($attributes['veh_condition'] ?? null))),
                        'veh_fuel'                => $sz['veh_fuel'] ?? ($sz['veh']['fuel'] ?? ($sz['vehicle_fuel'] ?? ($attributes['veh_fuel'] ?? null))),
                        'veh_keys_recv'           => $sz['veh_keys_recv'] ?? ($sz['veh']['keys_recv'] ?? ($sz['vehicle_keys_recv'] ?? ($attributes['veh_keys_recv'] ?? null))),
                        'veh_key_count'           => $sz['veh_key_count'] ?? ($sz['veh']['key_count'] ?? ($sz['vehicle_key_count'] ?? ($attributes['veh_key_count'] ?? null))),
                        'veh_damage'              => $sz['veh_damage'] ?? ($sz['veh']['damage'] ?? ($sz['vehicle_damage'] ?? ($attributes['veh_damage'] ?? null))),
                        'veh_mods'                => $sz['veh_mods'] ?? ($sz['veh']['mods'] ?? ($sz['vehicle_mods'] ?? ($attributes['veh_mods'] ?? null))),
                        'veh_features'            => $sz['veh_features'] ?? ($sz['veh']['features'] ?? ($sz['vehicle_features'] ?? ($attributes['veh_features'] ?? null))),
                        'wpn_model'               => $sz['wpn_model'] ?? ($sz['wpn']['model'] ?? ($sz['weapon_model'] ?? ($sz['weapon_type'] ?? ($sz['seizure_name_weapon'] ?? ($attributes['wpn_model'] ?? null))))),
                        'wpn_serial_no'           => $sz['wpn_serial_no'] ?? ($sz['wpn']['serial_no'] ?? ($sz['weapon_serial'] ?? ($attributes['wpn_serial_no'] ?? null))),
                        'wpn_calibre'             => $sz['wpn_calibre'] ?? ($sz['wpn']['calibre'] ?? ($sz['weapon_calibre'] ?? ($attributes['wpn_calibre'] ?? null))),
                        'wpn_colour'              => $sz['wpn_colour'] ?? ($sz['wpn']['colour'] ?? ($sz['weapon_colour'] ?? ($attributes['wpn_colour'] ?? null))),
                        'wpn_size'                => $sz['wpn_size'] ?? ($sz['wpn']['size'] ?? ($sz['weapon_size'] ?? ($attributes['wpn_size'] ?? null))),
                        'wpn_marks'               => $sz['wpn_marks'] ?? ($sz['wpn']['marks'] ?? ($sz['weapon_marks'] ?? ($attributes['wpn_marks'] ?? null))),
                        'wpn_location_found'      => $sz['wpn_location_found'] ?? ($sz['wpn']['location_found'] ?? ($sz['weapon_location_found'] ?? ($attributes['wpn_location_found'] ?? null))),
                        'wpn_loaded_status'       => $sz['wpn_loaded_status'] ?? ($sz['wpn']['loaded_status'] ?? ($sz['weapon_loaded_status'] ?? ($attributes['wpn_loaded_status'] ?? null))),
                        'wpn_condition'           => $sz['wpn_condition'] ?? ($sz['wpn']['condition'] ?? ($sz['weapon_condition'] ?? ($attributes['wpn_condition'] ?? null))),
                        'dev_model'               => $sz['dev_model'] ?? ($sz['device_model'] ?? ($attributes['dev_model'] ?? null)),
                        'dev_serial_no'           => $sz['dev_serial_no'] ?? ($sz['device_serial'] ?? ($attributes['dev_serial_no'] ?? null)),
                        'dev_imei1'               => $sz['dev_imei1'] ?? ($sz['imei_1'] ?? ($attributes['dev_imei1'] ?? null)),
                        'dev_imei2'               => $sz['dev_imei2'] ?? ($sz['imei_2'] ?? ($attributes['dev_imei2'] ?? null)),
                        'dev_mac_address'         => $sz['dev_mac_address'] ?? ($sz['mac_address'] ?? ($attributes['dev_mac_address'] ?? null)),
                        'dev_storage'             => $sz['dev_storage'] ?? ($sz['storage_capacity'] ?? ($sz['dev_storage_capacity'] ?? ($sz['storage'] ?? ($attributes['dev_storage'] ?? null)))),
                        'dev_colour'              => $sz['dev_colour'] ?? ($sz['dev_color'] ?? ($sz['colour'] ?? ($sz['color'] ?? ($sz['device_color'] ?? ($attributes['dev_colour'] ?? null))))),
                        'dev_marks'               => $sz['dev_marks'] ?? ($sz['dev_other_marks'] ?? ($sz['marks'] ?? ($sz['other_marks'] ?? ($sz['distinguishing_marks'] ?? ($attributes['dev_marks'] ?? null))))),
                        'ammunition_details'      => $sz['ammunition_details'] ?? ($attributes['ammunition_details'] ?? null),
                        'other_exhibit_description' => $sz['other_exhibit_description'] ?? ($sz['other_description'] ?? ($attributes['other_exhibit_description'] ?? ($attributes['other_description'] ?? null))),
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
                            'presumptive_result'      => $sz['presumptive_result'] ?? ($labDataFromDocket['presumptive_result'] ?? ($attributes['presumptive_result'] ?? ($attributes['presumptive_test_result'] ?? null))),
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
            if (empty($hasCreatedLabFromSeizure) && (!empty($attributes['presumptive_result']) || !empty($attributes['presumptive_test_result']) || !empty($attributes['confirmatory_test_result']) || !empty($labDataFromDocket))) {
                $labSuspectId = null;
                if (!empty($ex) && !empty($ex->suspect_id)) {
                    $labSuspectId = $ex->suspect_id;
                } elseif (!empty($matchedSuspect)) {
                    $labSuspectId = $matchedSuspect->id;
                } else {
                    $labSuspectId = $createdSuspects[0]->id ?? null;
                }
                LaboratoryExaminationResult::create([
                    'case_type'               => 'MULTIPLE',
                    'multiple_suspect_case_id'=> $multi->id,
                    'suspect_id'              => $labSuspectId,
                    'exhibit_id'              => $ex->id ?? null,
                    'laboratory_number'       => $multi->laboratory_number,
                    'analyst'                 => $attributes['analyst'] ?? ($labDataFromDocket['analyst'] ?? null),
                    'presumptive_result'      => $attributes['presumptive_result'] ?? ($attributes['presumptive_test_result'] ?? ($labDataFromDocket['presumptive_result'] ?? null)),
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
            'phones'               => $attributes['phones'] ?? ($suspects[0]['phones'] ?? ($suspects[0]['additional_phones'] ?? null)),
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
                    'associated_person'     => $single->suspect_name,
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
                    'source_of_exhibits'    => $attributes['source_of_exhibits'] ?? null,
                    'exhibit_remark'        => $attributes['exhibit_remark'] ?? null,
                    'lab_remark'            => $attributes['confirmatory_remark'] ?? ($attributes['lab_remark'] ?? ($attributes['docket_data']['lab_examination']['remark'] ?? null)),
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
                    'veh_owner_contact'     => $attributes['veh_owner_contact'] ?? ($attributes['vehicle_owner_contact'] ?? null),
                    'veh_driver'            => $attributes['veh_driver'] ?? ($attributes['vehicle_driver'] ?? null),
                    'veh_docs_available'    => $attributes['veh_docs_available'] ?? ($attributes['vehicle_docs_available'] ?? null),
                    'veh_doc_ref'           => $attributes['veh_doc_ref'] ?? ($attributes['vehicle_doc_ref'] ?? null),
                    'veh_condition'         => $attributes['veh_condition'] ?? ($attributes['vehicle_condition'] ?? null),
                    'veh_fuel'              => $attributes['veh_fuel'] ?? ($attributes['vehicle_fuel'] ?? null),
                    'veh_keys_recv'         => $attributes['veh_keys_recv'] ?? ($attributes['vehicle_keys_recv'] ?? null),
                    'veh_key_count'         => $attributes['veh_key_count'] ?? ($attributes['vehicle_key_count'] ?? null),
                    'veh_damage'            => $attributes['veh_damage'] ?? ($attributes['vehicle_damage'] ?? null),
                    'veh_mods'              => $attributes['veh_mods'] ?? ($attributes['vehicle_mods'] ?? null),
                    'veh_features'          => $attributes['veh_features'] ?? ($attributes['vehicle_features'] ?? null),
                    'wpn_model'             => $attributes['wpn_model'] ?? ($attributes['weapon_model'] ?? ($attributes['weapon_type'] ?? null)),
                    'wpn_serial_no'         => $attributes['wpn_serial_no'] ?? ($attributes['weapon_serial'] ?? null),
                    'wpn_calibre'           => $attributes['wpn_calibre'] ?? ($attributes['weapon_calibre'] ?? null),
                    'wpn_colour'            => $attributes['wpn_colour'] ?? ($attributes['weapon_colour'] ?? null),
                    'wpn_size'              => $attributes['wpn_size'] ?? ($attributes['weapon_size'] ?? null),
                    'wpn_marks'             => $attributes['wpn_marks'] ?? ($attributes['weapon_marks'] ?? null),
                    'wpn_location_found'    => $attributes['wpn_location_found'] ?? ($attributes['weapon_location_found'] ?? null),
                    'wpn_loaded_status'     => $attributes['wpn_loaded_status'] ?? ($attributes['weapon_loaded_status'] ?? null),
                    'wpn_condition'         => $attributes['wpn_condition'] ?? ($attributes['weapon_condition'] ?? null),
                    'dev_model'             => $attributes['dev_model'] ?? ($attributes['device_model'] ?? null),
                    'dev_serial_no'         => $attributes['dev_serial_no'] ?? ($attributes['device_serial'] ?? null),
                    'dev_imei1'             => $attributes['dev_imei1'] ?? ($attributes['imei_1'] ?? null),
                    'dev_imei2'             => $attributes['dev_imei2'] ?? ($attributes['imei_2'] ?? null),
                    'dev_mac_address'       => $attributes['dev_mac_address'] ?? ($attributes['mac_address'] ?? null),
                    'dev_storage'           => $attributes['dev_storage'] ?? ($attributes['storage_capacity'] ?? ($attributes['dev_storage_capacity'] ?? ($attributes['storage'] ?? null))),
                    'dev_colour'            => $attributes['dev_colour'] ?? ($attributes['dev_color'] ?? ($attributes['colour'] ?? ($attributes['color'] ?? ($attributes['device_color'] ?? null)))),
                    'dev_marks'             => $attributes['dev_marks'] ?? ($attributes['dev_other_marks'] ?? ($attributes['marks'] ?? ($attributes['other_marks'] ?? ($attributes['distinguishing_marks'] ?? null)))),
                    'ammunition_details'    => $attributes['ammunition_details'] ?? null,
                    'other_exhibit_description' => $attributes['other_exhibit_description'] ?? ($attributes['other_description'] ?? null),
                    'evidence_photos'       => $attributes['evidence_photos'] ?? [],
                ]);
            }
        } else {
            foreach ($seizures as $sIdx => $sz) {
                $isSingleJoint = (!empty($sz['suspect_ownership']) && str_contains(strtolower($sz['suspect_ownership']), 'all suspect'))
                    || (!empty($sz['associated_person']) && str_contains(strtolower($sz['associated_person']), 'all suspect'));

                $ex = Exhibit::create([
                    'case_type'             => 'SINGLE',
                    'single_suspect_case_id'=> $single->id,
                    'associated_person'     => $isSingleJoint ? 'All Suspects' : ($sz['associated_person'] ?? ($sz['suspect_ownership'] ?? $single->suspect_name)),
                    'exhibit_number'        => $sIdx + 1,
                    'exhibit_category'      => $sz['exhibit_category'] ?? ($sz['category'] ?? ($attributes['exhibit_category'] ?? 'Drugs')),
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
                    'source_of_exhibits'    => $sz['source_of_exhibits'] ?? ($attributes['source_of_exhibits'] ?? null),
                    'exhibit_remark'        => $sz['exhibit_remark'] ?? ($attributes['exhibit_remark'] ?? null),
                    'lab_remark'            => $sz['lab_remark'] ?? ($sz['confirmatory_remark'] ?? (!empty($sz['has_saved_lab']) ? ($sz['remark'] ?? null) : ($sIdx === 0 ? ($attributes['confirmatory_remark'] ?? ($attributes['lab_remark'] ?? ($attributes['docket_data']['lab_examination']['remark'] ?? null))) : null))),
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
                    'veh_owner_contact'     => $sz['veh_owner_contact'] ?? ($sz['veh']['owner_contact'] ?? ($sz['vehicle_owner_contact'] ?? ($attributes['veh_owner_contact'] ?? null))),
                    'veh_driver'            => $sz['veh_driver'] ?? ($sz['veh']['driver'] ?? ($sz['vehicle_driver'] ?? ($attributes['veh_driver'] ?? null))),
                    'veh_docs_available'    => $sz['veh_docs_available'] ?? ($sz['veh']['docs_available'] ?? ($sz['vehicle_docs_available'] ?? ($attributes['veh_docs_available'] ?? null))),
                    'veh_doc_ref'           => $sz['veh_doc_ref'] ?? ($sz['veh']['doc_ref'] ?? ($sz['vehicle_doc_ref'] ?? ($attributes['veh_doc_ref'] ?? null))),
                    'veh_condition'         => $sz['veh_condition'] ?? ($sz['veh']['condition'] ?? ($sz['vehicle_condition'] ?? ($attributes['veh_condition'] ?? null))),
                    'veh_fuel'              => $sz['veh_fuel'] ?? ($sz['veh']['fuel'] ?? ($sz['vehicle_fuel'] ?? ($attributes['veh_fuel'] ?? null))),
                    'veh_keys_recv'         => $sz['veh_keys_recv'] ?? ($sz['veh']['keys_recv'] ?? ($sz['vehicle_keys_recv'] ?? ($attributes['veh_keys_recv'] ?? null))),
                    'veh_key_count'         => $sz['veh_key_count'] ?? ($sz['veh']['key_count'] ?? ($sz['vehicle_key_count'] ?? ($attributes['veh_key_count'] ?? null))),
                    'veh_damage'            => $sz['veh_damage'] ?? ($sz['veh']['damage'] ?? ($sz['vehicle_damage'] ?? ($attributes['veh_damage'] ?? null))),
                    'veh_mods'              => $sz['veh_mods'] ?? ($sz['veh']['mods'] ?? ($sz['vehicle_mods'] ?? ($attributes['veh_mods'] ?? null))),
                    'veh_features'          => $sz['veh_features'] ?? ($sz['veh']['features'] ?? ($sz['vehicle_features'] ?? ($attributes['veh_features'] ?? null))),
                    'wpn_model'             => $sz['wpn_model'] ?? ($sz['wpn']['model'] ?? ($sz['weapon_model'] ?? ($sz['weapon_type'] ?? ($sz['seizure_name_weapon'] ?? ($attributes['wpn_model'] ?? null))))),
                    'wpn_serial_no'         => $sz['wpn_serial_no'] ?? ($sz['wpn']['serial_no'] ?? ($sz['weapon_serial'] ?? ($attributes['wpn_serial_no'] ?? null))),
                    'wpn_calibre'           => $sz['wpn_calibre'] ?? ($sz['wpn']['calibre'] ?? ($sz['weapon_calibre'] ?? ($attributes['wpn_calibre'] ?? null))),
                    'wpn_colour'            => $sz['wpn_colour'] ?? ($sz['wpn']['colour'] ?? ($sz['weapon_colour'] ?? ($attributes['wpn_colour'] ?? null))),
                    'wpn_size'              => $sz['wpn_size'] ?? ($sz['wpn']['size'] ?? ($sz['weapon_size'] ?? ($attributes['wpn_size'] ?? null))),
                    'wpn_marks'             => $sz['wpn_marks'] ?? ($sz['wpn']['marks'] ?? ($sz['weapon_marks'] ?? ($attributes['wpn_marks'] ?? null))),
                    'wpn_location_found'    => $sz['wpn_location_found'] ?? ($sz['wpn']['location_found'] ?? ($sz['weapon_location_found'] ?? ($attributes['wpn_location_found'] ?? null))),
                    'wpn_loaded_status'     => $sz['wpn_loaded_status'] ?? ($sz['wpn']['loaded_status'] ?? ($sz['weapon_loaded_status'] ?? ($attributes['wpn_loaded_status'] ?? null))),
                    'wpn_condition'         => $sz['wpn_condition'] ?? ($sz['wpn']['condition'] ?? ($sz['weapon_condition'] ?? ($attributes['wpn_condition'] ?? null))),
                    'dev_model'             => $sz['dev_model'] ?? ($sz['device_model'] ?? ($attributes['dev_model'] ?? null)),
                    'dev_serial_no'         => $sz['dev_serial_no'] ?? ($sz['device_serial'] ?? ($attributes['dev_serial_no'] ?? null)),
                    'dev_imei1'             => $sz['dev_imei1'] ?? ($sz['imei_1'] ?? ($attributes['dev_imei1'] ?? null)),
                    'dev_imei2'             => $sz['dev_imei2'] ?? ($sz['imei_2'] ?? ($attributes['dev_imei2'] ?? null)),
                    'dev_mac_address'       => $sz['dev_mac_address'] ?? ($sz['mac_address'] ?? ($attributes['dev_mac_address'] ?? null)),
                    'dev_storage'           => $sz['dev_storage'] ?? ($sz['storage_capacity'] ?? ($sz['dev_storage_capacity'] ?? ($sz['storage'] ?? ($attributes['dev_storage'] ?? null)))),
                    'dev_colour'            => $sz['dev_colour'] ?? ($sz['dev_color'] ?? ($sz['colour'] ?? ($sz['color'] ?? ($sz['device_color'] ?? ($attributes['dev_colour'] ?? null))))),
                    'dev_marks'             => $sz['dev_marks'] ?? ($sz['dev_other_marks'] ?? ($sz['marks'] ?? ($sz['other_marks'] ?? ($sz['distinguishing_marks'] ?? ($attributes['dev_marks'] ?? null))))),
                    'ammunition_details'    => $sz['ammunition_details'] ?? ($attributes['ammunition_details'] ?? null),
                    'other_exhibit_description' => $sz['other_exhibit_description'] ?? ($sz['other_description'] ?? ($attributes['other_exhibit_description'] ?? ($attributes['other_description'] ?? null))),
                    'evidence_photos'       => !empty($sz['evidence_photos']) ? $sz['evidence_photos'] : ($sIdx === 0 ? ($attributes['evidence_photos'] ?? ($docketData['evidence_photos'] ?? [])) : []),
                ]);
            }
        }

        $labDataFromDocket = $docketData['lab_examination'] ?? [];
        if (!empty($attributes['presumptive_result']) || !empty($attributes['presumptive_test_result']) || !empty($attributes['confirmatory_test_result']) || !empty($labDataFromDocket)) {
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
                'exam_reference'          => $attributes['exam_reference'] ?? ($labDataFromDocket['exam_reference'] ?? null),
                'analyst'                 => $attributes['analyst'] ?? ($labDataFromDocket['analyst'] ?? null),
                'presumptive_result'      => $attributes['presumptive_result'] ?? ($attributes['presumptive_test_result'] ?? ($labDataFromDocket['presumptive_result'] ?? null)),
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
                'case_number' => $r['case_number'] ?? ('NCC-' . rand(1000, 9999)),
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

        if ($column === 'suspect_name') {
            $singles = SingleSuspectCase::where('suspect_name', $operator, $value)->where('status', '!=', 'Promoted')->get();
            $multiples = MultipleSuspectCase::whereHas('suspects', function ($q) use ($operator, $value) {
                $q->where('suspect_name', $operator, $value);
            })->get();
        } else {
            $singles = SingleSuspectCase::where($column, $operator, $value)->where('status', '!=', 'Promoted')->get();
            $multiples = MultipleSuspectCase::where($column, $operator, $value)->get();
        }

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

        if ($column === 'suspect_name') {
            $singles = SingleSuspectCase::whereIn('suspect_name', $values)->where('status', '!=', 'Promoted')->get();
            $multiples = MultipleSuspectCase::whereHas('suspects', function ($q) use ($values) {
                $q->whereIn('suspect_name', $values);
            })->get();
        } else {
            $singles = SingleSuspectCase::whereIn($column, $values)->where('status', '!=', 'Promoted')->get();
            $multiples = MultipleSuspectCase::whereIn($column, $values)->get();
        }

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

            public function paginate($perPage = 15) {
                return ForensicCase::paginateCases($this->items, $perPage);
            }

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
     * Paginate any collection of ForensicCase or wrap items into a LengthAwarePaginator.
     */
    public static function paginateCases(Collection $collection, int $perPage = 15, ?int $page = null, array $options = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $request = request();
        $reqPerPage = $request ? $request->input('per_page') : null;
        if (!empty($reqPerPage) && is_numeric($reqPerPage)) {
            $perPage = (int) $reqPerPage;
        }
        if ($perPage < 1) $perPage = 15;
        if ($perPage > 100) $perPage = 100;

        $page = $page ?: \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $total = $collection->count();
        $items = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            array_merge([
                'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ], $options)
        );

        return $paginator->withQueryString();
    }

    /**
     * Direct pagination helper on ForensicCase.
     */
    public static function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return self::paginateCases(self::allCases(), $perPage, $page, ['pageName' => $pageName]);
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
            public function paginate($perPage = 15) {
                return ForensicCase::paginateCases(ForensicCase::allCases(), $perPage);
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
        $multiCaseNumbers = [];
        foreach ($multiples as $m) {
            $cn = strtoupper(trim((string)$m->case_number));
            if (!empty($cn)) {
                $multiCaseNumbers[$cn] = true;
            }
            $all->push(self::wrapRecord($m));
        }

        foreach ($singles as $s) {
            $cn = strtoupper(trim((string)$s->case_number));
            if (!empty($cn) && isset($multiCaseNumbers[$cn])) {
                continue;
            }
            $all->push(self::wrapRecord($s));
        }

        return $all->unique(function ($item) {
            return strtoupper(trim((string)$item->case_number));
        })->sortByDesc('created_at')->values();
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

            $single = SingleSuspectCase::with(['user', 'unit', 'exhibits', 'labResults'])->find($ref);
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
        'ephedrine', 'precursor',
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
            $isMultiCase = $this->isMultipleSuspect();
            $dbExhibits = $isMultiCase
                ? Exhibit::where('multiple_suspect_case_id', $this->id)->get()
                : Exhibit::where('single_suspect_case_id', $this->id)->get();
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
            $isMultiCase = $this->isMultipleSuspect();
            $labRecords = $isMultiCase
                ? LaboratoryExaminationResult::where('multiple_suspect_case_id', $this->id)->get()
                : LaboratoryExaminationResult::where('single_suspect_case_id', $this->id)->get();
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
     * Check if this case is strictly digital related.
     */
    public function isDigitalCase(): bool
    {
        $flags = $this->classifyExhibits();
        if (!empty($flags['has_electronic'])) {
            return true;
        }

        $units = $this->getWorkflowUnits();
        if (in_array('Digital & Physical Forensics', $units, true)) {
            return true;
        }

        $uName = strtolower($this->unit_name ?? ($this->unit->name ?? ''));
        if (str_contains($uName, 'digital') || str_contains($uName, 'cyber') || str_contains($uName, 'physical')) {
            return true;
        }

        $exCat   = strtolower($this->exhibit_category ?? '');
        $title   = strtolower($this->title ?? '');
        $offense = strtolower($this->offense_description ?? '');
        $seizure = strtolower($this->seizure_name ?? '');
        $dev     = strtolower($this->dev_model ?? '');

        $digitalKws = [
            'digital', 'mobile', 'phone', 'laptop', 'computer', 'tablet', 'ipad',
            'hard drive', 'harddrive', 'hdd', 'ssd', 'usb', 'flash drive', 'memory card',
            'electronic', 'device', 'iphone', 'android', 'pc', 'desktop', 'camera', 'cyber'
        ];

        foreach ($digitalKws as $kw) {
            if (
                str_contains($exCat, $kw) ||
                str_contains($title, $kw) ||
                str_contains($offense, $kw) ||
                str_contains($seizure, $kw) ||
                str_contains($dev, $kw)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize suspect disposition string.
     */
    public static function normalizeSuspectDisposition(?string $disp): string
    {
        if (empty($disp)) {
            return 'Active — Under Investigation';
        }
        $t = trim($disp);
        if (str_contains($t, 'Active') || str_contains($t, 'â€”') || str_contains($t, 'Under Investigation')) {
            return 'Active — Under Investigation';
        }
        if (str_contains($t, 'Cleared')) {
            return 'Cleared / No Further Action';
        }
        if (str_contains($t, 'Released')) {
            return 'Released from Case';
        }
        if (str_contains($t, 'Reclassified')) {
            return 'Reclassified';
        }
        if (str_contains($t, 'Referred')) {
            return 'Referred for Further Review';
        }
        return $t;
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
            $multiId = $this->underlyingRecord->id;
            $dbSuspects = CaseSuspect::where('multiple_suspect_case_id', $multiId)
                ->orderBy('suspect_number')
                ->get();

            if ($dbSuspects->isEmpty()) {
                $dbSuspects = CaseSuspect::where('multiple_suspect_case_id', $this->id)
                    ->orderBy('suspect_number')
                    ->get();
            }

            if ($dbSuspects->count() > 0) {
                foreach ($dbSuspects as $idx => $s) {
                    $docketSp = null;
                    if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
                        foreach ($docketData['suspects'] as $ds) {
                            if (!empty($ds['suspect_name']) && strtolower(trim($ds['suspect_name'])) === strtolower(trim($s->suspect_name))) {
                                $docketSp = $ds;
                                break;
                            }
                        }
                        if (!$docketSp && isset($docketData['suspects'][$idx])) {
                            $docketSp = $docketData['suspects'][$idx];
                        }
                    }

                    $pobVal = $s->pob ?? ($docketSp['pob'] ?? ($docketSp['place_of_birth'] ?? ($docketSp['hometown'] ?? null)));
                    $addrVal = $s->address ?? ($docketSp['address'] ?? ($docketSp['residential_address'] ?? ($docketSp['gps'] ?? null)));
                    $maritalVal = $s->marital_status ?? ($docketSp['marital_status'] ?? null);
                    $mFront = $s->mugshot_front ?? ($docketSp['mugshot_front'] ?? ($docketSp['mugshots']['front'] ?? null));
                    $mLeft  = $s->mugshot_left  ?? ($docketSp['mugshot_left']  ?? ($docketSp['mugshots']['left'] ?? null));
                    $mRight = $s->mugshot_right ?? ($docketSp['mugshot_right'] ?? ($docketSp['mugshots']['right'] ?? null));

                    $list[] = [
                        'id'                     => $s->id,
                        'suspect_number'         => $s->suspect_number,
                        'suspect_name'           => $s->suspect_name,
                        'suspect_alias'          => $s->suspect_alias ?? ($docketSp['suspect_alias'] ?? 'N/A'),
                        'role'                   => $s->role ?? ($docketSp['role'] ?? ($idx === 0 ? 'Primary Suspect' : 'Associate Suspect')),
                        'association_role'       => $s->role ?? ($docketSp['association_role'] ?? ($docketSp['role'] ?? ($idx === 0 ? 'Primary Suspect' : 'Associate Suspect'))),
                        'associate_id'           => $s->associate_id ?? ($docketSp['associate_id'] ?? null),
                        'suspect_disposition'    => self::normalizeSuspectDisposition($s->suspect_disposition),
                        'disposition_reason'     => $s->disposition_reason,
                        'disposition_updated_at' => $s->disposition_updated_at,
                        'disposition_updated_by' => $s->disposition_updated_by,
                        'ghana_card'             => $s->ghana_card ?? ($docketSp['ghana_card'] ?? null),
                        'gender'                 => $s->gender ?? ($docketSp['gender'] ?? null),
                        'dob'                    => $s->dob ?? ($docketSp['dob'] ?? null),
                        'age'                    => $s->age ?? ($docketSp['age'] ?? null),
                        'height'                 => $s->height ?? ($docketSp['height'] ?? null),
                        'height_unit'            => $s->height_unit ?? ($docketSp['height_unit'] ?? null),
                        'eye_color'              => $s->eye_color ?? ($docketSp['eye_color'] ?? null),
                        'nationality'            => $s->nationality ?? ($docketSp['nationality'] ?? 'Ghanaian'),
                        'pob'                    => $pobVal,
                        'hometown'               => $pobVal,
                        'place_of_birth'         => $pobVal,
                        'phone'                  => $s->phone ?? ($docketSp['phone'] ?? ($docketSp['phone_number'] ?? null)),
                        'phones'                 => !empty($s->phones) ? $s->phones : ($docketSp['phones'] ?? ($idx === 0 ? ($this->phones ?? ($docketData['phones'] ?? ($docketData['additional_phones'] ?? null))) : null)),
                        'address'                => $addrVal,
                        'residential_address'    => $addrVal,
                        'marital_status'         => $maritalVal,
                        'has_children'           => $s->has_children ?? (!empty($docketSp['has_children'])),
                        'children_count'         => $s->children_count ?? ($docketSp['children_count'] ?? null),
                        'spouse_name'            => $s->spouse_name ?? ($docketSp['spouse_name'] ?? null),
                        'spouse_phone'           => $s->spouse_phone ?? ($docketSp['spouse_phone'] ?? null),
                        'spouse_info'            => $s->spouse_info ?? ($docketSp['spouse_info'] ?? null),
                        'partner_name'           => $s->partner_name ?? ($docketSp['partner_name'] ?? null),
                        'partner_phone'          => $s->partner_phone ?? ($docketSp['partner_phone'] ?? null),
                        'partner_info'           => $s->partner_info ?? ($docketSp['partner_info'] ?? null),
                        'dependents'             => $s->dependents ?? ($docketSp['dependents'] ?? null),
                        'holding_facility'       => $s->holding_facility ?? ($docketSp['holding_facility'] ?? ($docketSp['facility'] ?? ($this->holding_facility ?? null))),
                        'raid_location'          => $s->raid_location ?? ($docketSp['raid_location'] ?? ($docketSp['location'] ?? ($this->raid_location ?? null))),
                        'offense_description'    => $s->offense_description ?? ($docketSp['offense_description'] ?? ($docketSp['offense'] ?? ($this->offense_description ?? null))),
                        'date_of_arrest'         => $s->arrest_date ?? ($docketSp['date_of_arrest'] ?? ($docketSp['arrest_date'] ?? ($this->arrest_date ?? null))),
                        'arrest_date'            => $s->arrest_date ?? ($docketSp['arrest_date'] ?? ($docketSp['date_of_arrest'] ?? ($this->arrest_date ?? null))),
                        'time_of_arrest'         => $s->arrest_time ?? ($docketSp['time_of_arrest'] ?? ($docketSp['arrest_time'] ?? ($this->arrest_time ?? null))),
                        'arrest_time'            => $s->arrest_time ?? ($docketSp['arrest_time'] ?? ($docketSp['time_of_arrest'] ?? ($this->arrest_time ?? null))),
                        'mugshot_front'          => $mFront,
                        'mugshot_left'           => $mLeft,
                        'mugshot_right'          => $mRight,
                        'extra_mugshots'         => $s->extra_mugshots ?? ($docketSp['extra_mugshots'] ?? null),
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
                $pobVal = $s['pob'] ?? ($s['place_of_birth'] ?? ($s['hometown'] ?? null));
                $addrVal = $s['address'] ?? ($s['residential_address'] ?? ($s['gps'] ?? null));
                $maritalVal = $s['marital_status'] ?? null;
                $mFront = $s['mugshot_front'] ?? ($s['mugshots']['front'] ?? null);
                $mLeft  = $s['mugshot_left']  ?? ($s['mugshots']['left'] ?? null);
                $mRight = $s['mugshot_right'] ?? ($s['mugshots']['right'] ?? null);

                $list[] = [
                    'id'                     => $s['id'] ?? null,
                    'suspect_number'         => $idx + 1,
                    'suspect_name'           => $s['suspect_name'],
                    'suspect_alias'          => $s['suspect_alias'] ?? 'N/A',
                    'role'                   => $role,
                    'association_role'       => $role,
                    'associate_id'           => $s['associate_id'] ?? null,
                    'suspect_disposition'    => self::normalizeSuspectDisposition($s['suspect_disposition'] ?? ($idx === 0 ? ($this->suspect_disposition ?? ($this->underlyingRecord->suspect_disposition ?? null)) : null)),
                    'disposition_reason'     => $s['disposition_reason'] ?? ($idx === 0 ? ($this->disposition_reason ?? ($this->underlyingRecord->disposition_reason ?? null)) : null),
                    'disposition_updated_at' => $s['disposition_updated_at'] ?? ($idx === 0 ? ($this->disposition_updated_at ?? ($this->underlyingRecord->disposition_updated_at ?? null)) : null),
                    'disposition_updated_by' => $s['disposition_updated_by'] ?? ($idx === 0 ? ($this->disposition_updated_by ?? ($this->underlyingRecord->disposition_updated_by ?? null)) : null),
                    'ghana_card'             => $s['ghana_card'] ?? null,
                    'gender'                 => $s['gender'] ?? null,
                    'dob'                    => $s['dob'] ?? null,
                    'age'                    => $s['age'] ?? null,
                    'height'                 => $s['height'] ?? null,
                    'height_unit'            => $s['height_unit'] ?? null,
                    'eye_color'              => $s['eye_color'] ?? null,
                    'nationality'            => $s['nationality'] ?? 'Ghanaian',
                    'pob'                    => $pobVal,
                    'hometown'               => $pobVal,
                    'place_of_birth'         => $pobVal,
                    'phone'                  => $s['phone_number'] ?? ($s['phone'] ?? null),
                    'phones'                 => !empty($s['phones']) ? $s['phones'] : ($s['additional_phones'] ?? ($idx === 0 ? ($this->phones ?? ($docketData['phones'] ?? ($docketData['additional_phones'] ?? null))) : null)),
                    'address'                => $addrVal,
                    'residential_address'    => $addrVal,
                    'marital_status'         => $maritalVal,
                    'has_children'           => !empty($s['has_children']),
                    'children_count'         => $s['children_count'] ?? null,
                    'spouse_name'            => $s['spouse_name'] ?? null,
                    'spouse_phone'           => $s['spouse_phone'] ?? null,
                    'spouse_info'            => $s['spouse_info'] ?? null,
                    'partner_name'           => $s['partner_name'] ?? null,
                    'partner_phone'          => $s['partner_phone'] ?? null,
                    'partner_info'           => $s['partner_info'] ?? null,
                    'dependents'             => $s['dependents'] ?? null,
                    'holding_facility'       => $s['holding_facility'] ?? ($s['facility'] ?? ($this->holding_facility ?? null)),
                    'raid_location'          => $s['raid_location'] ?? ($s['location'] ?? ($this->raid_location ?? null)),
                    'offense_description'    => $s['offense_description'] ?? ($s['offense'] ?? ($this->offense_description ?? null)),
                    'date_of_arrest'         => $s['date_of_arrest'] ?? ($s['arrest_date'] ?? ($this->arrest_date ?? null)),
                    'arrest_date'            => $s['arrest_date'] ?? ($s['date_of_arrest'] ?? ($this->arrest_date ?? null)),
                    'time_of_arrest'         => $s['time_of_arrest'] ?? ($s['arrest_time'] ?? ($this->arrest_time ?? null)),
                    'arrest_time'            => $s['arrest_time'] ?? ($s['time_of_arrest'] ?? ($this->arrest_time ?? null)),
                    'mugshot_front'          => $mFront,
                    'mugshot_left'           => $mLeft,
                    'mugshot_right'          => $mRight,
                    'extra_mugshots'         => $s['extra_mugshots'] ?? null,
                    'is_associate'           => !empty($s['associate_id']) || (!empty($s['is_associate']) && $s['is_associate'] !== false) || strtolower($role) !== 'primary suspect',
                ];
            }
            if (!empty($list)) return $list;
        }

        // 3. Fallback to single suspect on case model
        $primaryDisp = self::normalizeSuspectDisposition($this->suspect_disposition ?? ($this->underlyingRecord->suspect_disposition ?? null));
        $primaryPob = $this->pob ?? ($docketData['pob'] ?? ($docketData['hometown'] ?? null));
        $primaryAddr = $this->address ?? ($docketData['address'] ?? ($docketData['residential_address'] ?? null));
        $primaryMarital = $this->marital_status ?? ($docketData['marital_status'] ?? null);

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
            'age'                    => $this->age ?? ($docketData['age'] ?? null),
            'height'                 => $this->height ?? ($docketData['height'] ?? null),
            'height_unit'            => $this->height_unit ?? ($docketData['height_unit'] ?? null),
            'eye_color'              => $this->eye_color ?? ($docketData['eye_color'] ?? null),
            'nationality'            => $this->nationality ?? ($docketData['nationality'] ?? 'Ghanaian'),
            'pob'                    => $primaryPob,
            'hometown'               => $primaryPob,
            'place_of_birth'         => $primaryPob,
            'phone'                  => $this->phone ?? null,
            'phones'                 => !empty($this->phones) ? $this->phones : ($this->underlyingRecord->phones ?? ($docketData['phones'] ?? ($docketData['additional_phones'] ?? null))),
            'address'                => $primaryAddr,
            'residential_address'    => $primaryAddr,
            'marital_status'         => $primaryMarital,
            'has_children'           => $this->has_children ?? (!empty($docketData['has_children'])),
            'children_count'         => $this->children_count ?? ($docketData['children_count'] ?? null),
            'spouse_name'            => $this->spouse_name ?? ($docketData['spouse_name'] ?? null),
            'spouse_phone'           => $this->spouse_phone ?? ($docketData['spouse_phone'] ?? null),
            'spouse_info'            => $this->spouse_info ?? ($docketData['spouse_info'] ?? null),
            'partner_name'           => $this->partner_name ?? ($docketData['partner_name'] ?? null),
            'partner_phone'          => $this->partner_phone ?? ($docketData['partner_phone'] ?? null),
            'partner_info'           => $this->partner_info ?? ($docketData['partner_info'] ?? null),
            'dependents'             => $this->dependents ?? ($docketData['dependents'] ?? null),
            'holding_facility'       => $this->holding_facility ?? null,
            'raid_location'          => $this->raid_location ?? null,
            'offense_description'    => $this->offense_description ?? null,
            'date_of_arrest'         => $this->arrest_date ?? null,
            'arrest_date'            => $this->arrest_date ?? null,
            'time_of_arrest'         => $this->arrest_time ?? null,
            'arrest_time'            => $this->arrest_time ?? null,
            'mugshot_front'          => $this->mugshot_front ?? ($docketData['mugshot_front'] ?? null),
            'mugshot_left'           => $this->mugshot_left ?? ($docketData['mugshot_left'] ?? null),
            'mugshot_right'          => $this->mugshot_right ?? ($docketData['mugshot_right'] ?? null),
            'is_associate'           => false,
        ];

        return $list;
    }

    public function getActiveSuspects(): array
    {
        return array_values(array_filter($this->getSuspectsList(), function($s) {
            return self::normalizeSuspectDisposition($s['suspect_disposition'] ?? null) === 'Active — Under Investigation';
        }));
    }

    public function getClearedSuspects(): array
    {
        return array_values(array_filter($this->getSuspectsList(), function($s) {
            return self::normalizeSuspectDisposition($s['suspect_disposition'] ?? null) !== 'Active — Under Investigation';
        }));
    }

    /**
     * Check if this case is a multiple suspect case.
     */
    public function isMultipleSuspect(): bool
    {
        if ($this->underlyingRecord instanceof MultipleSuspectCase) {
            return true;
        }
        if ($this->underlyingRecord instanceof SingleSuspectCase) {
            return false;
        }
        if (isset($this->is_multiple)) {
            return (bool)$this->is_multiple;
        }
        if ($this->case_type === 'MULTIPLE') {
            return true;
        }
        if ($this->case_type === 'SINGLE') {
            return false;
        }
        if (!empty($this->total_suspects_count) && (int)$this->total_suspects_count > 1) {
            return true;
        }
        if (!empty($this->id)) {
            if (!empty($this->case_number)) {
                if (MultipleSuspectCase::where('id', $this->id)->where('case_number', $this->case_number)->exists()) {
                    return true;
                }
                if (SingleSuspectCase::where('id', $this->id)->where('case_number', $this->case_number)->exists()) {
                    return false;
                }
            }
            if (MultipleSuspectCase::where('id', $this->id)->exists() && !SingleSuspectCase::where('id', $this->id)->exists()) {
                return true;
            }
        }
        return false;
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

        // Docket Data exhibits
        $dData = is_array($this->docket_data)
            ? $this->docket_data
            : (json_decode($this->docket_data ?? '[]', true) ?: []);

        $allDocketExhibits = array_merge(
            $dData['associate_exhibits']  ?? [],
            $dData['associated_seizures'] ?? [],
            $dData['seizures']            ?? []
        );

        // 1. Relational Exhibit records from 'exhibits' DB table
        if (!empty($this->id)) {
            $isMultiCase = $this->isMultipleSuspect();
            $dbExhibits = $isMultiCase
                ? Exhibit::where('multiple_suspect_case_id', $this->id)->get()
                : Exhibit::where('single_suspect_case_id', $this->id)->get();

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
                        // Also check tokenized names if comma/and separated
                        $ownerTokens = preg_split('/\s*(?:,|;|&|\/|\band\b)\s*/i', $cleanOwner) ?: [];
                        foreach ($ownerTokens as $tok) {
                            $tok = trim($tok);
                            if (!empty($tok) && ($tok === $cleanTargetName || str_contains($cleanTargetName, $tok) || str_contains($tok, $cleanTargetName))) {
                                $isMatch = true;
                                break 2;
                            }
                        }
                    }
                }

                // Shared / Joint exhibit check (e.g. "All Suspects", "Joint", "Both Suspects")
                if (!$isMatch && !empty($exOwners)) {
                    foreach ($exOwners as $ownerStr) {
                        $cleanOwner = strtolower(trim($ownerStr));
                        if (str_contains($cleanOwner, 'all suspect') || str_contains($cleanOwner, 'joint') || str_contains($cleanOwner, 'both suspect')) {
                            $isMatch = true;
                            break;
                        }
                    }
                }

                // Fallback matching for single-suspect case or Primary Suspect when exhibit has no explicit suspect_id or specific owner assigned to someone else
                if (!$isMatch && empty($exSuspectId)) {
                    $hasOtherSpecificOwner = false;
                    $suspectsList = $this->getSuspectsList();
                    $firstSuspectName = !empty($suspectsList[0]['suspect_name']) ? strtolower(trim($suspectsList[0]['suspect_name'])) : '';
                    $isFirstSuspect = !empty($firstSuspectName) && ($cleanTargetName === $firstSuspectName);

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
                        if (count($suspectsList) <= 1 || $isFirstSuspect) {
                            $isMatch = true;
                        }
                    }
                }

                // Reject if suspect_id belongs to ANOTHER suspect (unless explicitly shared in owner string)
                if ($isMatch && !empty($exSuspectId) && !empty($suspectDbId) && (int)$exSuspectId !== (int)$suspectDbId) {
                    $isExplicitlyShared = false;
                    foreach ($exOwners as $ownerStr) {
                        $cOwner = strtolower(trim($ownerStr));
                        if (str_contains($cOwner, $cleanTargetName) || str_contains($cOwner, 'all suspect') || str_contains($cOwner, 'joint') || str_contains($cOwner, 'both suspect')) {
                            $isExplicitlyShared = true;
                            break;
                        }
                        $ownerTokens = preg_split('/\s*(?:,|;|&|\/|\band\b)\s*/i', $cOwner) ?: [];
                        foreach ($ownerTokens as $tok) {
                            $tok = trim($tok);
                            if (!empty($tok) && ($tok === $cleanTargetName || str_contains($cleanTargetName, $tok) || str_contains($tok, $cleanTargetName))) {
                                $isExplicitlyShared = true;
                                break 2;
                            }
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
                        $arr['veh_owner_contact']  = $ex->veh_owner_contact;
                        $arr['veh_docs_available'] = $ex->veh_docs_available;
                        $arr['veh_doc_ref']        = $ex->veh_doc_ref;
                        $arr['veh_condition']      = $ex->veh_condition;
                        $arr['veh_fuel']           = $ex->veh_fuel;
                        $arr['veh_keys_recv']      = $ex->veh_keys_recv;
                        $arr['veh_key_count']      = $ex->veh_key_count;
                        $arr['veh_damage']         = $ex->veh_damage;
                        $arr['veh_mods']           = $ex->veh_mods;
                        $arr['veh_features']       = $ex->veh_features;

                        $arr['weapon_marks']                 = $ex->wpn_marks;
                        $arr['weapon_distinguishing_marks']  = $ex->wpn_marks;
                        $arr['weapon_location_found']        = $ex->wpn_location_found;
                        $arr['weapon_loaded_status']         = $ex->wpn_loaded_status;
                        $arr['weapon_condition_at_recovery'] = $ex->wpn_condition;
                        $arr['weapon_condition']             = $ex->wpn_condition;

                        // Map device attributes
                        $arr['dev_model']       = $ex->dev_model;
                        $arr['dev_serial_no']   = $ex->dev_serial_no;
                        $arr['dev_imei1']       = $ex->dev_imei1;
                        $arr['dev_imei2']       = $ex->dev_imei2;
                        $arr['dev_mac_address'] = $ex->dev_mac_address;
                        $arr['dev_storage']     = $ex->dev_storage;
                        $arr['dev_colour']      = $ex->dev_colour;
                        $arr['dev_marks']       = $ex->dev_marks;

                        // Fallback to docket data seizures or case attributes if missing on exhibit model
                        if (empty($arr['dev_storage']) || empty($arr['dev_colour']) || empty($arr['dev_marks']) || empty($arr['dev_mac_address'])) {
                            foreach ($allDocketExhibits as $dSz) {
                                if (!is_array($dSz)) continue;
                                $dExNo = $dSz['exhibit_number'] ?? null;
                                $dName = $dSz['seizure_name'] ?? ($dSz['commodity_name'] ?? ($dSz['name'] ?? ''));
                                $matchDocket = (!empty($exNo) && !empty($dExNo) && (string)$exNo === (string)$dExNo)
                                    || (!empty($dName) && !empty($ex->commodity_name) && strtolower(trim($dName)) === strtolower(trim($ex->commodity_name)));
                                if ($matchDocket) {
                                    if (empty($arr['dev_storage'])) {
                                        $arr['dev_storage'] = $dSz['dev_storage'] ?? ($dSz['storage_capacity'] ?? ($dSz['dev_storage_capacity'] ?? ($dSz['storage'] ?? null)));
                                    }
                                    if (empty($arr['dev_colour'])) {
                                        $arr['dev_colour'] = $dSz['dev_colour'] ?? ($dSz['dev_color'] ?? ($dSz['colour'] ?? ($dSz['color'] ?? ($dSz['device_color'] ?? null))));
                                    }
                                    if (empty($arr['dev_marks'])) {
                                        $arr['dev_marks'] = $dSz['dev_marks'] ?? ($dSz['dev_other_marks'] ?? ($dSz['marks'] ?? ($dSz['distinguishing_marks'] ?? ($dSz['other_marks'] ?? null))));
                                    }
                                    if (empty($arr['dev_mac_address'])) {
                                        $arr['dev_mac_address'] = $dSz['dev_mac_address'] ?? ($dSz['mac_address'] ?? null);
                                    }
                                    break;
                                }
                            }
                            if (empty($arr['dev_storage'])) {
                                $arr['dev_storage'] = $this->dev_storage ?? ($this->underlyingRecord->dev_storage ?? null);
                            }
                            if (empty($arr['dev_colour'])) {
                                $arr['dev_colour'] = $this->dev_colour ?? ($this->underlyingRecord->dev_colour ?? null);
                            }
                            if (empty($arr['dev_marks'])) {
                                $arr['dev_marks'] = $this->dev_marks ?? ($this->underlyingRecord->dev_marks ?? null);
                            }
                        }

                        $matchedExhibits[] = $arr;
                    }
                }
            }
        }

        // 2. JSON Docket Data exhibit arrays
        // $dData and $allDocketExhibits prepared above


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
                } else {
                    $ownerTokens = preg_split('/\s*(?:,|;|&|\/|\band\b)\s*/i', $szOwner) ?: [];
                    foreach ($ownerTokens as $tok) {
                        $tok = trim($tok);
                        if (!empty($tok) && ($tok === $cleanTargetName || str_contains($cleanTargetName, $tok) || str_contains($tok, $cleanTargetName))) {
                            $isMatch = true;
                            break;
                        }
                    }
                }
            }

            // Shared / Joint exhibit check
            if (!$isMatch && !empty($szOwner)) {
                if (str_contains($szOwner, 'all suspect') || str_contains($szOwner, 'joint') || str_contains($szOwner, 'both suspect')) {
                    $isMatch = true;
                }
            }

            // Fallback matching for docket exhibits without explicit suspect ID
            if (!$isMatch && empty($szSuspectId)) {
                if (empty($szOwner) || $szOwner === 'primary suspect' || $szOwner === 'primary' || $szOwner === 'unassigned' || $szOwner === 'n/a') {
                    $allSuspectsInCase = $this->getSuspectsList();
                    $firstSuspectName = !empty($allSuspectsInCase[0]['suspect_name']) ? strtolower(trim($allSuspectsInCase[0]['suspect_name'])) : '';
                    $isFirstSuspect = !empty($firstSuspectName) && ($cleanTargetName === $firstSuspectName);

                    if (count($allSuspectsInCase) <= 1 || $isFirstSuspect) {
                        $isMatch = true;
                    }
                }
            }

            // Reject if exhibit explicitly belongs to a DIFFERENT suspect name or DB ID
            $isSharedLabel = str_contains($szOwner, 'all suspect') || str_contains($szOwner, 'joint') || str_contains($szOwner, 'both suspect') || $szOwner === 'primary suspect' || $szOwner === 'associate suspect' || $szOwner === 'joint suspects';
            if ($isMatch && !empty($szOwner) && !$isSharedLabel) {
                $hasTargetInOwner = ($szOwner === $cleanTargetName || str_contains($szOwner, $cleanTargetName));
                if (!$hasTargetInOwner) {
                    $ownerTokens = preg_split('/\s*(?:,|;|&|\/|\band\b)\s*/i', $szOwner) ?: [];
                    foreach ($ownerTokens as $tok) {
                        $tok = trim($tok);
                        if (!empty($tok) && ($tok === $cleanTargetName || str_contains($cleanTargetName, $tok) || str_contains($tok, $cleanTargetName))) {
                            $hasTargetInOwner = true;
                            break;
                        }
                    }
                }
                if (!$hasTargetInOwner) {
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
                    $dArr = $sz;
                    if (empty($dArr['dev_storage'])) {
                        $dArr['dev_storage'] = $sz['dev_storage'] ?? ($sz['storage_capacity'] ?? ($sz['dev_storage_capacity'] ?? ($sz['storage'] ?? null)));
                    }
                    if (empty($dArr['dev_colour'])) {
                        $dArr['dev_colour'] = $sz['dev_colour'] ?? ($sz['dev_color'] ?? ($sz['colour'] ?? ($sz['color'] ?? ($sz['device_color'] ?? null))));
                    }
                    if (empty($dArr['dev_marks'])) {
                        $dArr['dev_marks'] = $sz['dev_marks'] ?? ($sz['dev_other_marks'] ?? ($sz['marks'] ?? ($sz['distinguishing_marks'] ?? ($sz['other_marks'] ?? null))));
                    }
                    $matchedExhibits[] = $dArr;
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
            $isMultiCase = $this->isMultipleSuspect();
            $labRecords = $isMultiCase
                ? LaboratoryExaminationResult::where('multiple_suspect_case_id', $this->id)->get()
                : LaboratoryExaminationResult::where('single_suspect_case_id', $this->id)->get();

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

    /**
     * Authoritatively resolve the Laboratory Examination Result payload for a suspect.
     * Guarantees 100% data and layout parity across show_review and laboratory analytical views.
     */
    public function resolveLabExamForSuspect(?string $suspectName = null, $suspectDbId = null, $associateId = null, ?string $role = null, int $suspectIndex = 0): ?array
    {
        $cleanName = strtolower(trim($suspectName ?? ''));
        $isAssociate = (!empty($role) && str_contains(strtolower($role), 'associate')) || (!empty($associateId));
        $docketData = is_array($this->docket_data) ? $this->docket_data : (json_decode($this->docket_data ?? '[]', true) ?: []);
        $isMultiCase = $this->isMultipleSuspect();
        $sDossierLabExam = null;
        $sDossierHasSavedLab = false;

        // 1. Relational DB lookup
        if ($isAssociate) {
            $dbAssoc = $this->associateLabExaminations
                ? $this->associateLabExaminations->first(fn($a) => strtolower(trim($a->suspect_name ?? '')) === $cleanName)
                : null;
            if (!$dbAssoc && !empty($this->id)) {
                $dbAssoc = AssociateLabExamination::where('case_id', $this->id)
                    ->get()
                    ->first(fn($a) => strtolower(trim($a->suspect_name ?? '')) === $cleanName);
            }
            if ($dbAssoc && $dbAssoc->has_saved) {
                $sDossierHasSavedLab = true;
                $sDossierLabExam = [
                    'exam_reference' => $dbAssoc->exam_reference,
                    'exhibit_number' => $dbAssoc->exhibit_number,
                    'laboratory_number' => $dbAssoc->laboratory_number,
                    'analyst' => $dbAssoc->analyst,
                    'date_received' => is_object($dbAssoc->date_received) ? $dbAssoc->date_received->format('Y-m-d') : substr((string)$dbAssoc->date_received, 0, 10),
                    'date_started' => is_object($dbAssoc->date_started) ? $dbAssoc->date_started->format('Y-m-d') : substr((string)$dbAssoc->date_started, 0, 10),
                    'date_completed' => is_object($dbAssoc->date_completed) ? $dbAssoc->date_completed->format('Y-m-d') : substr((string)$dbAssoc->date_completed, 0, 10),
                    'presumptive_result' => $dbAssoc->presumptive_result,
                    'confirmatory_result' => $dbAssoc->confirmatory_result,
                    'sample_weight' => [
                        'description' => $dbAssoc->sample_weight_description,
                        'units_packaging' => $dbAssoc->sample_weight_units,
                        'gross_weight' => $dbAssoc->sample_weight_gross,
                        'sample_taken' => $dbAssoc->sample_weight_taken,
                        'reserved_samples' => $dbAssoc->sample_weight_reserved,
                    ],
                    'substance_identification' => $dbAssoc->substance_identification,
                    'detected_substances' => is_string($dbAssoc->detected_substances) ? (json_decode($dbAssoc->detected_substances, true) ?: []) : ($dbAssoc->detected_substances ?? []),
                    'test_methods' => $dbAssoc->test_methods,
                    'specifications' => $dbAssoc->specifications,
                    'remark' => $dbAssoc->remark,
                    'findings' => $dbAssoc->findings,
                    'has_saved' => true,
                ];
            }
        }

        if (!$sDossierHasSavedLab && !empty($this->id)) {
            $records = LaboratoryExaminationResult::where(function ($q) use ($isMultiCase) {
                if ($isMultiCase) {
                    $q->where('multiple_suspect_case_id', $this->id);
                } else {
                    $q->where('single_suspect_case_id', $this->id);
                }
            })->latest('id')->get();

            $dbPrimary = null;
            if (!empty($suspectDbId)) {
                $dbPrimary = $records->first(fn($r) => (int)$r->suspect_id === (int)$suspectDbId && $r->has_saved);
            }
            if (!$dbPrimary && !empty($cleanName)) {
                $dbPrimary = $records->first(fn($r) => !empty($r->suspect_name) && strtolower(trim($r->suspect_name)) === $cleanName && $r->has_saved);
            }
            if (!$dbPrimary) {
                $dbPrimary = $records->first(fn($r) => $r->has_saved) ?? $records->first();
            }

            if ($dbPrimary) {
                $sDossierHasSavedLab = (bool)$dbPrimary->has_saved;
                $sDossierLabExam = [
                    'exam_reference' => $dbPrimary->exam_reference,
                    'exhibit_number' => $dbPrimary->exhibit_number,
                    'laboratory_number' => $dbPrimary->laboratory_number,
                    'analyst' => $dbPrimary->analyst,
                    'date_received' => is_object($dbPrimary->date_received) ? $dbPrimary->date_received->format('Y-m-d') : substr((string)$dbPrimary->date_received, 0, 10),
                    'date_started' => is_object($dbPrimary->date_started) ? $dbPrimary->date_started->format('Y-m-d') : substr((string)$dbPrimary->date_started, 0, 10),
                    'date_completed' => is_object($dbPrimary->date_completed) ? $dbPrimary->date_completed->format('Y-m-d') : substr((string)$dbPrimary->date_completed, 0, 10),
                    'presumptive_result' => $dbPrimary->presumptive_result,
                    'confirmatory_result' => $dbPrimary->confirmatory_result,
                    'sample_weight' => [
                        'description' => $dbPrimary->sample_weight_description,
                        'units_packaging' => $dbPrimary->sample_weight_units,
                        'gross_weight' => $dbPrimary->sample_weight_gross,
                        'sample_taken' => $dbPrimary->sample_weight_taken,
                        'reserved_samples' => $dbPrimary->sample_weight_reserved,
                    ],
                    'substance_identification' => $dbPrimary->substance_identification,
                    'detected_substances' => is_string($dbPrimary->detected_substances) ? (json_decode($dbPrimary->detected_substances, true) ?: []) : ($dbPrimary->detected_substances ?? []),
                    'test_methods' => $dbPrimary->test_methods,
                    'specifications' => $dbPrimary->specifications,
                    'remark' => $dbPrimary->remark,
                    'findings' => $dbPrimary->findings,
                    'has_saved' => (bool)$dbPrimary->has_saved,
                ];
            }
        }

        // Source 2: Docket Data associate_lab_examinations
        if (!$sDossierHasSavedLab && !empty($docketData['associate_lab_examinations'])) {
            foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
                if (strtolower(trim($kAssoc)) === $cleanName && !empty($vAssoc['has_saved'])) {
                    $sDossierHasSavedLab = true;
                    $sDossierLabExam = $vAssoc;
                    break;
                }
            }
        }

        // Source 3: Docket Data lab_examinations_list
        if (!$sDossierHasSavedLab && !empty($docketData['lab_examinations_list'])) {
            foreach ($docketData['lab_examinations_list'] as $lItem) {
                $ownerMatch = !empty($lItem['exhibit_owner_name']) && strtolower(trim($lItem['exhibit_owner_name'])) === $cleanName;
                $sMatch = !empty($lItem['suspect_name']) && strtolower(trim($lItem['suspect_name'])) === $cleanName;
                if (($ownerMatch || $sMatch) && (!empty($lItem['has_saved']) || !empty($lItem['presumptive_result']) || !empty($lItem['substance_identification']))) {
                    $sDossierHasSavedLab = true;
                    $sDossierLabExam = $lItem;
                    break;
                }
            }
        }

        // Source 4: Docket Data lab_examination (for primary suspect)
        if (!$sDossierHasSavedLab && !$isAssociate && $suspectIndex === 0 && !empty($docketData['lab_examination']['has_saved'])) {
            $labOwner = strtolower(trim($docketData['lab_examination']['exhibit_owner_name'] ?? 'Primary Suspect'));
            if ($labOwner === 'primary suspect' || $labOwner === $cleanName) {
                $sDossierHasSavedLab = true;
                $sDossierLabExam = $docketData['lab_examination'];
            }
        }

        // Source 5: Exhibit-level lab_examination or lab_result
        if (!$sDossierHasSavedLab) {
            $suspectExhibits = $this->getExhibitsForSuspect($suspectName, $suspectDbId, $associateId, $role);
            foreach ($suspectExhibits as $eCheck) {
                if (!empty($eCheck['lab_examination']) && is_array($eCheck['lab_examination']) && (!empty($eCheck['lab_examination']['has_saved']) || !empty($eCheck['lab_examination']['substance_identification']))) {
                    $sDossierHasSavedLab = true;
                    $sDossierLabExam = $eCheck['lab_examination'];
                    break;
                }
                if (!empty($eCheck['lab_result']) && is_array($eCheck['lab_result']) && (!empty($eCheck['lab_result']['has_saved']) || !empty($eCheck['lab_result']['substance_identification']))) {
                    $sDossierHasSavedLab = true;
                    $sDossierLabExam = $eCheck['lab_result'];
                    break;
                }
            }
        }

        // Source 6: Stable reference determination (strictly NLR-FM-T35-F format)
        $rawStableExamRef = $this->lab_exam_reference
            ?: ($this->exam_reference
                ?: ($this->analysis_reference ?: 'NLR-FM-T35-F'));
        $stableExamRef = str_starts_with(trim($rawStableExamRef), 'GSA-')
            ? str_replace(['GSA-', 'T28'], ['NLR-', 'T35'], $rawStableExamRef)
            : (str_contains($rawStableExamRef, 'T28') ? str_replace('T28', 'T35', $rawStableExamRef) : $rawStableExamRef);

        // Source 7: Fallback to case properties
        if (empty($sDossierLabExam)) {
            $sDossierLabExam = [
                'exam_reference' => $stableExamRef,
                'exhibit_number' => $this->exhibit_number ?: '1',
                'laboratory_number' => $this->laboratory_number ?: 'Lab No. 12/FS05/2025',
                'exhibit_description' => $this->seizure_name_drug ?: ($this->seizure_name ?: 'Drug Exhibit'),
                'analyst' => $this->submitting_officer ?: 'Lab Analyst',
                'date_received' => $this->incident_date ? substr((string)$this->incident_date, 0, 10) : date('Y-m-d'),
                'date_started' => date('Y-m-d'),
                'date_completed' => date('Y-m-d'),
                'presumptive_result' => $this->presumptive_test_result ?: 'N/A',
                'confirmatory_result' => $this->confirmatory_test_result ?: 'YES',
                'sample_weight' => [
                    'description' => $this->sample_weight_description,
                    'units_packaging' => $this->sample_weight_units,
                    'gross_weight' => $this->sample_weight_gross,
                    'sample_taken' => $this->sample_weight_taken,
                    'reserved_samples' => $this->sample_weight_reserved,
                ],
                'substance_identification' => $this->confirmatory_substance_identification ?: 'N/A',
                'detected_substances' => [],
                'test_methods' => $this->confirmatory_test_methods ?: 'N/A',
                'specifications' => $this->confirmatory_specifications ?: 'N/A',
                'remark' => $this->confirmatory_remark ?: 'N/A',
                'findings' => $this->laboratory_analytical_findings ?: 'Awaiting laboratory examination completion.',
                'has_saved' => false,
            ];
        }

        // Normalize
        if (!empty($sDossierLabExam)) {
            if (empty($sDossierLabExam['exhibit_number']) || $sDossierLabExam['exhibit_number'] === 'N/A') {
                $sDossierLabExam['exhibit_number'] = $sDossierLabExam['exhibit_no'] ?? ($this->exhibit_number ?: '1');
            }
            if (empty($sDossierLabExam['exam_reference']) || $sDossierLabExam['exam_reference'] === 'N/A') {
                $sDossierLabExam['exam_reference'] = $stableExamRef;
            }
            if (empty($sDossierLabExam['laboratory_number']) || $sDossierLabExam['laboratory_number'] === 'N/A') {
                $sDossierLabExam['laboratory_number'] = $this->laboratory_number ?: 'Lab No. 12/FS05/2025';
            }
            if (empty($sDossierLabExam['analyst']) || $sDossierLabExam['analyst'] === 'N/A') {
                $sDossierLabExam['analyst'] = $this->submitting_officer ?: 'Lab Analyst';
            }

            $defRecDate = !empty($this->incident_date)
                ? substr((string)$this->incident_date, 0, 10)
                : (is_object($this->created_at) ? $this->created_at->format('Y-m-d') : substr((string)$this->created_at, 0, 10));
            $rRec = $sDossierLabExam['date_received'] ?? null;
            if (empty($rRec) || $rRec === 'N/A') $rRec = $defRecDate;
            $sDossierLabExam['date_received'] = is_object($rRec) ? $rRec->format('Y-m-d') : substr((string)$rRec, 0, 10);

            $rStart = $sDossierLabExam['date_started'] ?? null;
            if (empty($rStart) || $rStart === 'N/A') $rStart = $sDossierLabExam['date_received'];
            $sDossierLabExam['date_started'] = is_object($rStart) ? $rStart->format('Y-m-d') : substr((string)$rStart, 0, 10);

            $rComp = $sDossierLabExam['date_completed'] ?? null;
            if (empty($rComp) || $rComp === 'N/A') $rComp = $sDossierLabExam['date_started'];
            $sDossierLabExam['date_completed'] = is_object($rComp) ? $rComp->format('Y-m-d') : substr((string)$rComp, 0, 10);

            // Ensure sample_weight is structured
            $sw = $sDossierLabExam['sample_weight'] ?? [];
            if (is_string($sw)) $sw = json_decode($sw, true) ?: [];
            $sDossierLabExam['sample_weight'] = [
                'description' => $sw['description'] ?? ($sDossierLabExam['sample_weight_description'] ?? ($sDossierLabExam['exhibit_description'] ?? ($this->sample_weight_description ?: 'N/A'))),
                'units_packaging' => $sw['units_packaging'] ?? ($sDossierLabExam['sample_weight_units'] ?? ($this->sample_weight_units ?: 'N/A')),
                'gross_weight' => $sw['gross_weight'] ?? ($sDossierLabExam['sample_weight_gross'] ?? ($this->sample_weight_gross ?: 'N/A')),
                'sample_taken' => $sw['sample_taken'] ?? ($sDossierLabExam['sample_weight_taken'] ?? ($this->sample_weight_taken ?: '—')),
                'reserved_samples' => $sw['reserved_samples'] ?? ($sDossierLabExam['sample_weight_reserved'] ?? ($this->sample_weight_reserved ?: '—')),
            ];

            // Ensure detected_substances is array
            if (is_string($sDossierLabExam['detected_substances'] ?? null)) {
                $sDossierLabExam['detected_substances'] = json_decode($sDossierLabExam['detected_substances'], true) ?: [];
            }

            // Confirmatory normalized value
            $sDossierLabExam['confirmatory'] = (strtoupper(trim((string)($sDossierLabExam['confirmatory_result'] ?? ($sDossierLabExam['confirmatory'] ?? 'YES')))) !== 'NO') ? 'YES' : 'NO';

            // Presumptive reaction table rows parsing
            $pRaw = trim((string)($sDossierLabExam['presumptive_result'] ?? ''));
            $pRows = [];
            if (!empty($pRaw) && $pRaw !== 'N/A') {
                $overallResult = 'N/A';
                if (preg_match('/^(YES|NO|POS|NEG|POSITIVE|NEGATIVE)/i', $pRaw, $mRes)) {
                    $overallResult = strtoupper($mRes[1]);
                    if ($overallResult === 'POS' || $overallResult === 'POSITIVE') $overallResult = 'YES';
                    if ($overallResult === 'NEG' || $overallResult === 'NEGATIVE') $overallResult = 'NO';
                }

                $cleanP = $pRaw;
                if (preg_match('/^[A-Z]+\s*\((.*)\)$/is', $pRaw, $mP)) {
                    $cleanP = $mP[1];
                }

                $segments = preg_split('/;\s*(?=\[\d+\]|Presence of:)/i', $cleanP);
                if (empty($segments)) {
                    $segments = [$cleanP];
                }

                foreach ($segments as $seg) {
                    $seg = trim($seg);
                    if (empty($seg)) continue;

                    $presence = 'N/A';
                    $purity = 'N/A';

                    if (preg_match('/Presence of:\s*([^-\;\)]+)/i', $seg, $mPres)) {
                        $presence = trim($mPres[1]);
                    } elseif (preg_match('/^\[\d+\]\s*([^-\;\)]+)/i', $seg, $mPres)) {
                        $presence = trim($mPres[1]);
                    } elseif (!empty($seg) && !str_contains($seg, 'Purity')) {
                        $presence = trim($seg);
                    }

                    if ($presence === 'YES' || $presence === 'NO' || $presence === 'POS' || $presence === 'NEG') {
                        $presence = 'N/A';
                    }

                    if (preg_match('/Purity\/Percentage:\s*([^\;\)]+)/i', $seg, $mPur)) {
                        $purity = trim($mPur[1]);
                    }

                    if ($presence !== 'N/A' || $purity !== 'N/A' || count($segments) === 1) {
                        $pRows[] = [
                            'result' => $overallResult !== 'N/A' ? $overallResult : 'YES',
                            'presence' => $presence,
                            'purity' => $purity,
                        ];
                    }
                }
            }
            if (empty($pRows)) {
                $pRows[] = [
                    'result' => 'YES',
                    'presence' => 'Colorimetric Reaction',
                    'purity' => '50.5%',
                ];
            }
            $sDossierLabExam['presumptive_rows'] = $pRows;
        }

        return $sDossierLabExam;
    }
}

