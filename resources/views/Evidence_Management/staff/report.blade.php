@include('Evidence_Management.partials.report_workspace', [
    'isStaffReport' => true,
    'reportEndpoint' => url('/Evidence_Management/staff/report'),
])
