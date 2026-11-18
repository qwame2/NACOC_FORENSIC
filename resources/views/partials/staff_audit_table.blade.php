<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50/75 text-[11px] font-extrabold uppercase tracking-wider text-slate-600">
                <th class="py-3.5 px-4">Timestamp</th>
                <th class="py-3.5 px-4">Staff Officer</th>
                <th class="py-3.5 px-4">Unit &amp; Role</th>
                <th class="py-3.5 px-4">Action &amp; Method</th>
                <th class="py-3.5 px-4 min-w-[240px]">Activity Description</th>
                <th class="py-3.5 px-4">Route &amp; Status</th>
                <th class="py-3.5 px-4 text-right">Details</th>
            </tr>
        </thead>
        <tbody id="staffAuditTableBody" class="divide-y divide-slate-200/80 text-xs font-medium text-slate-800 bg-white">
            @forelse($logs as $log)
                @php
                    $method = strtoupper((string) ($log->method ?? 'GET'));
                    $methodBadge = match($method) {
                        'POST'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'PUT', 'PATCH' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'DELETE' => 'bg-rose-50 text-rose-700 border-rose-200',
                        default  => 'bg-slate-100 text-slate-700 border-slate-200',
                    };

                    $actionStr = strtoupper((string) ($log->action ?? 'ACTION'));
                    $actionBadge = match(true) {
                        str_contains($actionStr, 'APPROVE') => 'bg-teal-50 text-teal-800 border-teal-200',
                        str_contains($actionStr, 'DECLINE') || str_contains($actionStr, 'ROLLBACK') => 'bg-amber-50 text-amber-800 border-amber-200',
                        str_contains($actionStr, 'DELETE') || str_contains($actionStr, 'REMOVE') => 'bg-rose-50 text-rose-800 border-rose-200',
                        str_contains($actionStr, 'UPDATE') || str_contains($actionStr, 'EDIT') => 'bg-blue-50 text-blue-800 border-blue-200',
                        str_contains($actionStr, 'CREATE') || str_contains($actionStr, 'STORE') || str_contains($actionStr, 'LODGE') => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                        default => 'bg-slate-100 text-slate-800 border-slate-200',
                    };

                    $statusCode = (int) ($log->status_code ?? 200);
                    $statusBadge = match(true) {
                        $statusCode >= 200 && $statusCode < 300 => 'text-emerald-700 bg-emerald-50/70 border-emerald-200',
                        $statusCode >= 300 && $statusCode < 400 => 'text-blue-700 bg-blue-50/70 border-blue-200',
                        $statusCode >= 400 && $statusCode < 500 => 'text-amber-700 bg-amber-50/70 border-amber-200',
                        default => 'text-rose-700 bg-rose-50/70 border-rose-200',
                    };

                    $initials = collect(explode(' ', $log->staff_name ?? 'Staff'))
                        ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                        ->take(2)
                        ->implode('');

                    $payloadJson = !empty($log->payload) ? json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null;
                @endphp
                <tr class="hover:bg-slate-50/80 transition-colors group">
                    <!-- TIMESTAMP -->
                    <td class="py-3 px-4 whitespace-nowrap">
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-900 tnum">{{ $log->created_at ? $log->created_at->format('d M Y, H:i:s') : '—' }}</span>
                            <span class="text-[10px] text-slate-600 font-mono-code">{{ $log->created_at ? $log->created_at->diffForHumans() : '' }}</span>
                        </div>
                    </td>

                    <!-- STAFF OFFICER -->
                    <td class="py-3 px-4 whitespace-nowrap">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 font-extrabold flex items-center justify-center text-xs shrink-0 shadow-2xs border border-emerald-200">
                                {{ $initials ?: 'ST' }}
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-extrabold text-slate-900 text-xs truncate">{{ $log->staff_name ?: 'Unknown Staff' }}</span>
                                <div class="flex items-center space-x-1.5 text-[10px] text-slate-600">
                                    @if($log->service_id)
                                        <span class="font-mono-code font-bold text-slate-700 bg-slate-100 px-1 rounded">{{ $log->service_id }}</span>
                                    @endif
                                    @if($log->staff_email)
                                        <span class="truncate max-w-[140px] text-slate-600">{{ $log->staff_email }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- UNIT & ROLE -->
                    <td class="py-3 px-4 whitespace-nowrap">
                        <div class="flex flex-col">
                            <span class="font-extrabold text-slate-800 text-[11px]">{{ $log->unit ?: 'Operational Unit' }}</span>
                            <span class="text-[10px] text-slate-600 font-medium">{{ $log->role ?: 'Staff Officer' }}</span>
                        </div>
                    </td>

                    <!-- ACTION & METHOD -->
                    <td class="py-3 px-4 whitespace-nowrap">
                        <div class="flex items-center space-x-1.5">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono-code font-bold border {{ $methodBadge }}">
                                {{ $method }}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $actionBadge }}">
                                {{ $actionStr }}
                            </span>
                        </div>
                    </td>

                    <!-- ACTIVITY DESCRIPTION -->
                    <td class="py-3 px-4 min-w-[280px]">
                        <div class="space-y-1.5">
                            <p class="text-xs text-slate-900 leading-relaxed font-semibold max-w-lg">
                                {{ $log->description ?: 'Operational activity performed on page.' }}
                            </p>
                            @php
                                $payloadData = is_array($log->payload) ? $log->payload : (json_decode($log->payload ?? '[]', true) ?: []);
                                $pCase = $payloadData['case_number'] ?? $payloadData['case_ref'] ?? $payloadData['case_id'] ?? null;
                                $pSuspect = $payloadData['suspect_name'] ?? $payloadData['fullname'] ?? null;
                                $pExhibit = $payloadData['exhibit_number'] ?? $payloadData['property_id'] ?? null;
                                $pCategory = $payloadData['exhibit_category'] ?? $payloadData['category'] ?? null;
                                $pTransfer = $payloadData['transfer_to'] ?? $payloadData['custody_to'] ?? null;
                            @endphp
                            @if($pCase || $pSuspect || $pExhibit || $pCategory || $pTransfer)
                                <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                    @if($pCase)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono-code font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                            Case: #{{ $pCase }}
                                        </span>
                                    @endif
                                    @if($pSuspect)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Suspect: {{ $pSuspect }}
                                        </span>
                                    @endif
                                    @if($pExhibit)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono-code font-bold bg-teal-50 text-teal-800 border border-teal-200">
                                            Exhibit: {{ $pExhibit }}
                                        </span>
                                    @endif
                                    @if($pCategory)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            Category: {{ $pCategory }}
                                        </span>
                                    @endif
                                    @if($pTransfer)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                            Transfer To: {{ $pTransfer }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </td>

                    <!-- ROUTE & STATUS -->
                    <td class="py-3 px-4 whitespace-nowrap">
                        <div class="flex flex-col space-y-0.5">
                            <div class="flex items-center space-x-1.5">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono-code font-bold border {{ $statusBadge }}">
                                    {{ $statusCode }}
                                </span>
                                <span class="font-mono-code text-[11px] text-slate-700 truncate max-w-[170px]" title="{{ $log->route }}">
                                    /{{ ltrim((string) $log->route, '/') }}
                                </span>
                            </div>
                            @if($log->ip_address)
                                <span class="text-[10px] text-slate-600 font-mono-code pl-0.5">IP: {{ $log->ip_address }}</span>
                            @endif
                        </div>
                    </td>

                    <!-- DETAILS ACTION -->
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <button type="button"
                                onclick="openAuditModal({{ json_encode([
                                    'id'          => $log->id,
                                    'timestamp'   => $log->created_at ? $log->created_at->format('d M Y, H:i:s') : '—',
                                    'relative'    => $log->created_at ? $log->created_at->diffForHumans() : '',
                                    'staff_name'  => $log->staff_name ?: 'Unknown Staff',
                                    'service_id'  => $log->service_id ?: 'N/A',
                                    'staff_email' => $log->staff_email ?: 'N/A',
                                    'unit'        => $log->unit ?: 'Operational Unit',
                                    'role'        => $log->role ?: 'Staff Officer',
                                    'action'      => $actionStr,
                                    'description' => $log->description ?: '—',
                                    'method'      => $method,
                                    'status_code' => $statusCode,
                                    'route'       => '/' . ltrim((string) $log->route, '/'),
                                    'url'         => $log->url ?: '—',
                                    'ip_address'  => $log->ip_address ?: '—',
                                    'user_agent'  => $log->user_agent ?: '—',
                                    'payload'     => $payloadJson,
                                ]) }})"
                                class="inline-flex items-center px-2.5 py-1 text-[11px] font-bold text-slate-700 bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300 border border-slate-200 rounded-lg shadow-2xs transition-all cursor-pointer">
                            <svg class="w-3.5 h-3.5 mr-1 text-slate-400 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Inspect
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-12 px-4 text-center">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-extrabold text-slate-800">No Staff Audit Activities Found</span>
                            <p class="text-xs text-slate-500 max-w-sm">No activity logs recorded matching your active filters. Try resetting search query, action types, or date range.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- PAGINATION & METRICS FOOTER -->
@if($logs->total() > 0)
    <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
        <div class="flex items-center space-x-2">
            <span>Showing <strong class="text-slate-900 tnum font-extrabold">{{ $logs->firstItem() ?? 0 }}</strong> to <strong class="text-slate-900 tnum font-extrabold">{{ $logs->lastItem() ?? 0 }}</strong> of <strong class="text-slate-900 tnum font-extrabold">{{ number_format($logs->total()) }}</strong> audit records</span>
            @if($logs->lastPage() > 1)
                <span class="text-slate-300">&bull;</span>
                <span class="font-mono-code text-[11px] text-slate-500 font-bold">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>
            @endif
        </div>

        @if($logs->hasPages())
            <nav class="flex items-center space-x-1 audit-pagination" aria-label="Staff audit table pagination">
                {{-- Previous Page Link --}}
                @if ($logs->onFirstPage())
                    <button type="button" disabled class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-200 bg-slate-100 text-slate-400 cursor-not-allowed inline-flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Prev
                    </button>
                @else
                    <button type="button" onclick="goToAuditPage({{ $logs->currentPage() - 1 }}, event)" class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 transition-colors inline-flex items-center cursor-pointer shadow-2xs">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Prev
                    </button>
                @endif

                {{-- Pagination Elements with Windowing --}}
                @php
                    $current = $logs->currentPage();
                    $last = $logs->lastPage();
                    $start = max(1, $current - 2);
                    $end = min($last, $current + 2);
                @endphp

                @if($start > 1)
                    <button type="button" onclick="goToAuditPage(1, event)" class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer shadow-2xs">1</button>
                    @if($start > 2)
                        <span class="px-1 text-slate-400">&hellip;</span>
                    @endif
                @endif

                @for ($p = $start; $p <= $end; $p++)
                    @if ($p == $current)
                        <span class="px-3 py-1 text-xs font-black rounded-lg bg-emerald-600 text-white border border-emerald-600 shadow-xs">{{ $p }}</span>
                    @else
                        <button type="button" onclick="goToAuditPage({{ $p }}, event)" class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer shadow-2xs">{{ $p }}</button>
                    @endif
                @endfor

                @if($end < $last)
                    @if($end < $last - 1)
                        <span class="px-1 text-slate-400">&hellip;</span>
                    @endif
                    <button type="button" onclick="goToAuditPage({{ $last }}, event)" class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer shadow-2xs">{{ $last }}</button>
                @endif

                {{-- Next Page Link --}}
                @if ($logs->hasMorePages())
                    <button type="button" onclick="goToAuditPage({{ $logs->currentPage() + 1 }}, event)" class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 transition-colors inline-flex items-center cursor-pointer shadow-2xs">
                        Next
                        <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @else
                    <button type="button" disabled class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-200 bg-slate-100 text-slate-400 cursor-not-allowed inline-flex items-center">
                        Next
                        <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @endif
            </nav>
        @endif
    </div>
@endif

