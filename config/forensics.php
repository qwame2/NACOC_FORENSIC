<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Narcotics & Controlled Substance Detection Keywords
    |--------------------------------------------------------------------------
    |
    | Keywords used across case intake, exhibit categorization, and laboratory
    | routing to authoritatively detect drug/narcotic related items.
    |
    */
    'substance_keywords' => [
        'drug',
        'narcotic',
        'substance',
        'cannabis',
        'weed',
        'hemp',
        'marijuana',
        'hashish',
        'cocaine',
        'crack',
        'heroin',
        'diacetylmorphine',
        'meth',
        'methamphetamine',
        'tramadol',
        'ephedrine',
        'precursor',
        'opioid',
        'fentanyl',
        'amphetamine',
        'barbiturate',
        'benzodiazepine',
        'synthetic cannabinoid',
        'ecstasy',
        'mdma',
        'ketamine',
        'morphine',
        'codeine',
        'tapentadol',
        'pregabalin',
        'diazepam',
        'methaqualone',
        'rohypnol',
    ],

    /*
    |--------------------------------------------------------------------------
    | Service & Identification Prefixes
    |--------------------------------------------------------------------------
    |
    | Official prefixes for officer service badges, case folders, and dockets.
    |
    */
    'service_id' => [
        'prefixes' => ['SD', 'JD', 'SVC'],
        'regex' => '/^(SD|JD|SVC)-[0-9]{3,}$/i',
        'phone_regex' => '/^[0-9]{10}$/',
    ],

    'prefixes' => [
        'case'      => env('FORENSICS_CASE_PREFIX', 'NCC'),
        'dpf'       => env('FORENSICS_DPF_PREFIX', 'DPF'),
        'lab'       => env('FORENSICS_LAB_PREFIX', 'FS'),
        'associate' => env('FORENSICS_ASSOCIATE_PREFIX', 'ASC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Holding & Custody Facilities
    |--------------------------------------------------------------------------
    |
    | Approved custodial centers and evidence vaults across all NACOC divisions.
    |
    */
    'facilities' => [
        'default' => 'NACOC Central Custody Facility',
        'list' => [
            'NACOC Central Custody Facility',
            'NACOC Headquarters Holding Center',
            'NACOC Secure Evidence Vault',
            'Kotoka International Airport Holding Cell',
            'Kumasi Regional Command Custody',
            'Takoradi Harbor Marine Outpost',
            'Tamale Regional Detachment Facility',
            'Aflao Border Post Custody',
            'Elubo Border Post Custody',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload & Media Limits
    |--------------------------------------------------------------------------
    |
    | Maximum file sizes (in kilobytes) and permitted MIME types for forensic media.
    |
    */
    'upload_limits' => [
        'avatar_max_kb'           => env('FORENSICS_MAX_AVATAR_KB', 5120),    // 5 MB
        'signature_max_kb'        => env('FORENSICS_MAX_SIG_KB', 5120),       // 5 MB
        'evidence_photo_max_kb'   => env('FORENSICS_MAX_PHOTO_KB', 10240),    // 10 MB
        'document_max_kb'         => env('FORENSICS_MAX_DOC_KB', 15360),      // 15 MB
        'allowed_image_mimes'     => 'jpeg,jpg,png,webp',
        'allowed_signature_mimes' => 'jpeg,png,jpg,webp,svg',
        'allowed_document_mimes'  => 'pdf,docx,csv,xlsx',
        'allowed_evidence_mimes'  => 'jpeg,jpg,png,webp,pdf,docx,csv',
    ],

    /*
    |--------------------------------------------------------------------------
    | Master Organizational Units
    |--------------------------------------------------------------------------
    |
    | Canonical forensic units mapped to their unique codes and departments.
    |
    */
    'units' => [
        'CSSU' => [
            'name'     => 'Crime Scene Support',
            'code'     => 'CSSU',
            'category' => 'FIELD OPS',
            'role_key' => 'crime_scene',
        ],
        'EMU' => [
            'name'     => 'Evidence Management',
            'code'     => 'EMU',
            'category' => 'CUSTODY',
            'role_key' => 'evidence',
        ],
        'LAU' => [
            'name'     => 'Lab Analysis',
            'code'     => 'LAU',
            'category' => 'LAB',
            'role_key' => 'lab',
        ],
        'DPFU' => [
            'name'     => 'Digital & Physical Forensics',
            'code'     => 'DPFU',
            'category' => 'CYBER & TECH',
            'role_key' => 'digital',
        ],
        'FDIR' => [
            'name'     => 'Forensic Directorate',
            'code'     => 'FDIR',
            'category' => 'EXECUTIVE',
            'role_key' => 'directorate',
        ],
    ],

];
