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
@php
    $sArray = is_object($suspect) ? (method_exists($suspect, 'toArray') ? $suspect->toArray() : (array) $suspect) : (is_array($suspect) ? $suspect : []);

    $pickMugCandidate = static function (...$candidates): ?string {
        foreach ($candidates as $c) {
            if ($c !== null && $c !== false) {
                if (is_string($c)) {
                    $trim = trim($c);
                    if ($trim !== '' && $trim !== 'null' && $trim !== 'undefined' && $trim !== '#' && !str_ends_with($trim, '#') && !str_contains($trim, 'blank')) {
                        return $trim;
                    }
                } elseif (is_array($c)) {
                    $inner = $c['url'] ?? ($c['path'] ?? ($c['front'] ?? null));
                    if (is_string($inner) && trim($inner) !== '') {
                        return trim($inner);
                    }
                }
            }
        }
        return null;
    };

    $sFrontCandidate = $pickMugCandidate(
        $sArray['resolved_view']['sFront'] ?? null,
        $sArray['mugshot_front'] ?? null,
        $sArray['photo'] ?? null,
        $sArray['mugshots']['front'] ?? null,
        $sArray['mugshots']['front-profile'] ?? null,
        is_object($suspect) ? ($suspect->mugshot_front ?? ($suspect->photo ?? null)) : null,
        ($index === 0 && isset($case)) ? ($case->mugshot_front ?? ($case->photo ?? null)) : null,
        ($index === 0 && isset($payload)) ? ($payload['mugshot_front'] ?? ($payload['docket_data']['mugshot_front'] ?? null)) : null
    );
    $sFrontPhoto = \App\Services\CasePhotoResolver::resolveMugshotUrl($sFrontCandidate);

    $sLeftCandidate = $pickMugCandidate(
        $sArray['resolved_view']['sLeft'] ?? null,
        $sArray['mugshot_left'] ?? null,
        $sArray['mugshot_side'] ?? null,
        $sArray['mugshots']['left'] ?? null,
        $sArray['mugshots']['left-profile'] ?? null,
        is_object($suspect) ? ($suspect->mugshot_left ?? null) : null,
        ($index === 0 && isset($case)) ? ($case->mugshot_left ?? ($case->mugshot_side ?? null)) : null,
        ($index === 0 && isset($payload)) ? ($payload['mugshot_left'] ?? ($payload['mugshot_side'] ?? ($payload['docket_data']['mugshot_left'] ?? null))) : null
    );
    $sLeftPhoto = \App\Services\CasePhotoResolver::resolveMugshotUrl($sLeftCandidate);

    $sRightCandidate = $pickMugCandidate(
        $sArray['resolved_view']['sRight'] ?? null,
        $sArray['mugshot_right'] ?? null,
        $sArray['mugshots']['right'] ?? null,
        $sArray['mugshots']['right-profile'] ?? null,
        is_object($suspect) ? ($suspect->mugshot_right ?? null) : null,
        ($index === 0 && isset($case)) ? ($case->mugshot_right ?? null) : null,
        ($index === 0 && isset($payload)) ? ($payload['mugshot_right'] ?? ($payload['docket_data']['mugshot_right'] ?? null)) : null
    );
    $sRightPhoto = \App\Services\CasePhotoResolver::resolveMugshotUrl($sRightCandidate);

    $sNum = $sArray['resolved_view']['sNum'] ?? ($sArray['suspect_number'] ?? (is_object($suspect) ? ($suspect->suspect_number ?? ($index + 1)) : ($index + 1)));
    $sName = $sArray['resolved_view']['sName'] ?? ($sArray['suspect_name'] ?? (is_object($suspect) ? ($suspect->suspect_name ?? 'Suspect') : 'Suspect'));
@endphp
                    <!-- MUGSHOT (MULTI-ANGLE PHOTO CAPTURE FOR SUSPECT #{{ $sNum }}) -->
                    <div class="dossier-card p-6 border border-slate-200" data-field-group="mugshots" data-rollback-target="Biometric Mugshots">
                        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-xs font-mono-code font-extrabold text-blue-700 uppercase tracking-wider">
                                    SUSPECT #{{ $sNum }} MUGSHOTS
                                </span>
                                <div>
                                    <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                        {{ $sName }} — Biometric Mugshots
                                        @if ((!empty($sArray['resolved_view']['isAssociate']) || !empty($sArray['is_associate'])))
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-mono-code font-extrabold bg-amber-500 text-white shadow-2xs">{{ strtoupper(($sArray['resolved_view']['sRoleLabel'] ?? ($sArray['association_role'] ?? ($sArray['role'] ?? 'PRIMARY SUSPECT')))) }}</span>
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-mono-code font-extrabold bg-slate-900 text-amber-300 shadow-2xs border border-slate-700">ID: {{ ($sArray['resolved_view']['sId'] ?? ($sArray['associate_id'] ?? ($sArray['suspect_id'] ?? 'PRIM-001'))) }}</span>
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
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-3" data-rollback-target="Front Mugshot (Angle 1)">
                                <div class="flex items-center justify-between text-xs font-mono-code">
                                    <span
                                        class="px-2 py-0.5 rounded bg-emerald-600 text-white font-bold text-[10px]">ANGLE
                                        1</span>
                                    <span class="font-bold text-slate-700 text-[11px]">Front View (0&deg;)</span>
                                    <span
                                        class="font-bold text-[10px] {{ $sFrontPhoto ? 'text-emerald-700' : 'text-slate-400' }}">{{ $sFrontPhoto ? '✓ CAPTURED' : 'NOT CAPTURED' }}</span>
                                </div>
                                <div
                                    class="h-44 w-full bg-slate-100 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden relative">
                                    @if ($sFrontPhoto)
                                        <img src="{{ $sFrontPhoto }}" class="w-full h-full object-contain p-1 cursor-pointer hover:opacity-90 transition-opacity"
                                            alt="Front View Mugshot"
                                            data-img-src="{{ $sFrontPhoto }}"
                                            data-img-title="Suspect #{{ $sNum }} Front View (0°) — {{ $sName }}"
                                            onclick="if(typeof openMugshotModal==='function'){openMugshotModal(this.getAttribute('data-img-src')||this.src, this.getAttribute('data-img-title'));}else if(typeof openImageLightbox==='function'){openImageLightbox(this.getAttribute('data-img-src')||this.src, this.getAttribute('data-img-title'));}"
                                            onerror="if(!this.dataset.triedStorage && !this.src.startsWith('data:')){this.dataset.triedStorage=1; var cleanPath=(this.getAttribute('data-img-src')||'').replace(/^\/+/, ''); if(!cleanPath.startsWith('http') && !cleanPath.startsWith('storage/')){this.src='{{ asset('storage') }}/' + cleanPath; return;} } this.style.display='none'; var fb=this.parentElement.querySelector('.mugshot-empty-fallback'); if(fb){fb.classList.remove('hidden');}">
                                        <div class="mugshot-empty-fallback hidden text-center p-3 text-slate-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 text-slate-300" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-[10px] font-mono-code block">No Front Photo</span>
                                        </div>
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
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-3" data-rollback-target="Left Profile Mugshot (Angle 2)">
                                <div class="flex items-center justify-between text-xs font-mono-code">
                                    <span
                                        class="px-2 py-0.5 rounded bg-slate-800 text-white font-bold text-[10px]">ANGLE
                                        2</span>
                                    <span class="font-bold text-slate-700 text-[11px]">Left Profile (90&deg;)</span>
                                    <span
                                        class="font-bold text-[10px] {{ $sLeftPhoto ? 'text-emerald-700' : 'text-slate-400' }}">{{ $sLeftPhoto ? '✓ CAPTURED' : 'NOT CAPTURED' }}</span>
                                </div>
                                <div
                                    class="h-44 w-full bg-slate-100 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden relative">
                                    @if ($sLeftPhoto)
                                        <img src="{{ $sLeftPhoto }}" class="w-full h-full object-contain p-1 cursor-pointer hover:opacity-90 transition-opacity"
                                            alt="Left Profile Mugshot"
                                            data-img-src="{{ $sLeftPhoto }}"
                                            data-img-title="Suspect #{{ $sNum }} Left Profile (90°) — {{ $sName }}"
                                            onclick="if(typeof openMugshotModal==='function'){openMugshotModal(this.getAttribute('data-img-src')||this.src, this.getAttribute('data-img-title'));}else if(typeof openImageLightbox==='function'){openImageLightbox(this.getAttribute('data-img-src')||this.src, this.getAttribute('data-img-title'));}"
                                            onerror="if(!this.dataset.triedStorage && !this.src.startsWith('data:')){this.dataset.triedStorage=1; var cleanPath=(this.getAttribute('data-img-src')||'').replace(/^\/+/, ''); if(!cleanPath.startsWith('http') && !cleanPath.startsWith('storage/')){this.src='{{ asset('storage') }}/' + cleanPath; return;} } this.style.display='none'; var fb=this.parentElement.querySelector('.mugshot-empty-fallback'); if(fb){fb.classList.remove('hidden');}">
                                        <div class="mugshot-empty-fallback hidden text-center p-3 text-slate-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 text-slate-300" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-[10px] font-mono-code block">No Left Photo</span>
                                        </div>
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
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-3" data-rollback-target="Right Profile Mugshot (Angle 3)">
                                <div class="flex items-center justify-between text-xs font-mono-code">
                                    <span
                                        class="px-2 py-0.5 rounded bg-slate-800 text-white font-bold text-[10px]">ANGLE
                                        3</span>
                                    <span class="font-bold text-slate-700 text-[11px]">Right Profile (270&deg;)</span>
                                    <span
                                        class="font-bold text-[10px] {{ $sRightPhoto ? 'text-emerald-700' : 'text-slate-400' }}">{{ $sRightPhoto ? '✓ CAPTURED' : 'NOT CAPTURED' }}</span>
                                </div>
                                <div
                                    class="h-44 w-full bg-slate-100 rounded-lg border border-slate-300 flex items-center justify-center overflow-hidden relative">
                                    @if ($sRightPhoto)
                                        <img src="{{ $sRightPhoto }}" class="w-full h-full object-contain p-1 cursor-pointer hover:opacity-90 transition-opacity"
                                            alt="Right Profile Mugshot"
                                            data-img-src="{{ $sRightPhoto }}"
                                            data-img-title="Suspect #{{ $sNum }} Right Profile (270°) — {{ $sName }}"
                                            onclick="if(typeof openMugshotModal==='function'){openMugshotModal(this.getAttribute('data-img-src')||this.src, this.getAttribute('data-img-title'));}else if(typeof openImageLightbox==='function'){openImageLightbox(this.getAttribute('data-img-src')||this.src, this.getAttribute('data-img-title'));}"
                                            onerror="if(!this.dataset.triedStorage && !this.src.startsWith('data:')){this.dataset.triedStorage=1; var cleanPath=(this.getAttribute('data-img-src')||'').replace(/^\/+/, ''); if(!cleanPath.startsWith('http') && !cleanPath.startsWith('storage/')){this.src='{{ asset('storage') }}/' + cleanPath; return;} } this.style.display='none'; var fb=this.parentElement.querySelector('.mugshot-empty-fallback'); if(fb){fb.classList.remove('hidden');}">
                                        <div class="mugshot-empty-fallback hidden text-center p-3 text-slate-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 text-slate-300" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-[10px] font-mono-code block">No Right Photo</span>
                                        </div>
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
