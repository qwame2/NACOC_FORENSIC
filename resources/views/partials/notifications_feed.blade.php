@php
    $firstItemNum = method_exists($notifications, 'firstItem') ? ($notifications->firstItem() ?? 0) : (count($notifications) > 0 ? 1 : 0);
    $lastItemNum = method_exists($notifications, 'lastItem') ? ($notifications->lastItem() ?? 0) : count($notifications);
    $totalFilteredNum = method_exists($notifications, 'total') ? $notifications->total() : count($notifications);
    $visiblePageCount = count($notifications);
    $hasActiveNotifFilters = (($category ?? 'all') !== 'all')
        || (($statusFilter ?? 'all') !== 'all')
        || (trim((string) ($search ?? '')) !== '');
@endphp

<div id="notificationsFeedMeta"
     class="hidden"
     data-first-item="{{ $firstItemNum }}"
     data-last-item="{{ $lastItemNum }}"
     data-total-filtered="{{ $totalFilteredNum }}"
     data-visible-count="{{ $visiblePageCount }}"
     @if(isset($stats))
         data-stat-total="{{ $stats['total'] ?? 0 }}"
         data-stat-unread="{{ $stats['unread'] ?? 0 }}"
         data-stat-cross="{{ $stats['cross_unit'] ?? 0 }}"
         data-stat-handover="{{ $stats['handover'] ?? 0 }}"
         data-stat-case="{{ $stats['case_approval'] ?? 0 }}"
         data-stat-staff="{{ $stats['staff_approval'] ?? 0 }}"
     @endif></div>

<div id="notificationsFeedBody" class="divide-y divide-slate-200/75 bg-white">
    @forelse($notifications as $item)
        @php
            $severity = $item['severity'] ?? 'slate';
            $categoryKey = $item['category'] ?? '';
            $isUnread = empty($item['is_read']);

            $leftAccent = match ($severity) {
                'amber'   => 'border-l-amber-500',
                'emerald' => 'border-l-emerald-600',
                'blue'    => 'border-l-sky-600',
                'purple'  => 'border-l-teal-600',
                'rose'    => 'border-l-rose-600',
                default   => 'border-l-slate-400',
            };

            $iconWrapTone = $isUnread
                ? match ($severity) {
                    'amber'   => 'bg-amber-50 text-amber-700 border-amber-200/90',
                    'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200/90',
                    'blue'    => 'bg-sky-50 text-sky-700 border-sky-200/90',
                    'purple'  => 'bg-teal-50 text-teal-700 border-teal-200/90',
                    'rose'    => 'bg-rose-50 text-rose-700 border-rose-200/90',
                    default   => 'bg-slate-100 text-slate-700 border-slate-200',
                }
                : 'bg-slate-100 text-slate-500 border-slate-200/90';

            $badgeLabel = match ($categoryKey) {
                'cross_unit'     => 'Cross-Unit Intake',
                'handover'       => 'Case Handover',
                'case_approval'  => 'Case Sign-Off',
                'staff_approval' => 'Staff Activation',
                default          => 'Notice',
            };

            $badgeTone = match ($severity) {
                'amber'   => 'bg-amber-100 text-amber-800 border-amber-200',
                'emerald' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'blue'    => 'bg-sky-100 text-sky-800 border-sky-200',
                'purple'  => 'bg-teal-100 text-teal-800 border-teal-200',
                'rose'    => 'bg-rose-100 text-rose-800 border-rose-200',
                default   => 'bg-slate-100 text-slate-700 border-slate-200',
            };

            $suspectLabel = trim((string) ($item['suspect_name'] ?? ''));
            $showSuspectChip = $suspectLabel !== '' && strcasecmp($suspectLabel, 'Unknown') !== 0 && !str_contains(strtolower($suspectLabel), 'unassigned');
        @endphp

        <article data-notification-id="{{ $item['id'] }}"
                 class="group px-4 py-4 sm:px-6 transition-colors border-l-4 {{ $isUnread ? $leftAccent . ' bg-white hover:bg-slate-50/80' : 'border-l-slate-300 bg-slate-50/75 hover:bg-slate-100/70' }}">
            <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">

                <!-- LEFT: CATEGORY ICON + STRUCTURED DISPATCH DETAILS -->
                <div class="flex items-start gap-3.5 min-w-0 flex-1">
                    <div class="w-10 h-10 rounded-xl border flex items-center justify-center shrink-0 mt-0.5 {{ $iconWrapTone }}" aria-hidden="true">
                        @if($categoryKey === 'cross_unit')
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        @elseif($categoryKey === 'handover')
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                        @elseif($categoryKey === 'case_approval')
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        @elseif($categoryKey === 'staff_approval')
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        @else
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        @endif
                    </div>

                    <div class="space-y-1.5 min-w-0 flex-1">
                        <!-- METADATA CHIPS -->
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if($isUnread)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9.5px] font-extrabold font-mono-code uppercase bg-emerald-600 text-white tracking-wider">
                                    Unread
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9.5px] font-bold font-mono-code uppercase bg-slate-200/80 text-slate-600 border border-slate-300/70 tracking-wider">
                                    Read
                                </span>
                            @endif

                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold font-mono-code uppercase border {{ $badgeTone }}">
                                {{ $badgeLabel }}
                            </span>

                            @if(!empty($item['source_unit']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200/90">
                                    {{ $item['source_unit'] }}
                                </span>
                            @endif

                            @if(!empty($item['case_number']))
                                <button type="button"
                                        onclick="copyNotificationRef('{{ addslashes($item['case_number']) }}')"
                                        title="Click to copy case reference {{ $item['case_number'] }}"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold font-mono-code bg-slate-100 hover:bg-slate-200/80 text-slate-800 border border-slate-200 transition-colors">
                                    <span>{{ $item['case_number'] }}</span>
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            @endif

                            @if(!empty($item['property_id']))
                                <button type="button"
                                        onclick="copyNotificationRef('{{ addslashes($item['property_id']) }}')"
                                        title="Click to copy property ID {{ $item['property_id'] }}"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold font-mono-code bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition-colors">
                                    <span>{{ $item['property_id'] }}</span>
                                </button>
                            @endif

                            @if($showSuspectChip)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-white text-slate-700 border border-slate-200">
                                    Subject: {{ $suspectLabel }}
                                </span>
                            @endif
                        </div>

                        <!-- NOTIFICATION HEADLINE -->
                        <h3 class="text-sm font-extrabold {{ $isUnread ? 'text-slate-900' : 'text-slate-700' }} leading-snug">
                            {{ $item['title'] }}
                        </h3>

                        <!-- NOTIFICATION MESSAGE -->
                        @if(!empty($item['message']))
                            <p class="text-xs {{ $isUnread ? 'text-slate-700 font-medium' : 'text-slate-600' }} leading-relaxed max-w-4xl">
                                {{ $item['message'] }}
                            </p>
                        @endif

                        <!-- ACTOR & RELATIVE TIMESTAMP -->
                        @if(!empty($item['actor_name']) || !empty($item['time_human']))
                            <div class="flex flex-wrap items-center gap-2 pt-0.5 text-[11px] text-slate-500 font-mono-code">
                                @if(!empty($item['actor_name']))
                                    <span class="inline-flex items-center gap-1 text-slate-600 font-semibold">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span>{{ $item['actor_name'] }}</span>
                                    </span>
                                @endif
                                @if(!empty($item['actor_name']) && !empty($item['time_human']))
                                    <span class="text-slate-300">|</span>
                                @endif
                                @if(!empty($item['time_human']))
                                    <span>{{ $item['time_human'] }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <!-- RIGHT: COMMAND ACTION CLUSTER -->
                <div class="flex flex-wrap items-center gap-2 shrink-0 pl-13.5 lg:pl-0 lg:pt-0.5">
                    @if(!empty($item['handover_id']))
                        <button type="button"
                                onclick="acknowledgeHandoverNotification({{ (int) $item['handover_id'] }}, this)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-2xs transition-colors">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Acknowledge</span>
                        </button>
                    @endif

                    @if(!empty($item['action_url']) && !empty($item['action_label']))
                        <a href="{{ $item['action_url'] }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-colors no-underline {{ $isUnread ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs' : 'bg-slate-200/90 hover:bg-slate-300 text-slate-800 border border-slate-300/80' }}">
                            <span>{{ $item['action_label'] }}</span>
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    @endif

                    @if($isUnread)
                        <button type="button"
                                onclick="markSingleNotificationRead('{{ $item['id'] }}', this)"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/90 text-xs font-bold transition-colors">
                            <span>Mark Read</span>
                        </button>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="py-16 px-6 text-center bg-white">
            <div class="max-w-md mx-auto space-y-3">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <p class="text-sm font-extrabold text-slate-900">No Matching Notifications</p>
                @if($hasActiveNotifFilters)
                    <p class="text-xs text-slate-500 leading-relaxed">
                        No unit notifications match your current category, read-status, or search filter.
                    </p>
                    <div class="pt-1">
                        <button type="button"
                                onclick="resetNotificationFilters()"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 border border-slate-300 text-xs font-bold transition-colors">
                            <span>Clear active filters</span>
                        </button>
                    </div>
                @else
                    <p class="text-xs text-slate-500 leading-relaxed">
                        All clear — incoming cross-unit exhibit alerts, case handovers, docket sign-offs, and staff account activations will appear here automatically.
                    </p>
                @endif
            </div>
        </div>
    @endforelse
</div>

@if($notifications->hasPages())
    <div class="px-4 py-3.5 sm:px-6 border-t border-slate-200/80 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-600">
        <div class="font-mono-code text-[11px]">
            Showing <span class="font-bold text-slate-900">{{ $notifications->firstItem() ?? 0 }}</span>
            to <span class="font-bold text-slate-900">{{ $notifications->lastItem() ?? 0 }}</span>
            of <span class="font-bold text-slate-900">{{ $notifications->total() }}</span> notifications
        </div>
        <div class="notifications-pagination">
            {{ $notifications->links() }}
        </div>
    </div>
@endif
