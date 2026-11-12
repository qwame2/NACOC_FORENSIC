@php
    $vaultPendingBtnBadge = \App\Models\Exhibit::countPendingQrOrClassification();
    $dashboardConfig = [
        'unitKey'          => 'evidence_management',
        'userUnit'         => 'Evidence Management Unit',
        'unitBadge'        => 'EVIDENCE MANAGEMENT UNIT',
        'unitSubtitle'     => 'Evidence Vault & Custody Headquarters',
        'locationNoun'     => 'Custody & Vault Cases',
        'pageTitle'        => 'NACOC FORENSIC | Evidence Management Unit Command Dashboard',
        'defaultHeadName'  => 'Commanding Officer',
        'headRoleLabel'    => 'Evidence Management Unit Head',
        'officerRoleLabel' => 'Commanding Officer',
        'homeUrl'          => url('/Evidence_Management'),
        'casesUrl'         => url('/cases?from=Evidence+Management'),
        'createUrl'        => url('/Evidence_Management/create'),
        'createLabel'      => 'Exhibit Lodgement (New)',
        'reportUrl'        => url('/Evidence_Management/report'),
        'notificationsUrl' => url('/Evidence_Management/notifications'),
        'defaultUnitFilter'=> 'all',
        'vaultPendingBadge'=> $vaultPendingBtnBadge,
        'tabs' => [
            ['label' => 'Vault Overview',         'url' => url('/Evidence_Management'),               'icon' => 'package-search', 'active' => true],
            ['label' => 'Exhibit Vault',          'url' => url('/Evidence_Management/vault'),         'icon' => 'archive',        'active' => false, 'badge' => $vaultPendingBtnBadge],
            ['label' => 'Exhibit Lodgement (New)','url' => url('/Evidence_Management/create'),        'icon' => 'package-plus',   'active' => false],
            ['label' => 'Vault Case Directory',   'url' => url('/cases?from=Evidence+Management'),    'icon' => 'folder-search',  'active' => false],
            ['label' => 'Chain of Custody Logs',  'url' => url('/history?unit=Evidence+Management'),  'icon' => 'history',        'active' => false],
            ['label' => 'Vault Audit Report',     'url' => url('/Evidence_Management/report'),        'icon' => 'file-text',      'active' => false],
        ],
    ];
@endphp
@include('partials.forensic_dashboard_shell', ['dashboardConfig' => $dashboardConfig])
