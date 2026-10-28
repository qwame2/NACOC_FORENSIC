<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Drug classification keywords
    |--------------------------------------------------------------------------
    | WHY: These keyword lists were duplicated across labResultPage(),
    | buildLabSitrepDataset(), and store(). Centralizing them prevents
    | divergence and makes updates a one-line change.
    */
    'drug_keywords' => [
        'drug', 'narcotic', 'substance', 'cannabis', 'cocaine', 'heroin',
        'meth', 'tramadol', 'weed', 'hemp', 'hashish', 'ephedrine',
        'precursor', 'opioid', 'fentanyl', 'amphetamine', 'mdma', 'ecstasy',
        'morphine', 'opium', 'diacetylmorphine', 'codeine', 'syrup',
        'marijuana', 'thc', 'brown sugar', 'prohibited',
    ],

    'non_drug_keywords' => [
        'truck', 'vehicle', 'car', 'pistol', 'rifle', 'weapon', 'currency',
        'zonda', 'cash', 'usd', 'ghs', 'laptop', 'phone', 'automobile',
        'vessel', 'device', 'motorcycle', 'boat', 'firearm', 'electronics',
        'document',
    ],

    'drug_specific_keywords' => [
        'cocaine', 'heroin', 'cannabis', 'weed', 'meth', 'tramadol',
        'hashish', 'thc', 'codeine', 'amphetamine', 'mdma', 'ecstasy',
        'morphine', 'opium', 'diacetylmorphine', 'brown sugar', 'fentanyl',
    ],
];
