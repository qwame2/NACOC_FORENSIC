{{--
    Partial: partials/suspect-mugshots.blade.php
    Description: Renders the three-angle (front, left profile, right profile) biometric mugshot capture block for a suspect.
    Required Inputs (@props):
      - $suspect (array): Suspect array containing resolved_view (or top-level mugshot/identity keys).
      - $index (int): Zero-based index of the suspect in the case suspect list.
--}}@props([
    'suspect' => [],
    'index' => 0,
])
                    <!-- MUGSHOT (MULTI-ANGLE PHOTO CAPTURE FOR SUSPECT #{{ ($suspect['resolved_view']['sNum'] ?? ($suspect['suspect_number'] ?? ($index + 1))) }}) -->
                    <div class="dossier-card p-6 border border-slate-200">
                        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
                            <div class="flex items-center gap-3">
                                <span
                                    class="px-2.5 py-1 rounded-md text-[10px] font-mono-code font-bold bg-blue-100 text-blue-800 border border-blue-300 uppercase tracking-wider">
                                    SUSPECT #{{ ($suspect['resolved_view']['sNum'] ?? ($suspect['suspect_number'] ?? ($index + 1))) }} MUGSHOTS
                                </span>
                                <div>
                                    <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                        {{ ($suspect['resolved_view']['sName'] ?? ($suspect['suspect_name'] ?? 'Suspect')) }} — Biometric Mugshots
                                        @if ((!empty($suspect['resolved_view']['isAssociate']) || !empty($suspect['is_associate'])))
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-mono-code font-extrabold bg-amber-500 text-white shadow-2xs">{{ strtoupper(($suspect['resolved_view']['sRoleLabel'] ?? ($suspect['association_role'] ?? ($suspect['role'] ?? 'PRIMARY SUSPECT')))) }}</span>
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-mono-code font-extrabold bg-slate-900 text-amber-300 shadow-2xs border border-slate-700">ID: {{ ($suspect['resolved_view']['sId'] ?? ($suspect['associate_id'] ?? ($suspect['suspect_id'] ?? 'PRIM-001'))) }}</span>
                                        @endif
                                    </h2>
                                    <p class="text-xs text-slate-500">
                                        Suspect facial photo captures recorded during booking
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <!-- Angle 1: Frontal View -->
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-3">
                                <div class="flex items-center justify-between text-xs font-mono-code">
                                    <span
                                        class="px-2 py-0.5 rounded bg-emerald-600 text-white font-bold text-[10px]">ANGLE
                                        1</span>
                                    <span class="font-bold text-slate-700 text-[11px]">Front View (0&deg;)</span>
                                    <span
                                        class="font-bold text-[10px] {{ ($suspect['resolved_view']['sFront'] ?? ($suspect['mugshot_front'] ?? null)) ? 'text-emerald-700' : 'text-slate-400' }}">{{ ($suspect['resolved_view']['sFront'] ?? ($suspect['mugshot_front'] ?? null)) ? '✓ CAPTURED' : 'NOT CAPTURED' }}</span>
                                </div>
                                <div
                                    class="h-44 w-full bg-slate-100 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden">
                                    @if (($suspect['resolved_view']['sFront'] ?? ($suspect['mugshot_front'] ?? null)))
                                        <img src="{{ ($suspect['resolved_view']['sFront'] ?? ($suspect['mugshot_front'] ?? null)) }}" class="w-full h-full object-contain p-1 cursor-pointer hover:opacity-90 transition-opacity"
                                            alt="Front View Mugshot"
                                            data-img-src="{{ ($suspect['resolved_view']['sFront'] ?? ($suspect['mugshot_front'] ?? null)) }}"
                                            data-img-title="Suspect #{{ ($suspect['resolved_view']['sNum'] ?? ($suspect['suspect_number'] ?? ($index + 1))) }} Front View (0°) — {{ ($suspect['resolved_view']['sName'] ?? ($suspect['suspect_name'] ?? 'Suspect')) }}"
                                            onclick="openMugshotModal(this.getAttribute('data-img-src'), this.getAttribute('data-img-title'))"
                                            onerror="if(!this.dataset.triedStorage &amp;&amp; !this.src.startsWith('data:')){this.dataset.triedStorage=1; this.src='{{ asset('storage/') }}/' + this.src.split('/').pop();}">
                                    @else
                                        <div class="text-center p-3 text-slate-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 text-slate-300" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-[10px] font-mono-code block">No Front Photo</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Angle 2: Left Profile -->
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-3">
                                <div class="flex items-center justify-between text-xs font-mono-code">
                                    <span
                                        class="px-2 py-0.5 rounded bg-slate-800 text-white font-bold text-[10px]">ANGLE
                                        2</span>
                                    <span class="font-bold text-slate-700 text-[11px]">Left Profile (90&deg;)</span>
                                    <span
                                        class="font-bold text-[10px] {{ ($suspect['resolved_view']['sLeft'] ?? ($suspect['mugshot_left'] ?? null)) ? 'text-emerald-700' : 'text-slate-400' }}">{{ ($suspect['resolved_view']['sLeft'] ?? ($suspect['mugshot_left'] ?? null)) ? '✓ CAPTURED' : 'NOT CAPTURED' }}</span>
                                </div>
                                <div
                                    class="h-44 w-full bg-slate-100 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden">
                                    @if (($suspect['resolved_view']['sLeft'] ?? ($suspect['mugshot_left'] ?? null)))
                                        <img src="{{ ($suspect['resolved_view']['sLeft'] ?? ($suspect['mugshot_left'] ?? null)) }}" class="w-full h-full object-contain p-1 cursor-pointer hover:opacity-90 transition-opacity"
                                            alt="Left Profile Mugshot"
                                            data-img-src="{{ ($suspect['resolved_view']['sLeft'] ?? ($suspect['mugshot_left'] ?? null)) }}"
                                            data-img-title="Suspect #{{ ($suspect['resolved_view']['sNum'] ?? ($suspect['suspect_number'] ?? ($index + 1))) }} Left Profile (90°) — {{ ($suspect['resolved_view']['sName'] ?? ($suspect['suspect_name'] ?? 'Suspect')) }}"
                                            onclick="openMugshotModal(this.getAttribute('data-img-src'), this.getAttribute('data-img-title'))"
                                            onerror="if(!this.dataset.triedStorage &amp;&amp; !this.src.startsWith('data:')){this.dataset.triedStorage=1; this.src='{{ asset('storage/') }}/' + this.src.split('/').pop();}">
                                    @else
                                        <div class="text-center p-3 text-slate-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 text-slate-300" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-[10px] font-mono-code block">No Left Photo</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Angle 3: Right Profile -->
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-3">
                                <div class="flex items-center justify-between text-xs font-mono-code">
                                    <span
                                        class="px-2 py-0.5 rounded bg-slate-800 text-white font-bold text-[10px]">ANGLE
                                        3</span>
                                    <span class="font-bold text-slate-700 text-[11px]">Right Profile (270&deg;)</span>
                                    <span
                                        class="font-bold text-[10px] {{ ($suspect['resolved_view']['sRight'] ?? ($suspect['mugshot_right'] ?? null)) ? 'text-emerald-700' : 'text-slate-400' }}">{{ ($suspect['resolved_view']['sRight'] ?? ($suspect['mugshot_right'] ?? null)) ? '✓ CAPTURED' : 'NOT CAPTURED' }}</span>
                                </div>
                                <div
                                    class="h-44 w-full bg-slate-100 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden">
                                    @if (($suspect['resolved_view']['sRight'] ?? ($suspect['mugshot_right'] ?? null)))
                                        <img src="{{ ($suspect['resolved_view']['sRight'] ?? ($suspect['mugshot_right'] ?? null)) }}" class="w-full h-full object-contain p-1 cursor-pointer hover:opacity-90 transition-opacity"
                                            alt="Right Profile Mugshot"
                                            data-img-src="{{ ($suspect['resolved_view']['sRight'] ?? ($suspect['mugshot_right'] ?? null)) }}"
                                            data-img-title="Suspect #{{ ($suspect['resolved_view']['sNum'] ?? ($suspect['suspect_number'] ?? ($index + 1))) }} Right Profile (270°) — {{ ($suspect['resolved_view']['sName'] ?? ($suspect['suspect_name'] ?? 'Suspect')) }}"
                                            onclick="openMugshotModal(this.getAttribute('data-img-src'), this.getAttribute('data-img-title'))"
                                            onerror="if(!this.dataset.triedStorage &amp;&amp; !this.src.startsWith('data:')){this.dataset.triedStorage=1; this.src='{{ asset('storage/') }}/' + this.src.split('/').pop();}">
                                    @else
                                        <div class="text-center p-3 text-slate-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 text-slate-300" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-[10px] font-mono-code block">No Right Photo</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
