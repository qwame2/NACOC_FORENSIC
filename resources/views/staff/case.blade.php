@php
    $u = strtolower(auth()->user()->unit ?? (request()->query('from') ?? 'crime'));
    if (str_contains($u, 'lab')) {
        $viewName = 'Lab_Analysis.staff.case';
    } elseif (str_contains($u, 'digital') || str_contains($u, 'physical')) {
        $viewName = 'Digital_Forensics.staff.case';
    } elseif (str_contains($u, 'evidence')) {
        $viewName = 'Evidence_Management.staff.case';
    } elseif (str_contains($u, 'directorate') || str_contains($u, 'head')) {
        $viewName = 'Forensic_Head.staff.case';
    } else {
        $viewName = 'Crime_Scene_Support.staff.case';
    }
@endphp
@include($viewName)

