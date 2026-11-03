{{--
    Evidence Management Unit (EMU) Associate Co-Suspect & Custody Exhibit Dossier View
    Permits custody intake and registration across all forensic exhibit categories.
--}}
@include('cases.associate', [
    'case' => $case,
    'unitContext' => 'Evidence Management',
    'allowedCategories' => ['Drugs', 'Money', 'Gold', 'Vehicles', 'Electronic Devices', 'Weapons', 'Ammunition', 'Other Exhibits'],
    'defaultCategory' => 'Drugs',
])
