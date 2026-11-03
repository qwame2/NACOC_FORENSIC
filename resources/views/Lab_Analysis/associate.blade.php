{{--
    Laboratory Analysis Unit (LAU) Associate Co-Suspect & Drug Exhibit Dossier View
    Restricts exhibit categories strictly to Drug-related commodities.
--}}
@include('cases.associate', [
    'case' => $case,
    'unitContext' => 'Lab Analysis',
    'allowedCategories' => ['Drugs'],
    'defaultCategory' => 'Drugs',
])
