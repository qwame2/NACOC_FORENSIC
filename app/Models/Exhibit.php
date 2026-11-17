<?php

namespace App\Models;

use App\Models\Concerns\EnforcesMutuallyExclusiveCases;
use App\Services\CasePhotoResolver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exhibit extends Model
{
    use HasFactory, EnforcesMutuallyExclusiveCases;

    protected $table = 'exhibits';

    protected $fillable = [
        'case_type',
        'single_suspect_case_id',
        'multiple_suspect_case_id',
        'suspect_id',
        'associated_person',
        'exhibit_number',
        'lab_exhibit_no',
        'property_id',
        'exhibit_category',
        'exhibit_type',
        'drug_classification',
        'commodity_name',
        'seizure_quantity',
        'amount',
        'seizure_packaging',
        'seizure_weight',
        'seizure_unit',
        'seizure_value',
        'seizure_concealment',
        'seizure_date',
        'seizure_time',
        'seizure_location',
        'seizure_officer',
        'investigating_officer',
        'source_requesting_unit',
        'seizure_reason',
        'source_of_exhibits',
        'exhibit_remark',
        'lab_remark',
        'veh_make',
        'veh_model',
        'veh_type',
        'veh_year',
        'veh_colour',
        'veh_reg_no',
        'veh_vin',
        'veh_engine_no',
        'veh_country_reg',
        'veh_owner',
        'veh_driver',
        'veh_owner_contact',
        'veh_docs_available',
        'veh_doc_ref',
        'veh_condition',
        'veh_fuel',
        'veh_keys_recv',
        'veh_key_count',
        'veh_damage',
        'veh_mods',
        'veh_features',
        'wpn_model',
        'wpn_serial_no',
        'wpn_calibre',
        'wpn_colour',
        'wpn_size',
        'wpn_marks',
        'wpn_location_found',
        'wpn_loaded_status',
        'wpn_condition',
        'dev_model',
        'dev_serial_no',
        'dev_imei1',
        'dev_imei2',
        'dev_mac_address',
        'dev_storage',
        'dev_colour',
        'dev_marks',
        'ammunition_details',
        'evidence_photos',
        'tag_url',
        'other_exhibit_description',
        'contents',
        'description_of_exhibits',
        'custodial_status',
        'chain_of_custody',
        'qr_token',
    ];

    protected $casts = [
        'ammunition_details' => 'array',
    ];

    /**
     * Normalize an evidence photos value (array, JSON string, or double-encoded JSON string)
     * into a clean list of non-empty photo strings.
     */
    public static function decodePhotosValue(mixed $value): array
    {
        for ($i = 0; $i < 3 && is_string($value); $i++) {
            $trimmed = trim($value);
            if ($trimmed === '' || $trimmed === 'null') {
                return [];
            }
            $decoded = json_decode($trimmed, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                if (str_starts_with($trimmed, 'data:') || str_starts_with($trimmed, 'http') || str_starts_with($trimmed, '/') || str_contains($trimmed, '/')) {
                    return [$trimmed];
                }
                return [];
            }
            $value = $decoded;
        }

        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (is_string($item)) {
                $trimmed = trim($item);
                if ($trimmed !== '') {
                    $result[] = $trimmed;
                }
            } elseif (is_array($item)) {
                $candidate = $item['url'] ?? $item['src'] ?? $item['path'] ?? null;
                if (is_string($candidate) && trim($candidate) !== '') {
                    $result[] = trim($candidate);
                }
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Ensure evidence_photos always decodes cleanly (even legacy double-encoded JSON rows)
     * and encodes once on save.
     */
    protected function evidencePhotos(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): array => self::decodePhotosValue($value),
            set: fn (mixed $value): ?string => $value === null
                ? null
                : json_encode(self::decodePhotosValue($value), JSON_UNESCAPED_SLASHES),
        );
    }

    /**
     * Resolve display-ready photo URLs/data URIs for this exhibit, falling back to
     * linked case docket data if the exhibit row itself has no photos stored.
     */
    public function resolveEvidencePhotosArray(): array
    {
        $photos = self::decodePhotosValue($this->evidence_photos);

        if (empty($photos)) {
            $caseRecord = $this->singleSuspectCase ?? $this->multipleSuspectCase;
            $docket = is_array($caseRecord?->docket_data) ? $caseRecord->docket_data : [];
            $seizures = is_array($docket['seizures'] ?? null) ? $docket['seizures'] : [];

            foreach ($seizures as $sz) {
                if (!is_array($sz)) {
                    continue;
                }
                $matchesId = !empty($sz['id']) && (int) $sz['id'] === (int) $this->id;
                $matchesProp = !empty($sz['property_id']) && $this->property_id && $sz['property_id'] === $this->property_id;
                $matchesName = !empty($sz['commodity_name']) && $this->commodity_name && strcasecmp((string) $sz['commodity_name'], (string) $this->commodity_name) === 0;
                if ($matchesId || $matchesProp || ($matchesName && !empty($sz['evidence_photos']))) {
                    $photos = array_merge($photos, self::decodePhotosValue($sz['evidence_photos'] ?? []));
                }
            }

            if (empty($photos) && !empty($docket['evidence_photos'])) {
                $photos = self::decodePhotosValue($docket['evidence_photos']);
            }
        }

        if (empty($photos)) {
            return [];
        }

        $resolver = app(CasePhotoResolver::class);
        $formatted = [];
        foreach (array_values(array_unique($photos)) as $photo) {
            $url = $resolver->formatPhotoUrl($photo);
            if ($url !== '') {
                $formatted[] = $url;
            }
        }

        return array_values(array_unique($formatted));
    }

    protected $appends = [
        'case_number',
        'case_review_url',
        'associated_suspects',
        'tag_image',
        'qr_url',
    ];

    public function singleCase()
    {
        return $this->belongsTo(SingleSuspectCase::class, 'single_suspect_case_id');
    }

    public function singleSuspectCase()
    {
        return $this->singleCase();
    }

    public function multipleCase()
    {
        return $this->belongsTo(MultipleSuspectCase::class, 'multiple_suspect_case_id');
    }

    public function multipleSuspectCase()
    {
        return $this->multipleCase();
    }

    public function suspect()
    {
        return $this->belongsTo(CaseSuspect::class, 'suspect_id');
    }

    public function labResult()
    {
        return $this->hasOne(LaboratoryExaminationResult::class, 'exhibit_id');
    }

    /**
     * Return metadata for the completed Preliminary Exhibit Analysis Report PDF
     * attached to this exhibit (if Lab Analysis has completed examination and a
     * SuspectPreliminaryReport record exists), or null if still awaiting lab work.
     */
    public function resolveAttachedPreliminaryReport(): ?array
    {
        $caseRecord = $this->singleSuspectCase ?? $this->multipleSuspectCase;
        $caseNumber = $caseRecord?->case_number;
        $singleId   = $this->single_suspect_case_id;
        $multipleId = $this->multiple_suspect_case_id;

        if (empty($caseNumber) && empty($singleId) && empty($multipleId)) {
            return null;
        }

        $reports = SuspectPreliminaryReport::forCase($caseNumber, $multipleId, $singleId);
        if ($reports->isEmpty()) {
            return null;
        }

        $report = $this->suspect_id
            ? (SuspectPreliminaryReport::filterForSuspect($reports, $this->suspect_id, $this->suspect?->suspect_name)->first() ?? $reports->first())
            : $reports->first();

        if (!$report) {
            return null;
        }

        $labUnitName = (string) ($caseRecord?->unit_name ?? '');
        $isLabUnitCase = stripos($labUnitName, 'lab') !== false;
        $docket = is_array($caseRecord?->docket_data) ? $caseRecord->docket_data : [];
        $unitProgress = is_array($docket['unit_progress'] ?? null) ? $docket['unit_progress'] : [];
        $labProgress = is_array($unitProgress['Lab Analysis'] ?? null) ? $unitProgress['Lab Analysis'] : [];
        $isLabCompletedInProgress = strtoupper((string) ($labProgress['status'] ?? '')) === 'COMPLETED';

        $hasSavedLabResult = (bool) ($this->labResult?->has_saved);
        if (!$hasSavedLabResult && ($singleId || $multipleId)) {
            $hasSavedLabResult = LaboratoryExaminationResult::query()
                ->where('has_saved', true)
                ->where(function ($q) use ($singleId, $multipleId) {
                    if ($singleId) {
                        $q->where('single_suspect_case_id', $singleId);
                    } elseif ($multipleId) {
                        $q->where('multiple_suspect_case_id', $multipleId);
                    }
                })
                ->exists();
        }

        if (!$hasSavedLabResult && !$isLabUnitCase && !$isLabCompletedInProgress) {
            return null;
        }

        $resolvedCaseNo = $report->case_number ?: $caseNumber;
        $url = $report->url;
        if (empty($url) || $url === '#') {
            $url = !empty($resolvedCaseNo)
                ? url('/Lab_Analysis/preliminary-report-pdf/' . $resolvedCaseNo)
                : '#';
        }

        return [
            'id'                => (int) $report->id,
            'lab_number'        => $report->lab_number,
            'case_number'       => $resolvedCaseNo,
            'report_type'       => 'preliminary',
            'report_label'      => 'Preliminary Exhibit Analysis Report (PDF)',
            'original_filename' => $report->original_filename
                ?: ('Preliminary_Exhibit_Analysis_Report_' . ($resolvedCaseNo ?: $report->id) . '.pdf'),
            'url'               => $url,
            'download_url'      => $url === '#' ? '#' : ($url . (str_contains($url, '?') ? '&' : '?') . 'download=1'),
            'created_at'        => $report->created_at?->toIso8601String(),
        ];
    }

    /**
     * Resolve the attached Digital Forensics Extraction & Investigation Status Log Report
     * metadata if one has been generated for this electronic-device exhibit's case.
     */
    public function resolveAttachedDpfReport(): ?array
    {
        if (!$this->isElectronicDeviceRelated()) {
            return null;
        }

        $caseRecord = $this->singleSuspectCase ?? $this->multipleSuspectCase;
        $caseNumber = $caseRecord?->case_number;
        $singleId   = $this->single_suspect_case_id;
        $multipleId = $this->multiple_suspect_case_id;

        if (empty($caseNumber) && empty($singleId) && empty($multipleId)) {
            return null;
        }

        $reports = DpfReport::query()
            ->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            })
            ->where(function ($q) use ($singleId, $multipleId, $caseNumber) {
                if ($singleId) {
                    $q->orWhere('single_suspect_case_id', $singleId);
                }
                if ($multipleId) {
                    $q->orWhere('multiple_suspect_case_id', $multipleId);
                }
                if (!empty($caseNumber)) {
                    $q->orWhere('case_number', $caseNumber);
                }
            })
            ->orderByDesc('id')
            ->get();

        if ($reports->isEmpty()) {
            return null;
        }

        $dpfReport = $this->suspect_id
            ? ($reports->firstWhere('suspect_id', $this->suspect_id) ?? $reports->first())
            : $reports->first();

        if (!$dpfReport) {
            return null;
        }

        $resolvedCaseNo = $dpfReport->case_number ?: $caseNumber;
        $dpfCode = $dpfReport->dpf_code ?: ($resolvedCaseNo ?: ('DPF-' . $dpfReport->id));
        $url = url('/Digital_Forensics/dpf-report-pdf/' . urlencode($dpfCode));

        return [
            'id'                => (int) $dpfReport->id,
            'dpf_code'          => $dpfCode,
            'case_number'       => $resolvedCaseNo,
            'report_type'       => 'dpf_status_log',
            'report_label'      => 'Extraction & Investigation Status Log Report (PDF)',
            'original_filename' => 'Extraction & Investigation Status Log Report (' . $dpfCode . ').pdf',
            'url'               => $url,
            'download_url'      => $url . '?download=1',
            'created_at'        => $dpfReport->created_at?->toIso8601String(),
        ];
    }

    /**
     * Resolve either a completed Lab Preliminary Report or a generated DF Status Log Report.
     */
    public function resolveAttachedUnitReport(): ?array
    {
        return $this->resolveAttachedPreliminaryReport() ?? $this->resolveAttachedDpfReport();
    }

    /**
     * Whether this exhibit already has a completed Preliminary Exhibit Analysis Report
     * attached and is therefore locked from being re-selected by a Lab Officer.
     */
    public function isLockedByPreliminaryReport(): bool
    {
        return $this->resolveAttachedPreliminaryReport() !== null;
    }

    /**
     * Whether this electronic-device exhibit already has a generated Extraction &
     * Investigation Status Log Report and is therefore locked from re-selection.
     */
    public function isLockedByDpfReport(): bool
    {
        return $this->resolveAttachedDpfReport() !== null;
    }

    /**
     * Whether this exhibit is locked by either a Lab Preliminary Report or a DF Status Log Report.
     */
    public function isLockedByReport(): bool
    {
        return $this->resolveAttachedUnitReport() !== null;
    }

    /**
     * Resolve the Case Number associated with this exhibit.
     */
    public function getCaseNumberAttribute()
    {
        if ($this->singleSuspectCase) {
            return $this->singleSuspectCase->case_number;
        }
        if ($this->multipleSuspectCase) {
            return $this->multipleSuspectCase->case_number;
        }
        return 'N/A';
    }

    /**
     * Resolve review URL for the case.
     */
    public function getCaseReviewUrlAttribute()
    {
        $caseNo = $this->case_number;
        if ($caseNo !== 'N/A') {
            return url('/cases/' . urlencode($caseNo) . '/review?from=Evidence_Management');
        }
        return '#';
    }

    /**
     * Resolve tag image url for Seized Property badge tags.
     */
    public function getTagImageAttribute()
    {
        return $this->tag_url ?? ($this->attributes['tag_image'] ?? null);
    }

    /**
     * Resolve date seized fallback to seizure_date.
     */
    public function getDateSeizedAttribute()
    {
        return $this->seizure_date;
    }

    /**
     * Resolve all suspects associated with this exhibit.
     * Returns a Collection of uniform suspect descriptor objects.
     */
    public function getAssociatedSuspectsAttribute()
    {
        $suspects = collect();

        // 1. Direct CaseSuspect link
        if ($this->suspect) {
            $suspects->push((object)[
                'id' => $this->suspect->id,
                'name' => $this->suspect->suspect_name ?: 'Unnamed Suspect',
                'alias' => $this->suspect->suspect_alias,
                'ghana_card' => $this->suspect->ghana_card,
                'gender' => $this->suspect->gender,
                'age' => $this->suspect->age,
                'nationality' => $this->suspect->nationality,
                'mugshot' => $this->suspect->mugshot_front,
                'role' => $this->suspect->role ?: 'Primary Suspect',
                'is_directly_linked' => true,
                'case_type' => 'Multiple Suspect Case',
                'case_number' => $this->multipleSuspectCase?->case_number,
                'suspect_disposition' => $this->suspect->suspect_disposition ?? 'Under Investigation',
            ]);
            return $suspects;
        }

        // 2. Single Suspect Case
        if ($this->singleSuspectCase) {
            $case = $this->singleSuspectCase;
            $suspects->push((object)[
                'id' => $case->id,
                'name' => $case->suspect_name ?: 'Unnamed Suspect',
                'alias' => $case->suspect_alias,
                'ghana_card' => $case->ghana_card,
                'gender' => $case->gender,
                'age' => $case->age,
                'nationality' => $case->nationality,
                'mugshot' => $case->mugshot_front,
                'role' => 'Sole Suspect',
                'is_directly_linked' => true,
                'case_type' => 'Single Suspect Case',
                'case_number' => $case->case_number,
                'suspect_disposition' => $case->suspect_disposition ?? 'Under Investigation',
            ]);
            return $suspects;
        }

        // 3. Multiple Suspect Case without direct suspect_id link
        if ($this->multipleSuspectCase && $this->multipleSuspectCase->suspects && $this->multipleSuspectCase->suspects->isNotEmpty()) {
            foreach ($this->multipleSuspectCase->suspects as $s) {
                $matched = !empty($this->associated_person) && stripos($s->suspect_name, trim($this->associated_person)) !== false;
                $suspects->push((object)[
                    'id' => $s->id,
                    'name' => $s->suspect_name ?: 'Unnamed Suspect',
                    'alias' => $s->suspect_alias,
                    'ghana_card' => $s->ghana_card,
                    'gender' => $s->gender,
                    'age' => $s->age,
                    'nationality' => $s->nationality,
                    'mugshot' => $s->mugshot_front,
                    'role' => $s->role ?: 'Co-Suspect',
                    'is_directly_linked' => $matched,
                    'case_type' => 'Multiple Suspect Case',
                    'case_number' => $this->multipleSuspectCase->case_number,
                    'suspect_disposition' => $s->suspect_disposition ?? 'Under Investigation',
                ]);
            }
            return $suspects;
        }

        // 4. Free-form associated_person string
        if (!empty($this->associated_person)) {
            $suspects->push((object)[
                'id' => null,
                'name' => $this->associated_person,
                'alias' => null,
                'ghana_card' => null,
                'gender' => null,
                'age' => null,
                'nationality' => null,
                'mugshot' => null,
                'role' => 'Associated Person',
                'is_directly_linked' => true,
                'case_type' => 'Unclassified Case',
                'case_number' => $this->case_number,
                'suspect_disposition' => 'Under Investigation',
            ]);
        }

        return $suspects;
    }

    protected bool $wasAutoGeneratedExhibitNumber = false;

    public static function formatSystemExhibitNumber(int $sequence): string
    {
        return sprintf('EXH-%05d', max(1, $sequence));
    }

    public static function isSystemExhibitNumber(?string $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        return (bool) preg_match('/^EXH-\d{5,}$/i', trim($value));
    }

    public static function shouldGenerateSystemExhibitNumber($value, ?int $ignoreId = null): bool
    {
        $str = is_scalar($value) ? trim((string) $value) : '';
        if ($str === '') {
            return true;
        }

        // Plain integer indices (1, 2, "1") or legacy frontend placeholder ("EXH-2026-001-NFC")
        if (ctype_digit($str) || preg_match('/^EXH-\d{4}-\d{3}-NFC$/i', $str)) {
            return true;
        }

        // Check if value already exists in exhibits table
        $query = \Illuminate\Support\Facades\DB::table('exhibits')->where('exhibit_number', $str);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }
        if ($query->exists()) {
            return true;
        }

        return false;
    }

    public static function getMaxExhibitSequence(bool $lock = false): int
    {
        $query = \Illuminate\Support\Facades\DB::table('exhibits')
            ->select('exhibit_number')
            ->whereNotNull('exhibit_number')
            ->where('exhibit_number', 'LIKE', 'EXH-%');

        if ($lock && \Illuminate\Support\Facades\DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $maxSeq = 0;
        foreach ($query->pluck('exhibit_number') as $exNo) {
            $str = trim((string) $exNo);
            if (preg_match('/^EXH-(\d+)$/i', $str, $m)) {
                $num = (int) $m[1];
                if ($num > $maxSeq && $num < 100000000) {
                    $maxSeq = $num;
                }
            }
        }

        return $maxSeq;
    }

    public static function generateNextExhibitNumber(): string
    {
        $nextSeq = static::getMaxExhibitSequence(true) + 1;

        do {
            $candidate = static::formatSystemExhibitNumber($nextSeq);
            $exists = \Illuminate\Support\Facades\DB::table('exhibits')
                ->where('exhibit_number', $candidate)
                ->exists();
            if ($exists) {
                $nextSeq++;
            }
        } while ($exists);

        return $candidate;
    }

    public static function previewNextExhibitNumber(int $offset = 1): string
    {
        $baseSeq = static::getMaxExhibitSequence(false);
        return static::formatSystemExhibitNumber($baseSeq + max(1, $offset));
    }

    protected static function booted()
    {
        static::creating(function (Exhibit $exhibit) {
            if (empty($exhibit->qr_token)) {
                $exhibit->qr_token = (string) \Illuminate\Support\Str::uuid();
            }
            $exhibit->custodial_status = 'Seized';

            if (static::shouldGenerateSystemExhibitNumber($exhibit->exhibit_number)) {
                $exhibit->exhibit_number = static::generateNextExhibitNumber();
                $exhibit->wasAutoGeneratedExhibitNumber = true;
            } else {
                $exhibit->exhibit_number = trim((string) $exhibit->exhibit_number);
            }
        });

        static::updating(function (Exhibit $exhibit) {
            $originalExhibitNumber = $exhibit->getOriginal('exhibit_number');
            if (is_string($originalExhibitNumber) && trim($originalExhibitNumber) !== '') {
                $exhibit->exhibit_number = $originalExhibitNumber;
            }
        });

        static::saving(function (Exhibit $exhibit) {
            if (empty($exhibit->custodial_status)) {
                $exhibit->custodial_status = 'Seized';
            }
            if (!$exhibit->exists && static::shouldGenerateSystemExhibitNumber($exhibit->exhibit_number)) {
                $exhibit->exhibit_number = static::generateNextExhibitNumber();
                $exhibit->wasAutoGeneratedExhibitNumber = true;
            }
        });
    }

    protected function performInsert(\Illuminate\Database\Eloquent\Builder $query)
    {
        $maxAttempts = 5;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return parent::performInsert($query);
            } catch (\Illuminate\Database\QueryException $e) {
                $msg = strtolower($e->getMessage());
                $isUniqueConflict = str_contains($msg, 'unique')
                    || str_contains($msg, 'duplicate')
                    || ($e->errorInfo[1] ?? 0) === 1062;

                if (!$isUniqueConflict || $attempt === $maxAttempts) {
                    throw $e;
                }

                $this->exhibit_number = static::generateNextExhibitNumber();
                $this->wasAutoGeneratedExhibitNumber = true;
            }
        }

        return false;
    }

    public function getCustodialStatusAttribute($value)
    {
        return !empty($value) ? $value : 'Seized';
    }

    public function setCustodialStatusAttribute($value)
    {
        $this->attributes['custodial_status'] = !empty($value) ? $value : 'Seized';
    }

    public function getQrTokenAttribute($value)
    {
        if (empty($value) && $this->exists) {
            $newToken = (string) \Illuminate\Support\Str::uuid();
            $this->attributes['qr_token'] = $newToken;
            $this->saveQuietly();
            return $newToken;
        }
        return $value;
    }

    public function getQrUrlAttribute()
    {
        $token = $this->qr_token;
        if (empty($token) && $this->exists) {
            $token = (string) \Illuminate\Support\Str::uuid();
            $this->attributes['qr_token'] = $token;
            $this->saveQuietly();
        }
        return $token ? url("/exhibits/tag/{$token}") : '';
    }

    /**
     * Scope query to exhibits that have not been assigned QR code or have not been classified.
     */
    public function scopeNeedsQrOrClassification($query)
    {
        return $query->where(function ($q) {
            // Unassigned QR code
            $q->where(function ($qr) {
                $qr->whereNull('property_id')->orWhere('property_id', '');
            })->where(function ($qr) {
                $qr->whereNull('tag_url')->orWhere('tag_url', '');
            })
            // OR missing exhibit_type
            ->orWhereNull('exhibit_type')
            ->orWhere('exhibit_type', '')
            // OR missing drug_classification for drug/narcotic categories
            ->orWhere(function ($drug) {
                $drug->where(function ($cat) {
                    $cat->where('exhibit_category', 'LIKE', '%drug%')
                        ->orWhere('exhibit_category', 'LIKE', '%narcotic%');
                })->where(function ($dc) {
                    $dc->whereNull('drug_classification')->orWhere('drug_classification', '');
                });
            });
        });
    }

    /**
     * Scope query to exhibits with unassigned QR code.
     */
    public function scopeUnassignedQr($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('property_id')->orWhere('property_id', '');
        })->where(function ($q) {
            $q->whereNull('tag_url')->orWhere('tag_url', '');
        });
    }

    /**
     * Scope query to unclassified exhibits.
     */
    public function scopeUnclassified($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('exhibit_type')
              ->orWhere('exhibit_type', '')
              ->orWhere(function ($drug) {
                  $drug->where(function ($cat) {
                      $cat->where('exhibit_category', 'LIKE', '%drug%')
                          ->orWhere('exhibit_category', 'LIKE', '%narcotic%');
                  })->where(function ($dc) {
                      $dc->whereNull('drug_classification')->orWhere('drug_classification', '');
                  });
              });
        });
    }

    /**
     * Scope query to exhibits that are both assigned QR code and classified.
     */
    public function scopeCompletedQrAndClassification($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($qr) {
                $qr->whereNotNull('property_id')->where('property_id', '!=', '');
            })->orWhere(function ($qr) {
                $qr->whereNotNull('tag_url')->where('tag_url', '!=', '');
            });
        })
        ->whereNotNull('exhibit_type')
        ->where('exhibit_type', '!=', '')
        ->where(function ($q) {
            $q->where(function ($cat) {
                $cat->where('exhibit_category', 'NOT LIKE', '%drug%')
                    ->where('exhibit_category', 'NOT LIKE', '%narcotic%');
            })->orWhere(function ($drug) {
                $drug->whereNotNull('drug_classification')->where('drug_classification', '!=', '');
            });
        });
    }

    /**
     * Scope query to all exhibits without suspect(s) (standalone unassigned,
     * awaiting-suspect dockets, or cases with zero named/linked suspects).
     */
    public function scopeUnassignedOrAwaitingSuspect($query)
    {
        $noSuspectPlaceholders = [
            'none', 'n/a', 'na', 'unknown', 'nil',
            'no suspect', 'no suspects', 'unassigned', 'awaiting suspect',
            'all suspects', 'primary suspect',
        ];

        $applyPlaceholderNameCheck = function ($q, string $column, bool $includeAllSuspectsLabel = false) use ($noSuspectPlaceholders) {
            $q->whereNull($column)
                ->orWhere($column, '')
                ->orWhere($column, 'LIKE', '%Unassigned%')
                ->orWhere($column, 'LIKE', '%Awaiting%')
                ->orWhere($column, 'LIKE', '%No Suspect%')
                ->orWhereRaw('LOWER(TRIM(' . $column . ')) IN (?, ?, ?, ?, ?)', [
                    'none', 'n/a', 'na', 'unknown', 'nil',
                ]);

            if ($includeAllSuspectsLabel) {
                $q->orWhereRaw('LOWER(TRIM(' . $column . ')) IN (?, ?)', [
                    'all suspects', 'primary suspect',
                ]);
            }
        };

        $singleCaseIsUnassigned = function ($sq) use ($applyPlaceholderNameCheck) {
            $sq->where(function ($nameQ) use ($applyPlaceholderNameCheck) {
                $applyPlaceholderNameCheck($nameQ, 'suspect_name', true);
            })->orWhere('title', 'LIKE', '%Awaiting Suspect%');
        };

        return $query->where(function ($q) use ($applyPlaceholderNameCheck) {
            $q->whereNull('suspect_id')
                ->orWhereHas('suspect', function ($sq) use ($applyPlaceholderNameCheck) {
                    $applyPlaceholderNameCheck($sq, 'suspect_name', false);
                });
        })
        ->where(function ($q) use ($singleCaseIsUnassigned) {
            $q->whereNull('single_suspect_case_id')
                ->orWhereHas('singleSuspectCase', $singleCaseIsUnassigned);
        })
        ->where(function ($q) {
            $q->whereNull('multiple_suspect_case_id')
                ->orWhereDoesntHave('multipleSuspectCase.suspects');
        })
        ->whereDoesntHave('multipleSuspectCase.suspects')
        ->where(function ($q) use ($applyPlaceholderNameCheck, $singleCaseIsUnassigned) {
            $applyPlaceholderNameCheck($q, 'associated_person', true);
            $q->orWhereHas('singleSuspectCase', $singleCaseIsUnassigned);
        });
    }

    /**
     * Build a full manifest payload array for pre-filling Section 4 forms.
     */
    public function toManifestPayloadArray(): array
    {
        $singleCase = $this->singleSuspectCase;
        $parentCase = $singleCase ?? $this->multipleSuspectCase;
        $desc = $this->commodity_name ?: ($this->exhibit_category ?: 'General Exhibit');
        $meta = [];
        if ($this->seizure_quantity) {
            $meta[] = 'Qty: ' . $this->seizure_quantity;
        }
        if ($this->seizure_weight) {
            $meta[] = 'Wt: ' . $this->seizure_weight . ' ' . ($this->seizure_unit ?: '');
        }
        if ($this->seizure_location) {
            $meta[] = 'Loc: ' . $this->seizure_location;
        }
        if ($this->seizure_date) {
            $meta[] = 'Date: ' . $this->seizure_date;
        }
        $metaStr = !empty($meta) ? ' — ' . implode(' | ', array_map('trim', $meta)) : '';
        $displayText = ($this->property_id ?: ($this->exhibit_number ?: 'Item #' . $this->id)) . ' — ' . $desc . $metaStr;

        $ammo = is_array($this->ammunition_details) ? $this->ammunition_details : [];
        $attachedReport = $this->resolveAttachedUnitReport();

        return [
            'id'                        => $this->id,
            'linked_exhibit_id'         => $this->id,
            'single_suspect_case_id'    => $this->single_suspect_case_id,
            'multiple_suspect_case_id'  => $this->multiple_suspect_case_id,
            'case_number'               => $parentCase?->case_number ?? null,
            'entered_by_user_id'        => $parentCase?->user_id ?? null,
            'entered_by_name'           => $parentCase?->submitting_officer ?? ($parentCase?->arresting_officer ?? $this->seizure_officer),
            'entered_by_unit'           => $parentCase?->unit_name ?? null,
            'associated_person'         => $this->associated_person,
            'property_id'               => $this->property_id,
            'exhibit_number'            => $this->exhibit_number,
            'lab_exhibit_no'            => $this->lab_exhibit_no,
            'exhibit_category'          => $this->exhibit_category,
            'category'                  => $this->exhibit_category,
            'exhibit_type'              => $this->exhibit_type,
            'drug_classification'       => $this->drug_classification,
            'commodity_name'            => $this->commodity_name,
            'seizure_name'              => $this->commodity_name,
            'name'                      => $this->commodity_name,
            'contents'                  => $this->contents,
            'description_of_exhibits'   => $this->description_of_exhibits ?: ($singleCase?->description_of_exhibits ?? null),
            'other_exhibit_description' => $this->other_exhibit_description,
            'seizure_quantity'          => $this->seizure_quantity,
            'quantity'                  => $this->seizure_quantity,
            'amount'                    => $this->amount,
            'seizure_weight'            => $this->seizure_weight,
            'weight'                    => $this->seizure_weight,
            'seizure_unit'              => $this->seizure_unit,
            'unit'                      => $this->seizure_unit,
            'seizure_packaging'         => $this->seizure_packaging,
            'packaging'                 => $this->seizure_packaging,
            'seizure_value'             => $this->seizure_value,
            'value'                     => $this->seizure_value,
            'seizure_concealment'       => $this->seizure_concealment,
            'concealment'               => $this->seizure_concealment,
            'seizure_date'              => $this->seizure_date,
            'date'                      => $this->seizure_date,
            'seizure_time'              => $this->seizure_time,
            'time'                      => $this->seizure_time,
            'seizure_location'          => $this->seizure_location,
            'location'                  => $this->seizure_location,
            'seizure_officer'           => $this->seizure_officer,
            'seizing_officer'           => $this->seizure_officer,
            'investigating_officer'     => $this->investigating_officer,
            'source_requesting_unit'    => $this->source_requesting_unit,
            'seizure_reason'            => $this->seizure_reason,
            'reason'                    => $this->seizure_reason,
            'source_of_exhibits'        => $this->source_of_exhibits,
            'source'                    => $this->source_of_exhibits,
            'exhibit_remark'            => $this->exhibit_remark,
            'remark'                    => $this->exhibit_remark,
            'lab_remark'                => $this->lab_remark,
            'veh_make'                  => $this->veh_make,
            'veh_model'                 => $this->veh_model,
            'veh_type'                  => $this->veh_type,
            'veh_year'                  => $this->veh_year,
            'veh_colour'                => $this->veh_colour,
            'veh_reg_no'                => $this->veh_reg_no,
            'veh_vin'                   => $this->veh_vin,
            'veh_engine_no'             => $this->veh_engine_no,
            'veh_country_reg'           => $this->veh_country_reg,
            'veh_owner'                 => $this->veh_owner,
            'veh_driver'                => $this->veh_driver,
            'veh_owner_contact'         => $this->veh_owner_contact,
            'veh_docs_available'        => $this->veh_docs_available,
            'veh_doc_ref'               => $this->veh_doc_ref,
            'veh_condition'             => $this->veh_condition,
            'veh_fuel'                  => $this->veh_fuel,
            'veh_keys_recv'             => $this->veh_keys_recv,
            'veh_key_count'             => $this->veh_key_count,
            'veh_damage'                => $this->veh_damage,
            'veh_mods'                  => $this->veh_mods,
            'veh_features'              => $this->veh_features,
            'wpn_model'                 => $this->wpn_model,
            'wpn_serial_no'             => $this->wpn_serial_no,
            'wpn_calibre'               => $this->wpn_calibre,
            'wpn_colour'                => $this->wpn_colour,
            'wpn_size'                  => $this->wpn_size,
            'wpn_marks'                 => $this->wpn_marks,
            'wpn_location_found'        => $this->wpn_location_found,
            'wpn_loaded_status'         => $this->wpn_loaded_status,
            'wpn_condition'             => $this->wpn_condition,
            'dev_model'                 => $this->dev_model,
            'dev_serial_no'             => $this->dev_serial_no,
            'dev_imei1'                 => $this->dev_imei1,
            'dev_imei2'                 => $this->dev_imei2,
            'dev_mac_address'           => $this->dev_mac_address,
            'dev_storage'               => $this->dev_storage,
            'dev_colour'                => $this->dev_colour,
            'dev_marks'                 => $this->dev_marks,
            'ammunition_details'        => $ammo,
            'ammo_cartridge_appearance' => $ammo['cartridge_appearance'] ?? null,
            'ammo_projectile_type'      => $ammo['projectile_bullet_type'] ?? ($ammo['projectile_type'] ?? null),
            'ammo_case_material'        => $ammo['cartridge_case_material'] ?? ($ammo['case_material'] ?? null),
            'ammo_colour_markings'      => $ammo['colour_markings'] ?? null,
            'ammo_condition'            => $ammo['condition'] ?? null,
            'ammo_other_condition'      => $ammo['other_condition'] ?? null,
            'ammo_packaging_type'       => $ammo['packaging_type'] ?? null,
            'ammo_seal_condition'       => $ammo['seal_tamper_evident_condition'] ?? ($ammo['seal_condition'] ?? null),
            'ammo_package_reference_number' => $ammo['package_reference_number'] ?? null,
            'ammo_condition_when_received'  => $ammo['condition_when_received'] ?? null,
            'evidence_photos'           => $this->resolveEvidencePhotosArray(),
            'display_text'              => $displayText,
            'preliminary_report'        => $attachedReport,
            'has_preliminary_report'    => $attachedReport !== null,
            'dpf_status_report'         => $attachedReport && ($attachedReport['report_type'] ?? null) === 'dpf_status_log' ? $attachedReport : null,
            'has_dpf_status_report'     => $attachedReport !== null && ($attachedReport['report_type'] ?? null) === 'dpf_status_log',
            'is_selectable'             => $attachedReport === null,
        ];
    }

    /**
     * Helper to get count of exhibits pending QR code or classification.
     */
    public static function countPendingQrOrClassification(): int
    {
        return static::query()->needsQrOrClassification()->count();
    }

    /**
     * Canonicalize a unit name or create-view identifier into a domain key.
     */
    public static function normalizeCreateUnitKey(?string $unitName): string
    {
        $low = strtolower(trim((string) $unitName));
        if ($low === '') {
            return '';
        }
        if (str_contains($low, 'lab')) {
            return 'lab';
        }
        if (str_contains($low, 'digital') || str_contains($low, 'physical')) {
            return 'digital';
        }
        if (str_contains($low, 'evidence') || str_contains($low, 'custod')) {
            return 'evidence';
        }
        if (str_contains($low, 'crime') || str_contains($low, 'scene') || str_contains($low, 'field')) {
            return 'crime_scene';
        }
        return $low;
    }

    /**
     * Resolve the unit name that originally entered this exhibit, if recorded.
     */
    public function enteredByUnitName(): string
    {
        $caseUnit = $this->singleSuspectCase?->unit_name
            ?? $this->multipleSuspectCase?->unit_name
            ?? null;
        $creatorUnit = $this->singleSuspectCase?->user?->unit
            ?? $this->multipleSuspectCase?->user?->unit
            ?? null;

        return trim((string) ($caseUnit ?: ($this->source_requesting_unit ?: ($creatorUnit ?: ''))));
    }

    /**
     * Determine whether this exhibit is drug-related via the canonical classifier.
     */
    public function isDrugRelated(): bool
    {
        return app(\App\Services\DrugExhibitClassifier::class)->isDrugExhibit($this);
    }

    /**
     * Determine whether this exhibit is electronic-device-related.
     */
    public function isElectronicDeviceRelated(): bool
    {
        if (
            !empty($this->dev_model)
            || !empty($this->dev_imei1)
            || !empty($this->dev_imei2)
            || !empty($this->dev_serial_no)
            || !empty($this->dev_mac_address)
        ) {
            return true;
        }

        $cat = strtolower(trim((string) $this->exhibit_category));
        $type = strtolower(trim((string) $this->exhibit_type));

        if (str_contains($cat, 'weapon') || str_contains($cat, 'firearm') || str_contains($cat, 'ammunition')) {
            return false;
        }

        foreach (['electronic', 'device', 'digital', 'phone', 'mobile', 'laptop', 'computer', 'sim card', 'storage'] as $kw) {
            if (str_contains($cat, $kw) || str_contains($type, $kw)) {
                return true;
            }
        }

        if ($this->isDrugRelated()) {
            return false;
        }

        $name = strtolower(trim((string) $this->commodity_name));
        if ($name === '') {
            return false;
        }

        foreach (['phone', 'mobile', 'iphone', 'samsung', 'laptop', 'macbook', 'tablet', 'ipad', 'hard drive', 'hdd', 'ssd', 'usb drive', 'flash drive', 'memory card', 'sim card', 'router', 'drone', 'camera', 'desktop', 'smartwatch', 'computer'] as $kw) {
            if (str_contains($name, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scope unassigned/no-suspect exhibits strictly to those appropriate for the target unit's create view:
     * - Lab Analysis: only drug-related exhibits
     * - Digital Forensics: only electronic-device-related exhibits
     * - Other units: exclude drug-related and electronic-device-related exhibits
     */
    public function scopeForCreateUnit($query, ?string $targetUnit)
    {
        $unitKey = static::normalizeCreateUnitKey($targetUnit);
        if ($unitKey === '') {
            return $query;
        }

        $knownSubstances = \App\Services\DrugExhibitClassifier::KNOWN_CONTROLLED_SUBSTANCES;

        $applyDrugFilter = function ($q) use ($knownSubstances) {
            $q->whereRaw("LOWER(COALESCE(exhibit_category, '')) NOT LIKE '%non-drug%'")
              ->whereRaw("LOWER(COALESCE(exhibit_type, '')) NOT LIKE '%non-drug%'")
              ->whereRaw("LOWER(COALESCE(exhibit_category, '')) NOT LIKE '%electronic%'")
              ->whereRaw("LOWER(COALESCE(exhibit_category, '')) NOT LIKE '%device%'")
              ->whereRaw("LOWER(COALESCE(exhibit_category, '')) NOT LIKE '%digital%'")
              ->whereRaw("LOWER(COALESCE(exhibit_category, '')) NOT LIKE '%weapon%'")
              ->whereRaw("LOWER(COALESCE(exhibit_category, '')) NOT LIKE '%vehicle%'")
              ->whereRaw("LOWER(COALESCE(exhibit_category, '')) NOT LIKE '%currency%'")
              ->where(function ($match) use ($knownSubstances) {
                  $match->whereRaw("LOWER(COALESCE(exhibit_category, '')) LIKE '%drug%'")
                        ->orWhereRaw("LOWER(COALESCE(exhibit_category, '')) LIKE '%narcotic%'")
                        ->orWhereRaw("LOWER(COALESCE(exhibit_type, '')) LIKE '%drug%'")
                        ->orWhereRaw("TRIM(COALESCE(drug_classification, '')) != ''");

                  foreach ($knownSubstances as $substance) {
                      $pattern = '%' . strtolower($substance) . '%';
                      $match->orWhereRaw("LOWER(COALESCE(commodity_name, '')) LIKE ?", [$pattern])
                            ->orWhereRaw("LOWER(COALESCE(contents, '')) LIKE ?", [$pattern])
                            ->orWhereRaw("LOWER(COALESCE(description_of_exhibits, '')) LIKE ?", [$pattern]);
                  }
              });
        };

        $applyElectronicFilter = function ($q) {
            $q->where(function ($match) {
                $match->whereRaw("TRIM(COALESCE(dev_model, '')) != ''")
                      ->orWhereRaw("TRIM(COALESCE(dev_imei1, '')) != ''")
                      ->orWhereRaw("TRIM(COALESCE(dev_imei2, '')) != ''")
                      ->orWhereRaw("TRIM(COALESCE(dev_serial_no, '')) != ''")
                      ->orWhereRaw("TRIM(COALESCE(dev_mac_address, '')) != ''")
                      ->orWhere(function ($catMatch) {
                          $catMatch->whereRaw("LOWER(COALESCE(exhibit_category, '')) NOT LIKE '%weapon%'")
                                   ->whereRaw("LOWER(COALESCE(exhibit_category, '')) NOT LIKE '%firearm%'")
                                   ->whereRaw("LOWER(COALESCE(exhibit_category, '')) NOT LIKE '%ammunition%'")
                                   ->where(function ($inner) {
                                       foreach (['electronic', 'device', 'digital', 'phone', 'mobile', 'laptop', 'computer', 'sim card', 'storage'] as $kw) {
                                           $pattern = '%' . $kw . '%';
                                           $inner->orWhereRaw("LOWER(COALESCE(exhibit_category, '')) LIKE ?", [$pattern])
                                                 ->orWhereRaw("LOWER(COALESCE(exhibit_type, '')) LIKE ?", [$pattern]);
                                       }
                                   });
                      })
                      ->orWhere(function ($nameMatch) {
                          $nameMatch->whereRaw("LOWER(COALESCE(exhibit_category, '')) NOT LIKE '%drug%'")
                                    ->whereRaw("LOWER(COALESCE(exhibit_category, '')) NOT LIKE '%narcotic%'")
                                    ->whereRaw("LOWER(COALESCE(exhibit_category, '')) NOT LIKE '%weapon%'")
                                    ->where(function ($inner) {
                                        foreach (['phone', 'mobile', 'iphone', 'samsung', 'laptop', 'macbook', 'ipad', 'hard drive', 'hdd', 'ssd', 'usb drive', 'flash drive', 'memory card', 'sim card', 'router', 'drone', 'camera', 'desktop', 'smartwatch', 'computer'] as $kw) {
                                            $inner->orWhereRaw("LOWER(COALESCE(commodity_name, '')) LIKE ?", ['%' . $kw . '%']);
                                        }
                                    });
                      });
            });
        };

        $applyHasCompletedPreliminaryReport = function ($q) {
            $q->where(function ($caseMatch) {
                $caseMatch->whereIn('single_suspect_case_id', function ($sub) {
                    $sub->select('single_suspect_case_id')
                        ->from('suspect_preliminary_reports')
                        ->whereNotNull('single_suspect_case_id')
                        ->where(function ($s) {
                            $s->where('status', 'active')->orWhereNull('status');
                        });
                })->orWhereIn('single_suspect_case_id', function ($sub) {
                    $sub->select('ssc.id')
                        ->from('single_suspect_cases as ssc')
                        ->join('suspect_preliminary_reports as spr', 'spr.case_number', '=', 'ssc.case_number')
                        ->where(function ($s) {
                            $s->where('spr.status', 'active')->orWhereNull('spr.status');
                        });
                });
            })->where(function ($labCompletedMatch) {
                $labCompletedMatch->whereHas('labResult', function ($lq) {
                    $lq->where('has_saved', true);
                })->orWhereIn('single_suspect_case_id', function ($sub) {
                    $sub->select('single_suspect_case_id')
                        ->from('laboratory_examination_results')
                        ->whereNotNull('single_suspect_case_id')
                        ->where('has_saved', true);
                })->orWhereHas('singleSuspectCase', function ($cq) {
                    $cq->whereRaw("LOWER(COALESCE(unit_name, '')) LIKE '%lab%'");
                });
            });
        };

        $applyHasCompletedDpfReport = function ($q) use ($applyElectronicFilter) {
            $q->where($applyElectronicFilter)->where(function ($caseMatch) {
                $caseMatch->whereIn('single_suspect_case_id', function ($sub) {
                    $sub->select('single_suspect_case_id')
                        ->from('dpf_report')
                        ->whereNotNull('single_suspect_case_id')
                        ->where(function ($s) {
                            $s->where('status', 'active')->orWhereNull('status');
                        });
                })->orWhereIn('single_suspect_case_id', function ($sub) {
                    $sub->select('ssc.id')
                        ->from('single_suspect_cases as ssc')
                        ->join('dpf_report as dr', 'dr.case_number', '=', 'ssc.case_number')
                        ->where(function ($s) {
                            $s->where('dr.status', 'active')->orWhereNull('dr.status');
                        });
                });
            });
        };

        $applyHasCompletedReport = function ($q) use ($applyHasCompletedPreliminaryReport, $applyHasCompletedDpfReport) {
            $q->where($applyHasCompletedPreliminaryReport)->orWhere($applyHasCompletedDpfReport);
        };

        if ($unitKey === 'lab') {
            return $query->where(function ($q) use ($applyDrugFilter, $applyHasCompletedPreliminaryReport) {
                $q->where($applyDrugFilter)->orWhere($applyHasCompletedPreliminaryReport);
            });
        }

        if ($unitKey === 'digital') {
            return $query->where(function ($q) use ($applyElectronicFilter, $applyHasCompletedReport) {
                $q->where($applyElectronicFilter)->orWhere($applyHasCompletedReport);
            });
        }

        return $query->where(function ($q) use ($applyDrugFilter, $applyElectronicFilter, $applyHasCompletedReport) {
            $q->where(function ($other) use ($applyDrugFilter, $applyElectronicFilter) {
                $other->whereNot($applyDrugFilter)->whereNot($applyElectronicFilter);
            })->orWhere($applyHasCompletedReport);
        });
    }
}
