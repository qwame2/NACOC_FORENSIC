@php
    $dashboardConfig = [
        'unitKey'          => 'dg',
        'userUnit'         => 'Office of the Director-General',
        'unitBadge'        => 'OFFICE OF THE DIRECTOR-GENERAL',
        'unitSubtitle'     => 'NACOC National Supreme Command & Executive Directorate',
        'locationNoun'     => 'National Forensic Cases',
        'pageTitle'        => 'NACOC FORENSIC | Office of the Director-General Command Dashboard',
        'defaultHeadName'  => 'Director-General',
        'headRoleLabel'    => 'Director-General (Supreme Command)',
        'officerRoleLabel' => 'National Executive Command Officer',
        'homeUrl'          => url('/DG'),
        'casesUrl'         => url('/DG/cases'),
        'createUrl'        => null,
        'createLabel'      => null,
        'reportUrl'        => null,
        'notificationsUrl' => null,
        'reviewBaseUrl'    => url('/DG/cases'),
        'defaultUnitFilter'=> 'all',
        'tabs' => [
            ['label' => 'Dashboard',     'url' => url('/DG'),       'icon' => 'layout-dashboard', 'active' => true],
            ['label' => 'Case Register', 'url' => url('/DG/cases'), 'icon' => 'folder-kanban',    'active' => false],
        ],
    ];
@endphp
@include('partials.forensic_dashboard_shell', ['dashboardConfig' => $dashboardConfig])
