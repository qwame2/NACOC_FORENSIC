@php
    $documents = $documents ?? collect();
@endphp

<div id="courtEvidenceTableMeta"
     class="hidden"
     data-total="{{ method_exists($documents, 'total') ? $documents->total() : $documents->count() }}"
     data-first-item="{{ method_exists($documents, 'firstItem') ? ($documents->firstItem() ?? 0) : 1 }}"
     data-last-item="{{ method_exists($documents, 'lastItem') ? ($documents->lastItem() ?? 0) : $documents->count() }}"
     data-current-page="{{ method_exists($documents, 'currentPage') ? $documents->currentPage() : 1 }}"
     data-last-page="{{ method_exists($documents, 'lastPage') ? $documents->lastPage() : 1 }}">
</div>

<div class="overflow-x-auto custom-scrollbar">
    <table class="rep-table w-full min-w-[880px] text-left border-collapse" id="courtEvidenceTable">
        <thead>
            <tr>
                <th class="py-3.5 px-6 w-[44%]">Document &amp; File Dossier</th>
                <th class="py-3.5 px-4 w-[24%]">Division &amp; Origin</th>
                <th class="py-3.5 px-4 w-[16%]">Timestamp &amp; Format</th>
                <th class="py-3.5 px-6 w-[16%] text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white text-xs font-medium" id="courtEvidenceTableBody">
            @forelse($documents as $doc)
                <tr>
                    <!-- DOCUMENT & FILE DETAILS -->
                    <td class="py-3.5 px-6 align-top">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-rose-50 border border-rose-200/80 flex items-center justify-center text-rose-600 shrink-0 mt-0.5 font-mono-code font-black text-[9.5px]">
                                PDF
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900 leading-snug truncate max-w-md" title="{{ $doc['title'] ?? '' }}">
                                    {{ $doc['title'] ?? 'Court Evidence Document' }}
                                </div>
                                <div class="text-[11px] text-slate-400 font-mono-code mt-0.5 truncate max-w-sm" title="{{ $doc['original_filename'] ?? '' }}">
                                    {{ $doc['original_filename'] ?? 'document.pdf' }}
                                </div>
                                <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9.5px] font-bold font-mono-code border {{ $doc['category_badge_class'] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                        {{ $doc['category'] ?? 'Evidence PDF' }}
                                    </span>
                                    @if(!empty($doc['case_number']) && $doc['case_number'] !== 'N/A')
                                        <a href="{{ $doc['case_review_url'] ?? '#' }}" class="text-[10px] font-mono-code font-extrabold text-emerald-700 hover:text-emerald-900 hover:underline">
                                            {{ $doc['case_number'] }}
                                        </a>
                                    @endif
                                    @if(!empty($doc['reference_no']) && $doc['reference_no'] !== 'N/A' && (!isset($doc['case_number']) || $doc['reference_no'] !== $doc['case_number']))
                                        <span class="text-[9.5px] text-slate-600 font-mono-code font-bold bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                                            Ref: {{ $doc['reference_no'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- UNIT & ORIGIN -->
                    <td class="py-3.5 px-4 align-top">
                        <div class="space-y-1.5">
                            <div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold font-mono-code border {{ $doc['unit_badge_class'] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    {{ $doc['unit_name'] ?? 'Forensics' }}
                                </span>
                            </div>
                            <div>
                                @if(($doc['origin_type'] ?? '') === 'uploaded')
                                    <span class="inline-flex items-center gap-1 text-[10.5px] font-mono-code font-bold text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Officer Upload</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10.5px] font-mono-code font-bold text-blue-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        <span>System Generated</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>

                    <!-- DATE & SIZE -->
                    <td class="py-3.5 px-4 align-top whitespace-nowrap">
                        <div class="font-mono-code font-bold text-[11px] text-slate-800 tnum">{{ $doc['created_at'] ?? 'N/A' }}</div>
                        <div class="text-[10px] text-slate-400 font-mono-code mt-0.5">{{ $doc['file_size'] ?? 'PDF Document' }}</div>
                    </td>

                    <!-- ACTIONS (ALL GREEN BUTTONS) -->
                    <td class="py-3.5 px-6 align-top text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1.5 justify-end">
                            <button type="button"
                                    onclick="openPdfModal('{{ $doc['preview_url'] ?? $doc['url'] }}', '{{ addslashes($doc['title'] ?? 'Court Document') }}')"
                                    class="rep-btn-green px-2.5 py-1.5 rounded-lg cursor-pointer inline-flex items-center gap-1 text-[10.5px] font-mono-code font-bold"
                                    title="Preview PDF in-browser">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>Preview</span>
                            </button>

                            <a href="{{ $doc['url'] ?? '#' }}"
                               target="_blank"
                               class="rep-btn-green-soft p-1.5 rounded-lg text-decoration-none"
                               title="Open PDF in New Tab">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>

                            <a href="{{ $doc['download_url'] ?? ($doc['url'] ?? '#') }}"
                               download
                               class="rep-btn-green-soft p-1.5 rounded-lg text-decoration-none"
                               title="Download PDF">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="py-14 text-center text-slate-500">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div class="text-sm font-extrabold text-slate-900 font-mono-code uppercase">No Court Evidence Documents Found</div>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">No system-generated or officer-uploaded PDFs matched your active filter parameters.</p>
                        <button type="button" onclick="resetCourtEvidenceFilters()" class="rep-btn-green inline-block mt-4 px-4 py-2 rounded-xl text-xs font-bold font-mono-code cursor-pointer">
                            Clear All Filters
                        </button>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(method_exists($documents, 'hasPages') && $documents->hasPages())
    <div class="rep-pagination px-6 py-4 border-t border-slate-200/80 bg-slate-50/60 flex items-center justify-between flex-wrap gap-3">
        <div class="text-[11px] text-slate-500 font-mono-code tnum">
            Page <span class="font-extrabold text-slate-900">{{ $documents->currentPage() }}</span> of <span class="font-extrabold text-slate-900">{{ $documents->lastPage() }}</span>
        </div>
        <div>
            {{ $documents->links() }}
        </div>
    </div>
@endif
