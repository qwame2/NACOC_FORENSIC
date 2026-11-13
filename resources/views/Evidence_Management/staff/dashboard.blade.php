@php
    $vaultPendingBtnBadge = \App\Models\Exhibit::countPendingQrOrClassification();
    $dashboardConfig = [
        'isStaff'          => true,
        'unitKey'          => 'evidence_management',
        'userUnit'         => 'Evidence Management Unit',
        'unitBadge'        => 'EVIDENCE MANAGEMENT UNIT • STAFF',
        'unitSubtitle'     => 'Evidence Vault & Custody Staff Workstation',
        'locationNoun'     => 'Custody & Vault Cases',
        'pageTitle'        => 'NACOC FORENSIC | Evidence Management Staff Dashboard',
        'defaultHeadName'  => 'Vault Staff Custodian',
        'headRoleLabel'    => 'Evidence Management Unit Head',
        'officerRoleLabel' => 'Evidence Management Staff Officer',
        'homeUrl'          => url('/Evidence_Management/staff/dashboard'),
        'casesUrl'         => url('/Evidence_Management/staff/case'),
        'reviewBaseUrl'    => url('/Evidence_Management/staff/cases'),
        'createUrl'        => url('/Evidence_Management/staff/create'),
        'createLabel'      => 'Create New Case',
        'reportUrl'        => url('/Evidence_Management/staff/report'),
        'notificationsUrl' => null,
        'defaultUnitFilter'=> 'all',
        'vaultPendingBadge'=> $vaultPendingBtnBadge,
        'tabs' => [
            ['label' => 'Staff Dashboard',         'url' => url('/Evidence_Management/staff/dashboard'), 'icon' => 'package-search', 'active' => true],
            ['label' => 'Exhibit Lodgement (New)', 'url' => url('/Evidence_Management/staff/create'),    'icon' => 'package-plus',   'active' => false],
            ['label' => 'Case Register',           'url' => url('/Evidence_Management/staff/case'),      'icon' => 'folder-search',  'active' => false],
            ['label' => 'Vault Audit Report',      'url' => url('/Evidence_Management/staff/report'),    'icon' => 'file-text',      'active' => false],
        ],
    ];
@endphp
@include('partials.forensic_dashboard_shell', ['dashboardConfig' => $dashboardConfig])

