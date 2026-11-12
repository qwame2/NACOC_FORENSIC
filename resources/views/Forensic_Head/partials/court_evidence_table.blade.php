@php
    $documents = $documents ?? collect();
@endphp

<!-- TABLE METADATA FOR CLIENT-SIDE SYNC -->
<div id="courtEvidenceTableMeta" 
     class="hidden" 
     data-total="{{ method_exists($documents, 'total') ? $documents->total() : $documents->count() }}" 
     data-first-item="{{ method_exists($documents, 'firstItem') ? ($documents->firstItem() ?? 0) : 1 }}" 
     data-last-item="{{ method_exists($documents, 'lastItem') ? ($documents->lastItem() ?? 0) : $documents->count() }}" 
     data-current-page="{{ method_exists($documents, 'currentPage') ? $documents->currentPage() : 1 }}" 
     data-last-page="{{ method_exists($documents, 'lastPage') ? $documents->lastPage() : 1 }}">
</div>

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse" id="courtEvidenceTable">
        <thead>
            <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-mono-code uppercase text-slate-500 tracking-wider">
                <th class="py-3 px-4 font-bold">Document &amp; File Details</th>
                <th class="py-3 px-4 font-bold">Unit &amp; Origin</th>
                <th class="py-3 px-4 font-bold">Date &amp; Size</th>
                <th class="py-3 px-4 font-bold text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-xs font-medium" id="courtEvidenceTableBody">
            @forelse($documents as $doc)
                <tr class="table-row-hover transition-colors">
                    
                    <!-- DOCUMENT & FILE DETAILS -->
                    <td class="py-3 px-4">
                        <div class="flex items-start gap-3">
                            <!-- PDF ICON BADGE -->
                            <div class="w-9 h-9 rounded-xl bg-red-50 border border-red-200 flex items-center justify-center text-red-600 shrink-0 mt-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900 leading-tight truncate max-w-md" title="{{ $doc['title'] ?? '' }}">
                                    {{ $doc['title'] ?? 'Court Evidence Document' }}
                                </div>
                                <div class="text-[11px] text-slate-500 font-mono-code mt-0.5 truncate max-w-sm" title="{{ $doc['original_filename'] ?? '' }}">
                                    {{ $doc['original_filename'] ?? 'document.pdf' }}
                                </div>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold font-mono-code border {{ $doc['category_badge_class'] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                        {{ $doc['category'] ?? 'Evidence PDF' }}
                                    </span>
                                    @if(!empty($doc['case_number']) && $doc['case_number'] !== 'N/A')
                                        <a href="{{ $doc['case_review_url'] ?? '#' }}" class="text-[10px] font-mono-code font-bold text-blue-600 hover:text-blue-800 hover:underline">
                                            Case: {{ $doc['case_number'] }}
                                        </a>
                                    @endif
                                    @if(!empty($doc['reference_no']) && $doc['reference_no'] !== 'N/A' && (!isset($doc['case_number']) || $doc['reference_no'] !== $doc['case_number']))
                                        <span class="text-[10px] text-slate-600 font-mono-code font-bold bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                                            Ref: {{ $doc['reference_no'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- UNIT & ORIGIN -->
                    <td class="py-3 px-4">
                        <div class="space-y-1">
                            <div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold font-mono-code border {{ $doc['unit_badge_class'] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $doc['unit_name'] ?? 'Forensics' }}
                                </span>
                            </div>
                            <div>
                                @if(($doc['origin_type'] ?? '') === 'uploaded')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                        <span>Uploaded by Officer</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-700">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        <span>System Generated</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>

                    <!-- DATE & SIZE -->
                    <td class="py-3 px-4">
                        <div class="font-mono-code text-[11px] text-slate-700">{{ $doc['created_at'] ?? 'N/A' }}</div>
                        <div class="text-[10px] text-slate-500 font-mono-code mt-0.5">{{ $doc['file_size'] ?? 'PDF Document' }}</div>
                    </td>

                    <!-- ACTIONS -->
                    <td class="py-3 px-4 text-right">
                        <div class="inline-flex items-center gap-1.5 justify-end">
                            <!-- PREVIEW MODAL BUTTON -->
                            <button 
                                type="button" 
                                onclick="openPdfModal('{{ $doc['preview_url'] ?? $doc['url'] }}', '{{ addslashes($doc['title'] ?? 'Court Document') }}')"
                                class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors cursor-pointer border-none"
                                title="Preview PDF in-browser"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>

                            <!-- OPEN IN NEW TAB BUTTON -->
                            <a 
                                href="{{ $doc['url'] ?? '#' }}" 
                                target="_blank" 
                                class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition-colors text-decoration-none"
                                title="Open PDF in New Window"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>

                            <!-- DOWNLOAD BUTTON -->
                            <a 
                                href="{{ $doc['download_url'] ?? ($doc['url'] ?? '#') }}" 
                                download
                                class="p-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 transition-colors text-decoration-none"
                                title="Download PDF"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                        </div>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="4" class="py-12 text-center text-slate-500">
                        <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div class="text-sm font-bold text-slate-900 font-mono-code">No Court Evidence Documents Found</div>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">No generated or uploaded PDFs matched the specified filter criteria.</p>
                        <a href="{{ url('/court-evidence') }}" class="inline-block mt-3 px-4 py-1.5 bg-emerald-600 text-white rounded-xl text-xs font-bold font-mono-code text-decoration-none">
                            Clear All Filters
                        </a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- PAGINATION BAR -->
@if(method_exists($documents, 'hasPages') && $documents->hasPages())
    <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between flex-wrap gap-3">
        <div class="text-xs text-slate-500 font-mono-code">
            Page {{ $documents->currentPage() }} of {{ $documents->lastPage() }}
        </div>
        <div>
            {{ $documents->links() }}
        </div>
    </div>
@endif

