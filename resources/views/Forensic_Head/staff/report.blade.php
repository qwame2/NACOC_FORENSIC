@include('Crime_Scene_Support.partials.report_workspace', [
    'isStaffReport' => true,
    'sidebarUnit' => 'Forensic Directorate',
    'unitDisplayLabel' => 'Forensic Directorate',
    'reportEndpoint' => url('/Forensic_Head/staff/report'),
    'createCaseUrl' => url('/Forensic_Head/staff/create'),
])
