{{-- 
    REUSABLE TABLE SKELETON LOADER PARTIAL
    Used for instant fallback and high-fidelity pulse loading states across system tables
--}}
@php
    $columns = $columns ?? 6;
    $rows = $rows ?? 6;
    $widthPatterns = [
        ['w-20', 'w-44', 'w-24', 'w-28', 'w-20 rounded-full', 'w-14 rounded-lg'],
        ['w-24', 'w-36', 'w-28', 'w-32', 'w-16 rounded-full', 'w-14 rounded-lg'],
        ['w-16', 'w-48', 'w-20', 'w-24', 'w-24 rounded-full', 'w-14 rounded-lg'],
        ['w-28', 'w-40', 'w-32', 'w-36', 'w-20 rounded-full', 'w-14 rounded-lg'],
        ['w-20', 'w-32', 'w-24', 'w-28', 'w-16 rounded-full', 'w-14 rounded-lg'],
        ['w-24', 'w-44', 'w-28', 'w-20', 'w-20 rounded-full', 'w-14 rounded-lg'],
    ];
@endphp

<div class="overflow-x-auto" data-skeleton="true">
    <table class="w-full text-left border-collapse text-xs">
        <thead>
            <tr class="bg-slate-100/80 border-b border-slate-200">
                @for($c = 0; $c < $columns; $c++)
                    <th class="py-3.5 px-4">
                        <div class="h-3 bg-slate-300 rounded w-16 animate-pulse"></div>
                    </th>
                @endfor
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            @for($r = 0; $r < $rows; $r++)
                @php $widths = $widthPatterns[$r % count($widthPatterns)]; @endphp
                <tr class="animate-pulse border-b border-slate-100">
                    @for($c = 0; $c < $columns; $c++)
                        @php 
                            $w = $widths[$c % count($widths)]; 
                            $isLast = ($c === $columns - 1);
                            $isCenter = ($c === $columns - 2);
                        @endphp
                        @if($c === 0)
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="h-3.5 bg-slate-200 rounded {{ $w }}"></div>
                                <div class="h-2.5 bg-slate-100 rounded w-14 mt-1.5"></div>
                            </td>
                        @elseif($c === 1)
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-slate-200 shrink-0"></div>
                                    <div class="space-y-1.5 flex-1">
                                        <div class="h-3.5 bg-slate-200 rounded {{ $w }}"></div>
                                        <div class="h-2.5 bg-slate-100 rounded w-20"></div>
                                    </div>
                                </div>
                            </td>
                        @elseif($isLast)
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="h-7 bg-slate-200 rounded-xl w-14 ml-auto"></div>
                            </td>
                        @elseif($isCenter)
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="h-5 bg-slate-200 rounded-full w-20 mx-auto"></div>
                            </td>
                        @else
                            <td class="py-3.5 px-4">
                                <div class="h-3.5 bg-slate-200 rounded {{ $w }}"></div>
                            </td>
                        @endif
                    @endfor
                </tr>
            @endfor
        </tbody>
    </table>
</div>

