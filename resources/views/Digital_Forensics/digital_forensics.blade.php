@php
    $dashboardConfig = [
        'unitKey'          => 'digital_forensics',
        'userUnit'         => 'Digital & Physical Forensic Unit',
        'unitBadge'        => 'Digital & Physical Forensic Unit',
        'unitSubtitle'     => 'Cyber Intelligence & Hardware Forensics',
        'locationNoun'     => 'Cyber & Device Cases',
        'pageTitle'        => 'NACOC FORENSIC | Digital & Physical Forensic Unit Command Dashboard',
        'defaultHeadName'  => 'Lead Cyber Analyst',
        'headRoleLabel'    => 'Digital & Physical Forensic Unit Head',
        'officerRoleLabel' => 'Commanding Officer',
        'homeUrl'          => url('/Digital_Forensics'),
        'casesUrl'         => url('/Digital_Forensics/cases'),
        'createUrl'        => url('/Digital_Forensics/create'),
        'createLabel'      => 'Device Intake (New)',
        'reportUrl'        => url('/Report?unit=Digital+Forensics'),
        'notificationsUrl' => url('/Digital_Forensics/notifications'),
        'defaultUnitFilter'=> 'all',
        'tabs' => [
            ['label' => 'Cyber Command',                    'url' => url('/Digital_Forensics'),                 'icon' => 'hard-drive',    'active' => true],
            ['label' => 'Device Intake (New)',              'url' => url('/Digital_Forensics/create'),          'icon' => 'laptop',        'active' => false],
            ['label' => 'Forensic & Investigation Status',  'url' => url('/Digital_Forensics/forensic-status'), 'icon' => 'cpu',           'active' => false],
            ['label' => 'Cyber Case Register',              'url' => url('/Digital_Forensics/cases'),           'icon' => 'folder-search', 'active' => false],
            ['label' => 'Hardware & Hash Reports',          'url' => url('/Report?unit=Digital+Forensics'),     'icon' => 'file-text',     'active' => false],
        ],
    ];
@endphp
@include('partials.forensic_dashboard_shell', ['dashboardConfig' => $dashboardConfig])
