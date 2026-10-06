{{--
    Per-suspect attached GSA report files.

    Expects:
      $gsaFiles     — iterable of App\Models\SuspectGsaReport (already filtered to the suspect)
      $gsaForName   — (optional) suspect name shown in the heading
--}}
@if (!empty($gsaFiles) && count($gsaFiles) > 0)
    <div class="gsa-attached-files mt-4 p-4 bg-blue-50/70 border border-blue-200 rounded-2xl space-y-3">
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="flex items-center gap-2 text-xs font-black text-blue-900 font-mono-code uppercase tracking-tight">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                Attached Report Files ({{ count($gsaFiles) }})
                @if (!empty($gsaForName))
                    <span class="text-blue-600 normal-case font-bold">&mdash; {{ $gsaForName }}</span>
                @endif
            </span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
            @foreach ($gsaFiles as $gsaF)
                <div class="bg-white border border-blue-200/90 rounded-xl p-3 flex items-center justify-between gap-3 shadow-2xs hover:border-blue-400 hover:shadow-sm transition-all">
                    <div class="flex items-center gap-2.5 min-w-0">
                        @if ($gsaF->is_image)
                            <a href="{{ $gsaF->url }}" target="_blank" class="block w-9 h-9 rounded-lg overflow-hidden border border-blue-200 shrink-0">
                                <img src="{{ $gsaF->url }}" alt="{{ $gsaF->original_filename }}" class="w-full h-full object-cover">
                            </a>
                        @else
                            @php
                                $isNlr = ($gsaF instanceof \App\Models\SuspectNlrReport) || str_contains(strtoupper($gsaF->original_filename ?? ''), 'NLR');
                            @endphp
                            <div class="w-9 h-9 {{ $isNlr ? 'bg-emerald-600' : 'bg-blue-600' }} text-white rounded-lg flex items-center justify-center font-mono-code text-[10px] font-bold shrink-0">
                                {{ $isNlr ? 'NLR' : strtoupper(substr($gsaF->file_extension ?? 'PDF', 0, 4)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-900 truncate" title="{{ $gsaF->original_filename }}">
                                {{ $gsaF->original_filename }}</div>
                            <div class="text-[10px] text-slate-500 font-mono-code">
                                {{ $gsaF->file_size_label }} &bull;
                                {{ $gsaF->created_at ? $gsaF->created_at->format('M d, Y H:i') : 'Attached' }}
                            </div>
                        </div>
                    </div>
                    @php
                        $isNlrBtn = ($gsaF instanceof \App\Models\SuspectNlrReport) || str_contains(strtoupper($gsaF->original_filename ?? ''), 'NLR');
                    @endphp
                    <a href="{{ $gsaF->url }}" target="_blank"
                        class="no-print px-2.5 py-1 {{ $isNlrBtn ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-blue-600 hover:bg-blue-700' }} text-white text-[10px] font-extrabold font-mono-code rounded-lg transition-colors shrink-0">
                        Open
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endif
