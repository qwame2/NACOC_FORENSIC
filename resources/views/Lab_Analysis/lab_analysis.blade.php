@php
    $dashboardConfig = [
        'unitKey'          => 'lab_analysis',
        'userUnit'         => 'Lab Analysis Unit',
        'unitBadge'        => 'LAB ANALYSIS UNIT',
        'unitSubtitle'     => 'Laboratory Analysis Headquarters',
        'locationNoun'     => 'Lab Analysis Cases',
        'pageTitle'        => 'NACOC FORENSIC | Lab Analysis Unit Command Dashboard',
        'defaultHeadName'  => 'Commanding Officer',
        'headRoleLabel'    => 'Lab Analysis Unit Head',
        'officerRoleLabel' => 'Commanding Officer',
        'homeUrl'          => url('/Lab_Analysis'),
        'casesUrl'         => url('/cases?from=Lab+Analysis'),
        'createUrl'        => url('/Lab_Analysis/create'),
        'createLabel'      => 'Chemical Assay Intake',
        'reportUrl'        => url('/history?unit=Lab+Analysis'),
        'notificationsUrl' => url('/Lab_Analysis/notifications'),
        'defaultUnitFilter'=> 'all',
        'tabs' => [
            ['label' => 'Lab Command',               'url' => url('/Lab_Analysis'),             'icon' => 'flask-conical', 'active' => true],
            ['label' => 'Chemical Assay Intake',     'url' => url('/Lab_Analysis/create'),      'icon' => 'plus-circle',   'active' => false],
            ['label' => 'Analysis Results',          'url' => url('/Lab_Analysis/lab-result'),  'icon' => 'microscope',    'active' => false],
            ['label' => 'Lab Examination Directory', 'url' => url('/cases?from=Lab+Analysis'),  'icon' => 'folder-search', 'active' => false],
            ['label' => 'GSA & Lab Certificates',    'url' => url('/history?unit=Lab+Analysis'),'icon' => 'file-text',     'active' => false],
        ],
    ];
@endphp
@include('partials.forensic_dashboard_shell', ['dashboardConfig' => $dashboardConfig])
