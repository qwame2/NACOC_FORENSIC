@include('Crime_Scene_Support.partials.report_workspace', [
    'isStaffReport' => false,
    'sidebarUnit' => 'Crime Scene Support',
    'unitDisplayLabel' => 'Crime Scene Support Unit',
    'reportEndpoint' => url('/Crime_Scene_Support/report'),
    'createCaseUrl' => url('/Crime_Scene_Support/create'),
])
