@include('Crime_Scene_Support.partials.report_workspace', [
    'isStaffReport' => true,
    'sidebarUnit' => 'Digital Forensics',
    'unitDisplayLabel' => 'Digital & Physical Forensics Unit',
    'reportEndpoint' => url('/Digital_Forensics/staff/report'),
    'createCaseUrl' => url('/Digital_Forensics/staff/create'),
])
