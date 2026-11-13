@php
    $dashboardConfig = [
        'isStaff'          => true,
        'unitKey'          => 'forensic_head',
        'userUnit'         => 'Forensic Directorate',
        'unitBadge'        => 'Forensic Directorate • Staff',
        'unitSubtitle'     => 'Forensic Directorate Staff Operations',
        'locationNoun'     => 'Forensic Cases',
        'pageTitle'        => 'NACOC FORENSIC | Forensic Directorate Staff Dashboard',
        'defaultHeadName'  => 'Directorate Staff Officer',
        'headRoleLabel'    => 'Director of Forensics (Directorate Head)',
        'officerRoleLabel' => 'Directorate Staff Officer',
        'homeUrl'          => url('/Forensic_Head/staff/dashboard'),
        'casesUrl'         => url('/Forensic_Head/staff/case'),
        'reviewBaseUrl'    => url('/Forensic_Head/staff/cases'),
        'createUrl'        => url('/Forensic_Head/staff/create'),
        'createLabel'      => 'Create New Case',
        'reportUrl'        => url('/Forensic_Head/staff/report'),
        'notificationsUrl' => null,
        'defaultUnitFilter'=> 'all',
        'tabs' => [
            ['label' => 'Staff Dashboard', 'url' => url('/Forensic_Head/staff/dashboard'), 'icon' => 'layout-dashboard', 'active' => true],
            ['label' => 'Case Register',   'url' => url('/Forensic_Head/staff/case'),      'icon' => 'folder-search',    'active' => false],
            ['label' => 'Log New Case',    'url' => url('/Forensic_Head/staff/create'),    'icon' => 'plus-circle',      'active' => false],
            ['label' => 'Unit Reports',    'url' => url('/Forensic_Head/staff/report'),    'icon' => 'file-text',        'active' => false],
        ],
    ];
@endphp
@include('partials.forensic_dashboard_shell', ['dashboardConfig' => $dashboardConfig])

