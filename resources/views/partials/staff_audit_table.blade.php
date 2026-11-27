@include('partials.staff_audit_print_styles')
@include('partials.staff_audit_print_header')

<div class="overflow-x-auto custom-scrollbar">
    <table class="rep-table w-full min-w-[900px] text-left border-collapse">
        <thead>
            <tr>
                <th class="py-3.5 px-5 col-timestamp">Timestamp</th>
                <th class="py-3.5 px-4 col-officer">Staff Officer</th>
                <th class="py-3.5 px-4 col-action no-print">Action &amp; Method</th>
                <th class="py-3.5 px-4 min-w-[300px] col-description">Activity Description</th>
                <th class="py-3.5 px-5 col-route no-print">Endpoint &amp; IP</th>
            </tr>
        </thead>
        <tbody id="staffAuditTableBody" class="divide-y divide-slate-100 text-xs font-medium text-slate-800 bg-white">
            @forelse($logs as $log)
                @php
                    $method = strtoupper((string) ($log->method ?? 'GET'));
                    $actionStr = strtoupper((string) ($log->action ?? 'ACTION'));
                    $statusCode = (int) ($log->status_code ?? 200);

                    $initials = collect(explode(' ', trim((string) ($log->staff_name ?? 'Staff'))))
                        ->filter()
                        ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                        ->take(2)
                        ->implode('');

                    $actionBadgeClass = match (true) {
                        in_array($actionStr, ['CREATE', 'APPROVE', 'LOGIN'], true) => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                        in_array($actionStr, ['UPDATE', 'EDIT'], true) => 'bg-teal-50 text-teal-800 border-teal-200',
                        in_array($actionStr, ['DELETE', 'DECLINE', 'REVOKE'], true) => 'bg-rose-50 text-rose-800 border-rose-200',
                        in_array($actionStr, ['ROLLBACK'], true) => 'bg-amber-50 text-amber-800 border-amber-200',
                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                    };

                    $methodBadgeClass = match ($method) {
                        'POST'   => 'text-emerald-700 bg-emerald-50/70 border-emerald-200/80',
                        'PUT', 'PATCH' => 'text-amber-700 bg-amber-50/70 border-amber-200/80',
                        'DELETE' => 'text-rose-700 bg-rose-50/70 border-rose-200/80',
                        default  => 'text-slate-600 bg-slate-50 border-slate-200/80',
                    };

                    $payloadData = is_array($log->payload) ? $log->payload : (json_decode($log->payload ?? '[]', true) ?: []);
                    $pCase = $payloadData['case_number'] ?? $payloadData['case_ref'] ?? $payloadData['case_id'] ?? null;
                    $pSuspect = $payloadData['suspect_name'] ?? $payloadData['fullname'] ?? null;
                    $pExhibit = $payloadData['exhibit_number'] ?? $payloadData['property_id'] ?? null;
                    $pCategory = $payloadData['exhibit_category'] ?? $payloadData['category'] ?? null;
                    $pTransfer = $payloadData['transfer_to'] ?? $payloadData['transfer_unit'] ?? $payloadData['custody_to'] ?? null;

                    $cleanRoute = '/' . ltrim((string) ($log->route ?? ''), '/');
                @endphp
                <tr class="group">
                    <!-- 1. TIMESTAMP -->
                    <td class="py-3.5 px-5 whitespace-nowrap col-timestamp align-top">
                        <div class="flex flex-col">
                            <span class="font-extrabold text-slate-900 text-xs font-mono-code tnum">
                                {{ $log->created_at ? $log->created_at->format('d M Y, H:i:s') : '—' }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono-code mt-0.5 no-print">
                                {{ $log->created_at ? $log->created_at->diffForHumans() : '' }}
                            </span>
                        </div>
                    </td>

                    <!-- 2. STAFF OFFICER -->
                    <td class="py-3.5 px-4 col-officer align-top">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-800 font-mono-code font-extrabold flex items-center justify-center text-[11px] shrink-0 border border-emerald-200/90 no-print">
                                {{ $initials ?: 'ST' }}
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-extrabold text-slate-900 text-xs truncate max-w-[180px]" title="{{ $log->staff_name ?: 'Unknown Staff' }}">
                                    {{ $log->staff_name ?: 'Unknown Staff' }}
                                </span>
                                <div class="flex items-center gap-1.5 text-[10px] text-slate-500 mt-0.5 flex-wrap">
                                    @if($log->service_id)
                                        <span class="font-mono-code font-bold text-slate-700 bg-slate-100 px-1.5 py-0.2 rounded border border-slate-200/80">{{ $log->service_id }}</span>
                                    @endif
                                    @if($log->unit)
                                        <span class="text-[10px] text-slate-500 truncate max-w-[140px]">{{ $log->unit }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- 3. ACTION & METHOD (SCREEN ONLY) -->
                    <td class="py-3.5 px-4 whitespace-nowrap col-action no-print align-top">
                        <div class="flex flex-col items-start gap-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-mono-code font-extrabold uppercase border {{ $actionBadgeClass }}">
                                {{ $actionStr }}
                            </span>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[9.5px] font-mono-code font-bold border {{ $methodBadgeClass }}">
                                <span>{{ $method }}</span>
                                <span class="text-slate-300">&bull;</span>
                                <span class="tnum">{{ $statusCode }}</span>
                            </span>
                        </div>
                    </td>

                    <!-- 4. ACTIVITY DESCRIPTION -->
                    <td class="py-3.5 px-4 col-description align-top">
                        <div class="space-y-1.5">
                            <p class="text-xs text-slate-900 leading-relaxed font-semibold audit-desc-text">
                                {{ $log->description ?: 'Operational activity performed on page.' }}
                            </p>
                            @if($pCase || $pSuspect || $pExhibit || $pCategory || $pTransfer)
                                <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                    @if($pCase)
                                        <span class="audit-context-tag inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-mono-code font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                            Case: #{{ $pCase }}
                                        </span>
                                    @endif
                                    @if($pSuspect)
                                        <span class="audit-context-tag inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Suspect: {{ $pSuspect }}
                                        </span>
                                    @endif
                                    @if($pExhibit)
                                        <span class="audit-context-tag inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-mono-code font-bold bg-teal-50 text-teal-800 border border-teal-200">
                                            Exhibit: {{ $pExhibit }}
                                        </span>
                                    @endif
                                    @if($pCategory)
                                        <span class="audit-context-tag inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            Category: {{ $pCategory }}
                                        </span>
                                    @endif
                                    @if($pTransfer)
                                        <span class="audit-context-tag inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                            Transfer: {{ $pTransfer }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </td>

                    <!-- 5. ENDPOINT & IP (SCREEN ONLY) -->
                    <td class="py-3.5 px-5 col-route no-print align-top">
                        <div class="flex flex-col max-w-[210px]">
                            <span class="font-mono-code text-[10.5px] font-semibold text-slate-700 truncate" title="{{ $cleanRoute }}">
                                {{ $cleanRoute }}
                            </span>
                            <span class="font-mono-code text-[10px] text-slate-400 tnum mt-0.5">
                                IP: {{ $log->ip_address ?: '—' }}
                            </span>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-14 px-4 text-center">
                        <div class="flex flex-col items-center justify-center space-y-2.5">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200/90 text-slate-400 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-extrabold text-slate-800 font-mono-code uppercase tracking-wider">No Staff Audit Activities Found</span>
                            <p class="text-xs text-slate-500 max-w-sm">No activity logs matched your active search or filter parameters.</p>
                            <button type="button" onclick="resetAuditFilters()" class="rep-btn-green mt-1 px-3.5 py-1.5 rounded-lg text-[11px] font-bold font-mono-code cursor-pointer">
                                Reset Filter Console
                            </button>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.staff_audit_print_footer')

<!-- PAGINATION & METRICS FOOTER -->
@if($logs->total() > 0)
    <div class="px-5 py-3.5 bg-slate-50/90 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600 no-print">
        <div class="flex items-center gap-2 font-mono-code tnum">
            <span>Showing <strong class="text-slate-900 font-extrabold">{{ $logs->firstItem() ?? 0 }}</strong>&ndash;<strong class="text-slate-900 font-extrabold">{{ $logs->lastItem() ?? 0 }}</strong> of <strong class="text-slate-900 font-extrabold">{{ number_format($logs->total()) }}</strong> audit records</span>
            @if($logs->lastPage() > 1)
                <span class="text-slate-300">&bull;</span>
                <span class="text-[11px] text-slate-500 font-bold">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>
            @endif
        </div>

        @if($logs->hasPages())
            <nav class="flex items-center gap-1 audit-pagination font-mono-code" aria-label="Staff audit table pagination">
                @if ($logs->onFirstPage())
                    <button type="button" disabled class="px-2.5 py-1.5 text-xs font-bold rounded-lg border border-slate-200 bg-slate-100 text-slate-400 cursor-not-allowed inline-flex items-center">
                        Prev
                    </button>
                @else
                    <button type="button" onclick="goToAuditPage({{ $logs->currentPage() - 1 }}, event)" class="rep-btn-green-soft px-2.5 py-1.5 text-xs font-bold rounded-lg inline-flex items-center cursor-pointer">
                        Prev
                    </button>
                @endif

                @php
                    $current = $logs->currentPage();
                    $last = $logs->lastPage();
                    $start = max(1, $current - 2);
                    $end = min($last, $current + 2);
                @endphp

                @if($start > 1)
                    <button type="button" onclick="goToAuditPage(1, event)" class="rep-btn-green-soft px-2.5 py-1.5 text-xs font-bold rounded-lg cursor-pointer tnum">1</button>
                    @if($start > 2)
                        <span class="px-1 text-slate-400">&hellip;</span>
                    @endif
                @endif

                @for ($p = $start; $p <= $end; $p++)
                    @if ($p == $current)
                        <span class="rep-btn-green px-3 py-1.5 text-xs font-extrabold rounded-lg tnum">{{ $p }}</span>
                    @else
                        <button type="button" onclick="goToAuditPage({{ $p }}, event)" class="rep-btn-green-soft px-2.5 py-1.5 text-xs font-bold rounded-lg cursor-pointer tnum">{{ $p }}</button>
                    @endif
                @endfor

                @if($end < $last)
                    @if($end < $last - 1)
                        <span class="px-1 text-slate-400">&hellip;</span>
                    @endif
                    <button type="button" onclick="goToAuditPage({{ $last }}, event)" class="rep-btn-green-soft px-2.5 py-1.5 text-xs font-bold rounded-lg cursor-pointer tnum">{{ $last }}</button>
                @endif

                @if ($logs->hasMorePages())
                    <button type="button" onclick="goToAuditPage({{ $logs->currentPage() + 1 }}, event)" class="rep-btn-green-soft px-2.5 py-1.5 text-xs font-bold rounded-lg inline-flex items-center cursor-pointer">
                        Next
                    </button>
                @else
                    <button type="button" disabled class="px-2.5 py-1.5 text-xs font-bold rounded-lg border border-slate-200 bg-slate-100 text-slate-400 cursor-not-allowed inline-flex items-center">
                        Next
                    </button>
                @endif
            </nav>
        @endif
    </div>
@endif
