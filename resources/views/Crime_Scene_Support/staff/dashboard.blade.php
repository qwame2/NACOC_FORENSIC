@php
    $dashboardConfig = [
        'isStaff'          => true,
        'unitKey'          => 'crime_scene_support',
        'userUnit'         => 'Crime Scene Support Unit',
        'unitBadge'        => 'Crime Scene Support Unit | Staff',
        'unitSubtitle'     => 'Field Operations & Incident Workstation',
        'locationNoun'     => 'Field Seizures',
        'pageTitle'        => 'NACOC FORENSIC | Crime Scene Support Staff Dashboard',
        'defaultHeadName'  => 'Field Staff Investigator',
        'headRoleLabel'    => 'Crime Scene Support Unit Head',
        'officerRoleLabel' => 'Crime Scene Support Staff Officer',
        'homeUrl'          => url('/Crime_Scene_Support/staff/dashboard'),
        'casesUrl'         => url('/Crime_Scene_Support/staff/case'),
        'reviewBaseUrl'    => url('/Crime_Scene_Support/staff/cases'),
        'createUrl'        => url('/Crime_Scene_Support/staff/create'),
        'createLabel'      => 'Create New Case',
        'reportUrl'        => url('/Crime_Scene_Support/staff/report'),
        'notificationsUrl' => null,
        'defaultUnitFilter'=> 'all',
        'tabs' => [
            ['label' => 'Staff Dashboard',       'url' => url('/Crime_Scene_Support/staff/dashboard'), 'icon' => 'map-pin',       'active' => true],
            ['label' => 'Arrest & Seizure (New)','url' => url('/Crime_Scene_Support/staff/create'),    'icon' => 'plus-circle',   'active' => false],
            ['label' => 'Case Register',         'url' => url('/Crime_Scene_Support/staff/case'),      'icon' => 'folder-search', 'active' => false],
            ['label' => 'Incident Reports',      'url' => url('/Crime_Scene_Support/staff/report'),    'icon' => 'file-text',     'active' => false],
        ],
    ];
@endphp
@include('partials.forensic_dashboard_shell', ['dashboardConfig' => $dashboardConfig])

