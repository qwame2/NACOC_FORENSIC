{{--
    Crime Scene Support Unit (CSSU) Associate Co-Suspect & Evidence Dossier View
    Tailored to weapons, ammunition, vehicles, and scene physical recovery exhibits.
--}}
@include('cases.associate', [
    'case' => $case,
    'unitContext' => 'Crime Scene Support',
    'allowedCategories' => ['Weapons', 'Ammunition', 'Vehicles', 'Other Exhibits', 'Drugs'],
    'defaultCategory' => 'Weapons',
])
