@php
    $dashboardConfig = [
        'unitKey'          => 'crime_scene_support',
        'userUnit'         => 'Crime Scene Support Unit',
        'unitBadge'        => 'Crime Scene Support Unit',
        'unitSubtitle'     => 'Rapid Field Deployment & Incident Command',
        'locationNoun'     => 'Field Seizures',
        'pageTitle'        => 'NACOC FORENSIC | Crime Scene Support Unit Command Dashboard',
        'defaultHeadName'  => 'Commanding Officer',
        'headRoleLabel'    => 'Crime Scene Support Unit Head',
        'officerRoleLabel' => 'Commanding Officer',
        'homeUrl'          => url('/Crime_Scene_Support/head'),
        'casesUrl'         => url('/cases?from=Crime+Scene+Support'),
        'createUrl'        => url('/Crime_Scene_Support/create'),
        'createLabel'      => 'Arrest & Seizure (New)',
        'reportUrl'        => url('/Crime_Scene_Support/report'),
        'notificationsUrl' => url('/Crime_Scene_Support/notifications'),
        'defaultUnitFilter'=> 'all',
        'tabs' => [
            ['label' => 'Incident Command',     'url' => url('/Crime_Scene_Support/head'),              'icon' => 'map-pin',       'active' => true],
            ['label' => 'Arrest & Seizure (New)','url' => url('/Crime_Scene_Support/create'),            'icon' => 'plus-circle',   'active' => false],
            ['label' => 'Field Case Directory', 'url' => url('/cases?from=Crime+Scene+Support'),        'icon' => 'folder-search', 'active' => false],
            ['label' => 'Field Deployment Logs','url' => url('/history?unit=Crime+Scene+Support'),      'icon' => 'crosshair',     'active' => false],
            ['label' => 'Incident Reports',     'url' => url('/Crime_Scene_Support/report'),            'icon' => 'file-text',     'active' => false],
        ],
    ];
@endphp
@include('partials.forensic_dashboard_shell', ['dashboardConfig' => $dashboardConfig])
