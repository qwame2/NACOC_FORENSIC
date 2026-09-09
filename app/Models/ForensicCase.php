<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForensicCase extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cases';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'case_number',
        'title',
        'unit_name',
        'unit_id',
        'user_id',
        'commanding_officer',
        'submitting_officer',
        'suspect_name',
        'suspect_alias',
        'ghana_card',
        'gender',
        'dob',
        'age',
        'nationality',
        'pob',
        'phone',
        'address',
        'marital_status',
        'has_children',
        'children_count',
        'spouse_info',
        'spouse_name',
        'spouse_phone',
        'partner_name',
        'partner_phone',
        'partner_info',
        'dependents',
        'holding_facility',
        'exhibit_category',
        'valuation',
        'operation_code',
        'operation_code_name',
        'suspect_docket_mode',
        'raid_location',
        'offense_description',
        'arrest_date',
        'arrest_time',
        'has_seizures',
        'seizure_name',
        'seizure_name_drug',
        'seizure_name_currency',
        'seizure_quantity',
        'seizure_unit',
        'seizure_value',
        'seizure_vault',
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
        'evidence_photos',
        'mugshot_front',
        'mugshot_left',
        'mugshot_right',
        'docket_data',
        'priority',
        'status',
        'location',
        'description',
        'exhibits_summary',
        'hash_log',
        'incident_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'docket_data' => 'array',
        'evidence_photos' => 'array',
        'has_children' => 'boolean',
        'has_seizures' => 'boolean',
    ];

    /**
     * Get the user / unit head who entered the case.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the unit associated with the case.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
