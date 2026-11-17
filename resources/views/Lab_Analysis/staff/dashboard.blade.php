@php
    $dashboardConfig = [
        'isStaff'          => true,
        'unitKey'          => 'lab_analysis',
        'userUnit'         => 'Lab Analysis Unit',
        'unitBadge'        => 'LAB ANALYSIS UNIT | STAFF',
        'unitSubtitle'     => 'Laboratory Analysis Staff Workstation',
        'locationNoun'     => 'Lab Analysis Cases',
        'pageTitle'        => 'NACOC FORENSIC | Lab Analysis Staff Dashboard',
        'defaultHeadName'  => 'Senior Laboratory Chemist',
        'headRoleLabel'    => 'Lab Analysis Unit Head',
        'officerRoleLabel' => 'Lab Analysis Staff Officer',
        'homeUrl'          => url('/Lab_Analysis/staff/dashboard'),
        'casesUrl'         => url('/Lab_Analysis/staff/case'),
        'reviewBaseUrl'    => url('/Lab_Analysis/staff/cases'),
        'createUrl'        => url('/Lab_Analysis/staff/create'),
        'createLabel'      => 'Create New Case',
        'reportUrl'        => url('/Lab_Analysis/staff/report'),
        'notificationsUrl' => null,
        'defaultUnitFilter'=> 'all',
        'tabs' => [
            ['label' => 'Staff Dashboard',       'url' => url('/Lab_Analysis/staff/dashboard'), 'icon' => 'flask-conical', 'active' => true],
            ['label' => 'Chemical Assay Intake', 'url' => url('/Lab_Analysis/staff/create'),    'icon' => 'plus-circle',   'active' => false],
            ['label' => 'Case Register',         'url' => url('/Lab_Analysis/staff/case'),      'icon' => 'folder-search', 'active' => false],
            ['label' => 'Unit Reports',          'url' => url('/Lab_Analysis/staff/report'),    'icon' => 'file-text',     'active' => false],
        ],
    ];
@endphp
@include('partials.forensic_dashboard_shell', ['dashboardConfig' => $dashboardConfig])

