<div id="notificationsFeedBody" class="space-y-3">
    @forelse($notifications as $item)
        @php
            $hideDotsForStaff = request()->is('*/staff*') || request()->is('staff*') || request()->is('*field-officers*') || (auth()->check() && !auth()->user()->is_head);
            $severity = $item['severity'] ?? 'slate';
            $isUnread = empty($item['is_read']);
            $accentClasses = match ($severity) {
                'amber'  => 'border-amber-200 bg-amber-50/40',
                'emerald'=> 'border-emerald-200 bg-emerald-50/40',
                'blue'   => 'border-blue-200 bg-blue-50/40',
                'purple' => 'border-purple-200 bg-purple-50/40',
                'rose'   => 'border-rose-200 bg-rose-50/40',
                default  => 'border-slate-200 bg-white',
            };
            $badgeLabel = match ($item['category'] ?? '') {
                'cross_unit'     => 'Cross-Unit Intake',
                'handover'       => 'Case Handover',
                'case_approval'  => 'Case Sign-Off',
                'staff_approval' => 'Staff Activation',
                default          => 'Notice',
            };
            $badgeTone = match ($severity) {
                'amber'  => 'bg-amber-100 text-amber-800 border-amber-200',
                'emerald'=> 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'blue'   => 'bg-blue-100 text-blue-800 border-blue-200',
                'purple' => 'bg-purple-100 text-purple-800 border-purple-200',
                'rose'   => 'bg-rose-100 text-rose-800 border-rose-200',
                default  => 'bg-slate-100 text-slate-700 border-slate-200',
            };
        @endphp

        <article data-notification-id="{{ $item['id'] }}"
                 class="rounded-2xl border p-4 transition-all shadow-2xs hover:shadow-sm {{ $isUnread ? $accentClasses : 'border-slate-200 bg-slate-50/50 opacity-85' }}">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="space-y-1.5 min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        @if($isUnread && !$hideDotsForStaff)
                            <span class="w-2 h-2 rounded-full bg-emerald-600 shrink-0" title="Unread"></span>
                        @endif
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold font-mono-code uppercase border {{ $badgeTone }}">
                            {{ $badgeLabel }}
                        </span>
                        @if(!empty($item['source_unit']))
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-white text-slate-700 border border-slate-200">
                                {{ $item['source_unit'] }}
                            </span>
                        @endif
                        @if(!empty($item['case_number']))
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold font-mono-code bg-white text-slate-700 border border-slate-200">
                                {{ $item['case_number'] }}
                            </span>
                        @endif
                        @if(!empty($item['property_id']))
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold font-mono-code bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ $item['property_id'] }}
                            </span>
                        @endif
                    </div>

                    <h3 class="text-sm font-extrabold text-slate-900 leading-snug">
                        {{ $item['title'] }}
                    </h3>

                    @if(!empty($item['message']))
                        <p class="text-xs text-slate-700 font-medium leading-relaxed">
                            {{ $item['message'] }}
                        </p>
                    @endif

                    @if(!empty($item['actor_name']) || !empty($item['time_human']))
                        <p class="text-[11px] text-slate-500 font-medium">
                            {{ collect([$item['actor_name'] ?? '', $item['time_human'] ?? ''])->filter()->implode($hideDotsForStaff ? ' | ' : ' • ') }}
                        </p>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    @if(!empty($item['handover_id']))
                        <button type="button"
                                onclick="acknowledgeHandoverNotification({{ (int) $item['handover_id'] }}, this)"
                                class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-colors cursor-pointer">
                            Acknowledge
                        </button>
                    @endif

                    @if(!empty($item['action_url']) && !empty($item['action_label']))
                        <a href="{{ $item['action_url'] }}"
                           class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors text-decoration-none">
                            {{ $item['action_label'] }}
                        </a>
                    @endif

                    @if($isUnread)
                        <button type="button"
                                onclick="markSingleNotificationRead('{{ $item['id'] }}', this)"
                                class="px-3 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold transition-colors cursor-pointer">
                            Mark Read
                        </button>
                    @else
                        <span class="px-2.5 py-1 rounded-xl bg-slate-100 text-slate-500 text-[11px] font-bold font-mono-code">
                            Read
                        </span>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-10 text-center space-y-2">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-white border border-slate-200 flex items-center justify-center text-slate-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <p class="text-sm font-extrabold text-slate-800">No Matching Notifications</p>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
                There are currently no active unit notifications matching your selected filters.
            </p>
        </div>
    @endforelse
</div>

@if($notifications->hasPages())
    <div class="mt-4 pt-4 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-600">
        <div>
            Showing <span class="font-bold text-slate-900">{{ $notifications->firstItem() ?? 0 }}</span>
            to <span class="font-bold text-slate-900">{{ $notifications->lastItem() ?? 0 }}</span>
            of <span class="font-bold text-slate-900">{{ $notifications->total() }}</span> notifications
        </div>
        <div class="notifications-pagination">
            {{ $notifications->links() }}
        </div>
    </div>
@endif

