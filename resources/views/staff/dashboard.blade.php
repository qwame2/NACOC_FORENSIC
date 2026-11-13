@php
    $u = strtolower(auth()->user()->unit ?? (request()->query('from') ?? 'crime'));
    if (str_contains($u, 'lab')) {
        $viewName = 'Lab_Analysis.staff.dashboard';
    } elseif (str_contains($u, 'digital') || str_contains($u, 'physical')) {
        $viewName = 'Digital_Forensics.staff.dashboard';
    } elseif (str_contains($u, 'evidence')) {
        $viewName = 'Evidence_Management.staff.dashboard';
    } elseif (str_contains($u, 'directorate') || str_contains($u, 'head')) {
        $viewName = 'Forensic_Head.staff.dashboard';
    } else {
        $viewName = 'Crime_Scene_Support.staff.dashboard';
    }
@endphp
@include($viewName)

