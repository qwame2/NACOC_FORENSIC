@php
    $dashboardConfig = [
        'unitKey'          => 'forensic_head',
        'userUnit'         => 'Forensic Directorate',
        'unitBadge'        => 'Forensic Directorate',
        'unitSubtitle'     => 'Forensic Directorate Supreme Command',
        'locationNoun'     => 'Forensic Cases',
        'pageTitle'        => 'NACOC FORENSIC | Forensic Directorate Executive Command',
        'defaultHeadName'  => 'Director of Forensics',
        'headRoleLabel'    => 'Director of Forensics (Directorate Head)',
        'officerRoleLabel' => 'Executive Directorate Officer',
        'homeUrl'          => url('/Forensic_Head'),
        'casesUrl'         => url('/Forensic_Head/cases'),
        'createUrl'        => null,
        'createLabel'      => 'Open Master Case Directory',
        'reportUrl'        => null,
        'notificationsUrl' => null,
        'defaultUnitFilter'=> 'all',
        'tabs' => [
            ['label' => 'Executive Command',     'url' => url('/Forensic_Head'),       'icon' => 'layout-dashboard', 'active' => true],
            ['label' => 'Case Management',       'url' => url('/Forensic_Head/cases'), 'icon' => 'folder-kanban',    'active' => false],
            ['label' => 'Master Case Directory', 'url' => url('/cases'),               'icon' => 'folder-search',    'active' => false],
            ['label' => 'Audit & Compliance',    'url' => url('/Report'),              'icon' => 'file-text',        'active' => false],
        ],
    ];
@endphp
@include('partials.forensic_dashboard_shell', ['dashboardConfig' => $dashboardConfig])
