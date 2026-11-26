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

    public const PRIORITIES = ['HIGH', 'LOW'];

    public $underlyingRecord = null;

    /**
     * Normalize any case priority string strictly into 'HIGH' or 'LOW'.
     */
    public static function normalizePriority(?string $priority, string $default = 'HIGH'): string
    {
        $fallback = strtoupper(trim($default)) === 'LOW' ? 'LOW' : 'HIGH';
        $norm = strtoupper(trim((string) $priority));
        if ($norm === '') {
            return $fallback;
        }
        if (in_array($norm, ['LOW', 'NORMAL', 'MEDIUM', 'ROUTINE', 'MINOR'], true)) {
            return 'LOW';
        }
        return 'HIGH';
    }

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

    public function exhibits()
    {
        if ($this->underlyingRecord && method_exists($this->underlyingRecord, 'exhibits')) {
            return $this->underlyingRecord->exhibits();
        }
        $isMulti = method_exists($this, 'isMultipleSuspect') ? $this->isMultipleSuspect() : !empty($this->is_multiple);
        $foreignKey = $isMulti ? 'multiple_suspect_case_id' : 'single_suspect_case_id';
        return $this->hasMany(Exhibit::class, $foreignKey);
    }

    public function labResults()
    {
        if ($this->underlyingRecord && method_exists($this->underlyingRecord, 'labResults')) {
            return $this->underlyingRecord->labResults();
        }
        $isMulti = method_exists($this, 'isMultipleSuspect') ? $this->isMultipleSuspect() : !empty($this->is_multiple);
        $foreignKey = $isMulti ? 'multiple_suspect_case_id' : 'single_suspect_case_id';
        return $this->hasMany(LaboratoryExaminationResult::class, $foreignKey);
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
        $instance->title = $record->title;
        $instance->unit_name = $record->unit_name;
        $instance->unit_id = $record->unit_id;
        $instance->user_id = $record->user_id;

        $isDrugCase = $instance->isDrugCase() || str_contains(strtolower($instance->unit_name ?? ''), 'lab');
        $rawLabNo = $record->laboratory_number ?? null;
        if ($isDrugCase) {
            if (empty($rawLabNo) || (!str_starts_with(trim($rawLabNo), 'Lab No.'))) {
                $normalizedLabNo = app(\App\Services\CaseReferenceService::class)->generateLabNumber();
                $instance->laboratory_number = $normalizedLabNo;
                try {
                    $record->laboratory_number = $normalizedLabNo;
                    $record->save();
                } catch (\Throwable $e) {}
            } else {
                $instance->laboratory_number = $rawLabNo;
            }
        } else {
            $instance->laboratory_number = null;
            if (!empty($rawLabNo)) {
                try {
                    $record->laboratory_number = null;
                    $record->save();
                } catch (\Throwable $e) {}
            }
        }
        $instance->arresting_officer = $record->arresting_officer ?? ($record->submitting_officer ?? null);
        $instance->submitting_officer = $instance->arresting_officer;
        $instance->operation_code = $record->operation_code ?? null;
        $instance->operation_code_name = $record->operation_code_name ?? null;
        $instance->search_warrant_document = $record->search_warrant_document ?? null;
        $instance->raid_location = $record->raid_location ?? null;
        $instance->holding_facility = $record->holding_facility ?? null;
        $instance->latitude = $record->latitude ?? null;
        $instance->longitude = $record->longitude ?? null;
        $instance->offense_description = $record->offense_description ?? null;
        $instance->description_of_exhibits = $record->description_of_exhibits ?? null;
        $casePriority = $record->priority;
        if (empty($casePriority) || $casePriority === 'NORMAL') {
            if ($record instanceof MultipleSuspectCase) {
                $origSingle = SingleSuspectCase::where('case_number', $record->case_number)->first();
                if ($origSingle && !empty($origSingle->priority) && $origSingle->priority !== 'NORMAL') {
                    $casePriority = $origSingle->priority;
                    try {
                        $record->priority = self::normalizePriority($origSingle->priority, 'HIGH');
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
        $instance->priority = self::normalizePriority($casePriority, 'HIGH');
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
        if ($record->relationLoaded('labResults')) {
            $instance->setRelation('labResults', $record->labResults);
        }
        $firstExhibit = $exhibits->first();

        // Fallback for raid_location if multi case record has it null
        if (empty($instance->raid_location) && $isMulti) {
            $origSingle = isset($origSingle) ? $origSingle : SingleSuspectCase::where('case_number', $record->case_number)->first();
            if ($origSingle && !empty($origSingle->raid_location)) {
                $instance->raid_location = $origSingle->raid_location;
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
            'tramadol', 'weed', 'hemp', 'hashish', 'ephedrine', 'precursor', 'opioid', 'fentanyl',
            'tapentadol', 'pregabalin', 'ketamine', 'diazepam', 'methaqualone', 'rohypnol',
            'diacetylmorphine', 'barbiturate', 'benzodiazepine',
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
        $instance->investigating_officer = $firstExhibit->investigating_officer ?? ($record->investigating_officer ?? ($recDocket['investigating_officer'] ?? ($recDocket['seizures'][0]['investigating_officer'] ?? null)));
        $instance->source_requesting_unit = $firstExhibit->source_requesting_unit ?? ($record->source_requesting_unit ?? ($recDocket['source_requesting_unit'] ?? ($recDocket['seizures'][0]['source_requesting_unit'] ?? null)));
        $instance->seizure_reason = $firstExhibit->seizure_reason ?? ($record->seizure_reason ?? ($recDocket['seizure_reason'] ?? ($recDocket['seizures'][0]['seizure_reason'] ?? null)));
        $instance->source_of_exhibits = $firstExhibit->source_of_exhibits ?? ($record->source_of_exhibits ?? ($recDocket['source_of_exhibits'] ?? ($recDocket['seizures'][0]['source_of_exhibits'] ?? null)));
        
        $firstVehExhibit = $exhibits->first(function ($e) {
            return !empty($e->veh_make) || !empty($e->veh_model) || !empty($e->veh_reg_no) || !empty($e->veh_vin);
        }) ?? $firstExhibit;
        $instance->veh_make = $firstVehExhibit->veh_make ?? ($record->veh_make ?? ($recDocket['veh_make'] ?? ($recDocket['seizures'][0]['veh_make'] ?? null)));
        $instance->veh_model = $firstVehExhibit->veh_model ?? ($record->veh_model ?? ($recDocket['veh_model'] ?? ($recDocket['seizures'][0]['veh_model'] ?? null)));

        $firstWpnExhibit = $exhibits->first(function ($e) {
            return !empty($e->wpn_model) || !empty($e->wpn_serial_no);
        }) ?? $firstExhibit;
        $instance->wpn_model = $firstWpnExhibit->wpn_model ?? ($record->wpn_model ?? ($recDocket['wpn_model'] ?? ($recDocket['seizures'][0]['wpn_model'] ?? null)));

        $firstDevExhibit = $exhibits->first(function ($e) {
            return !empty($e->dev_model) || !empty($e->dev_serial_no) || !empty($e->dev_imei1) || !empty($e->dev_imei2) || !empty($e->dev_mac_address) || !empty($e->dev_storage) || !empty($e->dev_colour) || !empty($e->dev_marks);
        }) ?? $firstExhibit;
        $instance->dev_model = $firstDevExhibit->dev_model ?? ($record->dev_model ?? ($recDocket['dev_model'] ?? ($recDocket['seizures'][0]['dev_model'] ?? ($recDocket['seizures'][0]['dev']['model'] ?? null))));
        $instance->dev_serial_no = $firstDevExhibit->dev_serial_no ?? ($record->dev_serial_no ?? ($recDocket['dev_serial_no'] ?? ($recDocket['seizures'][0]['dev_serial_no'] ?? ($recDocket['seizures'][0]['dev']['serial_no'] ?? null))));
        $instance->dev_imei1 = $firstDevExhibit->dev_imei1 ?? ($record->dev_imei1 ?? ($recDocket['dev_imei1'] ?? ($recDocket['seizures'][0]['dev_imei1'] ?? ($recDocket['seizures'][0]['dev']['imei1'] ?? null))));
        $instance->dev_imei2 = $firstDevExhibit->dev_imei2 ?? ($record->dev_imei2 ?? ($recDocket['dev_imei2'] ?? ($recDocket['seizures'][0]['dev_imei2'] ?? ($recDocket['seizures'][0]['dev']['imei2'] ?? null))));
        $instance->dev_mac_address = $firstDevExhibit->dev_mac_address ?? ($record->dev_mac_address ?? ($recDocket['dev_mac_address'] ?? ($recDocket['seizures'][0]['dev_mac_address'] ?? ($recDocket['seizures'][0]['dev']['mac_address'] ?? null))));
        $instance->dev_storage = $firstDevExhibit->dev_storage ?? ($record->dev_storage ?? ($recDocket['dev_storage'] ?? ($recDocket['seizures'][0]['dev_storage'] ?? ($recDocket['seizures'][0]['dev']['storage'] ?? null))));
        $instance->dev_colour = $firstDevExhibit->dev_colour ?? ($record->dev_colour ?? ($recDocket['dev_colour'] ?? ($recDocket['seizures'][0]['dev_colour'] ?? ($recDocket['seizures'][0]['dev']['colour'] ?? null))));
        $instance->dev_marks = $firstDevExhibit->dev_marks ?? ($record->dev_marks ?? ($recDocket['dev_marks'] ?? ($recDocket['seizures'][0]['dev_marks'] ?? ($recDocket['seizures'][0]['dev']['marks'] ?? null))));
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
            $isNoSuspectRec = $instance->isNoSuspectCase();
            $instance->gender = $isNoSuspectRec ? (($primarySuspect->gender ?? null) ?: 'N/A') : ($primarySuspect->gender ?? null);
            $instance->dob = $primarySuspect->dob ?? null;
            $instance->age = $primarySuspect->age ?? null;
            $instance->height = $primarySuspect->height ?? null;
            $instance->height_unit = $primarySuspect->height_unit ?? 'CM';
            $instance->eye_color = $primarySuspect->eye_color ?? null;
            $instance->nationality = $isNoSuspectRec
                ? ((!empty($primarySuspect->nationality) && $primarySuspect->nationality !== 'Ghanaian') ? $primarySuspect->nationality : 'N/A')
                : ($primarySuspect->nationality ?? 'Ghanaian');
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

            $sIsUnassigned = $instance->isNoSuspectCase() || self::isUnassignedSuspectName($s->suspect_name ?? '');
            $suspectsArray[] = [
                'id'               => $s->id ?? null,
                'associate_id'     => $assocId,
                'suspect_id'       => $assocId,
                'suspect_number'   => $sIdx + 1,
                'suspect_name'     => $s->suspect_name ?? null,
                'suspect_alias'    => $s->suspect_alias ?? 'N/A',
                'ghana_card'       => $s->ghana_card ?? null,
                'gender'           => $s->gender ?? ($sIsUnassigned ? 'N/A' : 'Male'),
                'dob'              => $s->dob ?? null,
                'age'              => $s->age ?? null,
                'height'           => $s->height ?? null,
                'eye_color'        => $s->eye_color ?? null,
                'nationality'      => $sIsUnassigned
                    ? ((!empty($s->nationality) && $s->nationality !== 'Ghanaian') ? $s->nationality : 'N/A')
                    : ($s->nationality ?? 'Ghanaian'),
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
                'exam_reference'            => (!empty($al->exam_reference)) ? (str_starts_with(trim($al->exam_reference), 'GSA-') ? str_replace('GSA-', 'NLR-', $al->exam_reference) : $al->exam_reference) : null,
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

            $labResultForEx = $ex->labResult;
            $szRec = is_array($recDocket['seizures'][$exIdx] ?? null) ? $recDocket['seizures'][$exIdx] : [];
            $seizuresArray[] = [
                'id'                 => $ex->id ?? ($szRec['id'] ?? null),
                'suspect_id'         => $ex->suspect_id ?? ($szRec['suspect_id'] ?? null),
                'category'           => $ex->exhibit_category,
                'exhibit_type'       => $ex->exhibit_type ?? ($szRec['exhibit_type'] ?? null),
                'drug_classification'=> $ex->drug_classification ?? ($szRec['drug_classification'] ?? null),
                'seizure_name'       => $ex->commodity_name,
                'seizure_name_drug'  => str_contains(strtolower($ex->exhibit_category ?? ''), 'drug') ? $ex->commodity_name : null,
                'exhibit_number'     => $ex->exhibit_number ?? ($szRec['exhibit_number'] ?? ('EXH-' . sprintf('%05d', $exIdx + 1))),
                'lab_exhibit_no'     => $ex->lab_exhibit_no ?? ($szRec['lab_exhibit_no'] ?? null),
                'lab_remark'         => $ex->lab_remark ?? ($szRec['lab_remark'] ?? null),
                'seizure_quantity'   => $ex->seizure_quantity,
                'seizure_packaging'  => $ex->seizure_packaging,
                'seizure_weight'     => $ex->seizure_weight,
                'seizure_unit'       => $ex->seizure_unit,
                'seizure_description'=> $szRec['seizure_description'] ?? ($szRec['description'] ?? null),
                'seizure_value'      => $ex->seizure_value,
                'seizure_concealment'=> $ex->seizure_concealment ?? ($szRec['seizure_concealment'] ?? null),
                'seizure_date'       => $ex->seizure_date ?? ($szRec['seizure_date'] ?? null),
                'seizure_time'       => $ex->seizure_time ?? ($szRec['seizure_time'] ?? null),
                'seizure_location'   => $ex->seizure_location ?? ($szRec['seizure_location'] ?? null),
                'seizure_officer'    => $ex->seizure_officer ?? ($szRec['seizure_officer'] ?? ($record->seizure_officer ?? null)),
                'investigating_officer' => $ex->investigating_officer ?? ($szRec['investigating_officer'] ?? ($record->investigating_officer ?? null)),
                'source_requesting_unit' => $ex->source_requesting_unit ?? ($szRec['source_requesting_unit'] ?? ($record->source_requesting_unit ?? null)),
                'seizure_reason'     => $ex->seizure_reason ?? ($szRec['seizure_reason'] ?? ($record->seizure_reason ?? null)),
                'source_of_exhibits' => $ex->source_of_exhibits ?? ($szRec['source_of_exhibits'] ?? ($record->source_of_exhibits ?? null)),
                'associated_person'  => $associatedPerson,
                'associate_id'       => $ex->suspect ? $ex->suspect->associate_id : null,
                'is_associate'       => $isAssoc,
                'ammunition_details' => is_array($ex->ammunition_details) ? $ex->ammunition_details : (json_decode($ex->ammunition_details ?? '[]', true) ?: ($szRec['ammunition_details'] ?? null)),
                'has_saved_lab'      => $hasSavedLab,
                'laboratory_number'  => $labResultForEx?->laboratory_number ?? ($szRec['laboratory_number'] ?? null),
                'exam_reference'     => $labResultForEx?->exam_reference ?? ($szRec['exam_reference'] ?? null),
                'purpose_of_examination' => $labResultForEx?->purpose_of_examination ?? ($szRec['purpose_of_examination'] ?? null),
                'preliminary_exam_procedure' => $labResultForEx?->preliminary_exam_procedure ?? ($szRec['preliminary_exam_procedure'] ?? null),
                'screening_method'   => $labResultForEx?->screening_method ?? ($szRec['screening_method'] ?? null),
                'presumptive_result' => $presumptiveResult,
                'presumptive_presence' => $labResultForEx?->presumptive_presence ?? ($szRec['presumptive_presence'] ?? null),
                'presumptive_percentage' => $labResultForEx?->presumptive_percentage ?? ($szRec['presumptive_percentage'] ?? null),
                'confirmatory_result' => $confirmatoryResult,
                'substance_identification' => $substanceIdent,
                'detected_substances' => $labResultForEx?->detected_substances ?? ($szRec['detected_substances'] ?? []),
                'sample_weight_description' => $labResultForEx?->sample_weight_description ?? ($szRec['sample_weight_description'] ?? null),
                'sample_weight_units' => $labResultForEx?->sample_weight_units ?? ($szRec['sample_weight_units'] ?? null),
                'sample_weight_gross' => $labResultForEx?->sample_weight_gross ?? ($szRec['sample_weight_gross'] ?? null),
                'sample_taken'        => $sampleWeightTaken,
                'sample_weight_taken' => $sampleWeightTaken,
                'sample_reserved'     => $sampleWeightReserved,
                'sample_weight_reserved' => $sampleWeightReserved,
                'test_methods'       => $labResultForEx?->test_methods ?? ($szRec['test_methods'] ?? null),
                'specifications'     => $labResultForEx?->specifications ?? ($szRec['specifications'] ?? null),
                'remark'             => $labResultForEx?->remark ?? ($szRec['remark'] ?? null),
                'disposition_of_exhibit' => $labResultForEx?->disposition_of_exhibit ?? ($szRec['disposition_of_exhibit'] ?? null),
                'findings'           => $labResultForEx?->findings ?? ($szRec['findings'] ?? null),
                'analyst'            => $labResultForEx?->analyst ?? ($szRec['analyst'] ?? null),
                'date_received'      => $labResultForEx?->date_received ?? ($szRec['date_received'] ?? null),
                'date_started'       => $labResultForEx?->date_started ?? ($szRec['date_started'] ?? null),
                'date_completed'     => $labResultForEx?->date_completed ?? ($szRec['date_completed'] ?? null),
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
                'exhibit_id'                 => $lr->exhibit_id,
                'suspect_name'               => $lr->suspect ? $lr->suspect->suspect_name : ($instance->suspect_name ?? 'Primary Suspect'),
                'exhibit_owner_name'         => $lr->suspect ? $lr->suspect->suspect_name : ($instance->suspect_name ?? 'Primary Suspect'),
                'laboratory_number'          => $lr->laboratory_number,
                'exam_reference'             => !empty($recDocket['lab_examination']['exam_reference']) 
                    ? (str_starts_with(trim($recDocket['lab_examination']['exam_reference']), 'GSA-') ? str_replace('GSA-', 'NLR-', $recDocket['lab_examination']['exam_reference']) : $recDocket['lab_examination']['exam_reference'])
                    : ((!empty($lr->exam_reference)) ? (str_starts_with(trim($lr->exam_reference), 'GSA-') ? str_replace('GSA-', 'NLR-', $lr->exam_reference) : $lr->exam_reference) : null),
                'purpose_of_examination'     => $lr->purpose_of_examination,
                'preliminary_exam_procedure' => $lr->preliminary_exam_procedure,
                'screening_method'           => $lr->screening_method,
                'analyst'                    => $lr->analyst,
                'date_received'              => $lr->date_received,
                'date_started'               => $lr->date_started,
                'date_completed'             => $lr->date_completed,
                'presumptive_result'         => $lr->presumptive_result,
                'presumptive_presence'       => $lr->presumptive_presence,
                'presumptive_percentage'     => $lr->presumptive_percentage,
                'confirmatory_result'        => $lr->confirmatory_result,
                'sample_weight_description'  => $lr->sample_weight_description,
                'sample_weight_units'        => $lr->sample_weight_units,
                'sample_weight_gross'        => $lr->sample_weight_gross,
                'sample_weight_taken'        => $lr->sample_weight_taken,
                'sample_weight_reserved'     => $lr->sample_weight_reserved,
                'substance_identification'   => $lr->substance_identification,
                'detected_substances'        => $lr->detected_substances,
                'test_methods'               => $lr->test_methods,
                'specifications'             => $lr->specifications,
                'remark'                     => $lr->remark,
                'disposition_of_exhibit'     => $lr->disposition_of_exhibit,
                'findings'                   => $lr->findings,
                'has_saved'                  => $lr->has_saved,
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
                'presumptive_presence'     => $primaryLab->presumptive_presence,
                'presumptive_percentage'   => $primaryLab->presumptive_percentage,
                'confirmatory_result'      => (!empty($primaryLab->confirmatory_result) && strtoupper(trim((string)$primaryLab->confirmatory_result)) === 'NO') ? 'NO' : 'YES',
                'substance_identification' => $primaryLab->substance_identification
                    ?: ($recDocket['lab_examination']['substance_identification']
                    ?? ($primaryLab->sample_weight_description
                    ?? ($recDocket['seizures'][0]['seizure_name_drug']
                    ?? ($instance->seizure_name_drug ?? null)))),
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
     * Determine whether a suspect name represents an unassigned / no-suspect placeholder.
     */
    public static function isUnassignedSuspectName(?string $name): bool
    {
        $clean = strtolower(trim((string) $name));
        if ($clean === '' || in_array($clean, ['n/a', 'unknown', 'unnamed suspect', 'primary suspect', 'none', 'null'], true)) {
            return true;
        }
        return str_contains($clean, 'unassigned')
            || str_contains($clean, 'no suspect')
            || str_contains($clean, 'awaiting suspect');
    }

    /**
     * Collect non-empty seizure locations from a case record, its exhibits, and docket payload.
     */
    public static function extractSeizureLocations(mixed $case, array $docketData = [], array $suspectItem = []): array
    {
        $locations = [];
        $pushLocation = static function (mixed $val) use (&$locations): void {
            $trimmed = trim((string) ($val ?? ''));
            if ($trimmed !== '' && strtoupper($trimmed) !== 'N/A') {
                $locations[] = $trimmed;
            }
        };

        if (is_object($case)) {
            $pushLocation($case->seizure_location ?? null);
            $exhibits = $case->exhibits ?? null;
            if (is_iterable($exhibits)) {
                foreach ($exhibits as $ex) {
                    $pushLocation(is_object($ex) ? ($ex->seizure_location ?? null) : ($ex['seizure_location'] ?? null));
                }
            }
        }

        if (!empty($docketData['seizure_location'])) {
            $pushLocation($docketData['seizure_location']);
        }
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $sz) {
                if (is_array($sz)) {
                    $pushLocation($sz['seizure_location'] ?? null);
                }
            }
        }
        if (!empty($suspectItem['seizure_location'])) {
            $pushLocation($suspectItem['seizure_location']);
        }
        foreach (['seizures', 'associated_seizures', 'associate_exhibits'] as $szKey) {
            if (!empty($suspectItem[$szKey]) && is_array($suspectItem[$szKey])) {
                foreach ($suspectItem[$szKey] as $sz) {
                    if (is_array($sz)) {
                        $pushLocation($sz['seizure_location'] ?? null);
                    }
                }
            }
        }

        return array_values(array_unique($locations));
    }

    /**
     * Resolve Raid Scene / Offence Location for display without leaking vault defaults or seizure_location on unassigned cases.
     */
    public static function resolveRaidLocationForDisplay(?string $candidateRaid, bool $isUnassignedSuspect = false, array $seizureLocations = []): string
    {
        $clean = trim((string) $candidateRaid);
        if ($clean === '' || in_array(strtoupper($clean), ['N/A', 'NA', '-', 'NULL', 'UNSPECIFIED', 'UNSPECIFIED SCENE', 'UNSPECIFIED SCENE ()'], true)) {
            return 'N/A';
        }

        if ($isUnassignedSuspect) {
            $vaultPlaceholders = [
                'NACOC FORENSIC DIRECTORATE VAULT',
                'DIGITAL FORENSICS UNIT',
                'DIGITAL & PHYSICAL FORENSICS',
                'DIGITAL & PHYSICAL FORENSIC UNIT',
                'NACOC EVIDENCE VAULT',
                'EVIDENCE MANAGEMENT',
                'LAB ANALYSIS',
                'CRIME SCENE SUPPORT',
            ];
            if (in_array(strtoupper($clean), $vaultPlaceholders, true)) {
                return 'N/A';
            }
            foreach ($seizureLocations as $sLoc) {
                $trimmedLoc = trim((string) $sLoc);
                if ($trimmedLoc !== '' && strcasecmp($clean, $trimmedLoc) === 0) {
                    return 'N/A';
                }
            }
        }

        return $clean;
    }

    /**
     * Determine whether this case currently has no suspect assigned.
     */
    public function isNoSuspectCase(): bool
    {
        $dData = is_array($this->docket_data) ? $this->docket_data : (json_decode($this->docket_data ?? '[]', true) ?: []);
        if (!empty($this->no_suspect) || !empty($dData['no_suspect'])) {
            return true;
        }
        return self::isUnassignedSuspectName($this->suspect_name ?? null);
    }

    /**
     * Generate structured narrative for Brief Case Description from saved suspect & case info
     */
    public function generateBriefCaseDescription(): string
    {
        $dData = is_array($this->docket_data) ? $this->docket_data : (json_decode($this->docket_data ?? '[]', true) ?: []);
        $isNoSuspect = $this->isNoSuspectCase();

        // 1. Date (Formatted when available)
        $rawArrestDate = $this->arrest_date
            ?? ($this->date_of_arrest
            ?? ($this->incident_date
            ?? ($dData['arrest_date']
            ?? ($dData['date_of_arrest']
            ?? ($dData['incident_date']
            ?? ($dData['suspects'][0]['arrest_date']
            ?? ($dData['suspects'][0]['date_of_arrest']
            ?? null)))))));

        if (!empty($rawArrestDate) && strtotime((string) $rawArrestDate)) {
            $formattedDate = date('l, d F Y', strtotime((string) $rawArrestDate));
        } elseif ($this->created_at) {
            $formattedDate = $this->created_at->format('l, d F Y');
        } else {
            $formattedDate = null;
        }

        // 2. Operation Type (always fetch from operation_code_name column when present)
        $rawOpType = $this->operation_code_name
            ?? ($this->underlyingRecord->operation_code_name ?? null)
            ?? ($dData['operation_code_name'] ?? null)
            ?? ($dData['suspects'][0]['operation_code_name'] ?? null)
            ?? ($this->operation_code ?? null)
            ?? ($this->underlyingRecord->operation_code ?? null)
            ?? ($this->operation_type ?? null)
            ?? ($dData['operation_type'] ?? null)
            ?? ($dData['op_type'] ?? null);

        $opType = null;
        if (!empty($rawOpType)) {
            $opTypeStr = trim((string) $rawOpType);
            $opType = !str_contains(strtolower($opTypeStr), 'operation')
                ? strtolower($opTypeStr) . ' operation'
                : strtolower($opTypeStr);
        } elseif (!$isNoSuspect) {
            $opType = 'forensic operation';
        }

        $article = $opType && preg_match('/^[aeiou]/i', $opType) ? 'an' : 'a';

        // 3. Raid Scene / Offence Location
        $rawLoc = $this->raid_location
            ?? ($dData['raid_location']
            ?? ($this->location ?? null));
        $resolvedLoc = self::resolveRaidLocationForDisplay(
            $rawLoc,
            $isNoSuspect,
            self::extractSeizureLocations($this, $dData)
        );
        $loc = $resolvedLoc === 'N/A' ? null : $resolvedLoc;

        // 4. Suspect Full Legal Name
        $suspectName = !$isNoSuspect ? ($this->suspect_name ?? null) : null;
        if (!$isNoSuspect && empty($suspectName) && isset($this->underlyingRecord)) {
            $suspectName = $this->underlyingRecord->suspect_name ?? null;
        }
        if (!$isNoSuspect && empty($suspectName) && !empty($dData['suspects']) && is_array($dData['suspects'])) {
            $sNames = [];
            foreach ($dData['suspects'] as $spItem) {
                if (!empty($spItem['suspect_name']) && !self::isUnassignedSuspectName($spItem['suspect_name'])) {
                    $sNames[] = $spItem['suspect_name'];
                }
            }
            if (!empty($sNames)) {
                $suspectName = implode(', ', array_unique($sNames));
            }
        }
        if (!$isNoSuspect && self::isUnassignedSuspectName($suspectName)) {
            $suspectName = null;
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
        if (is_string($holdingFacility) && in_array(trim($holdingFacility), ['', 'N/A'], true)) {
            $holdingFacility = null;
        }

        // 6. Exhibit classification: determine if linked to only electronics, both drugs and electronics, or drugs
        $flags = $this->classifyExhibits();
        $hasDrug = !empty($flags['has_drug']) || $this->isDrugCase();
        $hasElectronic = !empty($flags['has_electronic']) || $this->isDigitalCase();

        $suspectPhones = $this->phones ?? ($this->underlyingRecord->phones ?? ($dData['phones'] ?? ($dData['additional_phones'] ?? null)));
        if (!empty($suspectPhones)) {
            $hasElectronic = true;
        }

        if ($isNoSuspect) {
            $explicitDesc = trim((string) (
                $this->offense_description
                ?? ($this->underlyingRecord->offense_description ?? null)
                ?? ($dData['offense_description'] ?? null)
                ?? ($this->underlyingRecord->description ?? null)
                ?? ($dData['description'] ?? null)
                ?? ''
            ));
            if ($explicitDesc !== '' && strtoupper($explicitDesc) !== 'N/A' && $explicitDesc !== 'Directorate Authorized Forensic Case File') {
                return $explicitDesc;
            }
            return 'N/A';
        }

        $dateStr = $formattedDate ?? 'N/A';
        $locStr = $loc ?? 'N/A';
        $suspectStr = $suspectName ?? 'N/A';
        $facilityStr = $holdingFacility ?? 'N/A';
        $opStr = $opType ?? 'forensic operation';
        $opArticle = preg_match('/^[aeiou]/i', $opStr) ? 'an' : 'a';

        if ($hasElectronic && !$hasDrug) {
            return "On {$dateStr}, officers of the Narcotics Control Commission (NACOC) conducted {$opArticle} {$opStr} at {$locStr} to intercept and seize illicit electronic devices and digital evidence within the area. During the operation, {$suspectStr} was arrested and electronic exhibits were seized, and the suspect was subsequently conveyed to {$facilityStr} for digital forensic examination and further investigation.";
        }

        if ($hasElectronic && $hasDrug) {
            return "On {$dateStr}, officers of the Narcotics Control Commission (NACOC) conducted {$opArticle} {$opStr} at {$locStr} to curb the trafficking of narcotic drugs and seize electronic devices used in illicit activities within the area. During the operation, {$suspectStr} was arrested in possession of narcotic substances and electronic exhibits, and subsequently conveyed to {$facilityStr} for chemical laboratory analysis and digital forensic investigation.";
        }

        return "On {$dateStr}, officers of the Narcotics Control Commission (NACOC) conducted {$opArticle} {$opStr} at {$locStr} to curb the use and sale of narcotic drugs within the area. During the operation, {$suspectStr} was arrested and subsequently conveyed to {$facilityStr} for further investigation.";
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
                if (isset($this->attributes['presumptive_test_result'])) {
                    $savePresParsed = self::parsePresumptiveData(
                        $this->attributes['presumptive_test_result'],
                        $this->attributes['presumptive_presence'] ?? ($lab->presumptive_presence ?? null),
                        $this->attributes['presumptive_percentage'] ?? ($lab->presumptive_percentage ?? null)
                    );
                    $labData['presumptive_result'] = $savePresParsed['result'];
                    if ($savePresParsed['presence'] !== null) {
                        $labData['presumptive_presence'] = $savePresParsed['presence'];
                    }
                    if ($savePresParsed['percentage'] !== null) {
                        $labData['presumptive_percentage'] = $savePresParsed['percentage'];
                    }
                } elseif (isset($this->attributes['presumptive_presence']) || isset($this->attributes['presumptive_percentage'])) {
                    $savePresParsed = self::parsePresumptiveData(
                        $lab->presumptive_result,
                        $this->attributes['presumptive_presence'] ?? ($lab->presumptive_presence ?? null),
                        $this->attributes['presumptive_percentage'] ?? ($lab->presumptive_percentage ?? null)
                    );
                    $labData['presumptive_result'] = $savePresParsed['result'];
                    $labData['presumptive_presence'] = $savePresParsed['presence'];
                    $labData['presumptive_percentage'] = $savePresParsed['percentage'];
                }
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
                if (in_array($col, ['suspect_disposition', 'disposition_reason', 'disposition_updated_at', 'disposition_updated_by'], true)) {
                    if (array_key_exists($col, $this->attributes) && $this->attributes[$col] !== null) {
                        $data[$col] = $this->attributes[$col];
                    }
                    continue;
                }
                if (array_key_exists($col, $this->attributes)) {
                    $data[$col] = $this->attributes[$col];
                } elseif (isset($this->$col) || property_exists($this, $col)) {
                    $data[$col] = $this->$col;
                }
            }
            if (!empty($data)) {
                $this->underlyingRecord->update($data);
            }

            $primarySuspect = $this->underlyingRecord->suspects()->where('role', 'Primary Suspect')->first();
            if ($primarySuspect) {
                $spFillable = (new CaseSuspect)->getFillable();
                $excludedCols = [
                    'id',
                    'single_suspect_case_id',
                    'multiple_suspect_case_id',
                    'suspect_number',
                    'role',
                    'associate_id',
                    'status',
                    'case_status',
                    'suspect_disposition',
                    'disposition_reason',
                    'disposition_updated_at',
                    'disposition_updated_by',
                ];
                $spData = [];
                foreach ($spFillable as $col) {
                    if (in_array($col, $excludedCols, true)) {
                        continue;
                    }
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

    public static function normalizeLabStringField($val): ?string
    {
        if (is_array($val)) {
            $filtered = array_values(array_filter(array_map('trim', $val), fn ($v) => $v !== ''));
            return count($filtered) > 0 ? implode(', ', $filtered) : null;
        }
        return (is_string($val) && trim($val) !== '') ? trim($val) : (is_scalar($val) ? (string) $val : null);
    }

    public static function firstNonEmptyString(array $candidates, ?string $default = null): ?string
    {
        foreach ($candidates as $candidate) {
            if ($candidate === null || is_array($candidate) || is_object($candidate)) {
                continue;
            }
            $trimmed = trim((string) $candidate);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return $default;
    }

    public static function hasTopLevelExhibitData(array $attributes): bool
    {
        return self::firstNonEmptyString([
            $attributes['commodity_name'] ?? null,
            $attributes['seizure_name'] ?? null,
            $attributes['seizure_name_drug'] ?? null,
            $attributes['seizure_name_currency'] ?? null,
            $attributes['seizure_name_weapon'] ?? null,
            $attributes['seizure_name_ammo'] ?? null,
            $attributes['seizure_name_device'] ?? null,
            $attributes['dev_model'] ?? null,
            $attributes['device_model'] ?? null,
            $attributes['dev_serial_no'] ?? null,
            $attributes['device_serial'] ?? null,
            $attributes['dev_imei1'] ?? null,
            $attributes['imei_1'] ?? null,
            $attributes['wpn_model'] ?? null,
            $attributes['veh_make'] ?? null,
            $attributes['veh_model'] ?? null,
        ]) !== null;
    }

    public static function resolveExhibitCommodityName(array $sz, array $attributes = []): string
    {
        return self::firstNonEmptyString([
            $sz['commodity_name'] ?? null,
            $sz['seizure_name_currency'] ?? null,
            $sz['seizure_name_drug'] ?? null,
            $sz['seizure_name_weapon'] ?? null,
            $sz['seizure_name_ammo'] ?? null,
            $sz['seizure_name_device'] ?? null,
            $sz['seizure_name'] ?? null,
            $sz['name'] ?? null,
            $sz['dev_model'] ?? null,
            $sz['device_model'] ?? null,
            $sz['wpn_model'] ?? null,
            $sz['veh_model'] ?? null,
            $attributes['commodity_name'] ?? null,
            $attributes['seizure_name_currency'] ?? null,
            $attributes['seizure_name_drug'] ?? null,
            $attributes['seizure_name_weapon'] ?? null,
            $attributes['seizure_name_ammo'] ?? null,
            $attributes['seizure_name_device'] ?? null,
            $attributes['seizure_name'] ?? null,
            $attributes['dev_model'] ?? null,
            $attributes['device_model'] ?? null,
        ], 'Exhibits');
    }

    public static function resolveExhibitCategory(array $sz, array $attributes = []): string
    {
        $explicit = self::firstNonEmptyString([
            $sz['exhibit_category'] ?? null,
            $sz['category'] ?? null,
            $attributes['exhibit_category'] ?? null,
        ]);

        if ($explicit !== null) {
            return $explicit;
        }

        $hasDeviceDetails = self::firstNonEmptyString([
            $sz['seizure_name_device'] ?? null,
            $sz['dev_model'] ?? null,
            $sz['device_model'] ?? null,
            $sz['dev_serial_no'] ?? null,
            $sz['dev_imei1'] ?? null,
            $attributes['seizure_name_device'] ?? null,
            $attributes['dev_model'] ?? null,
        ]) !== null;

        if ($hasDeviceDetails) {
            return 'Electronic Devices';
        }

        $texts = array_filter([
            $sz['commodity_name'] ?? null,
            $sz['seizure_name'] ?? null,
            $sz['name'] ?? null,
            $sz['description'] ?? null,
            $attributes['commodity_name'] ?? null,
            $attributes['seizure_name'] ?? null,
        ]);

        foreach ($texts as $text) {
            if (!is_string($text) || trim($text) === '') {
                continue;
            }
            $lower = strtolower($text);

            foreach (self::$ELECTRONIC_KEYWORDS as $kw) {
                if (str_contains($lower, $kw)) {
                    return 'Electronic Devices';
                }
            }

            foreach (self::$WEAPON_OR_VEHICLE_KEYWORDS as $kw) {
                if (str_contains($lower, $kw)) {
                    return 'Weapons & Ammunition';
                }
            }

            foreach (self::$MONEY_OR_GOLD_KEYWORDS as $kw) {
                if (str_contains($lower, $kw)) {
                    return 'Cash & Valuables';
                }
            }

            if (!empty($sz['seizure_name_drug'])) {
                return 'Drugs';
            }
            foreach (self::$DRUG_KEYWORDS as $dkw) {
                if (str_contains($lower, $dkw)) {
                    return 'Drugs';
                }
            }
        }

        $unitName = strtolower((string)($attributes['unit_name'] ?? ''));
        if (str_contains($unitName, 'digital') || str_contains($unitName, 'cyber')) {
            return 'Electronic Devices';
        }
        if (str_contains($unitName, 'ballistics') || str_contains($unitName, 'weapon')) {
            return 'Weapons & Ammunition';
        }

        return 'Drugs';
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
        $caseRef = $attributes['case_number'] ?? app(\App\Services\CaseNumberService::class)->generate();

        if ($isMulti) {
            $multi = MultipleSuspectCase::create([
                'case_number'          => $caseRef,
                'laboratory_number'    => $attributes['laboratory_number'] ?? null,
                'title'                => $attributes['title'] ?? 'Multiple Suspect Docket File',
                'unit_name'            => $attributes['unit_name'] ?? 'Crime Scene Support',
                'unit_id'              => $attributes['unit_id'] ?? null,
                'user_id'              => $attributes['user_id'] ?? null,
                'arresting_officer'    => $attributes['arresting_officer'] ?? ($attributes['submitting_officer'] ?? null),
                'submitting_officer'   => $attributes['arresting_officer'] ?? ($attributes['submitting_officer'] ?? null),
                'operation_code'       => $attributes['operation_code'] ?? null,
                'operation_code_name'  => $attributes['operation_code_name'] ?? null,
                'search_warrant_document' => $attributes['search_warrant_document'] ?? null,
                'raid_location'        => $attributes['raid_location'] ?? ($attributes['location'] ?? null),
                'latitude'             => isset($attributes['latitude']) && is_numeric($attributes['latitude']) ? (float)$attributes['latitude'] : null,
                'longitude'            => isset($attributes['longitude']) && is_numeric($attributes['longitude']) ? (float)$attributes['longitude'] : null,
                'offense_description'  => $attributes['offense_description'] ?? ($attributes['description'] ?? null),
                'description_of_exhibits' => $attributes['description_of_exhibits'] ?? ($docketData['description_of_exhibits'] ?? null),
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

            if (!empty($attributes['created_at'])) {
                $multi->created_at = $attributes['created_at'];
                $multi->saveQuietly();
            }

            $createdSuspects = [];
            foreach ($suspects as $sIdx => $s) {
                $multiSuspectName = $s['suspect_name'] ?? ($attributes['suspect_name'] ?? 'Unnamed Suspect');
                $sIsUnassigned = self::isUnassignedSuspectName($multiSuspectName);
                $cs = CaseSuspect::create([
                    'multiple_suspect_case_id' => $multi->id,
                    'suspect_number'           => $sIdx + 1,
                    'role'                     => $s['association_role'] ?? ($s['role'] ?? ($sIdx === 0 ? 'Primary Suspect' : 'Associate Suspect')),
                    'associate_id'             => $s['associate_id'] ?? ($s['suspect_id'] ?? ($sIdx > 0 ? ('ASC-' . str_pad($sIdx, 3, '0', STR_PAD_LEFT)) : null)),
                    'suspect_name'             => $multiSuspectName,
                    'suspect_alias'            => $s['suspect_alias'] ?? null,
                    'ghana_card'               => $s['ghana_card'] ?? null,
                    'gender'                   => $s['gender'] ?? ($attributes['gender'] ?? ($sIsUnassigned ? 'N/A' : 'Male')),
                    'dob'                      => $s['dob'] ?? null,
                    'age'                      => isset($s['age']) && $s['age'] !== '' ? (int)$s['age'] : null,
                    'height'                   => $s['height'] ?? null,
                    'eye_color'                => $s['eye_color'] ?? null,
                    'nationality'              => $s['nationality'] ?? ($attributes['nationality'] ?? ($sIsUnassigned ? 'N/A' : 'Ghanaian')),
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
                    'latitude'                 => isset($s['latitude']) && is_numeric($s['latitude']) ? (float)$s['latitude'] : (isset($attributes['latitude']) && is_numeric($attributes['latitude']) ? (float)$attributes['latitude'] : null),
                    'longitude'                => isset($s['longitude']) && is_numeric($s['longitude']) ? (float)$s['longitude'] : (isset($attributes['longitude']) && is_numeric($attributes['longitude']) ? (float)$attributes['longitude'] : null),
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
            $hasExplicitTopLevelExhibit = self::hasTopLevelExhibitData($attributes);
            if (empty($seizures)) {
                if ($hasExplicitTopLevelExhibit) {
                    $resolvedTopCategory = self::resolveExhibitCategory([], $attributes);
                    $ex = Exhibit::create([
                        'case_type'               => 'MULTIPLE',
                        'multiple_suspect_case_id'=> $multi->id,
                        'suspect_id'              => $createdSuspects[0]->id ?? null,
                        'associated_person'       => $createdSuspects[0]->suspect_name ?? ($attributes['suspect_name'] ?? 'All Suspects'),
                        'exhibit_number'          => null,
                        'lab_exhibit_no'          => $attributes['lab_exhibit_no'] ?? ($attributes['lab_exhibit_number'] ?? null),
                        'exhibit_category'        => $resolvedTopCategory,
                        'commodity_name'          => self::resolveExhibitCommodityName([], $attributes),
                        'seizure_quantity'        => $attributes['seizure_quantity'] ?? null,
                        'amount'                  => (!empty($attributes['amount']) && $attributes['amount'] !== 'N/A') ? $attributes['amount'] : (in_array(strtolower(trim((string)($attributes['exhibit_category'] ?? 'Drugs'))), ['money', 'currency', 'cash']) ? (!empty($attributes['seizure_quantity']) ? $attributes['seizure_quantity'] : ((!empty($attributes['seizure_value']) && $attributes['seizure_value'] !== 'N/A') ? $attributes['seizure_value'] : null)) : null),
                        'seizure_weight'          => $attributes['seizure_weight'] ?? null,
                        'seizure_unit'            => $attributes['seizure_unit'] ?? null,
                        'seizure_packaging'       => $attributes['seizure_packaging'] ?? ($attributes['packaging_type'] ?? null),
                        'contents'                => $attributes['contents'] ?? null,
                        'description_of_exhibits' => $attributes['description_of_exhibits'] ?? ($docketData['description_of_exhibits'] ?? null),
                        'seizure_value'           => $attributes['seizure_value'] ?? ($attributes['valuation'] ?? null),
                        'seizure_concealment'     => $attributes['seizure_concealment'] ?? null,
                        'seizure_date'            => $attributes['seizure_date'] ?? null,
                        'seizure_time'            => $attributes['seizure_time'] ?? null,
                        'seizure_location'        => $attributes['seizure_location'] ?? null,
                        'seizure_officer'         => $attributes['seizure_officer'] ?? null,
                        'investigating_officer'    => $attributes['investigating_officer'] ?? null,
                        'source_requesting_unit'   => $attributes['source_requesting_unit'] ?? null,
                        'seizure_reason'          => $attributes['seizure_reason'] ?? null,
                        'source_of_exhibits'      => $attributes['source_of_exhibits'] ?? null,
                        'exhibit_remark'          => $attributes['exhibit_remark'] ?? ($attributes['remark'] ?? ($attributes['remarks'] ?? null)),
                        'chain_of_custody'        => $attributes['chain_of_custody'] ?? null,
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
                    $linkedExhibitIdsMulti = array_map('intval', (array) ($docketData['linked_exhibit_ids'] ?? []));
                    if (!empty($sz['linked_exhibit_id']) && in_array((int) $sz['linked_exhibit_id'], $linkedExhibitIdsMulti, true)) {
                        continue;
                    }

                    $ex = Exhibit::create([
                        'case_type'               => 'MULTIPLE',
                        'multiple_suspect_case_id'=> $multi->id,
                        'suspect_id'              => $matchedSuspect ? $matchedSuspect->id : null,
                        'associated_person'       => $isJointOwner ? 'All Suspects' : ($matchedSuspect ? $matchedSuspect->suspect_name : ($ownerName ?: 'All Suspects')),
                        'exhibit_number'          => $sz['exhibit_number'] ?? null,
                        'lab_exhibit_no'          => $sz['lab_exhibit_no'] ?? ($sIdx === 0 ? ($attributes['lab_exhibit_no'] ?? ($attributes['lab_exhibit_number'] ?? null)) : null),
                        'property_id'             => $sz['property_id'] ?? null,
                        'exhibit_category'        => self::resolveExhibitCategory($sz, $attributes),
                        'exhibit_type'            => $sz['exhibit_type'] ?? ($attributes['exhibit_type'] ?? null),
                        'drug_classification'     => $sz['drug_classification'] ?? ($attributes['drug_classification'] ?? null),
                        'commodity_name'          => self::resolveExhibitCommodityName($sz, $attributes),
                        'seizure_quantity'        => $sz['seizure_quantity'] ?? null,
                        'amount'                  => (!empty($sz['amount']) && $sz['amount'] !== 'N/A') ? $sz['amount'] : ((!empty($attributes['amount']) && $attributes['amount'] !== 'N/A') ? $attributes['amount'] : (in_array(strtolower(trim((string)($sz['exhibit_category'] ?? ($sz['category'] ?? ($attributes['exhibit_category'] ?? 'Drugs'))))), ['money', 'currency', 'cash']) ? (!empty($sz['seizure_quantity']) ? $sz['seizure_quantity'] : ((!empty($sz['seizure_value']) && $sz['seizure_value'] !== 'N/A') ? $sz['seizure_value'] : null)) : null)),
                        'seizure_weight'          => $sz['seizure_weight'] ?? null,
                        'seizure_unit'            => $sz['seizure_unit'] ?? null,
                        'seizure_packaging'       => $sz['seizure_packaging'] ?? ($sz['packaging'] ?? ($attributes['seizure_packaging'] ?? null)),
                        'seizure_value'           => $sz['seizure_value'] ?? ($sz['valuation'] ?? ($attributes['seizure_value'] ?? ($attributes['valuation'] ?? null))),
                        'seizure_concealment'     => $sz['seizure_concealment'] ?? ($sz['concealment'] ?? ($attributes['seizure_concealment'] ?? null)),
                        'seizure_date'            => $sz['seizure_date'] ?? ($attributes['seizure_date'] ?? null),
                        'seizure_time'            => $sz['seizure_time'] ?? ($attributes['seizure_time'] ?? null),
                        'seizure_location'        => $sz['seizure_location'] ?? ($attributes['seizure_location'] ?? null),
                        'seizure_officer'         => $sz['seizure_officer'] ?? ($attributes['seizure_officer'] ?? null),
                        'investigating_officer'    => $sz['investigating_officer'] ?? ($attributes['investigating_officer'] ?? null),
                        'source_requesting_unit'   => $sz['source_requesting_unit'] ?? ($attributes['source_requesting_unit'] ?? null),
                        'seizure_reason'          => $sz['seizure_reason'] ?? ($attributes['seizure_reason'] ?? null),
                        'source_of_exhibits'      => $sz['source_of_exhibits'] ?? ($attributes['source_of_exhibits'] ?? null),
                        'exhibit_remark'          => $sz['exhibit_remark'] ?? ($sz['remark'] ?? ($sz['remarks'] ?? ($attributes['exhibit_remark'] ?? ($attributes['remark'] ?? ($attributes['remarks'] ?? null))))),
                        'chain_of_custody'        => $sz['chain_of_custody'] ?? ($attributes['chain_of_custody'] ?? null),
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
                        'contents'                => $sz['contents'] ?? ($sz['seizure_contents'] ?? ($attributes['contents'] ?? null)),
                        'description_of_exhibits' => $sz['description_of_exhibits'] ?? ($attributes['description_of_exhibits'] ?? ($docketData['description_of_exhibits'] ?? null)),
                        'evidence_photos'         => !empty($sz['evidence_photos']) ? $sz['evidence_photos'] : (!empty($sz['photos']) ? $sz['photos'] : (!empty($sz['photo']) ? [$sz['photo']] : ($sIdx === 0 ? ($attributes['evidence_photos'] ?? ($docketData['evidence_photos'] ?? [])) : []))),
                    ]);

                    $hasLabForSeizure = !empty($sz['has_saved_lab']) 
                        || !empty($sz['presumptive_result']) 
                        || !empty($sz['detected_substances']) 
                        || !empty($sz['sample_gross']) 
                        || !empty($sz['sample_taken']) 
                        || !empty($sz['substance_identification']);

                    if ($hasLabForSeizure) {
                        $hasCreatedLabFromSeizure = true;

                        // Resolve unique exam reference per exhibit to avoid unique constraint collision
                        $seizureExamRef = !empty($sz['exam_reference']) ? trim($sz['exam_reference']) : null;
                        $baseRef = $attributes['exam_reference'] ?? ($labDataFromDocket['exam_reference'] ?? null);

                        if (empty($seizureExamRef)) {
                            if (!empty($baseRef)) {
                                $candidateRef = ($sIdx === 0) ? $baseRef : ($baseRef . '-' . ($sIdx + 1));
                                if (!LaboratoryExaminationResult::where('exam_reference', $candidateRef)->exists()) {
                                    $seizureExamRef = $candidateRef;
                                } else {
                                    $seizureExamRef = app(\App\Services\CaseReferenceService::class)->generateExamReference();
                                }
                            } else {
                                $seizureExamRef = app(\App\Services\CaseReferenceService::class)->generateExamReference();
                            }
                        } else {
                            if (LaboratoryExaminationResult::where('exam_reference', $seizureExamRef)->exists()) {
                                $candidateRef = $seizureExamRef . '-' . ($sIdx + 1);
                                if (!LaboratoryExaminationResult::where('exam_reference', $candidateRef)->exists()) {
                                    $seizureExamRef = $candidateRef;
                                } else {
                                    $seizureExamRef = app(\App\Services\CaseReferenceService::class)->generateExamReference();
                                }
                            }
                        }

                        $szPresParsed = self::parsePresumptiveData(
                            $sz['presumptive_result'] ?? ($labDataFromDocket['presumptive_result'] ?? ($attributes['presumptive_result'] ?? ($attributes['presumptive_test_result'] ?? null))),
                            $sz['presumptive_presence'] ?? ($labDataFromDocket['presumptive_presence'] ?? ($attributes['presumptive_presence'] ?? null)),
                            $sz['presumptive_percentage'] ?? ($labDataFromDocket['presumptive_percentage'] ?? ($attributes['presumptive_percentage'] ?? ($attributes['presumptive_Percentage'] ?? null)))
                        );
                        LaboratoryExaminationResult::create([
                            'case_type'               => 'MULTIPLE',
                            'multiple_suspect_case_id'=> $multi->id,
                            'suspect_id'              => $matchedSuspect ? $matchedSuspect->id : ($createdSuspects[0]->id ?? null),
                            'exhibit_id'              => $ex->id,
                            'laboratory_number'       => $sz['laboratory_number'] ?? ($multi->laboratory_number ?? ($labDataFromDocket['laboratory_number'] ?? null)),
                            'exam_reference'          => $seizureExamRef,
                            'purpose_of_examination' => self::normalizeLabStringField($sz['purpose_of_examination'] ?? ($attributes['purpose_of_examination'] ?? ($attributes['lab_purpose_of_examination'] ?? ($labDataFromDocket['purpose_of_examination'] ?? null)))),
                            'preliminary_exam_procedure' => self::normalizeLabStringField($sz['preliminary_exam_procedure'] ?? ($attributes['preliminary_exam_procedure'] ?? ($attributes['lab_preliminary_exam_procedure'] ?? ($labDataFromDocket['preliminary_exam_procedure'] ?? null)))),
                            'screening_method'        => self::normalizeLabStringField($sz['screening_method'] ?? ($attributes['screening_method'] ?? ($attributes['confirmatory_screening_method'] ?? ($labDataFromDocket['screening_method'] ?? null)))),
                            'date_received'           => $sz['date_received'] ?? ($labDataFromDocket['date_received'] ?? ($labDataFromDocket['lab_date_received'] ?? date('Y-m-d'))),
                            'date_started'            => $sz['date_started'] ?? ($labDataFromDocket['date_started'] ?? ($labDataFromDocket['lab_date_started'] ?? date('Y-m-d'))),
                            'date_completed'          => $sz['date_completed'] ?? ($labDataFromDocket['date_completed'] ?? ($labDataFromDocket['lab_date_completed'] ?? date('Y-m-d'))),
                            'presumptive_result'      => $szPresParsed['result'],
                            'presumptive_presence'    => $szPresParsed['presence'],
                            'presumptive_percentage'  => $szPresParsed['percentage'],
                            'confirmatory_result'     => $sz['confirmatory_result'] ?? ($labDataFromDocket['confirmatory_result'] ?? 'Pending'),
                            'substance_identification'=> $sz['substance_identification'] ?? ($labDataFromDocket['substance_identification'] ?? ($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? null))),
                            'sample_weight_description'=> $sz['sample_description'] ?? ($sz['sample_weight_description'] ?? ($labDataFromDocket['sample_weight']['description'] ?? ($labDataFromDocket['sample_weight_description'] ?? null))),
                            'sample_weight_units'     => $sz['sample_units'] ?? ($sz['sample_weight_units'] ?? ($labDataFromDocket['sample_weight']['units_packaging'] ?? ($labDataFromDocket['sample_weight_units'] ?? null))),
                            'sample_weight_gross'     => $sz['sample_gross'] ?? ($sz['sample_weight_gross'] ?? ($labDataFromDocket['sample_weight']['gross_weight'] ?? ($labDataFromDocket['sample_weight_gross'] ?? null))),
                            'sample_weight_taken'     => $sz['sample_taken'] ?? ($sz['sample_weight_taken'] ?? ($labDataFromDocket['sample_weight_taken'] ?? ($labDataFromDocket['sample_taken'] ?? ($labDataFromDocket['sample_weight']['sample_taken'] ?? null)))),
                            'sample_weight_reserved'  => $sz['sample_reserved'] ?? ($sz['sample_weight_reserved'] ?? ($labDataFromDocket['sample_weight_reserved'] ?? ($labDataFromDocket['sample_reserved'] ?? ($labDataFromDocket['sample_weight']['reserved_samples'] ?? null)))),
                            'detected_substances'     => $sz['detected_substances'] ?? ($labDataFromDocket['detected_substances'] ?? []),
                            'detected_subtances_percentage' => $sz['detected_subtances_percentage'] ?? ($sz['detected_substances_percentage'] ?? ($labDataFromDocket['detected_subtances_percentage'] ?? ($labDataFromDocket['detected_substances_percentage'] ?? null))),
                            'detected_substances_percentage' => $sz['detected_substances_percentage'] ?? ($sz['detected_subtances_percentage'] ?? ($labDataFromDocket['detected_substances_percentage'] ?? ($labDataFromDocket['detected_subtances_percentage'] ?? null))),
                            'test_methods'            => self::normalizeLabStringField($sz['test_methods'] ?? ($labDataFromDocket['test_methods'] ?? null)),
                            'specifications'          => self::normalizeLabStringField($sz['specifications'] ?? ($labDataFromDocket['specifications'] ?? null)),
                            'remark'                  => self::normalizeLabStringField($sz['remark'] ?? ($labDataFromDocket['remark'] ?? null)),
                            'disposition_of_exhibit'  => self::normalizeLabStringField($sz['disposition_of_exhibit'] ?? ($attributes['disposition_of_exhibit'] ?? ($attributes['confirmatory_disposition_of_exhibit'] ?? ($labDataFromDocket['disposition_of_exhibit'] ?? null)))),
                            'findings'                => self::normalizeLabStringField($sz['findings'] ?? ($labDataFromDocket['findings'] ?? null)),
                            'analyst'                 => self::normalizeLabStringField($sz['analyst'] ?? ($labDataFromDocket['analyst'] ?? null)),
                            'has_saved'               => true,
                        ]);
                    }
                }
            }

            $labDataFromDocket = $docketData['lab_examination'] ?? [];
            $hasSavedLabExplicit = !empty($labDataFromDocket['has_saved']) || !empty($attributes['has_saved_lab']);
            $hasPresumptiveExplicit = !empty($attributes['presumptive_result']) || !empty($attributes['presumptive_test_result']) || !empty($labDataFromDocket['presumptive_result']);
            $hasConfirmatoryExplicit = !empty($attributes['confirmatory_test_result']) || !empty($labDataFromDocket['confirmatory_result']);

            $labWorkflowModeAttr = strtolower(trim((string) ($attributes['lab_workflow_mode'] ?? (request() ? request()->input('lab_workflow_mode', '') : ''))));
            $gsaFilesAttr = $attributes['gsa_files_uploaded'] ?? (request() ? request()->input('gsa_files_uploaded') : null);
            $hasGsaFlagAttr = is_array($gsaFilesAttr)
                ? count(array_filter($gsaFilesAttr, fn ($v) => !empty($v) && $v !== '0' && $v !== false)) > 0
                : (filter_var($gsaFilesAttr, FILTER_VALIDATE_BOOLEAN) || ((int) $gsaFilesAttr > 0));
            $section5CompletedAttr = $attributes['section5_completed'] ?? (request() && request()->has('section5_completed') ? request()->input('section5_completed') : null);
            $section5ExplicitlyFalse = $section5CompletedAttr !== null && !filter_var($section5CompletedAttr, FILTER_VALIDATE_BOOLEAN);

            $hasGsaUpload = !empty($attributes['gsa_report_images'])
                || $hasGsaFlagAttr
                || $labWorkflowModeAttr === 'gsa'
                || $labWorkflowModeAttr === 'none'
                || $section5ExplicitlyFalse
                || !empty($attributes['gsa_lab_number'])
                || (request() && (request()->hasFile('gsa_report_images') || request()->filled('gsa_lab_number')));

            $hasLinkedExhibitIdsMulti = !empty($docketData['linked_exhibit_ids'] ?? ($attributes['linked_exhibit_ids'] ?? []));
            if (!$hasGsaUpload && empty($hasCreatedLabFromSeizure) && !($hasLinkedExhibitIdsMulti && empty($ex)) && ($hasSavedLabExplicit || $hasPresumptiveExplicit || $hasConfirmatoryExplicit)) {
                $labSuspectId = null;
                if (!empty($ex) && !empty($ex->suspect_id)) {
                    $labSuspectId = $ex->suspect_id;
                } elseif (!empty($matchedSuspect)) {
                    $labSuspectId = $matchedSuspect->id;
                } else {
                    $labSuspectId = $createdSuspects[0]->id ?? null;
                }
                $multiPresParsed = self::parsePresumptiveData(
                    $attributes['presumptive_result'] ?? ($attributes['presumptive_test_result'] ?? ($labDataFromDocket['presumptive_result'] ?? null)),
                    $attributes['presumptive_presence'] ?? ($labDataFromDocket['presumptive_presence'] ?? null),
                    $attributes['presumptive_percentage'] ?? ($attributes['presumptive_Percentage'] ?? ($labDataFromDocket['presumptive_percentage'] ?? null))
                );
                LaboratoryExaminationResult::create([
                    'case_type'               => 'MULTIPLE',
                    'multiple_suspect_case_id'=> $multi->id,
                    'suspect_id'              => $labSuspectId,
                    'exhibit_id'              => $ex->id ?? null,
                    'laboratory_number'       => $multi->laboratory_number,
                    'exam_reference'          => $attributes['exam_reference'] ?? ($labDataFromDocket['exam_reference'] ?? null),
                    'purpose_of_examination' => self::normalizeLabStringField($attributes['purpose_of_examination'] ?? ($attributes['lab_purpose_of_examination'] ?? ($labDataFromDocket['purpose_of_examination'] ?? null))),
                    'preliminary_exam_procedure' => self::normalizeLabStringField($attributes['preliminary_exam_procedure'] ?? ($attributes['lab_preliminary_exam_procedure'] ?? ($labDataFromDocket['preliminary_exam_procedure'] ?? null))),
                    'screening_method'        => self::normalizeLabStringField($attributes['screening_method'] ?? ($attributes['confirmatory_screening_method'] ?? ($labDataFromDocket['screening_method'] ?? null))),
                    'analyst'                 => self::normalizeLabStringField($attributes['analyst'] ?? ($labDataFromDocket['analyst'] ?? null)),
                    'date_received'           => $attributes['date_received'] ?? ($attributes['lab_date_received'] ?? ($labDataFromDocket['date_received'] ?? ($labDataFromDocket['lab_date_received'] ?? date('Y-m-d')))),
                    'date_started'            => $attributes['date_started'] ?? ($attributes['lab_date_started'] ?? ($labDataFromDocket['date_started'] ?? ($labDataFromDocket['lab_date_started'] ?? date('Y-m-d')))),
                    'date_completed'          => $attributes['date_completed'] ?? ($attributes['lab_date_completed'] ?? ($labDataFromDocket['date_completed'] ?? ($labDataFromDocket['lab_date_completed'] ?? date('Y-m-d')))),
                    'presumptive_result'      => $multiPresParsed['result'],
                    'presumptive_presence'    => $multiPresParsed['presence'],
                    'presumptive_percentage'  => $multiPresParsed['percentage'],
                    'confirmatory_result'     => $attributes['confirmatory_test_result'] ?? ($labDataFromDocket['confirmatory_result'] ?? 'Pending'),
                    'sample_weight_description'=> $attributes['sample_weight_description'] ?? ($labDataFromDocket['sample_weight_description'] ?? ($labDataFromDocket['sample_weight']['description'] ?? null)),
                    'sample_weight_units'     => $attributes['sample_weight_units'] ?? ($labDataFromDocket['sample_weight_units'] ?? ($labDataFromDocket['sample_weight']['units_packaging'] ?? null)),
                    'sample_weight_gross'     => $attributes['sample_weight_gross'] ?? ($labDataFromDocket['sample_weight_gross'] ?? ($labDataFromDocket['sample_weight']['gross_weight'] ?? null)),
                    'sample_weight_taken'     => $attributes['sample_weight_taken'] ?? ($attributes['sample_taken'] ?? ($labDataFromDocket['sample_weight_taken'] ?? ($labDataFromDocket['sample_taken'] ?? ($labDataFromDocket['sample_weight']['sample_taken'] ?? null)))),
                    'sample_weight_reserved'  => $attributes['sample_weight_reserved'] ?? ($attributes['sample_reserved'] ?? ($labDataFromDocket['sample_weight_reserved'] ?? ($labDataFromDocket['sample_reserved'] ?? ($labDataFromDocket['sample_weight']['reserved_samples'] ?? null)))),
                    'substance_identification'=> $attributes['confirmatory_substance_identification'] ?? ($labDataFromDocket['substance_identification'] ?? null),
                    'test_methods'            => self::normalizeLabStringField($attributes['confirmatory_test_methods'] ?? ($labDataFromDocket['test_methods'] ?? null)),
                    'specifications'          => self::normalizeLabStringField($attributes['confirmatory_specifications'] ?? ($labDataFromDocket['specifications'] ?? null)),
                    'remark'                  => self::normalizeLabStringField($attributes['confirmatory_remark'] ?? ($attributes['remark'] ?? ($labDataFromDocket['remark'] ?? null))),
                    'disposition_of_exhibit'  => self::normalizeLabStringField($attributes['disposition_of_exhibit'] ?? ($attributes['confirmatory_disposition_of_exhibit'] ?? ($labDataFromDocket['disposition_of_exhibit'] ?? null))),
                    'detected_substances'     => $attributes['detected_substances'] ?? ($labDataFromDocket['detected_substances'] ?? null),
                    'findings'                => self::normalizeLabStringField($attributes['laboratory_analytical_findings'] ?? ($labDataFromDocket['findings'] ?? null)),
                    'has_saved'               => $hasSavedLabExplicit,
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
                            'has_saved'                 => !empty($assocLab['has_saved']),
                        ]
                    );
                }
            }

            return self::wrapRecord($multi->fresh(['suspects', 'exhibits', 'labResults']));
        }

        $singleSuspectName = $attributes['suspect_name'] ?? ($suspects[0]['suspect_name'] ?? 'Unnamed Suspect');
        $isSingleNoSuspect = self::isUnassignedSuspectName($singleSuspectName);

        // Single suspect case creation
        $single = SingleSuspectCase::create([
            'case_number'          => $caseRef,
            'laboratory_number'    => $attributes['laboratory_number'] ?? null,
            'title'                => $attributes['title'] ?? 'Single Suspect Docket File',
            'unit_name'            => $attributes['unit_name'] ?? 'Crime Scene Support',
            'unit_id'              => $attributes['unit_id'] ?? null,
            'user_id'              => $attributes['user_id'] ?? null,
            'arresting_officer'    => $attributes['arresting_officer'] ?? ($attributes['submitting_officer'] ?? null),
            'submitting_officer'   => $attributes['arresting_officer'] ?? ($attributes['submitting_officer'] ?? null),
            'suspect_name'         => $singleSuspectName,
            'suspect_alias'        => $attributes['suspect_alias'] ?? ($suspects[0]['suspect_alias'] ?? null),
            'ghana_card'           => $attributes['ghana_card'] ?? ($suspects[0]['ghana_card'] ?? null),
            'gender'               => $attributes['gender'] ?? ($suspects[0]['gender'] ?? ($isSingleNoSuspect ? 'N/A' : 'Male')),
            'dob'                  => $attributes['dob'] ?? ($suspects[0]['dob'] ?? null),
            'age'                  => isset($attributes['age']) && $attributes['age'] !== '' ? (int)$attributes['age'] : (isset($suspects[0]['age']) && $suspects[0]['age'] !== '' ? (int)$suspects[0]['age'] : null),
            'height'               => $attributes['height'] ?? ($suspects[0]['height'] ?? null),
            'height_unit'          => $attributes['height_unit'] ?? ($suspects[0]['height_unit'] ?? 'CM'),
            'eye_color'            => $attributes['eye_color'] ?? ($suspects[0]['eye_color'] ?? null),
            'nationality'          => $attributes['nationality'] ?? ($suspects[0]['nationality'] ?? ($isSingleNoSuspect ? 'N/A' : null)),
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
            'search_warrant_document' => $attributes['search_warrant_document'] ?? null,
            'raid_location'        => $attributes['raid_location'] ?? ($attributes['location'] ?? null),
            'latitude'             => isset($attributes['latitude']) && is_numeric($attributes['latitude']) ? (float)$attributes['latitude'] : null,
            'longitude'            => isset($attributes['longitude']) && is_numeric($attributes['longitude']) ? (float)$attributes['longitude'] : null,
            'offense_description'  => $attributes['offense_description'] ?? ($attributes['description'] ?? null),
            'description_of_exhibits' => $attributes['description_of_exhibits'] ?? ($docketData['description_of_exhibits'] ?? null),
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

        if (!empty($attributes['created_at'])) {
            $single->created_at = $attributes['created_at'];
            $single->saveQuietly();
        }

        $seizures = $docketData['seizures'] ?? [];
        $linkedExhibitIdsTopSingle = array_values(array_filter(array_map('intval', (array) ($docketData['linked_exhibit_ids'] ?? ($attributes['linked_exhibit_ids'] ?? [])))));
        $hasExplicitTopLevelExhibitSingle = self::hasTopLevelExhibitData($attributes);
        if (empty($seizures)) {
            if ($hasExplicitTopLevelExhibitSingle && empty($linkedExhibitIdsTopSingle)) {
                $ex = Exhibit::create([
                    'case_type'             => 'SINGLE',
                    'single_suspect_case_id'=> $single->id,
                    'associated_person'     => $single->suspect_name,
                    'exhibit_number'        => null,
                    'lab_exhibit_no'        => $attributes['lab_exhibit_no'] ?? ($attributes['lab_exhibit_number'] ?? null),
                    'exhibit_category'      => self::resolveExhibitCategory([], $attributes),
                    'commodity_name'        => self::resolveExhibitCommodityName([], $attributes),
                    'seizure_quantity'      => $attributes['seizure_quantity'] ?? null,
                    'amount'                => (!empty($attributes['amount']) && $attributes['amount'] !== 'N/A') ? $attributes['amount'] : (in_array(strtolower(trim((string)($attributes['exhibit_category'] ?? 'Drugs'))), ['money', 'currency', 'cash']) ? (!empty($attributes['seizure_quantity']) ? $attributes['seizure_quantity'] : ((!empty($attributes['seizure_value']) && $attributes['seizure_value'] !== 'N/A') ? $attributes['seizure_value'] : null)) : null),
                    'seizure_weight'        => $attributes['seizure_weight'] ?? null,
                    'seizure_unit'          => $attributes['seizure_unit'] ?? null,
                    'seizure_packaging'     => $attributes['seizure_packaging'] ?? ($attributes['packaging_type'] ?? null),
                    'seizure_value'         => $attributes['seizure_value'] ?? ($attributes['valuation'] ?? null),
                    'seizure_concealment'   => $attributes['seizure_concealment'] ?? null,
                    'seizure_date'          => $attributes['seizure_date'] ?? null,
                    'seizure_time'          => $attributes['seizure_time'] ?? null,
                    'seizure_location'      => $attributes['seizure_location'] ?? null,
                    'seizure_officer'       => $attributes['seizure_officer'] ?? null,
                    'investigating_officer' => $attributes['investigating_officer'] ?? null,
                    'source_requesting_unit' => $attributes['source_requesting_unit'] ?? null,
                    'seizure_reason'        => $attributes['seizure_reason'] ?? null,
                    'source_of_exhibits'    => $attributes['source_of_exhibits'] ?? null,
                    'exhibit_remark'        => $attributes['exhibit_remark'] ?? ($attributes['remark'] ?? ($attributes['remarks'] ?? null)),
                    'chain_of_custody'      => $attributes['chain_of_custody'] ?? null,
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
                    'contents'              => $attributes['contents'] ?? null,
                    'description_of_exhibits' => $attributes['description_of_exhibits'] ?? ($docketData['description_of_exhibits'] ?? null),
                    'evidence_photos'       => $attributes['evidence_photos'] ?? [],
                ]);
            }
        } else {
            $linkedExhibitIdsSingle = $linkedExhibitIdsTopSingle;
            foreach ($seizures as $sIdx => $sz) {
                $szExId = !empty($sz['linked_exhibit_id']) ? (int) $sz['linked_exhibit_id'] : (!empty($sz['id']) ? (int) $sz['id'] : 0);
                if ($szExId > 0 && in_array($szExId, $linkedExhibitIdsSingle, true)) {
                    continue;
                }
                $singleSuspectLower = strtolower(trim((string) ($single->suspect_name ?? '')));
                $isUnassignedCase = !empty($docketData['no_suspect'])
                    || $singleSuspectLower === ''
                    || str_contains($singleSuspectLower, 'unassigned')
                    || str_contains($singleSuspectLower, 'awaiting')
                    || str_contains($singleSuspectLower, 'no suspect');

                $isSingleJoint = !$isUnassignedCase && (
                    (!empty($sz['suspect_ownership']) && str_contains(strtolower($sz['suspect_ownership']), 'all suspect'))
                    || (!empty($sz['associated_person']) && str_contains(strtolower($sz['associated_person']), 'all suspect'))
                );

                $ex = Exhibit::create([
                    'case_type'             => 'SINGLE',
                    'single_suspect_case_id'=> $single->id,
                    'associated_person'     => $isUnassignedCase
                        ? 'Unassigned (No Suspect)'
                        : ($isSingleJoint ? 'All Suspects' : ($sz['associated_person'] ?? ($sz['suspect_ownership'] ?? $single->suspect_name))),
                    'exhibit_number'        => $sz['exhibit_number'] ?? null,
                    'lab_exhibit_no'        => $sz['lab_exhibit_no'] ?? ($sIdx === 0 ? ($attributes['lab_exhibit_no'] ?? ($attributes['lab_exhibit_number'] ?? null)) : null),
                    'property_id'           => $sz['property_id'] ?? null,
                    'exhibit_category'      => self::resolveExhibitCategory($sz, $attributes),
                    'exhibit_type'          => $sz['exhibit_type'] ?? ($attributes['exhibit_type'] ?? null),
                    'drug_classification'   => $sz['drug_classification'] ?? ($attributes['drug_classification'] ?? null),
                    'commodity_name'        => self::resolveExhibitCommodityName($sz, $attributes),
                    'seizure_quantity'      => $sz['seizure_quantity'] ?? null,
                    'amount'                => (!empty($sz['amount']) && $sz['amount'] !== 'N/A') ? $sz['amount'] : ((!empty($attributes['amount']) && $attributes['amount'] !== 'N/A') ? $attributes['amount'] : (in_array(strtolower(trim((string)($sz['exhibit_category'] ?? ($sz['category'] ?? ($attributes['exhibit_category'] ?? 'Drugs'))))), ['money', 'currency', 'cash']) ? (!empty($sz['seizure_quantity']) ? $sz['seizure_quantity'] : ((!empty($sz['seizure_value']) && $sz['seizure_value'] !== 'N/A') ? $sz['seizure_value'] : null)) : null)),
                    'seizure_weight'        => $sz['seizure_weight'] ?? ($sz['sample_gross'] ?? null),
                    'seizure_unit'          => $sz['seizure_unit'] ?? null,
                    'seizure_packaging'     => $sz['seizure_packaging'] ?? ($sz['packaging'] ?? ($attributes['seizure_packaging'] ?? null)),
                    'seizure_value'         => $sz['seizure_value'] ?? ($sz['valuation'] ?? ($attributes['seizure_value'] ?? ($attributes['valuation'] ?? null))),
                    'seizure_concealment'   => $sz['seizure_concealment'] ?? ($sz['concealment'] ?? ($attributes['seizure_concealment'] ?? null)),
                    'seizure_date'          => $sz['seizure_date'] ?? ($attributes['seizure_date'] ?? null),
                    'seizure_time'          => $sz['seizure_time'] ?? ($attributes['seizure_time'] ?? null),
                    'seizure_location'      => $sz['seizure_location'] ?? ($attributes['seizure_location'] ?? null),
                    'seizure_officer'       => $sz['seizure_officer'] ?? ($attributes['seizure_officer'] ?? null),
                    'investigating_officer' => $sz['investigating_officer'] ?? ($attributes['investigating_officer'] ?? null),
                    'source_requesting_unit' => $sz['source_requesting_unit'] ?? ($attributes['source_requesting_unit'] ?? null),
                    'seizure_reason'        => $sz['seizure_reason'] ?? ($attributes['seizure_reason'] ?? null),
                    'source_of_exhibits'    => $sz['source_of_exhibits'] ?? ($attributes['source_of_exhibits'] ?? null),
                    'exhibit_remark'        => $sz['exhibit_remark'] ?? ($sz['remark'] ?? ($sz['remarks'] ?? ($attributes['exhibit_remark'] ?? ($attributes['remark'] ?? ($attributes['remarks'] ?? null))))),
                    'chain_of_custody'      => $sz['chain_of_custody'] ?? ($attributes['chain_of_custody'] ?? null),
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
                    'contents'              => $sz['contents'] ?? ($sz['seizure_contents'] ?? ($attributes['contents'] ?? null)),
                    'description_of_exhibits' => $sz['description_of_exhibits'] ?? ($attributes['description_of_exhibits'] ?? ($docketData['description_of_exhibits'] ?? null)),
                    'evidence_photos'       => !empty($sz['evidence_photos']) ? $sz['evidence_photos'] : (!empty($sz['photos']) ? $sz['photos'] : (!empty($sz['photo']) ? [$sz['photo']] : ($sIdx === 0 ? ($attributes['evidence_photos'] ?? ($docketData['evidence_photos'] ?? [])) : []))),
                ]);

                $hasLabForSeizure = !empty($sz['has_saved_lab']) 
                    || !empty($sz['presumptive_result']) 
                    || !empty($sz['detected_substances']) 
                    || !empty($sz['sample_gross']) 
                    || !empty($sz['sample_taken']) 
                    || !empty($sz['substance_identification']);

                if ($hasLabForSeizure) {
                    $hasCreatedLabFromSeizure = true;

                    // Resolve unique exam reference per exhibit to avoid unique constraint collision
                    $seizureExamRef = !empty($sz['exam_reference']) ? trim($sz['exam_reference']) : null;
                    $baseRef = $attributes['exam_reference'] ?? ($labDataFromDocket['exam_reference'] ?? null);

                    if (empty($seizureExamRef)) {
                        if (!empty($baseRef)) {
                            $candidateRef = ($sIdx === 0) ? $baseRef : ($baseRef . '-' . ($sIdx + 1));
                            if (!LaboratoryExaminationResult::where('exam_reference', $candidateRef)->exists()) {
                                $seizureExamRef = $candidateRef;
                            } else {
                                $seizureExamRef = app(\App\Services\CaseReferenceService::class)->generateExamReference();
                            }
                        } else {
                            $seizureExamRef = app(\App\Services\CaseReferenceService::class)->generateExamReference();
                        }
                    } else {
                        if (LaboratoryExaminationResult::where('exam_reference', $seizureExamRef)->exists()) {
                            $candidateRef = $seizureExamRef . '-' . ($sIdx + 1);
                            if (!LaboratoryExaminationResult::where('exam_reference', $candidateRef)->exists()) {
                                $seizureExamRef = $candidateRef;
                            } else {
                                $seizureExamRef = app(\App\Services\CaseReferenceService::class)->generateExamReference();
                            }
                        }
                    }

                    $szPresParsed = self::parsePresumptiveData(
                        self::normalizeLabStringField($sz['presumptive_result'] ?? ($labDataFromDocket['presumptive_result'] ?? ($attributes['presumptive_result'] ?? ($attributes['presumptive_test_result'] ?? null)))),
                        self::normalizeLabStringField($sz['presumptive_presence'] ?? ($labDataFromDocket['presumptive_presence'] ?? ($attributes['presumptive_presence'] ?? null))),
                        self::normalizeLabStringField($sz['presumptive_percentage'] ?? ($labDataFromDocket['presumptive_percentage'] ?? ($attributes['presumptive_percentage'] ?? ($attributes['presumptive_Percentage'] ?? null))))
                    );
                    LaboratoryExaminationResult::create([
                        'case_type'               => 'SINGLE',
                        'single_suspect_case_id'  => $single->id,
                        'exhibit_id'              => $ex->id,
                        'laboratory_number'       => self::normalizeLabStringField($sz['laboratory_number'] ?? ($single->laboratory_number ?? ($labDataFromDocket['laboratory_number'] ?? null))),
                        'exam_reference'          => $seizureExamRef,
                        'purpose_of_examination' => self::normalizeLabStringField($sz['purpose_of_examination'] ?? ($attributes['purpose_of_examination'] ?? ($attributes['lab_purpose_of_examination'] ?? ($labDataFromDocket['purpose_of_examination'] ?? null)))),
                        'preliminary_exam_procedure' => self::normalizeLabStringField($sz['preliminary_exam_procedure'] ?? ($attributes['preliminary_exam_procedure'] ?? ($attributes['lab_preliminary_exam_procedure'] ?? ($labDataFromDocket['preliminary_exam_procedure'] ?? null)))),
                        'screening_method'        => self::normalizeLabStringField($sz['screening_method'] ?? ($attributes['screening_method'] ?? ($attributes['confirmatory_screening_method'] ?? ($labDataFromDocket['screening_method'] ?? null)))),
                        'date_received'           => $sz['date_received'] ?? ($labDataFromDocket['date_received'] ?? ($labDataFromDocket['lab_date_received'] ?? date('Y-m-d'))),
                        'date_started'            => $sz['date_started'] ?? ($labDataFromDocket['date_started'] ?? ($labDataFromDocket['lab_date_started'] ?? date('Y-m-d'))),
                        'date_completed'          => $sz['date_completed'] ?? ($labDataFromDocket['date_completed'] ?? ($labDataFromDocket['lab_date_completed'] ?? date('Y-m-d'))),
                        'presumptive_result'      => $szPresParsed['result'],
                        'presumptive_presence'    => $szPresParsed['presence'],
                        'presumptive_percentage'  => $szPresParsed['percentage'],
                        'confirmatory_result'     => self::normalizeLabStringField($sz['confirmatory_result'] ?? ($labDataFromDocket['confirmatory_result'] ?? 'Pending')),
                        'substance_identification'=> self::normalizeLabStringField($sz['substance_identification'] ?? ($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? ($labDataFromDocket['substance_identification'] ?? null)))),
                        'sample_weight_description'=> self::normalizeLabStringField($sz['sample_description'] ?? ($sz['sample_weight_description'] ?? ($labDataFromDocket['sample_weight']['description'] ?? ($labDataFromDocket['sample_weight_description'] ?? null)))),
                        'sample_weight_units'     => self::normalizeLabStringField($sz['sample_units'] ?? ($sz['sample_weight_units'] ?? ($labDataFromDocket['sample_weight']['units_packaging'] ?? ($labDataFromDocket['sample_weight_units'] ?? null)))),
                        'sample_weight_gross'     => self::normalizeLabStringField($sz['sample_gross'] ?? ($sz['sample_weight_gross'] ?? ($labDataFromDocket['sample_weight']['gross_weight'] ?? ($labDataFromDocket['sample_weight_gross'] ?? null)))),
                        'sample_weight_taken'     => self::normalizeLabStringField($sz['sample_taken'] ?? ($sz['sample_weight_taken'] ?? ($labDataFromDocket['sample_weight_taken'] ?? ($labDataFromDocket['sample_taken'] ?? ($labDataFromDocket['sample_weight']['sample_taken'] ?? null))))),
                        'sample_weight_reserved'  => self::normalizeLabStringField($sz['sample_reserved'] ?? ($sz['sample_weight_reserved'] ?? ($labDataFromDocket['sample_weight_reserved'] ?? ($labDataFromDocket['sample_reserved'] ?? ($labDataFromDocket['sample_weight']['reserved_samples'] ?? null))))),
                        'detected_substances'     => $sz['detected_substances'] ?? ($labDataFromDocket['detected_substances'] ?? []),
                        'test_methods'            => self::normalizeLabStringField($sz['test_methods'] ?? ($labDataFromDocket['test_methods'] ?? null)),
                        'specifications'          => self::normalizeLabStringField($sz['specifications'] ?? ($labDataFromDocket['specifications'] ?? null)),
                        'remark'                  => self::normalizeLabStringField($sz['remark'] ?? ($labDataFromDocket['remark'] ?? null)),
                        'disposition_of_exhibit'  => self::normalizeLabStringField($sz['disposition_of_exhibit'] ?? ($attributes['disposition_of_exhibit'] ?? ($attributes['confirmatory_disposition_of_exhibit'] ?? ($labDataFromDocket['disposition_of_exhibit'] ?? null)))),
                        'findings'                => self::normalizeLabStringField($sz['findings'] ?? ($labDataFromDocket['findings'] ?? null)),
                        'analyst'                 => self::normalizeLabStringField($sz['analyst'] ?? ($labDataFromDocket['analyst'] ?? null)),
                        'has_saved'               => true,
                    ]);
                }
            }
        }

        $labDataFromDocket = $docketData['lab_examination'] ?? [];
        $hasSavedLabExplicit = !empty($labDataFromDocket['has_saved']) || !empty($attributes['has_saved_lab']);
        $hasPresumptiveExplicit = !empty($attributes['presumptive_result']) || !empty($attributes['presumptive_test_result']) || !empty($labDataFromDocket['presumptive_result']);
        $hasConfirmatoryExplicit = !empty($attributes['confirmatory_test_result']) || !empty($labDataFromDocket['confirmatory_result']);
        $hasActualLabFields = $hasSavedLabExplicit
            || $hasPresumptiveExplicit
            || $hasConfirmatoryExplicit
            || !empty($attributes['purpose_of_examination'])
            || !empty($attributes['lab_purpose_of_examination'])
            || !empty($attributes['preliminary_exam_procedure'])
            || !empty($attributes['lab_preliminary_exam_procedure'])
            || !empty($attributes['screening_method'])
            || !empty($attributes['confirmatory_screening_method'])
            || !empty($attributes['substance_identification'])
            || !empty($attributes['detected_substances'])
            || !empty($attributes['test_methods'])
            || !empty($attributes['specifications'])
            || !empty($attributes['disposition_of_exhibit'])
            || !empty($attributes['confirmatory_remark'])
            || !empty($attributes['findings'])
            || !empty($labDataFromDocket['purpose_of_examination'])
            || !empty($labDataFromDocket['substance_identification'])
            || !empty($labDataFromDocket['detected_substances'])
            || !empty($labDataFromDocket['test_methods'])
            || !empty($labDataFromDocket['specifications'])
            || !empty($labDataFromDocket['disposition_of_exhibit']);

        $labWorkflowModeAttr = strtolower(trim((string) ($attributes['lab_workflow_mode'] ?? (request() ? request()->input('lab_workflow_mode', '') : ''))));
        $gsaFilesAttr = $attributes['gsa_files_uploaded'] ?? (request() ? request()->input('gsa_files_uploaded') : null);
        $hasGsaFlagAttr = is_array($gsaFilesAttr)
            ? count(array_filter($gsaFilesAttr, fn ($v) => !empty($v) && $v !== '0' && $v !== false)) > 0
            : (filter_var($gsaFilesAttr, FILTER_VALIDATE_BOOLEAN) || ((int) $gsaFilesAttr > 0));
        $section5CompletedAttr = $attributes['section5_completed'] ?? (request() && request()->has('section5_completed') ? request()->input('section5_completed') : null);
        $hasExplicitGsaUpload = !empty($attributes['gsa_report_images'])
            || $hasGsaFlagAttr
            || $labWorkflowModeAttr === 'gsa'
            || $labWorkflowModeAttr === 'none'
            || !empty($attributes['gsa_lab_number'])
            || (request() && (request()->hasFile('gsa_report_images') || request()->filled('gsa_lab_number')));
        $section5ExplicitlyFalse = $section5CompletedAttr !== null
            && !filter_var($section5CompletedAttr, FILTER_VALIDATE_BOOLEAN)
            && ($hasExplicitGsaUpload || !$hasActualLabFields);

        $hasGsaUpload = $hasExplicitGsaUpload || $section5ExplicitlyFalse;

        $hasLinkedExhibitIdsSingle = !empty($docketData['linked_exhibit_ids'] ?? ($attributes['linked_exhibit_ids'] ?? []));
        if (!$hasGsaUpload && empty($hasCreatedLabFromSeizure) && !($hasLinkedExhibitIdsSingle && empty($ex)) && $hasActualLabFields) {
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
            $singlePresParsed = self::parsePresumptiveData(
                $attributes['presumptive_result'] ?? ($attributes['presumptive_test_result'] ?? ($labDataFromDocket['presumptive_result'] ?? null)),
                $attributes['presumptive_presence'] ?? ($labDataFromDocket['presumptive_presence'] ?? null),
                $attributes['presumptive_percentage'] ?? ($attributes['presumptive_Percentage'] ?? ($labDataFromDocket['presumptive_percentage'] ?? null))
            );
            LaboratoryExaminationResult::create([
                'case_type'               => 'SINGLE',
                'single_suspect_case_id'  => $single->id,
                'exhibit_id'              => $ex->id ?? null,
                'laboratory_number'       => $single->laboratory_number,
                'exam_reference'          => $attributes['exam_reference'] ?? ($labDataFromDocket['exam_reference'] ?? null),
                'purpose_of_examination' => self::normalizeLabStringField($attributes['purpose_of_examination'] ?? ($attributes['lab_purpose_of_examination'] ?? ($labDataFromDocket['purpose_of_examination'] ?? null))),
                'preliminary_exam_procedure' => self::normalizeLabStringField($attributes['preliminary_exam_procedure'] ?? ($attributes['lab_preliminary_exam_procedure'] ?? ($labDataFromDocket['preliminary_exam_procedure'] ?? null))),
                'screening_method'        => self::normalizeLabStringField($attributes['screening_method'] ?? ($attributes['confirmatory_screening_method'] ?? ($labDataFromDocket['screening_method'] ?? null))),
                'analyst'                 => self::normalizeLabStringField($attributes['analyst'] ?? ($labDataFromDocket['analyst'] ?? null)),
                'date_received'           => $attributes['date_received'] ?? ($attributes['lab_date_received'] ?? ($labDataFromDocket['date_received'] ?? ($labDataFromDocket['lab_date_received'] ?? date('Y-m-d')))),
                'date_started'            => $attributes['date_started'] ?? ($attributes['lab_date_started'] ?? ($labDataFromDocket['date_started'] ?? ($labDataFromDocket['lab_date_started'] ?? date('Y-m-d')))),
                'date_completed'          => $attributes['date_completed'] ?? ($attributes['lab_date_completed'] ?? ($labDataFromDocket['date_completed'] ?? ($labDataFromDocket['lab_date_completed'] ?? date('Y-m-d')))),
                'presumptive_result'      => $singlePresParsed['result'],
                'presumptive_presence'    => $singlePresParsed['presence'],
                'presumptive_percentage'  => $singlePresParsed['percentage'],
                'confirmatory_result'     => $attributes['confirmatory_test_result'] ?? ($labDataFromDocket['confirmatory_result'] ?? 'Pending'),
                'sample_weight_description'=> $labSwDesc,
                'sample_weight_units'     => $labSwUnits,
                'sample_weight_gross'     => $labSwGross,
                'sample_weight_taken'     => $attributes['sample_weight_taken'] ?? ($attributes['sample_taken'] ?? ($labDataFromDocket['sample_weight_taken'] ?? ($labDataFromDocket['sample_taken'] ?? ($labDataFromDocket['sample_weight']['sample_taken'] ?? null)))),
                'sample_weight_reserved'  => $attributes['sample_weight_reserved'] ?? ($attributes['sample_reserved'] ?? ($labDataFromDocket['sample_weight_reserved'] ?? ($labDataFromDocket['sample_reserved'] ?? ($labDataFromDocket['sample_weight']['reserved_samples'] ?? null)))),
                'substance_identification'=> $attributes['confirmatory_substance_identification'] ?? ($labDataFromDocket['substance_identification'] ?? null),
                'test_methods'            => self::normalizeLabStringField($attributes['confirmatory_test_methods'] ?? ($labDataFromDocket['test_methods'] ?? null)),
                'specifications'          => self::normalizeLabStringField($attributes['confirmatory_specifications'] ?? ($labDataFromDocket['specifications'] ?? null)),
                'remark'                  => self::normalizeLabStringField($attributes['confirmatory_remark'] ?? ($attributes['remark'] ?? ($labDataFromDocket['remark'] ?? null))),
                'disposition_of_exhibit'  => self::normalizeLabStringField($attributes['disposition_of_exhibit'] ?? ($attributes['confirmatory_disposition_of_exhibit'] ?? ($labDataFromDocket['disposition_of_exhibit'] ?? null))),
                'detected_substances'     => $attributes['detected_substances'] ?? ($labDataFromDocket['detected_substances'] ?? null),
                'detected_subtances_percentage' => $attributes['detected_subtances_percentage'] ?? ($attributes['detected_substances_percentage'] ?? ($labDataFromDocket['detected_subtances_percentage'] ?? ($labDataFromDocket['detected_substances_percentage'] ?? null))),
                'detected_substances_percentage' => $attributes['detected_substances_percentage'] ?? ($attributes['detected_subtances_percentage'] ?? ($labDataFromDocket['detected_substances_percentage'] ?? ($labDataFromDocket['detected_subtances_percentage'] ?? null))),
                'findings'                => self::normalizeLabStringField($attributes['laboratory_analytical_findings'] ?? ($labDataFromDocket['findings'] ?? null)),
                'has_saved'               => $hasSavedLabExplicit,
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
                'case_number' => $r['case_number'] ?? app(\App\Services\CaseNumberService::class)->generate(),
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
     * Find a case by ID or case_number (explicit alias)
     */
    public static function findCase($id): ?self
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
        'ephedrine', 'precursor', 'tapentadol', 'pregabalin', 'ketamine', 'diazepam',
        'methaqualone', 'rohypnol', 'diacetylmorphine', 'barbiturate', 'benzodiazepine',
    ];

    private static array $ELECTRONIC_KEYWORDS = [
        'mobile', 'phone', 'smartphone', 'laptop', 'computer', 'tablet', 'ipad',
        'hard drive', 'harddrive', 'hdd', 'ssd', 'usb', 'flash drive', 'flashdrive',
        'memory card', 'memorycard', 'electronic', 'digital device', 'device',
        'iphone', 'android', 'pc', 'desktop', 'adapter', 'wi-fi', 'wifi', 'router',
        'modem', 'smartwatch', 'macbook', 'sim card',
    ];

    private static array $WEAPON_OR_VEHICLE_KEYWORDS = [
        // Weapons & ammunition
        'weapon', 'firearm', 'gun', 'pistol', 'rifle', 'shotgun', 'revolver',
        'knife', 'blade', 'machete', 'cutlass', 'sword', 'dagger',
        'ammunition', 'ammo', 'bullet', 'cartridge', 'shell', 'explosive', 'grenade',
        // Vehicles & Vessels
        'vehicle', 'vehicles', 'veh', 'car', 'truck', 'automobile', 'vessel', 'zonda',
    ];

    private static array $MONEY_OR_GOLD_KEYWORDS = [
        // Gold / precious metals / money
        'gold', 'precious', 'silver', 'diamond', 'gemstone', 'jewel', 'bullion',
        'currency', 'cash', 'money', 'banknote', 'note', 'coin',
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
            'has_weapon_or_vehicle'     => false,
            'has_money_or_gold'         => false,
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
                if (!$flags['has_weapon_or_vehicle'] && $matches($text, self::$WEAPON_OR_VEHICLE_KEYWORDS)) {
                    $flags['has_weapon_or_vehicle'] = true;
                }
                if (!$flags['has_money_or_gold'] && $matches($text, self::$MONEY_OR_GOLD_KEYWORDS)) {
                    $flags['has_money_or_gold'] = true;
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
            $uId = isset($this->underlyingRecord) ? $this->underlyingRecord->id : $this->id;
            $dbExhibits = Exhibit::where(function ($q) use ($uId, $isMultiCase) {
                if ($isMultiCase) {
                    $q->where('multiple_suspect_case_id', $this->id)
                        ->orWhere('multiple_suspect_case_id', $uId);
                } else {
                    $q->where('single_suspect_case_id', $this->id)
                        ->orWhere('single_suspect_case_id', $uId);
                }
            })->get();
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
     * Retrieve all exhibits associated with this case from the exhibits DB table and docket data.
     * Deduplicates and formats items for consistent display across case registers and dossiers.
     */
    public function getAllCaseExhibits(): array
    {
        $exhibitsList = [];
        $seenKeys = [];

        // 1. Relational records from 'exhibits' table
        if (!empty($this->id)) {
            $isMultiCase = $this->isMultipleSuspect();
            $uId = isset($this->underlyingRecord) ? $this->underlyingRecord->id : $this->id;
            try {
                $dbExhibits = Exhibit::where(function ($q) use ($uId, $isMultiCase) {
                    if ($isMultiCase) {
                        $q->where('multiple_suspect_case_id', $this->id)
                            ->orWhere('multiple_suspect_case_id', $uId);
                    } else {
                        $q->where('single_suspect_case_id', $this->id)
                            ->orWhere('single_suspect_case_id', $uId);
                    }
                })->get();

                foreach ($dbExhibits as $ex) {
                    $exNo = trim((string)($ex->exhibit_number ?? ($ex->property_id ?? ($ex->lab_exhibit_no ?? ''))));
                    $name = trim((string)($ex->commodity_name ?? ($ex->seizure_name_drug ?? ($ex->seizure_name ?? ''))));
                    $cat = trim((string)($ex->exhibit_category ?? 'General'));
                    $weight = trim((string)($ex->seizure_weight ?? ($ex->sample_weight ?? '')));
                    $unit = trim((string)($ex->seizure_unit ?? ''));
                    $qty = trim((string)($ex->seizure_quantity ?? ($ex->amount ?? '')));

                    $qtyStr = '';
                    if ($weight !== '') {
                        $qtyStr = $weight . ($unit !== '' ? ' ' . $unit : '');
                    } elseif ($qty !== '') {
                        $qtyStr = $qty . ' ' . ($ex->seizure_packaging ?? 'Units');
                    }

                    $dedupKey = strtolower(($exNo ?: $name) . '|' . $cat);
                    if (!isset($seenKeys[$dedupKey]) && ($name !== '' || $exNo !== '')) {
                        $seenKeys[$dedupKey] = true;
                        $exhibitsList[] = [
                            'id' => $ex->id,
                            'exhibit_number' => $exNo ?: ('EXH-' . str_pad((string)(count($exhibitsList) + 1), 3, '0', STR_PAD_LEFT)),
                            'commodity_name' => $name ?: 'Seized Exhibit',
                            'category' => $cat,
                            'quantity_or_weight' => $qtyStr,
                            'custodial_status' => $ex->custodial_status ?? 'In Vault Custody',
                            'packaging' => $ex->seizure_packaging ?? null,
                            'location' => $ex->seizure_location ?? ($this->holding_facility ?? 'NACOC Central Vault'),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Ignore DB error and fallback to docket data
            }
        }

        // 2. Docket data seizures
        $docketData = is_array($this->docket_data)
            ? $this->docket_data
            : (json_decode($this->docket_data ?? '[]', true) ?: []);

        $allSeizures = array_merge(
            $docketData['seizures'] ?? [],
            $docketData['associated_seizures'] ?? [],
            $docketData['associate_exhibits'] ?? []
        );

        foreach ($allSeizures as $sz) {
            $exNo = trim((string)($sz['exhibit_number'] ?? ($sz['property_id'] ?? ($sz['lab_exhibit_no'] ?? ''))));
            $name = trim((string)($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? ($sz['commodity_name'] ?? ($sz['name'] ?? '')))));
            $cat = trim((string)($sz['exhibit_category'] ?? ($sz['category'] ?? 'General')));
            $weight = trim((string)($sz['seizure_weight'] ?? ($sz['sample_weight'] ?? '')));
            $unit = trim((string)($sz['seizure_unit'] ?? ''));
            $qty = trim((string)($sz['seizure_quantity'] ?? ($sz['amount'] ?? '')));

            $qtyStr = '';
            if ($weight !== '') {
                $qtyStr = $weight . ($unit !== '' ? ' ' . $unit : '');
            } elseif ($qty !== '') {
                $qtyStr = $qty . ' ' . ($sz['seizure_packaging'] ?? 'Units');
            }

            $dedupKey = strtolower(($exNo ?: $name) . '|' . $cat);
            if (!isset($seenKeys[$dedupKey]) && ($name !== '' || $exNo !== '')) {
                $seenKeys[$dedupKey] = true;
                $exhibitsList[] = [
                    'id' => null,
                    'exhibit_number' => $exNo ?: ('EXH-' . str_pad((string)(count($exhibitsList) + 1), 3, '0', STR_PAD_LEFT)),
                    'commodity_name' => $name ?: 'Seized Exhibit',
                    'category' => $cat,
                    'quantity_or_weight' => $qtyStr,
                    'custodial_status' => $sz['custodial_status'] ?? 'In Vault Custody',
                    'packaging' => $sz['seizure_packaging'] ?? null,
                    'location' => $sz['seizure_location'] ?? ($this->holding_facility ?? 'NACOC Central Vault'),
                ];
            }
        }

        // 3. Fallback: top-level exhibit fields on case
        if (empty($exhibitsList) && (!empty($this->commodity_name) || !empty($this->exhibits_summary))) {
            $name = $this->commodity_name ?: ($this->exhibits_summary ?: 'Exhibits Logged');
            $exhibitsList[] = [
                'id' => null,
                'exhibit_number' => 'EXH-001',
                'commodity_name' => $name,
                'category' => $this->exhibit_category ?? 'General',
                'quantity_or_weight' => $this->seizure_weight ?? null,
                'custodial_status' => 'In Vault Custody',
                'packaging' => null,
                'location' => $this->holding_facility ?? 'NACOC Central Vault',
            ];
        }

        return $exhibitsList;
    }

    /**
     * Check if case has any exhibits logged.
     */
    public function hasExhibits(): bool
    {
        return !empty($this->getAllCaseExhibits());
    }

    /**
     * Retrieve comprehensive exhibit details linked to the case and its suspects for the Vault Storage exhibit modal.
     */
    public function getDetailedSuspectExhibits(): array
    {
        $exhibits = [];
        $seenKeys = [];

        $dData = is_array($this->docket_data) ? $this->docket_data : (json_decode($this->docket_data ?? '[]', true) ?: []);
        $primarySuspect = $this->suspect_name ?? ($dData['suspect_name'] ?? 'Primary Suspect');

        $allSeizures = array_merge(
            $dData['seizures'] ?? [],
            $dData['associated_seizures'] ?? [],
            $dData['associate_exhibits'] ?? []
        );

        $photoResolver = app(\App\Services\CasePhotoResolver::class);

        // Helper to format currency
        $fmtVal = function ($v) {
            if ($v === null || $v === '') return null;
            $num = (float) preg_replace('/[^0-9.]/', '', (string)$v);
            return $num > 0 ? 'GHS ' . number_format($num, 2) : (string)$v;
        };

        // 1. Database relational records from 'exhibits' table
        if (!empty($this->id)) {
            $isMultiCase = $this->isMultipleSuspect();
            $uId = isset($this->underlyingRecord) ? $this->underlyingRecord->id : $this->id;
            try {
                $dbExhibits = Exhibit::where(function ($q) use ($uId, $isMultiCase) {
                    if ($isMultiCase) {
                        $q->where('multiple_suspect_case_id', $this->id)
                            ->orWhere('multiple_suspect_case_id', $uId);
                    } else {
                        $q->where('single_suspect_case_id', $this->id)
                            ->orWhere('single_suspect_case_id', $uId);
                    }
                })->get();

                foreach ($dbExhibits as $ex) {
                    $exNo = trim((string)($ex->exhibit_number ?? ($ex->property_id ?? ($ex->lab_exhibit_no ?? ''))));
                    $name = trim((string)($ex->commodity_name ?? ($ex->seizure_name_drug ?? ($ex->seizure_name ?? ''))));
                    $cat = trim((string)($ex->exhibit_category ?? ($ex->exhibit_type ?? 'General')));
                    $weight = trim((string)($ex->seizure_weight ?? ($ex->sample_weight ?? '')));
                    $unit = trim((string)($ex->seizure_unit ?? ''));
                    $qty = trim((string)($ex->seizure_quantity ?? ($ex->amount ?? '')));

                    $qtyStr = '';
                    if ($weight !== '') {
                        $qtyStr = $weight . ($unit !== '' ? ' ' . $unit : '');
                    } elseif ($qty !== '') {
                        $qtyStr = $qty . ' ' . ($ex->seizure_packaging ?? 'Units');
                    }

                    // Match with docket seizures to backfill any unpersisted field or photos
                    $matchedDocketSz = null;
                    foreach ($allSeizures as $szCandidate) {
                        $candName = trim((string)($szCandidate['seizure_name_drug'] ?? ($szCandidate['seizure_name'] ?? ($szCandidate['commodity_name'] ?? ($szCandidate['name'] ?? '')))));
                        $candNo = trim((string)($szCandidate['exhibit_number'] ?? ($szCandidate['property_id'] ?? ($szCandidate['lab_exhibit_no'] ?? ''))));
                        if (($exNo !== '' && $candNo === $exNo) || ($name !== '' && strtolower($candName) === strtolower($name))) {
                            $matchedDocketSz = $szCandidate;
                            break;
                        }
                    }

                    $rawPhotos = [];
                    if (method_exists($ex, 'resolveEvidencePhotosArray')) {
                        $rawPhotos = $ex->resolveEvidencePhotosArray();
                    }
                    if (empty($rawPhotos)) {
                        $rawPhotos = $photoResolver->extractAllPhotos($ex);
                    }
                    if (!empty($matchedDocketSz)) {
                        $rawPhotos = array_merge($rawPhotos, $photoResolver->extractAllPhotos($matchedDocketSz));
                    }
                    if (empty($rawPhotos) && !empty($dData['evidence_photos'])) {
                        $rawPhotos = $photoResolver->extractAllPhotos(['evidence_photos' => $dData['evidence_photos']]);
                    }
                    if (empty($rawPhotos) && !empty($this->evidence_photos)) {
                        $rawPhotos = $photoResolver->extractAllPhotos(['evidence_photos' => $this->evidence_photos]);
                    }

                    $photos = [];
                    foreach ($rawPhotos as $p) {
                        $url = $photoResolver->formatPhotoUrl($p);
                        if (!empty($url)) {
                            $photos[] = $url;
                        }
                    }
                    $photos = array_values(array_unique($photos));

                    // Lab Result resolution
                    $lab = null;
                    try {
                        if (method_exists($ex, 'laboratoryExaminationResult')) {
                            $lab = $ex->laboratoryExaminationResult;
                        }
                        if (!$lab && !empty($ex->id)) {
                            $lab = \App\Models\LaboratoryExaminationResult::where('exhibit_id', $ex->id)->first();
                        }
                    } catch (\Throwable $e) {}

                    // Vehicle specs
                    $vehDetails = array_filter([
                        'Make & Model' => trim(($ex->veh_make ?? '') . ' ' . ($ex->veh_model ?? '')),
                        'Registration / Plate #' => $ex->veh_reg_no,
                        'VIN / Chassis #' => $ex->veh_vin,
                        'Engine Number' => $ex->veh_engine_no,
                        'Country of Reg' => $ex->veh_country_reg,
                        'Type & Model Year' => trim(($ex->veh_type ?? '') . ' ' . ($ex->veh_year ?? '')),
                        'Color' => $ex->veh_colour,
                        'Registered Owner' => $ex->veh_owner,
                        'Owner Contact' => $ex->veh_owner_contact,
                        'Driver at Interdiction' => $ex->veh_driver,
                        'Documents Available' => $ex->veh_docs_available,
                        'Document Ref #' => $ex->veh_doc_ref,
                        'Condition & Operability' => $ex->veh_condition,
                        'Fuel Level' => $ex->veh_fuel,
                        'Keys Received' => $ex->veh_keys_recv ? ('Yes' . ($ex->veh_key_count ? ' (' . $ex->veh_key_count . ' Keys)' : '')) : null,
                        'Visible Damage / Marks' => $ex->veh_damage,
                        'Modifications / Features' => trim(($ex->veh_mods ?? '') . ' ' . ($ex->veh_features ?? '')),
                    ]);

                    // Weapon specs
                    $ammoStr = null;
                    if (!empty($ex->ammunition_details)) {
                        $ammo = is_array($ex->ammunition_details) ? $ex->ammunition_details : (json_decode($ex->ammunition_details, true) ?: [$ex->ammunition_details]);
                        if (is_array($ammo)) {
                            $ammoParts = [];
                            foreach ($ammo as $k => $v) {
                                if (is_string($k)) $ammoParts[] = ucfirst($k) . ': ' . $v;
                                else $ammoParts[] = (string)$v;
                            }
                            $ammoStr = implode(', ', $ammoParts);
                        } else {
                            $ammoStr = (string)$ammo;
                        }
                    }

                    $wpnDetails = array_filter([
                        'Weapon Model' => $ex->wpn_model,
                        'Serial Number' => $ex->wpn_serial_no,
                        'Calibre / Gauge' => $ex->wpn_calibre,
                        'Color & Finish' => $ex->wpn_colour,
                        'Size / Barrel' => $ex->wpn_size,
                        'Distinctive Marks' => $ex->wpn_marks,
                        'Location Found' => $ex->wpn_location_found,
                        'Loaded Status' => $ex->wpn_loaded_status,
                        'Mechanical Condition' => $ex->wpn_condition,
                        'Ammunition & Rounds' => $ammoStr,
                    ]);

                    // Device specs
                    $devDetails = array_filter([
                        'Device Model' => $ex->dev_model,
                        'Serial Number' => $ex->dev_serial_no,
                        'IMEI 1' => $ex->dev_imei1,
                        'IMEI 2' => $ex->dev_imei2,
                        'MAC Address' => $ex->dev_mac_address,
                        'Storage Capacity' => $ex->dev_storage,
                        'Color' => $ex->dev_colour,
                        'Marks / Condition' => $ex->dev_marks,
                    ]);

                    // Chemical & Lab specs (ONLY for drug-related cases or when actual lab examination exists)
                    $labDetails = [];
                    $caseIsDrug = $this->isDrugCase() || str_contains(strtolower($this->unit_name ?? ''), 'lab') || !empty($ex->drug_classification) || !empty($ex->seizure_name_drug);
                    if ($caseIsDrug || $lab) {
                        $detSub = $lab ? $lab->detected_substances : null;
                        if (is_array($detSub)) $detSub = implode(', ', $detSub);
                        $testMeth = $lab ? $lab->test_methods : null;
                        if (is_array($testMeth)) $testMeth = implode(', ', $testMeth);

                        $labDetails = array_filter([
                            'Drug Classification' => $ex->drug_classification ?: ($lab->substance_identification ?? null),
                            'Presumptive Test Result' => $lab ? $lab->presumptive_result : ($this->presumptive_test_result ?? null),
                            'Confirmatory Test Result' => $lab ? $lab->confirmatory_result : ($this->confirmatory_test_result ?? null),
                            'Detected Substances' => $detSub ?: ($this->detected_substances ?? null),
                            'Substance Identification' => $lab ? $lab->substance_identification : null,
                            'Test Methods' => $testMeth ?: ($this->confirmatory_test_methods ?? null),
                            'Screening Method' => $lab ? $lab->screening_method : ($this->screening_method ?? null),
                            'Gross Weight' => $lab ? $lab->sample_weight_gross : null,
                            'Sample Taken for Lab' => $lab ? $lab->sample_weight_taken : null,
                            'Reserved Sample' => $lab ? $lab->sample_weight_reserved : null,
                            'Laboratory #' => $lab ? ($lab->laboratory_number ?: $lab->gsa_lab_number) : ($this->laboratory_number ?? null),
                            'Exam Reference #' => $lab ? $lab->exam_reference : ($ex->lab_exhibit_no ?? null),
                            'Forensic Analyst' => $lab ? $lab->analyst : null,
                            'Purpose of Examination' => $lab ? $lab->purpose_of_examination : ($this->purpose_of_examination ?? null),
                            'Exhibit Disposition' => $lab ? $lab->disposition_of_exhibit : ($this->disposition_of_exhibit ?? null),
                        ]);
                    }

                    $packaging = $ex->seizure_packaging ?: ($matchedDocketSz['seizure_packaging'] ?? ($matchedDocketSz['packaging'] ?? ($this->seizure_packaging ?? 'Official Sealed Evidence Bag')));
                    $concealment = $ex->seizure_concealment ?: ($matchedDocketSz['seizure_concealment'] ?? ($matchedDocketSz['concealment'] ?? ($this->seizure_concealment ?? 'N/A')));
                    $seizLoc = $ex->seizure_location ?: ($matchedDocketSz['seizure_location'] ?? ($this->raid_location ?? 'Location Undisclosed'));
                    $seizOff = $ex->seizure_officer ?: ($matchedDocketSz['seizure_officer'] ?? ($this->submitting_officer ?? 'Forensic Evidence Officer'));
                    $invOff = $ex->investigating_officer ?: ($matchedDocketSz['investigating_officer'] ?? ($this->investigating_officer ?? null));

                    // General attributes
                    $details = [];
                    if (!empty($ex->drug_classification)) $details['Drug Classification'] = $ex->drug_classification;
                    if ($concealment && $concealment !== 'N/A') $details['Concealment Method'] = $concealment;
                    if ($packaging) $details['Packaging'] = $packaging;
                    if ($seizLoc) $details['Seizure Location'] = $seizLoc;
                    if (!empty($ex->seizure_date)) $details['Seizure Date'] = $ex->seizure_date . (!empty($ex->seizure_time) ? ' ' . $ex->seizure_time : '');
                    if ($seizOff) $details['Seizing Officer'] = $seizOff;
                    if ($invOff) $details['Investigating Officer'] = $invOff;
                    if (!empty($ex->source_requesting_unit)) $details['Requesting Unit'] = $ex->source_requesting_unit;
                    if (!empty($ex->source_of_exhibits)) $details['Source of Exhibits'] = $ex->source_of_exhibits;
                    if (!empty($ex->seizure_reason)) $details['Seizure Reason'] = $ex->seizure_reason;
                    if (!empty($ex->seizure_value)) $details['Estimated Street Value'] = $fmtVal($ex->seizure_value);

                    // Reconstructed or resolved Chain of Custody
                    $chainOfCustody = null;
                    if (method_exists($ex, 'getResolvedChainOfCustodyAttribute')) {
                        $chainOfCustody = $ex->resolved_chain_of_custody;
                    } elseif (!empty($ex->chain_of_custody)) {
                        $chainOfCustody = trim($ex->chain_of_custody);
                    } else {
                        $from = $ex->source_of_exhibits ?: ($ex->source_requesting_unit ?: ($seizLoc ?: 'Seizure Site'));
                        $to = $ex->custodial_status === 'In Vault Custody' ? 'Evidence Vault' : ($ex->custodial_status ?: 'Central Evidence Vault');
                        $chainOfCustody = "{$from} ➔ {$to}";
                    }

                    $dedupKey = strtolower(($exNo ?: $name) . '|' . $cat);
                    if (!isset($seenKeys[$dedupKey]) && ($name !== '' || $exNo !== '')) {
                        $seenKeys[$dedupKey] = true;
                        $exhibits[] = [
                            'id' => $ex->id,
                            'exhibit_number' => $exNo ?: ('EXH-' . str_pad((string)(count($exhibits) + 1), 3, '0', STR_PAD_LEFT)),
                            'property_id' => $ex->property_id ?? null,
                            'lab_exhibit_no' => $ex->lab_exhibit_no ?? ($lab ? $lab->exam_reference : null),
                            'commodity_name' => $name ?: 'Seized Exhibit',
                            'category' => $cat,
                            'exhibit_type' => $ex->exhibit_type ?? $cat,
                            'quantity_or_weight' => $qtyStr ?: '1 Unit',
                            'quantity' => $qty,
                            'weight' => $weight,
                            'weight_unit' => $unit,
                            'estimated_value' => $fmtVal($ex->seizure_value),
                            'custodial_status' => $ex->custodial_status ?? 'In Vault Storage',
                            'location' => $ex->custody_location ?? ($seizLoc ?? ($this->holding_facility ?? 'NACOC Central Vault Safe B-04')),
                            'packaging' => $packaging,
                            'concealment' => $concealment,
                            'suspect_name' => $ex->associated_person ?: ($ex->suspect_name ?: $primarySuspect),
                            'seizure_date' => $ex->seizure_date ?? ($this->created_at ? $this->created_at->format('Y-m-d') : date('Y-m-d')),
                            'seizure_time' => $ex->seizure_time ?? null,
                            'seizure_location' => $seizLoc,
                            'seizure_officer' => $seizOff,
                            'investigating_officer' => $invOff,
                            'requesting_unit' => $ex->source_requesting_unit ?? ($this->unit_name ?? 'Forensic Directorate'),
                            'source_of_exhibits' => $ex->source_of_exhibits ?? null,
                            'seizure_reason' => $ex->seizure_reason ?? null,
                            'description' => $ex->description_of_exhibits ?? ($ex->other_exhibit_description ?? ($ex->contents ?? null)),
                            'remark' => $ex->exhibit_remark ?? ($ex->lab_remark ?? 'Exhibit secured in Central Vault with chain of custody verified.'),
                            'chain_of_custody' => $chainOfCustody,
                            'photos' => $photos,
                            'details' => $details,
                            'vehicle' => $vehDetails,
                            'weapon' => $wpnDetails,
                            'device' => $devDetails,
                            'lab' => $labDetails,
                        ];
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 2. Docket data seizures
        $allSeizures = array_merge(
            $dData['seizures'] ?? [],
            $dData['associated_seizures'] ?? [],
            $dData['associate_exhibits'] ?? []
        );

        foreach ($allSeizures as $sz) {
            $exNo = trim((string)($sz['exhibit_number'] ?? ($sz['property_id'] ?? ($sz['lab_exhibit_no'] ?? ''))));
            $name = trim((string)($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? ($sz['commodity_name'] ?? ($sz['name'] ?? '')))));
            $cat = trim((string)($sz['exhibit_category'] ?? ($sz['category'] ?? ($sz['exhibit_type'] ?? 'General'))));
            $weight = trim((string)($sz['seizure_weight'] ?? ($sz['sample_weight'] ?? ($sz['weight'] ?? ''))));
            $unit = trim((string)($sz['seizure_unit'] ?? ($sz['unit'] ?? '')));
            $qty = trim((string)($sz['seizure_quantity'] ?? ($sz['amount'] ?? ($sz['quantity'] ?? ''))));

            $qtyStr = '';
            if ($weight !== '') {
                $qtyStr = $weight . ($unit !== '' ? ' ' . $unit : '');
            } elseif ($qty !== '') {
                $qtyStr = $qty . ' ' . ($sz['seizure_packaging'] ?? ($sz['packaging'] ?? 'Units'));
            }

            $dedupKey = strtolower(($exNo ?: $name) . '|' . $cat);
            if (!isset($seenKeys[$dedupKey]) && ($name !== '' || $exNo !== '')) {
                $seenKeys[$dedupKey] = true;

                $rawPhotos = $photoResolver->extractAllPhotos($sz);
                if (empty($rawPhotos) && !empty($dData['evidence_photos'])) {
                    $rawPhotos = $photoResolver->extractAllPhotos(['evidence_photos' => $dData['evidence_photos']]);
                }
                if (empty($rawPhotos) && !empty($this->evidence_photos)) {
                    $rawPhotos = $photoResolver->extractAllPhotos(['evidence_photos' => $this->evidence_photos]);
                }

                $photos = [];
                foreach ($rawPhotos as $p) {
                    $url = $photoResolver->formatPhotoUrl($p);
                    if (!empty($url)) {
                        $photos[] = $url;
                    }
                }
                $photos = array_values(array_unique($photos));

                // Vehicle specs from docket
                $vehDetails = array_filter([
                    'Make & Model' => trim(($sz['veh_make'] ?? '') . ' ' . ($sz['veh_model'] ?? '')),
                    'Registration / Plate #' => $sz['veh_reg_no'] ?? null,
                    'VIN / Chassis #' => $sz['veh_vin'] ?? null,
                    'Engine Number' => $sz['veh_engine_no'] ?? null,
                    'Country of Reg' => $sz['veh_country_reg'] ?? null,
                    'Type & Model Year' => trim(($sz['veh_type'] ?? '') . ' ' . ($sz['veh_year'] ?? '')),
                    'Color' => $sz['veh_colour'] ?? null,
                    'Registered Owner' => $sz['veh_owner'] ?? null,
                    'Owner Contact' => $sz['veh_owner_contact'] ?? null,
                    'Driver at Interdiction' => $sz['veh_driver'] ?? null,
                    'Documents Available' => $sz['veh_docs_available'] ?? null,
                    'Document Ref #' => $sz['veh_doc_ref'] ?? null,
                    'Condition & Operability' => $sz['veh_condition'] ?? null,
                    'Fuel Level' => $sz['veh_fuel'] ?? null,
                    'Keys Received' => !empty($sz['veh_keys_recv']) ? ('Yes' . (!empty($sz['veh_key_count']) ? ' (' . $sz['veh_key_count'] . ' Keys)' : '')) : null,
                    'Visible Damage / Marks' => $sz['veh_damage'] ?? null,
                    'Modifications / Features' => trim(($sz['veh_mods'] ?? '') . ' ' . ($sz['veh_features'] ?? '')),
                ]);

                // Weapon specs from docket
                $ammoStr = null;
                if (!empty($sz['ammunition_details'])) {
                    $ammo = is_array($sz['ammunition_details']) ? $sz['ammunition_details'] : (json_decode($sz['ammunition_details'], true) ?: [$sz['ammunition_details']]);
                    if (is_array($ammo)) {
                        $ammoParts = [];
                        foreach ($ammo as $k => $v) {
                            if (is_string($k)) $ammoParts[] = ucfirst($k) . ': ' . $v;
                            else $ammoParts[] = (string)$v;
                        }
                        $ammoStr = implode(', ', $ammoParts);
                    } else {
                        $ammoStr = (string)$ammo;
                    }
                }

                $wpnDetails = array_filter([
                    'Weapon Model' => $sz['wpn_model'] ?? null,
                    'Serial Number' => $sz['wpn_serial_no'] ?? null,
                    'Calibre / Gauge' => $sz['wpn_calibre'] ?? null,
                    'Color & Finish' => $sz['wpn_colour'] ?? null,
                    'Size / Barrel' => $sz['wpn_size'] ?? null,
                    'Distinctive Marks' => $sz['wpn_marks'] ?? null,
                    'Location Found' => $sz['wpn_location_found'] ?? null,
                    'Loaded Status' => $sz['wpn_loaded_status'] ?? null,
                    'Mechanical Condition' => $sz['wpn_condition'] ?? null,
                    'Ammunition & Rounds' => $ammoStr,
                ]);

                // Device specs from docket
                $devDetails = array_filter([
                    'Device Model' => $sz['dev_model'] ?? null,
                    'Serial Number' => $sz['dev_serial_no'] ?? null,
                    'IMEI 1' => $sz['dev_imei1'] ?? null,
                    'IMEI 2' => $sz['dev_imei2'] ?? null,
                    'MAC Address' => $sz['dev_mac_address'] ?? null,
                    'Storage Capacity' => $sz['dev_storage'] ?? null,
                    'Color' => $sz['dev_colour'] ?? null,
                    'Marks / Condition' => $sz['dev_marks'] ?? null,
                ]);

                // Chemical & Lab specs from docket
                // Chemical & Lab specs (ONLY for drug-related cases or when actual lab examination exists)
                $labDetails = [];
                $caseIsDrug = $this->isDrugCase() || str_contains(strtolower($this->unit_name ?? ''), 'lab') || !empty($sz['drug_classification']) || !empty($sz['seizure_name_drug']);
                if ($caseIsDrug || !empty($sz['substance_identification']) || !empty($sz['presumptive_result'])) {
                    $detSub = $sz['detected_substances'] ?? null;
                    if (is_array($detSub)) $detSub = implode(', ', $detSub);
                    $testMeth = $sz['test_methods'] ?? null;
                    if (is_array($testMeth)) $testMeth = implode(', ', $testMeth);

                    $labDetails = array_filter([
                        'Drug Classification' => $sz['drug_classification'] ?? ($sz['substance_identification'] ?? null),
                        'Presumptive Test Result' => $sz['presumptive_result'] ?? ($this->presumptive_test_result ?? null),
                        'Confirmatory Test Result' => $sz['confirmatory_result'] ?? ($this->confirmatory_test_result ?? null),
                        'Detected Substances' => $detSub ?: ($this->detected_substances ?? null),
                        'Substance Identification' => $sz['substance_identification'] ?? null,
                        'Test Methods' => $testMeth ?: ($this->confirmatory_test_methods ?? null),
                        'Screening Method' => $sz['screening_method'] ?? ($this->screening_method ?? null),
                        'Gross Weight' => $sz['sample_gross'] ?? null,
                        'Sample Taken for Lab' => $sz['sample_taken'] ?? null,
                        'Reserved Sample' => $sz['sample_reserved'] ?? null,
                        'Laboratory #' => $sz['laboratory_number'] ?? ($this->laboratory_number ?? null),
                        'Exam Reference #' => $sz['exam_reference'] ?? ($sz['lab_exhibit_no'] ?? null),
                        'Forensic Analyst' => $sz['analyst'] ?? null,
                        'Purpose of Examination' => $sz['purpose_of_examination'] ?? ($this->purpose_of_examination ?? null),
                        'Exhibit Disposition' => $sz['disposition_of_exhibit'] ?? ($this->disposition_of_exhibit ?? null),
                    ]);
                }

                // General key-values
                $details = [];
                if (!empty($sz['drug_classification'])) $details['Drug Classification'] = $sz['drug_classification'];
                if (!empty($sz['seizure_concealment'])) $details['Concealment Method'] = $sz['seizure_concealment'];
                if (!empty($sz['seizure_packaging'])) $details['Packaging'] = $sz['seizure_packaging'];
                if (!empty($sz['seizure_location'])) $details['Seizure Location'] = $sz['seizure_location'];
                if (!empty($sz['seizure_date'])) $details['Seizure Date'] = $sz['seizure_date'] . (!empty($sz['seizure_time']) ? ' ' . $sz['seizure_time'] : '');
                if (!empty($sz['seizure_officer'])) $details['Seizing Officer'] = $sz['seizure_officer'];
                if (!empty($sz['investigating_officer'])) $details['Investigating Officer'] = $sz['investigating_officer'];
                if (!empty($sz['source_requesting_unit'])) $details['Requesting Unit'] = $sz['source_requesting_unit'];
                if (!empty($sz['source_of_exhibits'])) $details['Source of Exhibits'] = $sz['source_of_exhibits'];
                if (!empty($sz['seizure_reason'])) $details['Seizure Reason'] = $sz['seizure_reason'];
                if (!empty($sz['seizure_value'])) $details['Estimated Street Value'] = $fmtVal($sz['seizure_value']);

                $from = $sz['source_of_exhibits'] ?? ($sz['seizure_location'] ?? 'Seizure Site');
                $to = $this->holding_facility ?? 'NACOC Central Vault Safe B-04';
                $chain = $sz['chain_of_custody'] ?? "{$from} ➔ {$to}";

                $invOffDocket = $sz['investigating_officer'] ?? null;
                if (empty($invOffDocket) && !empty($sz['id'])) {
                    try { $invOffDocket = Exhibit::where('id', $sz['id'])->value('investigating_officer'); } catch (\Throwable $e) {}
                }
                if (empty($invOffDocket) && !empty($exNo)) {
                    try { $invOffDocket = Exhibit::where('exhibit_number', $exNo)->value('investigating_officer'); } catch (\Throwable $e) {}
                }
                if (empty($invOffDocket) && !empty($this->id)) {
                    $uId = isset($this->underlyingRecord) ? $this->underlyingRecord->id : $this->id;
                    try {
                        $invOffDocket = Exhibit::where(function($q) use ($uId) {
                            $q->where('single_suspect_case_id', $this->id)
                                ->orWhere('multiple_suspect_case_id', $this->id)
                                ->orWhere('single_suspect_case_id', $uId)
                                ->orWhere('multiple_suspect_case_id', $uId);
                        })->whereNotNull('investigating_officer')->value('investigating_officer');
                    } catch (\Throwable $e) {}
                }
                if (empty($invOffDocket)) {
                    $invOffDocket = $this->investigating_officer ?? null;
                }

                if ($invOffDocket) $details['Investigating Officer'] = $invOffDocket;

                $exhibits[] = [
                    'id' => null,
                    'exhibit_number' => $exNo ?: ('EXH-' . str_pad((string)(count($exhibits) + 1), 3, '0', STR_PAD_LEFT)),
                    'property_id' => $sz['property_id'] ?? null,
                    'lab_exhibit_no' => $sz['lab_exhibit_no'] ?? null,
                    'commodity_name' => $name ?: 'Seized Exhibit',
                    'category' => $cat,
                    'exhibit_type' => $sz['exhibit_type'] ?? $cat,
                    'quantity_or_weight' => $qtyStr ?: '1 Unit',
                    'quantity' => $qty,
                    'weight' => $weight,
                    'weight_unit' => $unit,
                    'estimated_value' => $fmtVal($sz['seizure_value'] ?? ($sz['amount'] ?? null)),
                    'custodial_status' => $sz['custodial_status'] ?? 'In Vault Storage',
                    'location' => $sz['seizure_location'] ?? ($this->holding_facility ?? 'NACOC Central Vault Safe B-04'),
                    'packaging' => $sz['seizure_packaging'] ?? ($sz['packaging'] ?? 'Official Sealed Evidence Bag'),
                    'concealment' => $sz['seizure_concealment'] ?? ($sz['concealment'] ?? 'N/A'),
                    'suspect_name' => $sz['associated_person'] ?? ($sz['suspect_name'] ?? $primarySuspect),
                    'seizure_date' => $sz['seizure_date'] ?? ($this->created_at ? $this->created_at->format('Y-m-d') : date('Y-m-d')),
                    'seizure_time' => $sz['seizure_time'] ?? null,
                    'seizure_location' => $sz['seizure_location'] ?? ($this->raid_location ?? 'Location Undisclosed'),
                    'seizure_officer' => $sz['seizure_officer'] ?? ($this->submitting_officer ?? 'Forensic Evidence Officer'),
                    'investigating_officer' => $invOffDocket,
                    'requesting_unit' => $sz['source_requesting_unit'] ?? ($this->unit_name ?? 'Forensic Directorate'),
                    'source_of_exhibits' => $sz['source_of_exhibits'] ?? null,
                    'seizure_reason' => $sz['seizure_reason'] ?? null,
                    'description' => $sz['description_of_exhibits'] ?? ($sz['description'] ?? ($sz['contents'] ?? null)),
                    'remark' => $sz['remark'] ?? ($sz['notes'] ?? 'Exhibit secured in Central Vault with chain of custody verified.'),
                    'chain_of_custody' => $chain,
                    'photos' => $photos,
                    'details' => $details,
                    'vehicle' => $vehDetails,
                    'weapon' => $wpnDetails,
                    'device' => $devDetails,
                    'lab' => $labDetails,
                ];
            }
        }

        // 3. Fallback: top-level exhibit fields
        if (empty($exhibits)) {
            $name = $this->commodity_name ?: ($this->exhibits_summary ?: 'Seized Forensic Exhibit');
            $rawPhotos = $photoResolver->extractAllPhotos($dData);
            if (empty($rawPhotos) && !empty($this->evidence_photos)) {
                $rawPhotos = $photoResolver->extractAllPhotos(['evidence_photos' => $this->evidence_photos]);
            }
            $photos = [];
            foreach ($rawPhotos as $p) {
                $url = $photoResolver->formatPhotoUrl($p);
                if (!empty($url)) {
                    $photos[] = $url;
                }
            }
            $photos = array_values(array_unique($photos));

            $exhibits[] = [
                'id' => null,
                'exhibit_number' => 'EXH-001',
                'property_id' => null,
                'lab_exhibit_no' => $this->lab_exhibit_no ?? null,
                'commodity_name' => $name,
                'category' => $this->exhibit_category ?? 'General Evidence',
                'exhibit_type' => $this->exhibit_type ?? 'General Evidence',
                'quantity_or_weight' => $this->seizure_weight ? ($this->seizure_weight . ' ' . ($this->seizure_unit ?? 'g')) : '1 Unit',
                'quantity' => $this->seizure_quantity ?? 1,
                'weight' => $this->seizure_weight ?? null,
                'weight_unit' => $this->seizure_unit ?? null,
                'estimated_value' => $fmtVal($this->seizure_value),
                'custodial_status' => 'In Vault Storage',
                'location' => $this->holding_facility ?? 'NACOC Central Vault Safe B-04',
                'packaging' => $this->seizure_packaging ?? 'Official Evidence Packaging',
                'concealment' => $this->seizure_concealment ?? 'N/A',
                'suspect_name' => $primarySuspect,
                'seizure_date' => $this->seizure_date ?? ($this->created_at ? $this->created_at->format('Y-m-d') : date('Y-m-d')),
                'seizure_time' => $this->seizure_time ?? null,
                'seizure_location' => $this->raid_location ?? 'Location Undisclosed',
                'seizure_officer' => $this->submitting_officer ?? 'Evidence Officer',
                'investigating_officer' => $this->investigating_officer ?? null,
                'requesting_unit' => $this->unit_name ?? 'Forensic Directorate',
                'source_of_exhibits' => null,
                'seizure_reason' => null,
                'description' => $this->description_of_exhibits ?? ($this->description ?? null),
                'remark' => 'Exhibit securely vaulted under NACOC evidence custody regulations.',
                'chain_of_custody' => ($this->raid_location ?: 'Seizure Site') . ' ➔ ' . ($this->holding_facility ?: 'NACOC Central Vault'),
                'photos' => $photos,
                'details' => array_filter([
                    'Classification' => $this->commodity_name,
                    'Seizure Location' => $this->raid_location,
                    'Holding Vault' => $this->holding_facility ?? 'NACOC Central Vault',
                ]),
                'vehicle' => [],
                'weapon' => [],
                'device' => [],
                'lab' => ($this->isDrugCase() || str_contains(strtolower($this->unit_name ?? ''), 'lab')) ? array_filter([
                    'Presumptive Test Result' => $this->presumptive_test_result,
                    'Confirmatory Test Result' => $this->confirmatory_test_result,
                    'Laboratory #' => $this->laboratory_number,
                ]) : [],
            ];
        }

        return $exhibits;
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
        $isCreatorEvidence = (str_contains(strtolower($this->unit_name ?? ''), 'evidence'))
            || str_starts_with(strtoupper($this->case_number ?? ''), 'EM-')
            || str_starts_with(strtoupper($this->case_number ?? ''), 'EVD-');
        $hasCssInUnitProgress = isset($unitProgress['Crime Scene Support']);
        $includeCss = (!empty($flags['has_weapon_or_vehicle']))
            || (!$isCreatorEvidence && !empty($flags['has_crime_scene_mandatory']))
            || $hasCssInUnitProgress
            || str_starts_with($this->case_number ?? '', 'CSS-');

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
        if (str_contains($uName, 'digital') || str_contains($uName, 'cyber') || str_contains($uName, 'physical') || str_contains($uName, 'dpf')) {
            return true;
        }

        try {
            if (\App\Models\DpfReport::forCase($this->id, $this->case_number)) {
                return true;
            }
        } catch (\Throwable $e) {}

        $exCat   = strtolower($this->exhibit_category ?? '');
        $title   = strtolower($this->title ?? '');
        $offense = strtolower($this->offense_description ?? '');
        $seizure = strtolower($this->seizure_name ?? '');
        $dev     = strtolower($this->dev_model ?? '');

        $digitalKws = [
            'digital', 'mobile', 'phone', 'laptop', 'computer', 'tablet', 'ipad',
            'hard drive', 'harddrive', 'hdd', 'ssd', 'usb', 'flash drive', 'memory card',
            'electronic', 'device', 'iphone', 'android', 'pc', 'desktop', 'camera', 'cyber',
            'sim card', 'pendrive', 'pen drive', 'modem', 'router', 'cctv', 'dvr', 'nvr'
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
                    $mFront = $s->mugshot_front
                        ?? $s->photo
                        ?? ($docketSp['mugshot_front'] ?? null)
                        ?? ($docketSp['photo'] ?? null)
                        ?? ($docketSp['mugshots']['front'] ?? null)
                        ?? ($docketSp['mugshots']['front-profile'] ?? null)
                        ?? ($idx === 0 ? ($this->mugshot_front ?? $this->photo ?? ($docketData['mugshot_front'] ?? null) ?? ($docketData['photo'] ?? null) ?? ($docketData['mugshots']['front'] ?? null)) : null);
                    $mLeft = $s->mugshot_left
                        ?? ($docketSp['mugshot_left'] ?? null)
                        ?? ($docketSp['mugshots']['left'] ?? null)
                        ?? ($docketSp['mugshots']['left-profile'] ?? null)
                        ?? ($idx === 0 ? ($this->mugshot_left ?? ($docketData['mugshot_left'] ?? null) ?? ($docketData['mugshots']['left'] ?? null)) : null);
                    $mRight = $s->mugshot_right
                        ?? ($docketSp['mugshot_right'] ?? null)
                        ?? ($docketSp['mugshots']['right'] ?? null)
                        ?? ($docketSp['mugshots']['right-profile'] ?? null)
                        ?? ($idx === 0 ? ($this->mugshot_right ?? ($docketData['mugshot_right'] ?? null) ?? ($docketData['mugshots']['right'] ?? null)) : null);

                    $formattedFront = \App\Services\CasePhotoResolver::resolveMugshotUrl($mFront);
                    $formattedLeft  = \App\Services\CasePhotoResolver::resolveMugshotUrl($mLeft);
                    $formattedRight = \App\Services\CasePhotoResolver::resolveMugshotUrl($mRight);

                    $isUnassignedRow = $this->isNoSuspectCase() || self::isUnassignedSuspectName($s->suspect_name ?? '');
                    $natCandidate = $s->nationality ?? ($docketSp['nationality'] ?? null);
                    $natValue = $isUnassignedRow
                        ? ((!empty($natCandidate) && $natCandidate !== 'Ghanaian') ? $natCandidate : 'N/A')
                        : ($natCandidate ?? 'Ghanaian');

                    $list[] = [
                        'id'                     => $s->id,
                        'suspect_number'         => $s->suspect_number,
                        'suspect_name'           => $s->suspect_name,
                        'suspect_alias'          => $s->suspect_alias ?? ($docketSp['suspect_alias'] ?? 'N/A'),
                        'role'                   => $s->role ?? ($docketSp['role'] ?? ($idx === 0 ? 'Primary Suspect' : 'Associate Suspect')),
                        'association_role'       => $s->role ?? ($docketSp['association_role'] ?? ($docketSp['role'] ?? ($idx === 0 ? 'Primary Suspect' : 'Associate Suspect'))),
                        'associate_id'           => $s->associate_id ?? ($docketSp['associate_id'] ?? null),
                        'status'                 => self::normalizeSuspectDisposition($s->status ?? $s->suspect_disposition),
                        'suspect_disposition'    => self::normalizeSuspectDisposition($s->suspect_disposition ?? $s->status),
                        'disposition_reason'     => $s->disposition_reason,
                        'disposition_updated_at' => $s->disposition_updated_at,
                        'disposition_updated_by' => $s->disposition_updated_by,
                        'ghana_card'             => $s->ghana_card ?? ($docketSp['ghana_card'] ?? null),
                        'gender'                 => $isUnassignedRow ? ($s->gender ?? ($docketSp['gender'] ?? 'N/A')) : ($s->gender ?? ($docketSp['gender'] ?? null)),
                        'dob'                    => $s->dob ?? ($docketSp['dob'] ?? null),
                        'age'                    => $s->age ?? ($docketSp['age'] ?? null),
                        'height'                 => $s->height ?? ($docketSp['height'] ?? null),
                        'height_unit'            => $s->height_unit ?? ($docketSp['height_unit'] ?? null),
                        'eye_color'              => $s->eye_color ?? ($docketSp['eye_color'] ?? null),
                        'nationality'            => $natValue,
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
                        'mugshot_front'          => $formattedFront ?? $mFront,
                        'mugshot_left'           => $formattedLeft ?? $mLeft,
                        'mugshot_right'          => $formattedRight ?? $mRight,
                        'raw_mugshot_front'      => $mFront,
                        'raw_mugshot_left'       => $mLeft,
                        'raw_mugshot_right'      => $mRight,
                        'mugshot_front_url'      => $formattedFront,
                        'mugshot_left_url'       => $formattedLeft,
                        'mugshot_right_url'      => $formattedRight,
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
                $mFront = ($s['mugshot_front'] ?? null)
                    ?? ($s['photo'] ?? null)
                    ?? ($s['mugshots']['front'] ?? null)
                    ?? ($s['mugshots']['front-profile'] ?? null)
                    ?? ($idx === 0 ? ($this->mugshot_front ?? $this->photo ?? ($docketData['mugshot_front'] ?? null) ?? ($docketData['photo'] ?? null) ?? ($docketData['mugshots']['front'] ?? null)) : null);
                $mLeft = ($s['mugshot_left'] ?? null)
                    ?? ($s['mugshots']['left'] ?? null)
                    ?? ($s['mugshots']['left-profile'] ?? null)
                    ?? ($idx === 0 ? ($this->mugshot_left ?? ($docketData['mugshot_left'] ?? null) ?? ($docketData['mugshots']['left'] ?? null)) : null);
                $mRight = ($s['mugshot_right'] ?? null)
                    ?? ($s['mugshots']['right'] ?? null)
                    ?? ($s['mugshots']['right-profile'] ?? null)
                    ?? ($idx === 0 ? ($this->mugshot_right ?? ($docketData['mugshot_right'] ?? null) ?? ($docketData['mugshots']['right'] ?? null)) : null);

                $formattedFront = \App\Services\CasePhotoResolver::resolveMugshotUrl($mFront);
                $formattedLeft  = \App\Services\CasePhotoResolver::resolveMugshotUrl($mLeft);
                $formattedRight = \App\Services\CasePhotoResolver::resolveMugshotUrl($mRight);

                $isUnassignedRow = $this->isNoSuspectCase() || self::isUnassignedSuspectName($s['suspect_name'] ?? '');
                $natValue = $isUnassignedRow
                    ? ((!empty($s['nationality']) && $s['nationality'] !== 'Ghanaian') ? $s['nationality'] : 'N/A')
                    : ($s['nationality'] ?? 'Ghanaian');

                $list[] = [
                    'id'                     => $s['id'] ?? null,
                    'suspect_number'         => $idx + 1,
                    'suspect_name'           => $s['suspect_name'],
                    'suspect_alias'          => $s['suspect_alias'] ?? 'N/A',
                    'role'                   => $role,
                    'association_role'       => $role,
                    'associate_id'           => $s['associate_id'] ?? null,
                    'status'                 => self::normalizeSuspectDisposition($s['status'] ?? ($s['suspect_disposition'] ?? ($idx === 0 ? ($this->suspect_disposition ?? ($this->underlyingRecord->suspect_disposition ?? null)) : null))),
                    'suspect_disposition'    => self::normalizeSuspectDisposition($s['suspect_disposition'] ?? ($s['status'] ?? ($idx === 0 ? ($this->suspect_disposition ?? ($this->underlyingRecord->suspect_disposition ?? null)) : null))),
                    'disposition_reason'     => $s['disposition_reason'] ?? ($idx === 0 ? ($this->disposition_reason ?? ($this->underlyingRecord->disposition_reason ?? null)) : null),
                    'disposition_updated_at' => $s['disposition_updated_at'] ?? ($idx === 0 ? ($this->disposition_updated_at ?? ($this->underlyingRecord->disposition_updated_at ?? null)) : null),
                    'disposition_updated_by' => $s['disposition_updated_by'] ?? ($idx === 0 ? ($this->disposition_updated_by ?? ($this->underlyingRecord->disposition_updated_by ?? null)) : null),
                    'ghana_card'             => $s['ghana_card'] ?? null,
                    'gender'                 => $isUnassignedRow ? ($s['gender'] ?? 'N/A') : ($s['gender'] ?? null),
                    'dob'                    => $s['dob'] ?? null,
                    'age'                    => $s['age'] ?? null,
                    'height'                 => $s['height'] ?? null,
                    'height_unit'            => $s['height_unit'] ?? null,
                    'eye_color'              => $s['eye_color'] ?? null,
                    'nationality'            => $natValue,
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
                    'mugshot_front'          => $formattedFront ?? $mFront,
                    'mugshot_left'           => $formattedLeft ?? $mLeft,
                    'mugshot_right'          => $formattedRight ?? $mRight,
                    'raw_mugshot_front'      => $mFront,
                    'raw_mugshot_left'       => $mLeft,
                    'raw_mugshot_right'      => $mRight,
                    'mugshot_front_url'      => $formattedFront,
                    'mugshot_left_url'       => $formattedLeft,
                    'mugshot_right_url'      => $formattedRight,
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

        $mFront = $this->mugshot_front
            ?? $this->photo
            ?? ($docketData['mugshot_front'] ?? null)
            ?? ($docketData['photo'] ?? null)
            ?? ($docketData['mugshots']['front'] ?? null)
            ?? ($docketData['mugshots']['front-profile'] ?? null);
        $mLeft = $this->mugshot_left
            ?? ($docketData['mugshot_left'] ?? null)
            ?? ($docketData['mugshots']['left'] ?? null)
            ?? ($docketData['mugshots']['left-profile'] ?? null);
        $mRight = $this->mugshot_right
            ?? ($docketData['mugshot_right'] ?? null)
            ?? ($docketData['mugshots']['right'] ?? null)
            ?? ($docketData['mugshots']['right-profile'] ?? null);

        $formattedFront = \App\Services\CasePhotoResolver::resolveMugshotUrl($mFront);
        $formattedLeft  = \App\Services\CasePhotoResolver::resolveMugshotUrl($mLeft);
        $formattedRight = \App\Services\CasePhotoResolver::resolveMugshotUrl($mRight);

        $list[] = [
            'id'                     => $this->id,
            'suspect_number'         => 1,
            'suspect_name'           => $this->suspect_name ?? $this->title,
            'suspect_alias'          => $this->suspect_alias ?? 'N/A',
            'role'                   => 'Primary Suspect',
            'association_role'       => 'Primary Suspect',
            'associate_id'           => null,
            'status'                 => $primaryDisp,
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
            'nationality'            => $this->isNoSuspectCase()
                ? ((!empty($docketData['nationality']) && $docketData['nationality'] !== 'Ghanaian') ? $docketData['nationality'] : 'N/A')
                : ($this->nationality ?? ($docketData['nationality'] ?? 'Ghanaian')),
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
            'mugshot_front'          => $formattedFront ?? $mFront,
            'mugshot_left'           => $formattedLeft ?? $mLeft,
            'mugshot_right'          => $formattedRight ?? $mRight,
            'raw_mugshot_front'      => $mFront,
            'raw_mugshot_left'       => $mLeft,
            'raw_mugshot_right'      => $mRight,
            'mugshot_front_url'      => $formattedFront,
            'mugshot_left_url'       => $formattedLeft,
            'mugshot_right_url'      => $formattedRight,
            'is_associate'           => false,
        ];

        return $list;
    }

    /**
     * Retrieve suspects strictly associated with Electronic Devices for this case.
     * In multi-suspect cases, this filters out suspects tied only to other exhibit types
     * (e.g. drugs, cash, weapons) and strictly retains users associated with electronic devices.
     *
     * @return array
     */
    public function getElectronicDeviceSuspects(): array
    {
        $allSuspects = $this->getSuspectsList();
        if (empty($allSuspects)) {
            return [];
        }

        $electronicCat = config('forensic.exhibit_categories.electronic_devices', 'Electronic Devices');

        $isMatchingCategory = function ($cat) use ($electronicCat) {
            if (empty($cat)) return false;
            $cTrim = trim((string)$cat);
            $eTrim = trim((string)$electronicCat);
            if (!empty($eTrim) && ((strcasecmp($cTrim, $eTrim) === 0) || (stripos($cTrim, $eTrim) !== false))) {
                return true;
            }
            $lower = strtolower($cTrim);
            return str_contains($lower, 'electronic')
                || str_contains($lower, 'device')
                || str_contains($lower, 'digital')
                || str_contains($lower, 'phone')
                || str_contains($lower, 'mobile')
                || str_contains($lower, 'laptop')
                || str_contains($lower, 'computer');
        };

        $isElectronicItem = function ($ex) use ($isMatchingCategory) {
            $cat = '';
            $type = '';
            $model = '';
            $imei = '';
            $serial = '';
            $name = '';

            if (is_array($ex)) {
                $cat = $ex['exhibit_category'] ?? ($ex['category'] ?? '');
                $type = $ex['exhibit_type'] ?? '';
                $model = $ex['dev_model'] ?? ($ex['device_model'] ?? '');
                $imei = $ex['dev_imei1'] ?? ($ex['dev_imei2'] ?? '');
                $serial = $ex['dev_serial_no'] ?? ($ex['dev_mac_address'] ?? '');
                $name = $ex['commodity_name'] ?? ($ex['seizure_name'] ?? ($ex['name'] ?? ''));
            } elseif (is_object($ex)) {
                $cat = $ex->exhibit_category ?? ($ex->category ?? '');
                $type = $ex->exhibit_type ?? '';
                $model = $ex->dev_model ?? ($ex->device_model ?? '');
                $imei = $ex->dev_imei1 ?? ($ex->dev_imei2 ?? '');
                $serial = $ex->dev_serial_no ?? ($ex->dev_mac_address ?? '');
                $name = $ex->commodity_name ?? '';
            }

            if (!empty($model) || !empty($imei) || !empty($serial)) {
                return true;
            }

            if ($isMatchingCategory($cat) || $isMatchingCategory($type)) {
                return true;
            }

            $lowerName = strtolower((string)$name);
            $elecKws = ['phone', 'mobile', 'iphone', 'samsung', 'laptop', 'macbook', 'tablet', 'ipad', 'hard drive', 'hdd', 'ssd', 'usb drive', 'flash drive', 'memory card'];
            foreach ($elecKws as $kw) {
                if (str_contains($lowerName, $kw)) {
                    if ($kw === 'tablet' && (\App\Services\DrugExhibitClassifier::extractKnownSubstance($lowerName) !== null || str_contains($lowerName, 'drug') || str_contains(strtolower((string)$cat), 'drug'))) {
                        continue;
                    }
                    return true;
                }
            }

            return false;
        };

        // Collect all exhibits from Eloquent relations and DB
        $allCaseExhibits = collect();
        if (isset($this->underlyingRecord) && isset($this->underlyingRecord->exhibits)) {
            $allCaseExhibits = collect($this->underlyingRecord->exhibits);
        } elseif (isset($this->underlyingRecord) && method_exists($this->underlyingRecord, 'exhibits')) {
            $allCaseExhibits = $this->underlyingRecord->exhibits()->get();
        }

        if ($allCaseExhibits->isEmpty() && !empty($this->id)) {
            $isMulti = method_exists($this, 'isMultipleSuspect') ? $this->isMultipleSuspect() : !empty($this->is_multiple);
            $fk = $isMulti ? 'multiple_suspect_case_id' : 'single_suspect_case_id';
            $allCaseExhibits = \App\Models\Exhibit::where($fk, $this->id)->get();
        }

        if ($allCaseExhibits->isEmpty()) {
            if (!empty($this->id)) {
                $allCaseExhibits = \App\Models\Exhibit::where('single_suspect_case_id', $this->id)
                    ->orWhere('multiple_suspect_case_id', $this->id)
                    ->get();
            }
            if ($allCaseExhibits->isEmpty() && !empty($this->case_number)) {
                $allCaseExhibits = \App\Models\Exhibit::whereHas('singleCase', function($q) {
                    $q->where('case_number', $this->case_number);
                })->orWhereHas('multipleCase', function($q) {
                    $q->where('case_number', $this->case_number);
                })->get();
            }
        }

        $docketData = is_array($this->docket_data)
            ? $this->docket_data
            : (json_decode($this->docket_data ?? '[]', true) ?: []);

        $docketSeizures = array_merge(
            $docketData['seizures'] ?? [],
            $docketData['associated_seizures'] ?? [],
            $docketData['associate_exhibits'] ?? []
        );

        $hasElectronicExhibits = $allCaseExhibits->contains($isElectronicItem)
            || collect($docketSeizures)->contains($isElectronicItem)
            || !empty($this->dev_model)
            || !empty($this->dev_imei1)
            || $isMatchingCategory($this->exhibit_category ?? '');

        // If no electronic exhibits exist at all:
        if (!$hasElectronicExhibits) {
            $hasAnyExhibits = $allCaseExhibits->isNotEmpty() || !empty($docketSeizures);
            if ($hasAnyExhibits) {
                return [];
            }
            return $allSuspects;
        }

        // If case has only 1 suspect, that suspect is the user of the device(s)
        if (count($allSuspects) === 1) {
            return $allSuspects;
        }

        // Multi-suspect case: match suspects to electronic exhibits
        $electronicSuspects = [];
        $allSuspectsLinked = false;

        foreach ($allSuspects as $idx => $s) {
            $sId = $s['id'] ?? null;
            $sNum = $s['suspect_number'] ?? null;
            $sName = strtolower(trim((string)($s['suspect_name'] ?? '')));
            $sAlias = strtolower(trim((string)($s['suspect_alias'] ?? '')));

            $isLinkedToDevice = false;

            // Check Eloquent exhibits
            foreach ($allCaseExhibits as $ex) {
                if (!$isElectronicItem($ex)) continue;

                if (!empty($ex->suspect_id)) {
                    if ((!empty($sId) && (string)$ex->suspect_id === (string)$sId) || (!empty($sNum) && (string)$ex->suspect_id === (string)$sNum)) {
                        $isLinkedToDevice = true;
                        break;
                    }
                }

                if (!empty($ex->suspect)) {
                    $relName = strtolower(trim((string)($ex->suspect->suspect_name ?? '')));
                    if (!empty($sName) && (strcasecmp($relName, $sName) === 0 || str_contains($relName, $sName) || str_contains($sName, $relName))) {
                        $isLinkedToDevice = true;
                        break;
                    }
                }

                $assoc = strtolower(trim((string)($ex->associated_person ?? ($ex->dev_owner ?? ''))));
                if (!empty($assoc)) {
                    if ($assoc === 'all suspects' || str_contains($assoc, 'all suspect')) {
                        $allSuspectsLinked = true;
                        $isLinkedToDevice = true;
                        break;
                    }
                    if (!empty($sNum) && (str_contains($assoc, 'suspect ' . $sNum) || str_contains($assoc, 'suspect #' . $sNum))) {
                        $isLinkedToDevice = true;
                        break;
                    }
                    if (!empty($sName) && (strcasecmp($assoc, $sName) === 0 || str_contains($assoc, $sName) || str_contains($sName, $assoc))) {
                        $isLinkedToDevice = true;
                        break;
                    }
                    if (!empty($sAlias) && $sAlias !== 'n/a' && (strcasecmp($assoc, $sAlias) === 0 || str_contains($assoc, $sAlias))) {
                        $isLinkedToDevice = true;
                        break;
                    }
                }
            }

            // Check Docket seizures
            if (!$isLinkedToDevice) {
                foreach ($docketSeizures as $sz) {
                    if (!$isElectronicItem($sz)) continue;

                    if (!empty($sz['suspect_id'])) {
                        if ((!empty($sId) && (string)$sz['suspect_id'] === (string)$sId) || (!empty($sNum) && (string)$sz['suspect_id'] === (string)$sNum)) {
                            $isLinkedToDevice = true;
                            break;
                        }
                    }

                    $assoc = strtolower(trim((string)($sz['associated_person'] ?? ($sz['suspect_ownership'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? ($sz['dev_owner'] ?? '')))))));
                    if (!empty($assoc)) {
                        if ($assoc === 'all suspects' || str_contains($assoc, 'all suspect')) {
                            $allSuspectsLinked = true;
                            $isLinkedToDevice = true;
                            break;
                        }
                        if (!empty($sNum) && (str_contains($assoc, 'suspect ' . $sNum) || str_contains($assoc, 'suspect #' . $sNum))) {
                            $isLinkedToDevice = true;
                            break;
                        }
                        if (!empty($sName) && (strcasecmp($assoc, $sName) === 0 || str_contains($assoc, $sName) || str_contains($sName, $assoc))) {
                            $isLinkedToDevice = true;
                            break;
                        }
                        if (!empty($sAlias) && $sAlias !== 'n/a' && (strcasecmp($assoc, $sAlias) === 0 || str_contains($assoc, $sAlias))) {
                            $isLinkedToDevice = true;
                            break;
                        }
                    }
                }
            }

            // Also check suspect's phones if suspect directly has phone details in CaseSuspect or docket
            if (!$isLinkedToDevice) {
                $sPhones = $s['phones'] ?? null;
                if (!empty($sPhones)) {
                    if (is_string($sPhones)) {
                        $decoded = json_decode($sPhones, true);
                        if (!empty($decoded)) $sPhones = $decoded;
                    }
                    if (is_array($sPhones) && count($sPhones) > 0) {
                        $isLinkedToDevice = true;
                    }
                }
            }

            if ($isLinkedToDevice) {
                $electronicSuspects[] = $s;
            }
        }

        if ($allSuspectsLinked) {
            return $allSuspects;
        }

        if (!empty($electronicSuspects)) {
            return $electronicSuspects;
        }

        return [$allSuspects[0]];
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
     * Check if this case is pending approval or awaiting approval from Head of Unit.
     */
    public function isPendingApproval(): bool
    {
        $status = strtolower(trim((string) ($this->status ?? '')));
        $caseStatus = strtolower(trim((string) ($this->case_status ?? '')));

        if (in_array($status, ['pending approval', 'awaiting approval', 'pending review', 'awaiting sign-off', 'pending'], true)) {
            return true;
        }

        if (in_array($caseStatus, ['pending approval', 'awaiting approval', 'pending review', 'awaiting sign-off'], true)) {
            return true;
        }

        if (isset($this->underlyingRecord) && method_exists($this->underlyingRecord, 'isPendingApproval')) {
            if ($this->underlyingRecord->isPendingApproval()) {
                return true;
            }
        }

        if (!empty($this->case_number)) {
            return \App\Models\StaffCaseSubmission::where('case_number_preview', $this->case_number)
                ->whereIn('status', ['Pending Approval', 'Awaiting Approval', 'Pending Review'])
                ->exists();
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
        $dbExhibits = collect();
        if ($this->relationLoaded('exhibits') && $this->exhibits) {
            $dbExhibits = $this->exhibits instanceof Collection ? $this->exhibits : collect($this->exhibits);
        } elseif (!empty($this->id)) {
            $isMultiCase = $this->isMultipleSuspect();
            $uId = isset($this->underlyingRecord) ? $this->underlyingRecord->id : $this->id;
            $dbExhibits = Exhibit::where(function ($q) use ($uId, $isMultiCase) {
                if ($isMultiCase) {
                    $q->where('multiple_suspect_case_id', $this->id)
                        ->orWhere('multiple_suspect_case_id', $uId);
                } else {
                    $q->where('single_suspect_case_id', $this->id)
                        ->orWhere('single_suspect_case_id', $uId);
                }
            })->get();
        } elseif (!empty($this->case_number) && empty($this->is_staff_submission)) {
            try {
                $dbExhibits = Exhibit::whereHas('singleSuspectCase', fn($q) => $q->where('case_number', $this->case_number))
                    ->orWhereHas('multipleSuspectCase', fn($q) => $q->where('case_number', $this->case_number))
                    ->get();
            } catch (\Throwable $e) {
                $dbExhibits = collect();
            }
        }

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

                        // Fallback to docket data seizures or case attributes if missing or empty on exhibit model
                        $hasMissingDev = empty(trim((string)$arr['dev_model']))
                            || empty(trim((string)$arr['dev_serial_no']))
                            || empty(trim((string)$arr['dev_imei1']))
                            || empty(trim((string)$arr['dev_imei2']))
                            || empty(trim((string)$arr['dev_storage']))
                            || empty(trim((string)$arr['dev_colour']))
                            || empty(trim((string)$arr['dev_marks']))
                            || empty(trim((string)$arr['dev_mac_address']));

                        if ($hasMissingDev) {
                            foreach ($allDocketExhibits as $dSz) {
                                if (!is_array($dSz)) continue;
                                $dExNo = $dSz['exhibit_number'] ?? null;
                                $dName = $dSz['seizure_name'] ?? ($dSz['commodity_name'] ?? ($dSz['name'] ?? ''));
                                $matchDocket = (!empty($exNo) && !empty($dExNo) && (string)$exNo === (string)$dExNo)
                                    || (!empty($dName) && !empty($ex->commodity_name) && strtolower(trim($dName)) === strtolower(trim($ex->commodity_name)));
                                if ($matchDocket) {
                                    if (empty(trim((string)($arr['dev_model'] ?? '')))) {
                                        $arr['dev_model'] = $dSz['dev_model'] ?? ($dSz['dev']['model'] ?? ($dSz['device_model'] ?? ($dSz['model'] ?? null)));
                                    }
                                    if (empty(trim((string)($arr['dev_serial_no'] ?? '')))) {
                                        $arr['dev_serial_no'] = $dSz['dev_serial_no'] ?? ($dSz['dev']['serial_no'] ?? ($dSz['device_serial'] ?? ($dSz['serial_no'] ?? ($dSz['serial_number'] ?? null))));
                                    }
                                    if (empty(trim((string)($arr['dev_imei1'] ?? '')))) {
                                        $arr['dev_imei1'] = $dSz['dev_imei1'] ?? ($dSz['dev']['imei1'] ?? ($dSz['dev_imei_1'] ?? ($dSz['imei1'] ?? ($dSz['imei_1'] ?? null))));
                                    }
                                    if (empty(trim((string)($arr['dev_imei2'] ?? '')))) {
                                        $arr['dev_imei2'] = $dSz['dev_imei2'] ?? ($dSz['dev']['imei2'] ?? ($dSz['dev_imei_2'] ?? ($dSz['imei2'] ?? ($dSz['imei_2'] ?? null))));
                                    }
                                    if (empty(trim((string)($arr['dev_mac_address'] ?? '')))) {
                                        $arr['dev_mac_address'] = $dSz['dev_mac_address'] ?? ($dSz['dev']['mac_address'] ?? ($dSz['mac_address'] ?? null));
                                    }
                                    if (empty(trim((string)($arr['dev_storage'] ?? '')))) {
                                        $arr['dev_storage'] = $dSz['dev_storage'] ?? ($dSz['dev']['storage'] ?? ($dSz['storage_capacity'] ?? ($dSz['dev_storage_capacity'] ?? ($dSz['storage'] ?? null))));
                                    }
                                    if (empty(trim((string)($arr['dev_colour'] ?? '')))) {
                                        $arr['dev_colour'] = $dSz['dev_colour'] ?? ($dSz['dev']['colour'] ?? ($dSz['dev_color'] ?? ($dSz['colour'] ?? ($dSz['color'] ?? ($dSz['device_color'] ?? null)))));
                                    }
                                    if (empty(trim((string)($arr['dev_marks'] ?? '')))) {
                                        $arr['dev_marks'] = $dSz['dev_marks'] ?? ($dSz['dev']['marks'] ?? ($dSz['dev_other_marks'] ?? ($dSz['marks'] ?? ($dSz['distinguishing_marks'] ?? ($dSz['other_marks'] ?? null)))));
                                    }
                                    break;
                                }
                            }
                            if (empty(trim((string)($arr['dev_model'] ?? '')))) {
                                $arr['dev_model'] = $this->dev_model ?? ($this->underlyingRecord->dev_model ?? null);
                            }
                            if (empty(trim((string)($arr['dev_serial_no'] ?? '')))) {
                                $arr['dev_serial_no'] = $this->dev_serial_no ?? ($this->underlyingRecord->dev_serial_no ?? null);
                            }
                            if (empty(trim((string)($arr['dev_imei1'] ?? '')))) {
                                $arr['dev_imei1'] = $this->dev_imei1 ?? ($this->underlyingRecord->dev_imei1 ?? null);
                            }
                            if (empty(trim((string)($arr['dev_imei2'] ?? '')))) {
                                $arr['dev_imei2'] = $this->dev_imei2 ?? ($this->underlyingRecord->dev_imei2 ?? null);
                            }
                            if (empty(trim((string)($arr['dev_mac_address'] ?? '')))) {
                                $arr['dev_mac_address'] = $this->dev_mac_address ?? ($this->underlyingRecord->dev_mac_address ?? null);
                            }
                            if (empty(trim((string)($arr['dev_storage'] ?? '')))) {
                                $arr['dev_storage'] = $this->dev_storage ?? ($this->underlyingRecord->dev_storage ?? null);
                            }
                            if (empty(trim((string)($arr['dev_colour'] ?? '')))) {
                                $arr['dev_colour'] = $this->dev_colour ?? ($this->underlyingRecord->dev_colour ?? null);
                            }
                            if (empty(trim((string)($arr['dev_marks'] ?? '')))) {
                                $arr['dev_marks'] = $this->dev_marks ?? ($this->underlyingRecord->dev_marks ?? null);
                            }
                        }

                        $arr['dev'] = [
                            'model'       => $arr['dev_model'],
                            'serial_no'   => $arr['dev_serial_no'],
                            'imei1'       => $arr['dev_imei1'],
                            'imei2'       => $arr['dev_imei2'],
                            'mac_address' => $arr['dev_mac_address'],
                            'storage'     => $arr['dev_storage'],
                            'colour'      => $arr['dev_colour'],
                            'marks'       => $arr['dev_marks'],
                        ];
                        $arr['device_model']  = $arr['dev_model'];
                        $arr['device_serial'] = $arr['dev_serial_no'];

                        // Ensure custody & investigating officer attributes are mapped from DB model
                        $arr['investigating_officer']  = $ex->investigating_officer;
                        $arr['source_requesting_unit'] = $ex->source_requesting_unit;
                        $arr['seizure_reason']         = $ex->seizure_reason;
                        $arr['source_of_exhibits']     = $ex->source_of_exhibits ?? $ex->source_of_exhibit;
                        $arr['exhibit_remark']         = $ex->exhibit_remark ?? $ex->lab_remark;
                        $arr['seizure_location']       = $ex->seizure_location;
                        $arr['seizure_officer']        = $ex->seizure_officer;
                        $arr['seizure_packaging']      = $ex->seizure_packaging;
                        $arr['seizure_concealment']    = $ex->seizure_concealment;
                        $arr['seizure_date']           = $ex->seizure_date;
                        $arr['seizure_time']           = $ex->seizure_time;

                        $matchedExhibits[] = $arr;
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
                    $dArr['dev_model']       = $dArr['dev_model'] ?? ($sz['dev']['model'] ?? ($sz['device_model'] ?? ($sz['model'] ?? ($this->dev_model ?? null))));
                    $dArr['dev_serial_no']   = $dArr['dev_serial_no'] ?? ($sz['dev']['serial_no'] ?? ($sz['device_serial'] ?? ($sz['serial_no'] ?? ($sz['serial_number'] ?? ($this->dev_serial_no ?? null)))));
                    $dArr['dev_imei1']       = $dArr['dev_imei1'] ?? ($sz['dev']['imei1'] ?? ($sz['dev_imei_1'] ?? ($sz['imei1'] ?? ($sz['imei_1'] ?? ($this->dev_imei1 ?? null)))));
                    $dArr['dev_imei2']       = $dArr['dev_imei2'] ?? ($sz['dev']['imei2'] ?? ($sz['dev_imei_2'] ?? ($sz['imei2'] ?? ($sz['imei_2'] ?? ($this->dev_imei2 ?? null)))));
                    $dArr['dev_mac_address'] = $dArr['dev_mac_address'] ?? ($sz['dev']['mac_address'] ?? ($sz['mac_address'] ?? ($this->dev_mac_address ?? null)));
                    $dArr['dev_storage']     = $dArr['dev_storage'] ?? ($sz['dev']['storage'] ?? ($sz['storage_capacity'] ?? ($sz['dev_storage_capacity'] ?? ($sz['storage'] ?? ($this->dev_storage ?? null)))));
                    $dArr['dev_colour']      = $dArr['dev_colour'] ?? ($sz['dev']['colour'] ?? ($sz['dev_color'] ?? ($sz['colour'] ?? ($sz['color'] ?? ($sz['device_color'] ?? ($this->dev_colour ?? null))))));
                    $dArr['dev_marks']       = $dArr['dev_marks'] ?? ($sz['dev']['marks'] ?? ($sz['dev_other_marks'] ?? ($sz['marks'] ?? ($sz['distinguishing_marks'] ?? ($sz['other_marks'] ?? ($this->dev_marks ?? null))))));
                    $dArr['dev'] = [
                        'model'       => $dArr['dev_model'],
                        'serial_no'   => $dArr['dev_serial_no'],
                        'imei1'       => $dArr['dev_imei1'],
                        'imei2'       => $dArr['dev_imei2'],
                        'mac_address' => $dArr['dev_mac_address'],
                        'storage'     => $dArr['dev_storage'],
                        'colour'      => $dArr['dev_colour'],
                        'marks'       => $dArr['dev_marks'],
                    ];
                    $dArr['device_model']  = $dArr['dev_model'];
                    $dArr['device_serial'] = $dArr['dev_serial_no'];

                    if (empty($dArr['investigating_officer'])) {
                        $dbMatch = ($dbExhibits instanceof Collection && $dbExhibits->isNotEmpty()) ? $dbExhibits->first(function ($dbE) use ($sz, $exNo, $name) {
                            if (!empty($sz['id']) && (int)$dbE->id === (int)$sz['id']) return true;
                            if (!empty($exNo) && (string)$dbE->exhibit_number === (string)$exNo) return true;
                            if (!empty($name) && strtolower(trim((string)$dbE->commodity_name)) === strtolower(trim((string)$name))) return true;
                            return false;
                        }) : null;
                        if ($dbMatch && !empty($dbMatch->investigating_officer)) {
                            $dArr['investigating_officer'] = $dbMatch->investigating_officer;
                        } elseif (!empty($sz['id'])) {
                            try { $dArr['investigating_officer'] = Exhibit::where('id', $sz['id'])->value('investigating_officer'); } catch (\Throwable $e) {}
                        } elseif (!empty($exNo)) {
                            try { $dArr['investigating_officer'] = Exhibit::where('exhibit_number', $exNo)->value('investigating_officer'); } catch (\Throwable $e) {}
                        } elseif (!empty($this->investigating_officer)) {
                            $dArr['investigating_officer'] = $this->investigating_officer;
                        }
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
        $cleanRole = strtolower(trim($role ?? ''));
        $isAssociate = (!empty($cleanRole) && (str_contains($cleanRole, 'associate') || str_contains($cleanRole, 'co-suspect') || str_contains($cleanRole, 'co suspect')))
            || (!empty($associateId))
            || ($suspectIndex > 0);
        $docketData = is_array($this->docket_data) ? $this->docket_data : (json_decode($this->docket_data ?? '[]', true) ?: []);
        $isMultiCase = $this->isMultipleSuspect();
        $sDossierLabExam = null;
        $sDossierHasSavedLab = false;

        $foundInAssociateLab = false;
        $foundInLabExamResult = false;
        $foundInLabExaminationsList = false;
        $foundInAssociateLabExaminations = false;
        $foundInSeizures = false;

        // Candidate 1: App\Models\AssociateLabExamination
        if (!empty($this->id)) {
            $dbAssocList = $this->associateLabExaminations ?? AssociateLabExamination::where('case_id', $this->id)->get();
            $dbAssoc = $dbAssocList->first(function ($a) use ($cleanName) {
                if (empty($a->has_saved)) return false;
                if (empty($a->analyst) && empty($a->exam_reference) && empty($a->substance_identification) && empty($a->date_completed)) return false;
                $aName = strtolower(trim($a->suspect_name ?? ''));
                if (empty($aName)) return false;
                return $aName === $cleanName;
            });

            if ($dbAssoc && $dbAssoc->has_saved) {
                $foundInAssociateLab = true;
                $sDossierHasSavedLab = true;
                $sDossierLabExam = [
                    'exhibit_owner_name' => $dbAssoc->suspect_name,
                    'exam_reference' => $dbAssoc->exam_reference,
                    'exhibit_number' => $dbAssoc->exhibit_number,
                    'laboratory_number' => $dbAssoc->laboratory_number,
                    'analyst' => $dbAssoc->analyst,
                    'date_received' => (is_object($dbAssoc->date_received) ? $dbAssoc->date_received->format('Y-m-d') : (substr((string)$dbAssoc->date_received, 0, 10) ?: null))
                        ?: ($docketData['associate_lab_examinations'][$cleanName]['date_received'] ?? ($docketData['associate_lab_examinations'][$cleanName]['lab_date_received'] ?? ($dbAssoc->created_at ? $dbAssoc->created_at->format('Y-m-d') : null))),
                    'date_started' => (is_object($dbAssoc->date_started) ? $dbAssoc->date_started->format('Y-m-d') : (substr((string)$dbAssoc->date_started, 0, 10) ?: null))
                        ?: ($docketData['associate_lab_examinations'][$cleanName]['date_started'] ?? ($docketData['associate_lab_examinations'][$cleanName]['lab_date_started'] ?? null)),
                    'date_completed' => (is_object($dbAssoc->date_completed) ? $dbAssoc->date_completed->format('Y-m-d') : (substr((string)$dbAssoc->date_completed, 0, 10) ?: null))
                        ?: ($docketData['associate_lab_examinations'][$cleanName]['date_completed'] ?? ($docketData['associate_lab_examinations'][$cleanName]['lab_date_completed'] ?? null)),
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
                    'detected_subtances_percentage' => $dbAssoc->detected_subtances_percentage ?? ($dbAssoc->detected_substances_percentage ?? null),
                    'detected_substances_percentage' => $dbAssoc->detected_substances_percentage ?? ($dbAssoc->detected_subtances_percentage ?? null),
                    'test_methods' => $dbAssoc->test_methods,
                    'specifications' => $dbAssoc->specifications,
                    'remark' => $dbAssoc->remark,
                    'findings' => $dbAssoc->findings,
                    'purpose_of_examination' => $dbAssoc->purpose_of_examination ?? null,
                    'preliminary_exam_procedure' => $dbAssoc->preliminary_exam_procedure ?? null,
                    'screening_method' => $dbAssoc->screening_method ?? null,
                    'disposition_of_exhibit' => $dbAssoc->disposition_of_exhibit ?? null,
                    'has_saved' => true,
                ];
            }
        }

        // Candidate 2: App\Models\LaboratoryExaminationResult
        if (!$sDossierHasSavedLab && !empty($this->id)) {
            $records = LaboratoryExaminationResult::where(function ($q) use ($isMultiCase) {
                if ($isMultiCase) {
                    $q->where('multiple_suspect_case_id', $this->id);
                } else {
                    $q->where('single_suspect_case_id', $this->id);
                }
            })->where('has_saved', true)->latest('id')->get();

            // Filter out placeholder registration records that lack genuine lab analysis
            $records = $records->filter(function ($r) {
                if (empty($r->has_saved)) return false;
                return !empty($r->analyst) || !empty($r->exam_reference) || !empty($r->substance_identification) || !empty($r->date_completed);
            });

            $dbPrimary = null;
            if (!empty($suspectDbId) && is_numeric($suspectDbId)) {
                $dbPrimary = $records->first(fn($r) => (int)$r->suspect_id === (int)$suspectDbId);
            }
            if (!$dbPrimary && !empty($cleanName)) {
                $dbPrimary = $records->first(function ($r) use ($cleanName) {
                    $rName = strtolower(trim($r->suspect_name ?? ''));
                    return !empty($rName) && $rName === $cleanName;
                });
            }
            if (!$dbPrimary && !$isAssociate && $suspectIndex === 0) {
                $dbPrimary = $records->first(function ($r) use ($cleanName) {
                    $rName = strtolower(trim($r->suspect_name ?? ''));
                    return empty($rName) || $rName === 'primary suspect' || $rName === $cleanName;
                });
            }

            if ($dbPrimary && $dbPrimary->has_saved) {
                $foundInLabExamResult = true;
                $sDossierHasSavedLab = true;
                $sDossierLabExam = [
                    'exhibit_owner_name' => $dbPrimary->suspect_name,
                    'exam_reference' => $dbPrimary->exam_reference,
                    'exhibit_number' => $dbPrimary->exhibit_number,
                    'laboratory_number' => $dbPrimary->laboratory_number,
                    'analyst' => $dbPrimary->analyst,
                    'date_received' => (is_object($dbPrimary->date_received) ? $dbPrimary->date_received->format('Y-m-d') : (substr((string)$dbPrimary->date_received, 0, 10) ?: null))
                        ?: ($docketData['lab_examination']['date_received'] ?? ($docketData['lab_examination']['lab_date_received'] ?? ($docketData['seizures'][$suspectIndex]['date_received'] ?? ($dbPrimary->created_at ? $dbPrimary->created_at->format('Y-m-d') : null)))),
                    'date_started' => (is_object($dbPrimary->date_started) ? $dbPrimary->date_started->format('Y-m-d') : (substr((string)$dbPrimary->date_started, 0, 10) ?: null))
                        ?: ($docketData['lab_examination']['date_started'] ?? ($docketData['lab_examination']['lab_date_started'] ?? ($docketData['seizures'][$suspectIndex]['date_started'] ?? null))),
                    'date_completed' => (is_object($dbPrimary->date_completed) ? $dbPrimary->date_completed->format('Y-m-d') : (substr((string)$dbPrimary->date_completed, 0, 10) ?: null))
                        ?: ($docketData['lab_examination']['date_completed'] ?? ($docketData['lab_examination']['lab_date_completed'] ?? ($docketData['seizures'][$suspectIndex]['date_completed'] ?? null))),
                    'presumptive_result' => $dbPrimary->presumptive_result,
                    'presumptive_presence' => $dbPrimary->presumptive_presence,
                    'presumptive_percentage' => $dbPrimary->presumptive_percentage,
                    'confirmatory_result' => $dbPrimary->confirmatory_result,
                    'sample_weight' => [
                        'description' => $dbPrimary->sample_weight_description,
                        'units_packaging' => $dbPrimary->sample_weight_units,
                        'gross_weight' => $dbPrimary->sample_weight_gross,
                        'sample_taken' => $dbPrimary->sample_weight_taken,
                        'reserved_samples' => $dbPrimary->sample_weight_reserved,
                    ],
                    'substance_identification' => $dbPrimary->substance_identification
                        ?: ($docketData['lab_examination']['substance_identification']
                        ?? ($dbPrimary->sample_weight_description
                        ?? ($docketData['seizures'][$suspectIndex]['seizure_name_drug'] ?? null))),
                    'detected_substances' => is_string($dbPrimary->detected_substances) ? (json_decode($dbPrimary->detected_substances, true) ?: []) : ($dbPrimary->detected_substances ?? []),
                    'detected_subtances_percentage' => $dbPrimary->detected_subtances_percentage ?? ($dbPrimary->detected_substances_percentage ?? null),
                    'detected_substances_percentage' => $dbPrimary->detected_substances_percentage ?? ($dbPrimary->detected_subtances_percentage ?? null),
                    'test_methods' => $dbPrimary->test_methods,
                    'specifications' => $dbPrimary->specifications,
                    'remark' => $dbPrimary->remark,
                    'findings' => $dbPrimary->findings,
                    'purpose_of_examination' => $dbPrimary->purpose_of_examination ?? null,
                    'preliminary_exam_procedure' => $dbPrimary->preliminary_exam_procedure ?? null,
                    'screening_method' => $dbPrimary->screening_method ?? null,
                    'disposition_of_exhibit' => $dbPrimary->disposition_of_exhibit ?? null,
                    'has_saved' => true,
                ];
            }
        }

        // Candidate 3: $docketData['lab_examinations_list']
        if (!$sDossierHasSavedLab && !empty($docketData['lab_examinations_list']) && is_array($docketData['lab_examinations_list'])) {
            foreach ($docketData['lab_examinations_list'] as $lItem) {
                if (!empty($lItem['has_saved'])) {
                    $lOwner = strtolower(trim($lItem['exhibit_owner_name'] ?? ($lItem['suspect_name'] ?? '')));
                    if ($lOwner === $cleanName) {
                        $foundInLabExaminationsList = true;
                        $sDossierHasSavedLab = true;
                        $sDossierLabExam = $lItem;
                        $sDossierLabExam['has_saved'] = true;
                        break;
                    } elseif (!$isAssociate && $suspectIndex === 0 && ($lOwner === 'primary suspect' || empty($lOwner))) {
                        $foundInLabExaminationsList = true;
                        $sDossierHasSavedLab = true;
                        $sDossierLabExam = $lItem;
                        $sDossierLabExam['has_saved'] = true;
                        break;
                    }
                }
            }
        }

        // Candidate 4: $docketData['associate_lab_examinations']
        if (!$sDossierHasSavedLab && !empty($docketData['associate_lab_examinations']) && is_array($docketData['associate_lab_examinations'])) {
            foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
                if (!empty($vAssoc['has_saved'])) {
                    $kLow = strtolower(trim($kAssoc));
                    if ($kLow === $cleanName) {
                        $foundInAssociateLabExaminations = true;
                        $sDossierHasSavedLab = true;
                        $sDossierLabExam = $vAssoc;
                        $sDossierLabExam['has_saved'] = true;
                        break;
                    }
                }
            }
        }

        // Candidate 5: $docketData['seizures'] where has_saved_lab === true
        if (!$sDossierHasSavedLab && !empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $szItem) {
                if (!empty($szItem['has_saved_lab'])) {
                    $szOwner = strtolower(trim($szItem['associated_person'] ?? ($szItem['suspect_name'] ?? ($szItem['owner_name'] ?? ''))));
                    $isOwnerMatch = !empty($cleanName) && ($szOwner === $cleanName);
                    $isPrimaryMatch = (!$isAssociate && $suspectIndex === 0 && (empty($szOwner) || $szOwner === 'primary suspect' || $szOwner === 'primary'));
                    if ($isOwnerMatch || $isPrimaryMatch) {
                        $foundInSeizures = true;
                        $sDossierHasSavedLab = true;
                        $sDossierLabExam = [
                            'exhibit_owner_name' => $szItem['associated_person'] ?? ($szItem['suspect_name'] ?? ($szItem['owner_name'] ?? $suspectName)),
                            'laboratory_number' => $szItem['laboratory_number'] ?? ($this->laboratory_number ?? ($docketData['lab_examination']['laboratory_number'] ?? ($docketData['laboratory_number'] ?? null))),
                            'exam_reference' => $szItem['exam_reference'] ?? ($this->lab_exam_reference ?: ($this->exam_reference ?: ($docketData['lab_examination']['exam_reference'] ?? null))),
                            'exhibit_number' => $szItem['exhibit_number'] ?? ($szItem['lab_exhibit_no'] ?? ($szItem['lab_exhibit_number'] ?? ($szItem['exhibit_no'] ?? ($docketData['lab_examination']['exhibit_number'] ?? ($docketData['lab_examination']['lab_exhibit_no'] ?? null))))),
                            'lab_exhibit_no' => $szItem['lab_exhibit_no'] ?? ($szItem['lab_exhibit_number'] ?? ($docketData['lab_examination']['lab_exhibit_no'] ?? null)),
                            'analyst' => $szItem['analyst'] ?? ($docketData['lab_examination']['analyst'] ?? ($this->submitting_officer ?? null)),
                            'date_received' => $szItem['date_received'] ?? null,
                            'date_started' => $szItem['date_started'] ?? null,
                            'date_completed' => $szItem['date_completed'] ?? null,
                            'presumptive_result' => $szItem['presumptive_result'] ?? null,
                            'confirmatory_result' => $szItem['confirmatory_result'] ?? null,
                            'sample_weight' => [
                                'description' => $szItem['sample_description'] ?? null,
                                'units_packaging' => $szItem['sample_units'] ?? null,
                                'gross_weight' => $szItem['sample_gross'] ?? null,
                                'sample_taken' => $szItem['sample_taken'] ?? null,
                                'reserved_samples' => $szItem['sample_reserved'] ?? null,
                            ],
                            'substance_identification' => $szItem['substance_identification'] ?? null,
                            'detected_substances' => $szItem['detected_substances'] ?? [],
                            'test_methods' => $szItem['test_methods'] ?? null,
                            'specifications' => $szItem['specifications'] ?? null,
                            'remark' => $szItem['remark'] ?? null,
                            'findings' => $szItem['findings'] ?? null,
                            'purpose_of_examination' => $szItem['purpose_of_examination'] ?? null,
                            'preliminary_exam_procedure' => $szItem['preliminary_exam_procedure'] ?? null,
                            'screening_method' => $szItem['screening_method'] ?? null,
                            'disposition_of_exhibit' => $szItem['disposition_of_exhibit'] ?? null,
                            'has_saved' => true,
                        ];
                        break;
                    }
                }
            }
        }

        // Primary fallback: $docketData['lab_examination'] when has_saved === true
        if (!$sDossierHasSavedLab && !$isAssociate && $suspectIndex === 0 && !empty($docketData['lab_examination']['has_saved'])) {
            $labOwner = strtolower(trim($docketData['lab_examination']['exhibit_owner_name'] ?? ''));
            if (empty($labOwner) || $labOwner === 'primary suspect' || $labOwner === $cleanName) {
                $sDossierHasSavedLab = true;
                $sDossierLabExam = $docketData['lab_examination'];
                $sDossierLabExam['has_saved'] = true;
            }
        }

        // If not saved in any of the authoritative sources, return null
        if (!$sDossierHasSavedLab || empty($sDossierLabExam)) {
            $result = null;
        } else {
            // Normalize structure for valid saved record
            if (empty($sDossierLabExam['exhibit_number']) || $sDossierLabExam['exhibit_number'] === 'N/A') {
                $sDossierLabExam['exhibit_number'] = $sDossierLabExam['lab_exhibit_no']
                    ?? ($sDossierLabExam['lab_exhibit_number']
                    ?? ($sDossierLabExam['exhibit_no']
                    ?? ($docketData['lab_examination']['exhibit_number']
                    ?? ($docketData['lab_examination']['lab_exhibit_no']
                    ?? ($docketData['lab_examination']['lab_exhibit_number'] ?? 'N/A')))));
            }
            if (empty($sDossierLabExam['lab_exhibit_no']) || $sDossierLabExam['lab_exhibit_no'] === 'N/A') {
                $sDossierLabExam['lab_exhibit_no'] = $sDossierLabExam['exhibit_number']
                    ?? ($docketData['lab_examination']['lab_exhibit_no'] ?? null);
            }
            if (empty($sDossierLabExam['exam_reference']) || $sDossierLabExam['exam_reference'] === 'N/A') {
                $sDossierLabExam['exam_reference'] = $this->lab_exam_reference ?: ($this->exam_reference ?: ($docketData['lab_examination']['exam_reference'] ?? 'N/A'));
            }
            if (empty($sDossierLabExam['laboratory_number']) || $sDossierLabExam['laboratory_number'] === 'N/A') {
                $sDossierLabExam['laboratory_number'] = $this->laboratory_number
                    ?? ($docketData['lab_examination']['laboratory_number']
                    ?? ($docketData['laboratory_number'] ?? 'N/A'));
            }
            if (empty($sDossierLabExam['analyst']) || $sDossierLabExam['analyst'] === 'N/A') {
                $sDossierLabExam['analyst'] = $docketData['lab_examination']['analyst']
                    ?? ($this->submitting_officer ?? 'N/A');
            }

            $caseIncDate = !empty($this->incident_date)
                ? (is_object($this->incident_date) ? $this->incident_date->format('Y-m-d') : substr((string)$this->incident_date, 0, 10))
                : null;
            $caseDate = $caseIncDate ?: ($this->created_at ? (is_object($this->created_at) ? $this->created_at->format('Y-m-d') : substr((string)$this->created_at, 0, 10)) : date('Y-m-d'));
            $caseUpDate = $this->updated_at ? (is_object($this->updated_at) ? $this->updated_at->format('Y-m-d') : substr((string)$this->updated_at, 0, 10)) : date('Y-m-d');

            $recRaw = $sDossierLabExam['date_received'] ?? null;
            if (empty($recRaw) || $recRaw === 'N/A') {
                $recRaw = $sDossierLabExam['lab_date_received']
                    ?? ($docketData['lab_examination']['date_received']
                    ?? ($docketData['lab_examination']['lab_date_received']
                    ?? $caseDate));
            }
            if (empty($recRaw) || $recRaw === 'N/A') {
                $recRaw = $caseDate;
            }
            $sDossierLabExam['date_received'] = is_object($recRaw) ? $recRaw->format('Y-m-d') : substr((string)$recRaw, 0, 10);

            $startRaw = $sDossierLabExam['date_started'] ?? null;
            if (empty($startRaw) || $startRaw === 'N/A') {
                $startRaw = $sDossierLabExam['lab_date_started']
                    ?? ($docketData['lab_examination']['date_started']
                    ?? ($docketData['lab_examination']['lab_date_started']
                    ?? $sDossierLabExam['date_received']));
            }
            if (empty($startRaw) || $startRaw === 'N/A') {
                $startRaw = $sDossierLabExam['date_received'];
            }
            $sDossierLabExam['date_started'] = is_object($startRaw) ? $startRaw->format('Y-m-d') : substr((string)$startRaw, 0, 10);

            $compRaw = $sDossierLabExam['date_completed'] ?? null;
            if (empty($compRaw) || $compRaw === 'N/A') {
                $compRaw = $sDossierLabExam['lab_date_completed']
                    ?? ($docketData['lab_examination']['date_completed']
                    ?? ($docketData['lab_examination']['lab_date_completed']
                    ?? ($caseUpDate ?: $sDossierLabExam['date_started'])));
            }
            if (empty($compRaw) || $compRaw === 'N/A') {
                $compRaw = $caseUpDate ?: $sDossierLabExam['date_started'];
            }
            $sDossierLabExam['date_completed'] = is_object($compRaw) ? $compRaw->format('Y-m-d') : substr((string)$compRaw, 0, 10);

            // Ensure sample_weight is structured
            $sw = $sDossierLabExam['sample_weight'] ?? [];
            if (is_string($sw)) $sw = json_decode($sw, true) ?: [];
            $sDossierLabExam['sample_weight'] = [
                'description' => $sw['description'] ?? ($sDossierLabExam['sample_weight_description'] ?? ($sDossierLabExam['exhibit_description'] ?? 'N/A')),
                'units_packaging' => $sw['units_packaging'] ?? ($sDossierLabExam['sample_weight_units'] ?? 'N/A'),
                'gross_weight' => $sw['gross_weight'] ?? ($sDossierLabExam['sample_weight_gross'] ?? 'N/A'),
                'sample_taken' => $sw['sample_taken'] ?? ($sDossierLabExam['sample_weight_taken'] ?? '—'),
                'reserved_samples' => $sw['reserved_samples'] ?? ($sDossierLabExam['sample_weight_reserved'] ?? '—'),
            ];

            // Ensure detected_substances is array
            if (is_string($sDossierLabExam['detected_substances'] ?? null)) {
                $sDossierLabExam['detected_substances'] = json_decode($sDossierLabExam['detected_substances'], true) ?: [];
            }
            if (!is_array($sDossierLabExam['detected_substances'] ?? null)) {
                $sDossierLabExam['detected_substances'] = [];
            }

            // Confirmatory normalized value - no hardcoded 'YES'
            $rawConf = $sDossierLabExam['confirmatory_result'] ?? ($sDossierLabExam['confirmatory'] ?? null);
            $sDossierLabExam['confirmatory'] = !empty($rawConf) ? (strtoupper(trim((string)$rawConf)) !== 'NO' ? 'YES' : 'NO') : 'N/A';

            // Presumptive reaction table rows parsing
            $parsedPres = self::parsePresumptiveData(
                $sDossierLabExam['presumptive_result'] ?? null,
                $sDossierLabExam['presumptive_presence'] ?? null,
                $sDossierLabExam['presumptive_percentage'] ?? null
            );
            $sDossierLabExam['presumptive_rows'] = (!empty($parsedPres['result']) || $parsedPres['presence'] !== null || $parsedPres['percentage'] !== null)
                ? $parsedPres['rows']
                : [];
            $sDossierLabExam['has_saved'] = true;
            $result = $sDossierLabExam;
        }

        if (config('app.debug')) {
            \Log::debug('[NACOC resolveLabExamForSuspect]', [
                'case_id' => $this->id ?? null,
                'suspect_name' => $suspectName ?? null,
                'suspect_id' => $suspectDbId ?? null,
                'suspect_role' => $role ?? null,
                'index' => $suspectIndex ?? null,
                'candidates_checked' => [
                    'AssociateLabExamination' => $foundInAssociateLab ?? false,
                    'LaboratoryExaminationResult' => $foundInLabExamResult ?? false,
                    'lab_examinations_list' => $foundInLabExaminationsList ?? false,
                    'associate_lab_examinations' => $foundInAssociateLabExaminations ?? false,
                    'seizures.has_saved_lab' => $foundInSeizures ?? false,
                ],
                'returned_has_saved' => $result['has_saved'] ?? null,
                'returned' => $result ?? null,
            ]);
        }

        return $result;
    }

    /**
     * Resolve all lab examination records for a suspect / case.
     * When multiple drug exhibits are examined, this returns an array of all lab exam records.
     *
     * @return array<int, array>
     */
    /**
     * Parse presumptive reaction result, detected substance(s), and percentage(s) from either
     * structured columns or legacy formatted strings.
     *
     * @return array{result: ?string, presence: ?string, percentage: ?string, rows: array<int, array<string, string>>}
     */
    public static function parsePresumptiveData(?string $pRaw, mixed $explicitPresence = null, mixed $explicitPercentage = null): array
    {
        $formatPct = function (?string $val): ?string {
            $v = trim((string) ($val ?? ''));
            if ($v === '' || strtoupper($v) === 'N/A' || $v === '—') {
                return null;
            }
            if (is_numeric(str_replace('%', '', $v)) && !str_ends_with($v, '%')) {
                return trim($v) . '%';
            }
            return $v;
        };

        $splitList = function (mixed $val): array {
            if ($val === null || $val === '') {
                return [];
            }
            if (is_string($val)) {
                $t = trim($val);
                if ($t === '' || strtoupper($t) === 'N/A' || $t === '—') {
                    return [];
                }
                if (str_starts_with($t, '[')) {
                    $decoded = json_decode($t, true);
                    if (is_array($decoded)) {
                        $val = $decoded;
                    }
                }
            }
            if (is_array($val)) {
                return array_values(array_filter(array_map(fn ($v) => trim((string) $v), $val), fn ($v) => $v !== '' && strtoupper($v) !== 'N/A'));
            }
            return array_values(array_filter(array_map('trim', preg_split('/\s*;\s*/', (string) $val) ?: []), fn ($v) => $v !== '' && strtoupper($v) !== 'N/A'));
        };

        $rawStr = trim((string) ($pRaw ?? ''));
        $overallResult = null;
        if ($rawStr !== '' && strtoupper($rawStr) !== 'N/A') {
            if (preg_match('/^(YES|POS|POSITIVE)\b/i', $rawStr)) {
                $overallResult = 'YES';
            } elseif (preg_match('/^(NO|NEG|NEGATIVE)\b/i', $rawStr)) {
                $overallResult = 'NO';
            }
        }

        $presList = $splitList($explicitPresence);
        $pctList = array_values(array_filter(array_map($formatPct, $splitList($explicitPercentage))));

        if (!empty($presList) || !empty($pctList)) {
            if ($overallResult === null) {
                $overallResult = 'YES';
            }
            $rows = [];
            $max = max(count($presList), count($pctList));
            for ($i = 0; $i < $max; $i++) {
                $rows[] = [
                    'result'   => $overallResult,
                    'presence' => $presList[$i] ?? ($presList[0] ?? '—'),
                    'purity'   => $pctList[$i] ?? ($pctList[0] ?? '—'),
                ];
            }
            return [
                'result'     => $overallResult,
                'presence'   => !empty($presList) ? implode('; ', $presList) : null,
                'percentage' => !empty($pctList) ? implode('; ', $pctList) : null,
                'rows'       => $rows,
            ];
        }

        if ($rawStr === '' || strtoupper($rawStr) === 'N/A') {
            return [
                'result'     => null,
                'presence'   => null,
                'percentage' => null,
                'rows'       => [['result' => 'N/A', 'presence' => 'N/A', 'purity' => 'N/A']],
            ];
        }

        if ($overallResult === 'NO') {
            return [
                'result'     => 'NO',
                'presence'   => null,
                'percentage' => null,
                'rows'       => [['result' => 'NO', 'presence' => '—', 'purity' => '—']],
            ];
        }

        $cleanP = $rawStr;
        if (preg_match('/^[A-Z]+\s*\((.*)\)$/is', $rawStr, $mP)) {
            $cleanP = $mP[1];
        } else {
            $cleanP = preg_replace('/^(?:YES|NO|POS|NEG|POSITIVE|NEGATIVE)\s*[:|\-]\s*/i', '', $cleanP);
        }

        $cleanP = preg_replace('/\s*\|\s*(?=Purity)/i', ' - ', $cleanP);

        $segments = preg_split('/\s*[;\|]\s*(?=(?:\[\d+\]|Test\s*#\d+\s*:|Presence\s+of\b))/i', trim($cleanP));
        if (empty($segments)) {
            $segments = [trim($cleanP)];
        }

        $rows = [];
        $parsedPresences = [];
        $parsedPcts = [];

        foreach ($segments as $seg) {
            $seg = trim((string) $seg);
            if ($seg === '') {
                continue;
            }
            $seg = preg_replace('/^(?:\[\d+\]|Test\s*#\d+\s*:)\s*/i', '', $seg);
            $seg = preg_replace('/^(?:YES|NO|POS|NEG|POSITIVE|NEGATIVE)\s*[:\-]?\s*/i', '', $seg);

            $presence = null;
            $purity = null;

            if (preg_match('/Purity(?:\/Percentage)?\s*:\s*([^;\)\|\n]+)/i', $seg, $mPur)) {
                $purity = $formatPct($mPur[1]);
                $seg = trim(str_replace($mPur[0], '', $seg), " -;\t\n\r");
            } elseif (preg_match('/\(\s*(\d+(?:\.\d+)?%?)\s*\)\s*$/', $seg, $mPur)) {
                $purity = $formatPct($mPur[1]);
                $seg = trim(substr($seg, 0, -strlen($mPur[0])), " -;\t\n\r");
            }

            if (preg_match('/Presence\s+of\s*:?\s*(.+)$/i', $seg, $mPres)) {
                $presence = trim($mPres[1], " -;\t\n\r");
            } else {
                $fallback = trim($seg, " -;()\t\n\r\0\x0B");
                if ($fallback !== '' && !in_array(strtoupper($fallback), ['YES', 'NO', 'POS', 'NEG', 'POSITIVE', 'NEGATIVE', 'PRESUMPTIVE POSITIVE', 'NEGATIVE REACTION'], true)) {
                    $presence = $fallback;
                }
            }

            if ($presence !== null || $purity !== null) {
                $resVal = $overallResult ?? 'YES';
                $rows[] = [
                    'result'   => $resVal,
                    'presence' => $presence ?? '—',
                    'purity'   => $purity ?? '—',
                ];
                if ($presence !== null) {
                    $parsedPresences[] = $presence;
                }
                if ($purity !== null) {
                    $parsedPcts[] = $purity;
                }
            }
        }

        $finalResult = $overallResult ?? (!empty($rows) ? 'YES' : null);
        if (empty($rows)) {
            $rows[] = [
                'result'   => $finalResult ?? 'N/A',
                'presence' => $finalResult ? '—' : 'N/A',
                'purity'   => $finalResult ? '—' : 'N/A',
            ];
        }

        return [
            'result'     => $finalResult,
            'presence'   => !empty($parsedPresences) ? implode('; ', $parsedPresences) : null,
            'percentage' => !empty($parsedPcts) ? implode('; ', $parsedPcts) : null,
            'rows'       => $rows,
        ];
    }

    /**
     * Parse presumptive test string into structured rows (Presence & Purity).
     *
     * @param string|null $pRaw
     * @param mixed $presence
     * @param mixed $percentage
     * @return array<int, array>
     */
    public static function parsePresumptiveRows(?string $pRaw, mixed $presence = null, mixed $percentage = null): array
    {
        return self::parsePresumptiveData($pRaw, $presence, $percentage)['rows'];
    }

    /**
     * Parse confirmatory test result into structured rows (Result, Presence of, Purity/Percentage).
     *
     * @param mixed $confResult
     * @param mixed $detectedSubstances
     * @param mixed $percentages
     * @return array<int, array{result: string, presence: string, purity: string}>
     */
    public static function parseConfirmatoryRows(mixed $confResult = null, mixed $detectedSubstances = null, mixed $percentages = null): array
    {
        $resStr = is_scalar($confResult) ? trim((string)$confResult) : '';
        $overallResult = null;
        if (!empty($resStr)) {
            $upper = strtoupper($resStr);
            if (str_starts_with($upper, 'YES') || str_contains($upper, 'CONFIRMED') || str_starts_with($upper, 'POS')) {
                $overallResult = 'YES';
            } elseif (str_starts_with($upper, 'NO') || str_starts_with($upper, 'NEG')) {
                $overallResult = 'NO';
            } else {
                $overallResult = $resStr;
            }
        }

        // Normalize detected_substances
        $detSubs = $detectedSubstances;
        if (is_string($detSubs)) {
            $trimmed = trim($detSubs);
            if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                $detSubs = json_decode($trimmed, true) ?: [];
            } else {
                $detSubs = preg_split('/\s*[,;\n\r]+\s*/', $trimmed);
            }
        }
        if (!is_array($detSubs)) {
            $detSubs = (!empty($detSubs) && is_scalar($detSubs)) ? [(string)$detSubs] : [];
        }

        // Normalize percentages
        $pcts = $percentages;
        if (is_string($pcts)) {
            $trimmedPct = trim($pcts);
            if (str_starts_with($trimmedPct, '[') || str_starts_with($trimmedPct, '{')) {
                $pcts = json_decode($trimmedPct, true) ?: [];
            } else {
                $pcts = preg_split('/\s*[,;\n\r]+\s*/', $trimmedPct);
            }
        }
        if (!is_array($pcts)) {
            $pcts = (!empty($pcts) && is_scalar($pcts)) ? [(string)$pcts] : [];
        }
        $pcts = array_values($pcts);

        $rows = [];
        foreach (array_values($detSubs) as $idx => $item) {
            $name = '';
            $pct = $pcts[$idx] ?? null;
            if (is_array($item)) {
                $name = trim((string)($item['substance'] ?? ($item['name'] ?? ($item['compound'] ?? ''))));
                if (empty($pct)) {
                    $pct = $item['percentage'] ?? ($item['purity'] ?? ($item['concentration'] ?? null));
                }
            } elseif (is_scalar($item)) {
                $name = trim((string)$item);
            }

            if ($name === '' || strtoupper($name) === 'N/A' || $name === '—') {
                continue;
            }

            // Clean up name if it has parenthetical purity e.g. "Cocaine (88%)"
            if (preg_match('/\s*\(\s*(\d+(?:\.\d+)?\s*%?)\s*\)/', $name, $mP)) {
                if (empty($pct)) {
                    $pct = $mP[1];
                }
                $name = trim(preg_replace('/\s*\(\s*\d+(?:\.\d+)?\s*%?\s*\)/', '', $name));
            }
            // Strip trailing "detected"
            $name = trim(preg_replace('/\bdetected\s*$/i', '', $name));

            $purityStr = '—';
            if (!empty($pct)) {
                $pStr = trim((string)$pct);
                if (is_numeric(str_replace('%', '', $pStr)) && !str_ends_with($pStr, '%')) {
                    $pStr .= '%';
                }
                $purityStr = $pStr;
            }

            $rows[] = [
                'result'   => $overallResult ?? 'YES',
                'presence' => $name,
                'purity'   => $purityStr,
            ];
        }

        if (empty($rows)) {
            if ($overallResult !== null) {
                $rows[] = [
                    'result'   => $overallResult,
                    'presence' => '—',
                    'purity'   => '—',
                ];
            } else {
                $rows[] = [
                    'result'   => 'N/A',
                    'presence' => 'N/A',
                    'purity'   => 'N/A',
                ];
            }
        }

        return $rows;
    }

    public function resolveAllLabExamsForSuspect(?string $suspectName = null, $suspectDbId = null, $associateId = null, ?string $role = null, int $suspectIndex = 0): array
    {
        $primaryExam = $this->resolveLabExamForSuspect($suspectName, $suspectDbId, $associateId, $role, $suspectIndex);
        
        $isMultiCase = $this->isMultipleSuspect();
        $records = collect();
        if (!empty($this->id)) {
            $records = LaboratoryExaminationResult::where(function ($q) use ($isMultiCase) {
                if ($isMultiCase) {
                    $q->where('multiple_suspect_case_id', $this->id);
                } else {
                    $q->where('single_suspect_case_id', $this->id);
                }
            })->where('has_saved', true)->orderBy('id', 'asc')->get();
        }

        $allExams = [];
        $docketData = is_array($this->docket_data) ? $this->docket_data : (json_decode($this->docket_data ?? '[]', true) ?: []);
        $exhibits = method_exists($this, 'exhibits') ? $this->exhibits()->get()->keyBy('id') : collect();
        $labExamService = app(\App\Services\LaboratoryExamService::class);

        foreach ($records as $idx => $r) {
            $exModel = $r->exhibit_id ? ($exhibits[$r->exhibit_id] ?? Exhibit::find($r->exhibit_id)) : null;

            $examItem = [
                'id' => $r->id,
                'exhibit_id' => $r->exhibit_id,
                'exhibit_owner_name' => $r->suspect_name ?? ($suspectName ?? 'Primary Suspect'),
                'exam_reference' => $r->exam_reference,
                'exhibit_number' => $exModel->exhibit_number ?? ($r->exhibit_number ?? ($r->lab_exhibit_no ?? ('EXH-' . str_pad($idx + 1, 5, '0', STR_PAD_LEFT)))),
                'lab_exhibit_no' => $exModel->lab_exhibit_no ?? ($r->lab_exhibit_no ?? null),
                'exhibit_description' => !empty($r->sample_weight_description) ? $r->sample_weight_description : ($exModel->commodity_name ?? null),
                'commodity_name' => $exModel->commodity_name ?? null,
                'contents' => $exModel->contents ?? null,
                'laboratory_number' => $r->laboratory_number ?? ($primaryExam['laboratory_number'] ?? ($this->laboratory_number ?? ($docketData['lab_examination']['laboratory_number'] ?? ($docketData['laboratory_number'] ?? 'N/A')))),
                'analyst' => $r->analyst ?? ($primaryExam['analyst'] ?? 'N/A'),
                'date_received' => is_object($r->date_received) ? $r->date_received->format('Y-m-d') : substr((string)$r->date_received, 0, 10),
                'date_started' => is_object($r->date_started) ? $r->date_started->format('Y-m-d') : substr((string)$r->date_started, 0, 10),
                'date_completed' => is_object($r->date_completed) ? $r->date_completed->format('Y-m-d') : substr((string)$r->date_completed, 0, 10),
                'presumptive_result' => $r->presumptive_result,
                'presumptive_presence' => $r->presumptive_presence,
                'presumptive_percentage' => $r->presumptive_percentage,
                'confirmatory_result' => $r->confirmatory_result,
                'confirmatory' => !empty($r->confirmatory_result) ? (strtoupper(trim((string)$r->confirmatory_result)) !== 'NO' ? 'YES' : 'NO') : 'N/A',
                'sample_weight' => [
                    'description' => $r->sample_weight_description,
                    'units_packaging' => $r->sample_weight_units,
                    'gross_weight' => $r->sample_weight_gross,
                    'sample_taken' => $r->sample_weight_taken,
                    'reserved_samples' => $r->sample_weight_reserved,
                ],
                'substance_identification' => $r->substance_identification,
                'detected_substances' => is_string($r->detected_substances) ? (json_decode($r->detected_substances, true) ?: []) : ($r->detected_substances ?? []),
                'detected_subtances_percentage' => $r->detected_subtances_percentage ?? ($r->detected_substances_percentage ?? null),
                'detected_substances_percentage' => $r->detected_substances_percentage ?? ($r->detected_subtances_percentage ?? null),
                'test_methods' => $r->test_methods,
                'specifications' => $r->specifications,
                'remark' => $r->remark,
                'findings' => $r->findings,
                'purpose_of_examination' => $r->purpose_of_examination,
                'preliminary_exam_procedure' => $r->preliminary_exam_procedure,
                'screening_method' => $r->screening_method,
                'disposition_of_exhibit' => $r->disposition_of_exhibit,
                'has_saved' => true,
            ];

            $examItem['presumptive_rows'] = self::parsePresumptiveRows($r->presumptive_result, $r->presumptive_presence, $r->presumptive_percentage);
            $allExams[] = $examItem;
        }

        // If no DB exams found but a primary exam was resolved, seed it
        if (empty($allExams) && !empty($primaryExam)) {
            $allExams[] = $primaryExam;
        }

        // Supplement from docket_data['seizures'] or docket_data['lab_examinations_list'] if missing
        $seizuresList = $docketData['seizures'] ?? [];
        if (!empty($seizuresList) && is_array($seizuresList)) {
            $existingExamCommodities = array_filter(array_map(fn($e) => strtolower(trim((string)($e['commodity_name'] ?? ''))), $allExams));
            $existingExamLabExNos = array_filter(array_map(fn($e) => strtolower(trim((string)($e['lab_exhibit_no'] ?? ''))), $allExams));

            foreach ($seizuresList as $sIdx => $sz) {
                if (!$labExamService->isDrugExhibit($sz)) {
                    continue;
                }

                $hasExamData = !empty($sz['has_saved_lab']) 
                    || !empty($sz['presumptive_result']) 
                    || !empty($sz['detected_substances']) 
                    || !empty($sz['sample_gross']) 
                    || !empty($sz['test_methods']);

                if (!$hasExamData) {
                    continue;
                }

                $szLabExNo = strtolower(trim((string)($sz['lab_exhibit_no'] ?? '')));
                $szName = strtolower(trim((string)($sz['commodity_name'] ?? ($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '')))));

                $alreadyInExams = false;
                if (!empty($szLabExNo) && in_array($szLabExNo, $existingExamLabExNos, true)) {
                    $alreadyInExams = true;
                } elseif (!empty($szName) && in_array($szName, $existingExamCommodities, true) && count($allExams) >= count($seizuresList)) {
                    $alreadyInExams = true;
                }

                if (!$alreadyInExams && count($allExams) < count($seizuresList)) {
                    $extraExamItem = [
                        'id' => 2000 + $sIdx,
                        'exhibit_id' => null,
                        'exhibit_owner_name' => $sz['associated_person'] ?? ($suspectName ?? 'Primary Suspect'),
                        'exam_reference' => $sz['exam_reference'] ?? ($primaryExam['exam_reference'] ?? null),
                        'exhibit_number' => $sz['exhibit_number'] ?? ($sz['lab_exhibit_no'] ?? ($sz['lab_exhibit_number'] ?? ($sz['exhibit_no'] ?? ($primaryExam['exhibit_number'] ?? ('EXH-' . str_pad(count($allExams) + 1, 5, '0', STR_PAD_LEFT)))))),
                        'lab_exhibit_no' => $sz['lab_exhibit_no'] ?? ($sz['lab_exhibit_number'] ?? ($primaryExam['lab_exhibit_no'] ?? chr(65 + count($allExams)))),
                        'commodity_name' => $sz['commodity_name'] ?? ($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? null)),
                        'contents' => $sz['contents'] ?? ($sz['seizure_contents'] ?? null),
                        'laboratory_number' => $sz['laboratory_number'] ?? ($primaryExam['laboratory_number'] ?? ($this->laboratory_number ?? ($docketData['lab_examination']['laboratory_number'] ?? ($docketData['laboratory_number'] ?? 'N/A')))),
                        'analyst' => $sz['analyst'] ?? ($primaryExam['analyst'] ?? 'N/A'),
                        'date_received' => $sz['date_received'] ?? ($primaryExam['date_received'] ?? date('Y-m-d')),
                        'date_started' => $sz['date_started'] ?? ($primaryExam['date_started'] ?? date('Y-m-d')),
                        'date_completed' => $sz['date_completed'] ?? ($primaryExam['date_completed'] ?? date('Y-m-d')),
                        'presumptive_result' => $sz['presumptive_result'] ?? ($primaryExam['presumptive_result'] ?? null),
                        'presumptive_presence' => $sz['presumptive_presence'] ?? ($primaryExam['presumptive_presence'] ?? null),
                        'presumptive_percentage' => $sz['presumptive_percentage'] ?? ($primaryExam['presumptive_percentage'] ?? null),
                        'confirmatory_result' => $sz['confirmatory_result'] ?? ($primaryExam['confirmatory_result'] ?? 'Pending'),
                        'confirmatory' => !empty($sz['confirmatory_result']) ? (strtoupper(trim((string)$sz['confirmatory_result'])) !== 'NO' ? 'YES' : 'NO') : 'N/A',
                        'sample_weight' => [
                            'description' => $sz['sample_description'] ?? ($sz['sample_weight_description'] ?? ($sz['sample_weight']['description'] ?? null)),
                            'units_packaging' => $sz['sample_units'] ?? ($sz['sample_weight_units'] ?? ($sz['sample_weight']['units_packaging'] ?? null)),
                            'gross_weight' => $sz['sample_gross'] ?? ($sz['sample_weight_gross'] ?? ($sz['sample_weight']['gross_weight'] ?? null)),
                            'sample_taken' => $sz['sample_taken'] ?? ($sz['sample_weight_taken'] ?? ($sz['sample_weight']['sample_taken'] ?? null)),
                            'reserved_samples' => $sz['sample_reserved'] ?? ($sz['sample_weight_reserved'] ?? ($sz['sample_weight']['reserved_samples'] ?? null)),
                        ],
                        'substance_identification' => $sz['substance_identification'] ?? ($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? null)),
                        'detected_substances' => is_string($sz['detected_substances'] ?? null) ? (json_decode($sz['detected_substances'], true) ?: []) : ($sz['detected_substances'] ?? []),
                        'test_methods' => $sz['test_methods'] ?? ($primaryExam['test_methods'] ?? null),
                        'specifications' => $sz['specifications'] ?? ($primaryExam['specifications'] ?? null),
                        'remark' => $sz['remark'] ?? ($primaryExam['remark'] ?? null),
                        'findings' => $sz['findings'] ?? ($primaryExam['findings'] ?? null),
                        'purpose_of_examination' => $sz['purpose_of_examination'] ?? ($primaryExam['purpose_of_examination'] ?? null),
                        'preliminary_exam_procedure' => $sz['preliminary_exam_procedure'] ?? ($primaryExam['preliminary_exam_procedure'] ?? null),
                        'screening_method' => $sz['screening_method'] ?? ($primaryExam['screening_method'] ?? null),
                        'disposition_of_exhibit' => $sz['disposition_of_exhibit'] ?? ($primaryExam['disposition_of_exhibit'] ?? null),
                        'has_saved' => true,
                    ];
                    $extraExamItem['presumptive_rows'] = self::parsePresumptiveRows($extraExamItem['presumptive_result'], $extraExamItem['presumptive_presence'], $extraExamItem['presumptive_percentage']);
                    $allExams[] = $extraExamItem;
                    if (!empty($szName)) $existingExamCommodities[] = $szName;
                    if (!empty($szLabExNo)) $existingExamLabExNos[] = $szLabExNo;
                }
            }
        }

        return !empty($allExams) ? $allExams : (!empty($primaryExam) ? [$primaryExam] : []);
    }

    public function getArrestingOfficerAttribute()
    {
        return $this->attributes['arresting_officer'] ?? ($this->attributes['submitting_officer'] ?? null);
    }

    public function getSubmittingOfficerAttribute()
    {
        return $this->attributes['arresting_officer'] ?? ($this->attributes['submitting_officer'] ?? null);
    }
}

