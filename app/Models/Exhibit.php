<?php

namespace App\Models;

use App\Models\Concerns\EnforcesMutuallyExclusiveCases;
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
        'property_id',
        'exhibit_category',
        'exhibit_type',
        'drug_classification',
        'commodity_name',
        'seizure_quantity',
        'seizure_packaging',
        'seizure_weight',
        'seizure_unit',
        'seizure_value',
        'seizure_concealment',
        'seizure_date',
        'seizure_time',
        'seizure_location',
        'seizure_officer',
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
        'qr_token',
    ];

    protected $casts = [
        'evidence_photos'    => 'array',
        'ammunition_details' => 'array',
    ];

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

    protected static function booted()
    {
        static::creating(function ($exhibit) {
            if (empty($exhibit->qr_token)) {
                $exhibit->qr_token = (string) \Illuminate\Support\Str::uuid();
            }
            $exhibit->custodial_status = 'Seized';
        });

        static::saving(function ($exhibit) {
            if (empty($exhibit->custodial_status)) {
                $exhibit->custodial_status = 'Seized';
            }
        });
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
     * Helper to get count of exhibits pending QR code or classification.
     */
    public static function countPendingQrOrClassification(): int
    {
        return static::query()->needsQrOrClassification()->count();
    }
}
