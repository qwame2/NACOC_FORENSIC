<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exhibit extends Model
{
    use HasFactory;

    protected $table = 'exhibits';

    protected $fillable = [
        'case_type',
        'single_suspect_case_id',
        'multiple_suspect_case_id',
        'suspect_id',
        'associated_person',
        'exhibit_number',
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
        'wpn_model',
        'wpn_serial_no',
        'wpn_calibre',
        'wpn_colour',
        'wpn_size',
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
        'other_exhibit_description',
    ];

    protected $casts = [
        'evidence_photos'    => 'array',
        'ammunition_details' => 'array',
    ];

    public function singleCase()
    {
        return $this->belongsTo(SingleSuspectCase::class, 'single_suspect_case_id');
    }

    public function multipleCase()
    {
        return $this->belongsTo(MultipleSuspectCase::class, 'multiple_suspect_case_id');
    }

    public function suspect()
    {
        return $this->belongsTo(CaseSuspect::class, 'suspect_id');
    }

    public function labResult()
    {
        return $this->hasOne(LaboratoryExaminationResult::class, 'exhibit_id');
    }
}
