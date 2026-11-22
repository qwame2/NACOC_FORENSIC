<!-- VAULT STORAGE SUSPECT EXHIBIT DETAILS POPUP MODAL (DG & FORENSIC HEAD) -->
<div id="vaultExhibitModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-2 sm:p-4 md:p-6 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="vaultModalTitle">
    <div class="bg-white rounded-2xl border border-slate-300 shadow-2xl max-w-5xl lg:max-w-6xl w-full max-h-[92vh] flex flex-col overflow-hidden my-auto relative animate-in fade-in zoom-in-95 duration-150">
        
        <!-- MODAL HEADER -->
        <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between border-b border-slate-800 shrink-0">
            <div class="flex items-center space-x-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-mono-code font-extrabold uppercase px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            Vault Storage &bull; Complete Exhibit Dossier
                        </span>
                        <span class="text-xs font-mono-code font-bold text-slate-300" id="vaultModalCaseRef">NACOC/2026/0000</span>
                    </div>
                    <h3 class="text-base sm:text-lg font-black text-white mt-0.5 line-clamp-1" id="vaultModalTitle">Exhibit Manifest</h3>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button type="button" onclick="closeVaultExhibitModal()" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-all cursor-pointer border border-slate-700" title="Close Exhibit Dossier">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- SUSPECT PROFILE BANNER -->
        <div class="bg-slate-50 border-b border-slate-200 px-6 py-3.5 flex flex-wrap items-center justify-between gap-3 shrink-0">
            <div class="flex items-center space-x-3.5">
                <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center shadow-xs" id="vaultModalSuspectPicWrap">
                    <img id="vaultModalSuspectPic" src="" alt="Suspect" class="w-full h-full object-cover hidden">
                    <div id="vaultModalSuspectInitial" class="w-full h-full flex items-center justify-center font-black text-slate-700 text-sm bg-slate-200 font-mono-code">
                        S
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-extrabold text-sm text-slate-900" id="vaultModalSuspectName">Primary Suspect</span>
                        <span class="text-[10px] font-mono-code font-bold px-2 py-0.5 rounded-full border bg-amber-50 text-amber-800 border-amber-300" id="vaultModalSuspectDisp">
                            Under Investigation
                        </span>
                        <span class="text-[9.5px] font-mono-code font-bold px-1.5 py-0.2 rounded bg-blue-50 text-blue-700 border border-blue-200 uppercase" id="vaultModalPriority">HIGH</span>
                    </div>
                    <div class="text-[11px] text-slate-500 font-mono-code flex items-center gap-2 mt-0.5 flex-wrap">
                        <span>AKA: <strong class="text-slate-700 font-semibold" id="vaultModalSuspectAlias">N/A</strong></span>
                        <span>&bull;</span>
                        <span id="vaultModalUnit" class="text-blue-700 font-bold">Evidence Management</span>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-[10px] font-mono-code font-bold uppercase text-slate-400">Incident / Raid Location</div>
                <div class="text-xs font-bold text-slate-700 flex items-center gap-1 justify-end mt-0.5">
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span id="vaultModalRaidLocation" class="truncate max-w-[260px]">Location Undisclosed</span>
                </div>
            </div>
        </div>

        <!-- CUSTODIAL SUMMARY BAR -->
        <div class="mx-6 my-3 p-3 bg-gradient-to-r from-amber-50/90 via-slate-50 to-emerald-50/70 border border-amber-200/90 rounded-xl flex flex-wrap items-center justify-between gap-3 text-xs font-mono-code shrink-0">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="font-bold text-slate-800">Total Exhibits Belonging to Suspect in Vault:</span>
                <span id="vaultModalExhibitsCount" class="font-black px-2.5 py-0.5 rounded-full bg-amber-200 text-amber-900 text-[10.5px]">0 Items</span>
            </div>
            <div class="flex items-center gap-4 text-slate-600 flex-wrap">
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="font-bold text-slate-800">Vault Location:</span>
                    <span id="vaultModalHoldingLoc" class="text-amber-900 font-bold">NACOC Central Vault</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="font-bold text-slate-800">Integrity:</span>
                    <span class="text-emerald-800 font-extrabold uppercase">100% Sealed &amp; Logged</span>
                </div>
            </div>
        </div>

        <!-- SCROLLABLE EXHIBITS LIST (FULL DETAILS OF EVERYTHING) -->
        <div class="px-6 py-2 flex-1 overflow-y-auto space-y-4" id="vaultModalExhibitsList">
            <!-- Dynamically populated exhibit cards with complete details -->
        </div>

        <!-- MODAL FOOTER -->
        <div class="bg-slate-50 border-t border-slate-200 px-6 py-3.5 flex items-center justify-between shrink-0">
            <div class="text-[11px] text-slate-500 font-mono-code flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>NACOC Central Vault Archival, Integrity &amp; Chain of Custody</span>
            </div>
            <div class="flex items-center space-x-2.5">
                <button type="button" onclick="closeVaultExhibitModal()" class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 transition-all cursor-pointer shadow-2xs">
                    Close
                </button>
                <a id="vaultModalReviewLink" href="#" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-extrabold rounded-xl shadow-md border-none flex items-center gap-1.5 transition-all text-decoration-none">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <span>Open Case Review</span>
                </a>
            </div>
        </div>

    </div>
</div>

<!-- LIGHTBOX MODAL FOR FULL-SIZE EXHIBIT PHOTOGRAPHIC EVIDENCE -->
<div id="vaultPhotoLightboxModal" class="hidden fixed inset-0 z-[60] bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-3 sm:p-5" onclick="closeVaultPhotoLightbox(event)">
    <div class="relative max-w-4xl max-h-[90vh] flex flex-col items-center" onclick="event.stopPropagation()">
        <div class="absolute -top-12 right-0 flex items-center space-x-2">
            <a id="vaultLightboxNewTab" href="#" target="_blank" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-mono-code font-bold flex items-center gap-1.5 transition-all text-decoration-none shadow-xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>Open Original</span>
            </a>
            <button type="button" onclick="closeVaultPhotoLightbox()" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-white flex items-center justify-center transition-all cursor-pointer shadow-xs border border-slate-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <img id="vaultLightboxImg" src="" alt="Exhibit Evidence Image" class="max-w-full max-h-[80vh] object-contain rounded-2xl shadow-2xl border border-slate-700/80">
        <div id="vaultLightboxCaption" class="mt-3 text-xs font-mono-code text-slate-200 font-bold bg-slate-900/90 px-4 py-1.5 rounded-full border border-slate-700/80 shadow-xs">
            Exhibit Photo
        </div>
    </div>
</div>

<script>
    /**
     * Open Vault Exhibit Modal with full details of exhibits belonging to the suspect
     */
    function openVaultExhibitModal(payload) {
        if (!payload) return;
        const modal = document.getElementById('vaultExhibitModal');
        if (!modal) return;

        // Basic Header & Case Info
        const caseRefEl = document.getElementById('vaultModalCaseRef');
        if (caseRefEl) caseRefEl.innerText = payload.case_number || 'N/A';

        const titleEl = document.getElementById('vaultModalTitle');
        if (titleEl) titleEl.innerText = payload.title || 'Exhibit Dossier';

        const unitEl = document.getElementById('vaultModalUnit');
        if (unitEl) unitEl.innerText = payload.unit_name || 'Forensic Department';

        const prioEl = document.getElementById('vaultModalPriority');
        if (prioEl) prioEl.innerText = (payload.priority || 'HIGH').toUpperCase();

        // Suspect Profile
        const sNameEl = document.getElementById('vaultModalSuspectName');
        if (sNameEl) sNameEl.innerText = payload.suspect_name || 'Unassigned Target';

        const sAliasEl = document.getElementById('vaultModalSuspectAlias');
        if (sAliasEl) sAliasEl.innerText = payload.suspect_alias || 'N/A';

        const sDispEl = document.getElementById('vaultModalSuspectDisp');
        if (sDispEl) sDispEl.innerText = payload.suspect_disposition || 'Under Investigation';

        const sLocEl = document.getElementById('vaultModalRaidLocation');
        if (sLocEl) sLocEl.innerText = payload.raid_location || 'Location Undisclosed';

        // Suspect Photo / Initial
        const sPic = document.getElementById('vaultModalSuspectPic');
        const sInitial = document.getElementById('vaultModalSuspectInitial');
        if (sPic && sInitial) {
            if (payload.suspect_pic) {
                sPic.src = payload.suspect_pic;
                sPic.classList.remove('hidden');
                sInitial.classList.add('hidden');
            } else {
                sPic.classList.add('hidden');
                sInitial.innerText = (payload.suspect_name || 'S').trim().substring(0, 1).toUpperCase();
                sInitial.classList.remove('hidden');
            }
        }

        // Review Link
        const reviewLink = document.getElementById('vaultModalReviewLink');
        if (reviewLink && payload.review_url) {
            reviewLink.href = payload.review_url;
        }

        // Exhibits List Render
        const exhibits = payload.exhibits || [];
        const countEl = document.getElementById('vaultModalExhibitsCount');
        if (countEl) countEl.innerText = exhibits.length + (exhibits.length === 1 ? ' Item' : ' Items');

        // Vault holding facility
        const holdingEl = document.getElementById('vaultModalHoldingLoc');
        if (holdingEl) {
            holdingEl.innerText = (exhibits.length > 0 && exhibits[0].location) ? exhibits[0].location : 'NACOC Central Vault';
        }

        const listContainer = document.getElementById('vaultModalExhibitsList');
        if (listContainer) {
            if (exhibits.length === 0) {
                listContainer.innerHTML = `
                    <div class="p-12 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-300 font-mono-code text-xs text-slate-500">
                        <svg class="w-10 h-10 mx-auto text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <p class="font-bold text-slate-700 text-sm">No exhibits cataloged in Vault Storage</p>
                        <p class="text-[11px] text-slate-400 mt-1">Exhibit intake registration pending from field seizure team.</p>
                    </div>
                `;
            } else {
                let html = '';
                exhibits.forEach((ex, idx) => {
                    const cat = (ex.category || 'General').toLowerCase();
                    let badgeClass = 'bg-slate-100 text-slate-800 border-slate-300';
                    let categoryIcon = `
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>`;

                    if (cat.includes('drug') || cat.includes('narcotic') || cat.includes('powder') || cat.includes('weed') || cat.includes('cannabis') || cat.includes('cocaine') || cat.includes('heroin')) {
                        badgeClass = 'bg-rose-50 text-rose-800 border-rose-300';
                        categoryIcon = `
                            <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>`;
                    } else if (cat.includes('weapon') || cat.includes('firearm') || cat.includes('ammo') || cat.includes('gun')) {
                        badgeClass = 'bg-amber-50 text-amber-900 border-amber-300';
                        categoryIcon = `
                            <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>`;
                    } else if (cat.includes('device') || cat.includes('electron') || cat.includes('phone') || cat.includes('laptop') || cat.includes('digital')) {
                        badgeClass = 'bg-purple-50 text-purple-800 border-purple-300';
                        categoryIcon = `
                            <svg class="w-3.5 h-3.5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>`;
                    } else if (cat.includes('vehic') || cat.includes('car') || cat.includes('truck') || cat.includes('auto')) {
                        badgeClass = 'bg-blue-50 text-blue-800 border-blue-300';
                        categoryIcon = `
                            <svg class="w-3.5 h-3.5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                            </svg>`;
                    } else if (cat.includes('money') || cat.includes('cash') || cat.includes('currency') || cat.includes('gold')) {
                        badgeClass = 'bg-emerald-50 text-emerald-800 border-emerald-300';
                        categoryIcon = `
                            <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>`;
                    }

                    // Build Seizure & Intake Metrics Grid
                    const intakeGrid = [
                        { label: 'Seizure Date & Time', val: (ex.seizure_date || '') + (ex.seizure_time ? ' ' + ex.seizure_time : '') },
                        { label: 'Seizure Location', val: ex.seizure_location || payload.raid_location },
                        { label: 'Seizing Officer', val: ex.seizure_officer },
                        { label: 'Investigating Officer', val: ex.investigating_officer },
                        { label: 'Originating Unit', val: ex.requesting_unit || payload.unit_name },
                        { label: 'Source of Exhibits', val: ex.source_of_exhibits },
                        { label: 'Reason for Seizure', val: ex.seizure_reason },
                        { label: 'Estimated Street Value', val: ex.estimated_value },
                        { label: 'Packaging Specification', val: ex.packaging },
                        { label: 'Concealment Method', val: ex.concealment },
                        { label: 'Vault Custody Rack / Safe', val: ex.location },
                        { label: 'Custodial Status', val: ex.custodial_status || 'In Vault Storage' }
                    ].filter(item => item.val && item.val !== 'N/A' && item.val !== 'null');

                    let intakeGridHtml = '';
                    if (intakeGrid.length > 0) {
                        intakeGridHtml = `
                            <div class="mt-3 pt-3 border-t border-slate-200">
                                <div class="text-[10.5px] font-mono-code font-extrabold uppercase text-slate-700 mb-2 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <span>Seizure, Intake &amp; Custody Metrics</span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                                    ${intakeGrid.map(item => `
                                        <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
                                            <div class="text-[9px] font-mono-code font-bold uppercase text-slate-400 leading-tight">${item.label}</div>
                                            <div class="text-[11px] font-bold text-slate-900 font-mono-code mt-0.5 truncate" title="${item.val}">${item.val}</div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }

                    // Specialized Card: Lab & Chemical Analysis (if present)
                    let labCardHtml = '';
                    if (ex.lab && Object.keys(ex.lab).length > 0) {
                        const labItems = Object.entries(ex.lab).filter(([_, v]) => v && v !== 'N/A');
                        if (labItems.length > 0) {
                            labCardHtml = `
                                <div class="mt-3 p-3.5 bg-rose-50/50 rounded-xl border border-rose-200/80 space-y-2">
                                    <div class="flex items-center justify-between text-xs font-mono-code">
                                        <span class="font-extrabold text-rose-900 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                            </svg>
                                            <span>Laboratory Chemical Analysis &amp; Narcotics Profile</span>
                                        </span>
                                        <span class="text-[9.5px] px-2 py-0.5 rounded font-black bg-rose-100 text-rose-800 border border-rose-300 uppercase">Chemical Dossier</span>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 text-xs font-mono-code pt-1">
                                        ${labItems.map(([k, v]) => `
                                            <div class="bg-white p-2.5 rounded-lg border border-rose-100 shadow-2xs">
                                                <div class="text-[9px] font-bold uppercase text-rose-500">${k}</div>
                                                <div class="text-[11px] font-black text-slate-900 mt-0.5 truncate" title="${v}">${v}</div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            `;
                        }
                    }

                    // Specialized Card: Weapon & Ballistics (if present)
                    let wpnCardHtml = '';
                    if (ex.weapon && Object.keys(ex.weapon).length > 0) {
                        const wpnItems = Object.entries(ex.weapon).filter(([_, v]) => v && v !== 'N/A');
                        if (wpnItems.length > 0) {
                            wpnCardHtml = `
                                <div class="mt-3 p-3.5 bg-amber-50/60 rounded-xl border border-amber-200/80 space-y-2">
                                    <div class="flex items-center justify-between text-xs font-mono-code">
                                        <span class="font-extrabold text-amber-900 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            <span>Ballistics, Firearm &amp; Ammunition Specifications</span>
                                        </span>
                                        <span class="text-[9.5px] px-2 py-0.5 rounded font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase">Ballistics Log</span>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 text-xs font-mono-code pt-1">
                                        ${wpnItems.map(([k, v]) => `
                                            <div class="bg-white p-2.5 rounded-lg border border-amber-200/80 shadow-2xs">
                                                <div class="text-[9px] font-bold uppercase text-amber-600">${k}</div>
                                                <div class="text-[11px] font-black text-slate-900 mt-0.5 truncate" title="${v}">${v}</div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            `;
                        }
                    }

                    // Specialized Card: Vehicle Specifications (if present)
                    let vehCardHtml = '';
                    if (ex.vehicle && Object.keys(ex.vehicle).length > 0) {
                        const vehItems = Object.entries(ex.vehicle).filter(([_, v]) => v && v !== 'N/A');
                        if (vehItems.length > 0) {
                            vehCardHtml = `
                                <div class="mt-3 p-3.5 bg-blue-50/50 rounded-xl border border-blue-200/80 space-y-2">
                                    <div class="flex items-center justify-between text-xs font-mono-code">
                                        <span class="font-extrabold text-blue-900 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                            </svg>
                                            <span>Vehicle &amp; Conveyance Specifications</span>
                                        </span>
                                        <span class="text-[9.5px] px-2 py-0.5 rounded font-black bg-blue-100 text-blue-900 border border-blue-300 uppercase">Transport Log</span>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 text-xs font-mono-code pt-1">
                                        ${vehItems.map(([k, v]) => `
                                            <div class="bg-white p-2.5 rounded-lg border border-blue-100 shadow-2xs">
                                                <div class="text-[9px] font-bold uppercase text-blue-500">${k}</div>
                                                <div class="text-[11px] font-black text-slate-900 mt-0.5 truncate" title="${v}">${v}</div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            `;
                        }
                    }

                    // Specialized Card: Digital & Electronic Devices (if present)
                    let devCardHtml = '';
                    if (ex.device && Object.keys(ex.device).length > 0) {
                        const devItems = Object.entries(ex.device).filter(([_, v]) => v && v !== 'N/A');
                        if (devItems.length > 0) {
                            devCardHtml = `
                                <div class="mt-3 p-3.5 bg-purple-50/50 rounded-xl border border-purple-200/80 space-y-2">
                                    <div class="flex items-center justify-between text-xs font-mono-code">
                                        <span class="font-extrabold text-purple-900 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                            <span>Digital Forensics &amp; Device Identity</span>
                                        </span>
                                        <span class="text-[9.5px] px-2 py-0.5 rounded font-black bg-purple-100 text-purple-900 border border-purple-300 uppercase">Digital Profile</span>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 text-xs font-mono-code pt-1">
                                        ${devItems.map(([k, v]) => `
                                            <div class="bg-white p-2.5 rounded-lg border border-purple-100 shadow-2xs">
                                                <div class="text-[9px] font-bold uppercase text-purple-500">${k}</div>
                                                <div class="text-[11px] font-black text-slate-900 mt-0.5 truncate" title="${v}">${v}</div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            `;
                        }
                    }

                    // Build photos preview
                    let photosHtml = '';
                    if (ex.photos && Array.isArray(ex.photos) && ex.photos.length > 0) {
                        photosHtml = `
                            <div class="mt-4 pt-3.5 border-t border-slate-200">
                                <div class="flex items-center justify-between mb-2.5">
                                    <div class="text-[11px] font-mono-code font-black uppercase text-slate-800 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span>Exhibit Photographic Evidence &amp; Forensic Imagery</span>
                                    </div>
                                    <span class="text-[9.5px] px-2 py-0.5 rounded-full font-black bg-blue-100 text-blue-800 border border-blue-200 font-mono-code">
                                        ${ex.photos.length} ${ex.photos.length === 1 ? 'Photograph' : 'Photographs'} Attached
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                    ${ex.photos.map((p, pIdx) => `
                                        <div class="group relative rounded-xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-md transition-all bg-slate-900 cursor-pointer" onclick="openVaultPhotoLightbox('${p}', '${(ex.commodity_name || 'Exhibit').replace(/'/g, "\\'")} - Photo #${pIdx + 1}')">
                                            <div class="aspect-video sm:aspect-square w-full overflow-hidden flex items-center justify-center bg-slate-100">
                                                <img src="${p}" alt="Exhibit Photo #${pIdx + 1}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                            </div>
                                            <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-white p-2 text-center">
                                                <svg class="w-6 h-6 text-white drop-shadow mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                                </svg>
                                                <span class="text-[10px] font-mono-code font-bold drop-shadow">Enlarge Image</span>
                                            </div>
                                            <div class="absolute bottom-1.5 left-1.5 right-1.5 flex items-center justify-between text-[9px] font-mono-code font-bold bg-slate-900/80 text-white px-2 py-0.5 rounded-md backdrop-blur-xs">
                                                <span>Photo #${pIdx + 1}</span>
                                                <span class="text-slate-300">Evidence</span>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    } else {
                        photosHtml = `
                            <div class="mt-3 pt-3 border-t border-slate-200 flex items-center gap-2 text-slate-400 font-mono-code text-[10.5px]">
                                <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>No digital photographic evidence attached to this exhibit record.</span>
                            </div>
                        `;
                    }

                    // Chain of Custody & Description / Observations
                    let custodyAndNotesHtml = '';
                    if (ex.description || ex.remark || ex.chain_of_custody) {
                        custodyAndNotesHtml = `
                            <div class="mt-3 pt-3 border-t border-slate-200 space-y-2">
                                ${ex.chain_of_custody ? `
                                    <div class="p-2.5 rounded-xl bg-slate-900 text-white font-mono-code text-xs flex flex-wrap items-center justify-between gap-2 shadow-xs">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-amber-400 font-extrabold uppercase text-[10px] tracking-wider">Custody Pathway:</span>
                                            <span class="text-slate-200 font-bold text-[11px]">${ex.chain_of_custody}</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 uppercase">Secured</span>
                                    </div>
                                ` : ''}

                                ${ex.description ? `
                                    <div class="p-3 rounded-xl bg-white border border-slate-200 text-xs text-slate-800 leading-relaxed font-sans shadow-2xs">
                                        <span class="font-extrabold text-slate-900 font-mono-code text-[10px] uppercase block mb-1 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h7"/>
                                            </svg>
                                            <span>Full Manifest &amp; Detailed Content Description:</span>
                                        </span>
                                        ${ex.description}
                                    </div>
                                ` : ''}

                                ${ex.remark ? `
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/90 text-xs text-slate-700 leading-relaxed font-sans">
                                        <span class="font-extrabold text-slate-800 font-mono-code text-[10px] uppercase block mb-1 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                            </svg>
                                            <span>Vault Custodian Notes &amp; Observations:</span>
                                        </span>
                                        ${ex.remark}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    }

                    html += `
                        <div class="bg-slate-50/70 rounded-2xl border border-slate-200/90 p-4 sm:p-5 space-y-3 hover:border-slate-300 transition-colors shadow-xs">
                            
                            <!-- EXHIBIT HEADER BADGES & AMOUNTS -->
                            <div class="flex items-start justify-between flex-wrap gap-2.5">
                                <div class="flex items-center flex-wrap gap-2">
                                    <span class="px-2.5 py-1 rounded-lg font-mono-code font-black text-xs bg-slate-900 text-white shadow-xs">
                                        ${ex.exhibit_number || ('EXH-' + (idx + 1))}
                                    </span>
                                    ${ex.property_id ? `
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono-code bg-slate-200 text-slate-800 border border-slate-300" title="Property ID">
                                            PID: ${ex.property_id}
                                        </span>
                                    ` : ''}
                                    ${ex.lab_exhibit_no ? `
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono-code bg-indigo-50 text-indigo-800 border border-indigo-200" title="Lab Exhibit #">
                                            LAB: ${ex.lab_exhibit_no}
                                        </span>
                                    ` : ''}
                                    <span class="px-2.5 py-0.5 rounded-lg text-[10.5px] font-bold font-mono-code border flex items-center gap-1 ${badgeClass}">
                                        ${categoryIcon}
                                        <span>${ex.category || 'General'}</span>
                                    </span>
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold font-mono-code bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>${ex.custodial_status || 'In Vault Storage'}</span>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="text-xs font-mono-code font-black text-indigo-700 bg-indigo-50 border border-indigo-200 px-2.5 py-1 rounded-lg shadow-2xs">
                                        ${ex.quantity_or_weight || '1 Unit'}
                                    </div>
                                    ${ex.estimated_value ? `
                                        <div class="text-xs font-mono-code font-black text-emerald-800 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg shadow-2xs">
                                            ${ex.estimated_value}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>

                            <!-- COMMODITY NAME & TARGET SUSPECT STRIP -->
                            <div class="pt-1">
                                <h4 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">${ex.commodity_name || 'Seized Exhibit Item'}</h4>
                                <div class="text-[11px] text-slate-600 mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 font-mono-code">
                                    <span><strong>Suspect / Associated:</strong> <span class="text-slate-900 font-bold">${ex.suspect_name || payload.suspect_name || 'Primary Suspect'}</span></span>
                                    <span>&bull;</span>
                                    <span><strong>Intake Date:</strong> <span class="text-slate-800">${ex.seizure_date || 'Logged at Intake'}</span></span>
                                    <span>&bull;</span>
                                    <span><strong>Seizing Officer:</strong> <span class="text-slate-800">${ex.seizure_officer || 'Officer Recorded'}</span></span>
                                </div>
                            </div>

                            <!-- ALL SECTIONS -->
                            ${intakeGridHtml}
                            ${labCardHtml}
                            ${wpnCardHtml}
                            ${vehCardHtml}
                            ${devCardHtml}
                            ${custodyAndNotesHtml}
                            ${photosHtml}

                        </div>
                    `;
                });
                listContainer.innerHTML = html;
            }
        }

        // Display modal
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    /**
     * Close Vault Exhibit Modal
     */
    function closeVaultExhibitModal() {
        const modal = document.getElementById('vaultExhibitModal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }

    /**
     * Robust parser for vault payload supporting Base64, raw JSON, and escaped entities
     */
    function parseVaultPayload(raw) {
        if (!raw) return null;
        raw = raw.trim();

        // 1. Try Base64 decoding (supports UTF-8 Unicode)
        if (!raw.startsWith('{') && !raw.startsWith('[')) {
            try {
                const binString = atob(raw);
                const bytes = Uint8Array.from(binString, (m) => m.codePointAt(0));
                const jsonText = new TextDecoder().decode(bytes);
                return JSON.parse(jsonText);
            } catch (_) {
                try {
                    return JSON.parse(decodeURIComponent(escape(atob(raw))));
                } catch (_) {
                    try {
                        return JSON.parse(atob(raw));
                    } catch (_) {}
                }
            }
        }

        // 2. Direct JSON string parse
        try {
            return JSON.parse(raw);
        } catch (_) {}

        // 3. Fallback: decode HTML entities if double-escaped in HTML attribute
        try {
            const txt = document.createElement('textarea');
            txt.innerHTML = raw;
            const unescaped = txt.value;
            try {
                return JSON.parse(unescaped);
            } catch (_) {
                txt.innerHTML = unescaped;
                return JSON.parse(txt.value);
            }
        } catch (e) {
            console.error('Failed to parse vault exhibit payload:', e, raw);
            return null;
        }
    }

    /**
     * Handler for clicking eye icon on case register rows
     * When under Vault Storage filter, navigates to the dedicated Vault Storage Exhibit page.
     */
    function handleCaseEyeClick(event, button) {
        if (!button) return;

        // Check if currently filtered to Vault Storage
        const isVaultActive = (typeof currentStatusFilter !== 'undefined' && currentStatusFilter.toLowerCase().includes('vault'))
            || (new URLSearchParams(window.location.search).get('status') || '').toLowerCase().includes('vault')
            || (document.querySelector('.status-tab.active-tab')?.textContent || '').toLowerCase().includes('vault')
            || (document.getElementById('vaultFilterBadge') && !document.getElementById('vaultFilterBadge').classList.contains('hidden'));

        if (isVaultActive) {
            event.preventDefault();
            event.stopPropagation();
            const targetVaultUrl = button.getAttribute('data-vault-url') 
                || (button.href && button.href.includes('/vault-exhibits') ? button.href : button.href.replace('/review', '/vault-exhibits'));
            if (targetVaultUrl) {
                window.location.href = targetVaultUrl;
                return false;
            }
        }
    }

    // Close on Escape or click outside
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeVaultPhotoLightbox();
            closeVaultExhibitModal();
        }
    });

    /**
     * Lightbox functions for exhibit imagery
     */
    function openVaultPhotoLightbox(imgUrl, caption) {
        const modal = document.getElementById('vaultPhotoLightboxModal');
        const img = document.getElementById('vaultLightboxImg');
        const cap = document.getElementById('vaultLightboxCaption');
        const newTab = document.getElementById('vaultLightboxNewTab');
        if (modal && img) {
            img.src = imgUrl;
            if (cap) cap.innerText = caption || 'Exhibit Evidence Photograph';
            if (newTab) newTab.href = imgUrl;
            modal.classList.remove('hidden');
        }
    }

    function closeVaultPhotoLightbox() {
        const modal = document.getElementById('vaultPhotoLightboxModal');
        if (modal) modal.classList.add('hidden');
    }

    document.getElementById('vaultExhibitModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeVaultExhibitModal();
    });

    // Delegated click listener in case table rows are dynamically updated via AJAX
    document.addEventListener('click', function(e) {
        const eyeBtn = e.target.closest('.case-review-btn');
        if (eyeBtn) {
            const isVaultActive = (typeof currentStatusFilter !== 'undefined' && currentStatusFilter.toLowerCase().includes('vault'))
                || (new URLSearchParams(window.location.search).get('status') || '').toLowerCase().includes('vault')
                || (document.querySelector('.status-tab.active-tab')?.textContent || '').toLowerCase().includes('vault')
                || (document.getElementById('vaultFilterBadge') && !document.getElementById('vaultFilterBadge').classList.contains('hidden'));

            if (isVaultActive) {
                handleCaseEyeClick(e, eyeBtn);
            }
        }
    });
</script>
