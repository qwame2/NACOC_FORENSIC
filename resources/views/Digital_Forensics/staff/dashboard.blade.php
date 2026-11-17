@php
    $dashboardConfig = [
        'isStaff'          => true,
        'unitKey'          => 'digital_forensics',
        'userUnit'         => 'Digital & Physical Forensic Unit',
        'unitBadge'        => 'Digital & Physical Forensic Unit | Staff',
        'unitSubtitle'     => 'Cyber Intelligence & Hardware Forensics',
        'locationNoun'     => 'Cases',
        'pageTitle'        => 'NACOC FORENSIC | Digital & Physical Forensics Staff Dashboard',
        'defaultHeadName'  => 'Cyber Forensic Specialist',
        'headRoleLabel'    => 'Digital & Physical Forensic Unit Head',
        'officerRoleLabel' => 'Digital Forensics Staff Officer',
        'homeUrl'          => url('/Digital_Forensics/staff/dashboard'),
        'casesUrl'         => url('/Digital_Forensics/staff/case'),
        'reviewBaseUrl'    => url('/Digital_Forensics/staff/cases'),
        'createUrl'        => url('/Digital_Forensics/staff/create'),
        'createLabel'      => 'Create New Case',
        'reportUrl'        => url('/Digital_Forensics/staff/report'),
        'notificationsUrl' => null,
        'defaultUnitFilter'=> 'all',
        'tabs' => [
            ['label' => 'Staff Dashboard',                 'url' => url('/Digital_Forensics/staff/dashboard'),       'icon' => 'hard-drive',    'active' => true],
            ['label' => 'Forensic & Investigation Status', 'url' => url('/Digital_Forensics/staff/forensic-status'), 'icon' => 'cpu',           'active' => false],
            ['label' => 'Device Intake (New)',             'url' => url('/Digital_Forensics/staff/create'),          'icon' => 'laptop',        'active' => false],
            ['label' => 'Case Register',                   'url' => url('/Digital_Forensics/staff/case'),            'icon' => 'folder-search', 'active' => false],
            ['label' => 'Unit Reports',                    'url' => url('/Digital_Forensics/staff/report'),          'icon' => 'file-text',     'active' => false],
        ],
    ];
@endphp
@include('partials.forensic_dashboard_shell', ['dashboardConfig' => $dashboardConfig])

