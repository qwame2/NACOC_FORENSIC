@include('Crime_Scene_Support.partials.report_workspace', [
    'isStaffReport' => true,
    'sidebarUnit' => 'Crime Scene Support',
    'unitDisplayLabel' => 'Crime Scene Support Unit',
    'reportEndpoint' => url('/Crime_Scene_Support/staff/report'),
    'createCaseUrl' => url('/Crime_Scene_Support/staff/create'),
])
