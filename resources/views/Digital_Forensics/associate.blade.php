{{--
    Digital & Physical Forensics Unit (DPFU) Associate Co-Suspect & Device Dossier View
    Restricts exhibit categories strictly to Electronic Devices / Digital Media.
--}}
@include('cases.associate', [
    'case' => $case,
    'unitContext' => 'Digital & Physical Forensic Unit',
    'allowedCategories' => ['Electronic Devices'],
    'defaultCategory' => 'Electronic Devices',
])
