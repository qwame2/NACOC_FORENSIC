<!-- INCIDENT GEOLOCATION RADAR & INTERACTIVE PINPOINT COMPONENT -->
<div id="incidentLocationMapWrapper" class="mt-4 pt-1 border-t border-slate-100">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
        <div class="flex items-center space-x-2">
            <span class="relative flex h-2.5 w-2.5">
                <span id="incidentMapStatusPing" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 hidden"></span>
                <span id="incidentMapStatusDot" class="relative inline-flex rounded-full h-2.5 w-2.5 bg-slate-400"></span>
            </span>
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-800">
                Incident Location Pinpoint &amp; GPS Radar
            </span>
            <span id="incidentMapSourceBadge" class="hidden text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">
                Live Geocoded
            </span>
        </div>

        <div class="flex items-center space-x-3 text-xs">
            <div id="incidentGpsBadge" class="font-mono-code text-[11px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                No GPS coordinates pinpointed
            </div>
            <button type="button" id="recenterIncidentMapBtn" class="text-[11px] font-bold text-slate-600 hover:text-emerald-700 hover:underline flex items-center space-x-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Recenter</span>
            </button>
            <button type="button" id="clearIncidentPinBtn" class="text-[11px] font-bold text-slate-500 hover:text-red-600 hover:underline">
                Clear Pin
            </button>
        </div>
    </div>

    <!-- MAP CONTAINER -->
    <div class="relative w-full rounded-xl border border-slate-300 overflow-hidden shadow-2xs bg-slate-100">
        <div id="incidentLocationMap" style="height: 240px; width: 100%; z-index: 10;"></div>

        <!-- Floating Quick Tip -->
        <div class="absolute bottom-2 left-2 z-20 pointer-events-none bg-slate-900/85 backdrop-blur-xs text-white text-[10px] font-medium px-2.5 py-1 rounded-md shadow-xs border border-white/10 flex items-center space-x-1.5">
            <svg class="w-3 h-3 text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
            <span>Type place name above to auto-pinpoint, or click anywhere on the map to drop the pin.</span>
        </div>
    </div>

    <!-- Hidden GPS Latitude and Longitude Form Inputs -->
    <input type="hidden" id="incidentLatitude" name="latitude" value="{{ old('latitude', $case->latitude ?? '') }}">
    <input type="hidden" id="incidentLongitude" name="longitude" value="{{ old('longitude', $case->longitude ?? '') }}">
</div>

<!-- LEAFLET STYLES & SCRIPT LOADING -->
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>

<style>
    /* Tactical Radar Animated Marker */
    .incident-marker-radar {
        background: transparent !important;
        border: none !important;
    }
    .incident-radar-container {
        position: relative;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        transform: translate(-18px, -18px);
    }
    .incident-radar-pulse {
        position: absolute;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: rgba(16, 185, 129, 0.35);
        animation: incidentRadarPing 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;
    }
    .incident-radar-core {
        position: relative;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: linear-gradient(135deg, #059669 0%, #064e3b 100%);
        border: 2.5px solid #ffffff;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4), 0 2px 4px -2px rgba(0, 0, 0, 0.3);
    }
    @keyframes incidentRadarPing {
        0% { transform: scale(0.6); opacity: 1; }
        75%, 100% { transform: scale(1.6); opacity: 0; }
    }

    /* Auto-Suggest Dropdown styling */
    .incident-geocode-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 60;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 0.75rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        margin-top: 0.35rem;
        max-height: 240px;
        overflow-y: auto;
    }
    .incident-geocode-item {
        padding: 0.6rem 0.85rem;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s ease;
    }
    .incident-geocode-item:last-child {
        border-bottom: none;
    }
    .incident-geocode-item:hover, .incident-geocode-item.active {
        background: #ecfdf5;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    (function () {
        // Elements
        const raidInput = document.getElementById('newArrestedRaidLocation');
        const latInput  = document.getElementById('incidentLatitude');
        const lngInput  = document.getElementById('incidentLongitude');
        const gpsBadge  = document.getElementById('gpsBadge') || document.getElementById('incidentGpsBadge');
        const statusDot = document.getElementById('incidentMapStatusDot');
        const statusPing= document.getElementById('incidentMapStatusPing');
        const srcBadge  = document.getElementById('incidentMapSourceBadge');
        const recenterBtn = document.getElementById('recenterIncidentMapBtn');
        const clearBtn  = document.getElementById('clearIncidentPinBtn');
        const mapEl     = document.getElementById('incidentLocationMap');

        if (!mapEl || typeof L === 'undefined') {
            return;
        }

        // Ghana center default coordinates
        const GHANA_CENTER = [7.9465, -1.0232];
        const DEFAULT_ZOOM = 7;

        // Initialize Leaflet Map
        const map = L.map('incidentLocationMap', {
            center: GHANA_CENTER,
            zoom: DEFAULT_ZOOM,
            zoomControl: true,
            scrollWheelZoom: true
        });

        // OpenStreetMap standard tile layer
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Fix potential tile rendering glitches on container resize or tab switch
        setTimeout(function () {
            map.invalidateSize();
        }, 400);

        // Tactical Radar DivIcon
        const tacticalRadarIcon = L.divIcon({
            className: 'incident-marker-radar',
            html: '<div class="incident-radar-container"><div class="incident-radar-pulse"></div><div class="incident-radar-core"></div></div>',
            iconSize: [36, 36],
            iconAnchor: [18, 18],
            popupAnchor: [0, -14]
        });

        let currentMarker = null;
        let lastGeocodedQuery = '';
        let debounceTimer = null;
        let isProgrammaticInput = false;

        // Create Dropdown Container for Suggestions
        let suggestDropdown = null;
        if (raidInput && raidInput.parentElement) {
            const parent = raidInput.parentElement;
            if (parent.style.position !== 'relative') {
                parent.classList.add('relative');
            }
            suggestDropdown = document.createElement('div');
            suggestDropdown.className = 'incident-geocode-dropdown hidden';
            parent.appendChild(suggestDropdown);
        }

        function updateGpsBadge(lat, lng, labelText) {
            if (!gpsBadge) return;
            if (lat !== null && lng !== null && !isNaN(lat) && !isNaN(lng)) {
                const latStr = Number(lat).toFixed(6);
                const lngStr = Number(lng).toFixed(6);
                const latHem = lat >= 0 ? 'N' : 'S';
                const lngHem = lng >= 0 ? 'E' : 'W';
                gpsBadge.innerHTML = `<span class="text-emerald-700 font-extrabold">📍 ${Math.abs(latStr)}° ${latHem}, ${Math.abs(lngStr)}° ${lngHem}</span>`;
                gpsBadge.classList.remove('bg-slate-100', 'text-slate-500');
                gpsBadge.classList.add('bg-emerald-50', 'text-emerald-900', 'border-emerald-300');

                if (statusDot) {
                    statusDot.classList.remove('bg-slate-400');
                    statusDot.classList.add('bg-emerald-500');
                }
                if (statusPing) {
                    statusPing.classList.remove('hidden');
                }
            } else {
                gpsBadge.textContent = 'No GPS coordinates pinpointed';
                gpsBadge.classList.remove('bg-emerald-50', 'text-emerald-900', 'border-emerald-300');
                gpsBadge.classList.add('bg-slate-100', 'text-slate-500');

                if (statusDot) {
                    statusDot.classList.remove('bg-emerald-500');
                    statusDot.classList.add('bg-slate-400');
                }
                if (statusPing) {
                    statusPing.classList.add('hidden');
                }
                if (srcBadge) {
                    srcBadge.classList.add('hidden');
                }
            }
        }

        // Set or move pin marker
        function pinpoint(lat, lng, label, flyTo = true, source = 'Live Geocoded') {
            const numLat = parseFloat(lat);
            const numLng = parseFloat(lng);
            if (isNaN(numLat) || isNaN(numLng)) return;

            if (latInput) latInput.value = numLat.toFixed(7);
            if (lngInput) lngInput.value = numLng.toFixed(7);

            updateGpsBadge(numLat, numLng, label);

            if (srcBadge) {
                srcBadge.textContent = source;
                srcBadge.classList.remove('hidden');
            }

            const popupContent = `
                <div class="text-xs p-1">
                    <div class="font-extrabold text-slate-900 mb-0.5">${label || 'Incident Location'}</div>
                    <div class="text-[11px] font-mono-code text-emerald-700 font-bold">${numLat.toFixed(6)}, ${numLng.toFixed(6)}</div>
                    <div class="text-[10px] text-slate-400 mt-1">Drag marker to fine-tune exact position</div>
                </div>
            `;

            if (!currentMarker) {
                currentMarker = L.marker([numLat, numLng], {
                    icon: tacticalRadarIcon,
                    draggable: true,
                    autoPan: true
                }).addTo(map);

                currentMarker.bindPopup(popupContent);

                // Drag event
                currentMarker.on('dragend', function (e) {
                    const pos = e.target.getLatLng();
                    handleReverseGeocode(pos.lat, pos.lng);
                });
            } else {
                currentMarker.setLatLng([numLat, numLng]);
                currentMarker.setPopupContent(popupContent);
            }

            if (flyTo) {
                map.flyTo([numLat, numLng], 15, {
                    animate: true,
                    duration: 1.0
                });
            }
        }

        // Reverse geocode when map is clicked or marker is dragged
        async function handleReverseGeocode(lat, lng) {
            const numLat = parseFloat(lat);
            const numLng = parseFloat(lng);
            if (isNaN(numLat) || isNaN(numLng)) return;

            pinpoint(numLat, numLng, 'Pinpointed Location', false, 'Interactive Pin');

            try {
                const res = await fetch(`/api/geocode/reverse?lat=${encodeURIComponent(numLat)}&lng=${encodeURIComponent(numLng)}`);
                if (res.ok) {
                    const json = await res.json();
                    if (json && json.status === 'success' && json.data) {
                        const placeName = json.data.name || json.data.display_name;
                        if (placeName && raidInput) {
                            isProgrammaticInput = true;
                            raidInput.value = placeName;
                            isProgrammaticInput = false;
                        }
                        if (currentMarker) {
                            currentMarker.setPopupContent(`
                                <div class="text-xs p-1">
                                    <div class="font-extrabold text-slate-900 mb-0.5">${placeName || 'Incident Location'}</div>
                                    <div class="text-[11px] font-mono-code text-emerald-700 font-bold">${numLat.toFixed(6)}, ${numLng.toFixed(6)}</div>
                                    <div class="text-[10px] text-slate-400 mt-1">Drag marker to fine-tune exact position</div>
                                </div>
                            `).openPopup();
                        }
                    }
                }
            } catch (err) {
                console.warn('Reverse geocode error:', err);
            }
        }

        // Map Click Listener
        map.on('click', function (e) {
            handleReverseGeocode(e.latlng.lat, e.latlng.lng);
        });

        // Search Geocoding Query
        async function performGeocode(query, autoSelectTop = false) {
            const trimmed = query ? query.trim() : '';
            if (trimmed.length < 2) {
                if (suggestDropdown) suggestDropdown.classList.add('hidden');
                return;
            }

            if (trimmed === lastGeocodedQuery && !autoSelectTop) {
                return;
            }
            lastGeocodedQuery = trimmed;

            try {
                const res = await fetch(`/api/geocode?q=${encodeURIComponent(trimmed)}`);
                if (!res.ok) return;
                const json = await res.json();
                const list = (json && json.data) ? json.data : [];

                if (list.length === 0) {
                    if (suggestDropdown) suggestDropdown.classList.add('hidden');
                    return;
                }

                // Render Suggestions Dropdown
                if (suggestDropdown) {
                    suggestDropdown.innerHTML = '';
                    list.forEach((item, index) => {
                        const row = document.createElement('div');
                        row.className = 'incident-geocode-item flex items-start justify-between gap-2';
                        row.innerHTML = `
                            <div>
                                <div class="text-xs font-bold text-slate-800">${item.name}</div>
                                <div class="text-[10px] text-slate-500 leading-tight">${item.display_name}</div>
                            </div>
                            <span class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 shrink-0 uppercase tracking-wide">
                                ${item.type || 'Town'}
                            </span>
                        `;
                        row.addEventListener('click', function (e) {
                            e.stopPropagation();
                            isProgrammaticInput = true;
                            if (raidInput) raidInput.value = item.name;
                            isProgrammaticInput = false;
                            suggestDropdown.classList.add('hidden');
                            pinpoint(item.latitude, item.longitude, item.name, true, item.source || 'Live Geocoded');
                        });
                        suggestDropdown.appendChild(row);
                    });
                    suggestDropdown.classList.remove('hidden');
                }

                // Auto-pinpoint top match if requested or on first good search
                if (list.length > 0) {
                    const top = list[0];
                    pinpoint(top.latitude, top.longitude, top.name, true, top.source || 'Live Geocoded');
                }
            } catch (err) {
                console.warn('Geocoding search error:', err);
            }
        }

        // Keystroke listener on Place of Incident input
        if (raidInput) {
            raidInput.addEventListener('input', function () {
                if (isProgrammaticInput) return;
                clearTimeout(debounceTimer);
                const val = raidInput.value.trim();
                if (val.length < 2) {
                    if (suggestDropdown) suggestDropdown.classList.add('hidden');
                    return;
                }
                debounceTimer = setTimeout(function () {
                    performGeocode(val, true);
                }, 400);
            });

            raidInput.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    if (suggestDropdown) suggestDropdown.classList.add('hidden');
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(debounceTimer);
                    performGeocode(raidInput.value.trim(), true);
                    if (suggestDropdown) suggestDropdown.classList.add('hidden');
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function (e) {
                if (suggestDropdown && !suggestDropdown.contains(e.target) && e.target !== raidInput) {
                    suggestDropdown.classList.add('hidden');
                }
            });
        }

        // Recenter Map button
        if (recenterBtn) {
            recenterBtn.addEventListener('click', function () {
                if (currentMarker) {
                    map.flyTo(currentMarker.getLatLng(), 15, { animate: true, duration: 0.8 });
                } else {
                    map.flyTo(GHANA_CENTER, DEFAULT_ZOOM, { animate: true, duration: 0.8 });
                }
            });
        }

        // Clear Pin button
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (currentMarker) {
                    map.removeLayer(currentMarker);
                    currentMarker = null;
                }
                if (latInput) latInput.value = '';
                if (lngInput) lngInput.value = '';
                updateGpsBadge(null, null);
                map.flyTo(GHANA_CENTER, DEFAULT_ZOOM, { animate: true, duration: 0.8 });
            });
        }

        // Initialize state on page load (pre-existing coordinates or prefilled text)
        const initLat = parseFloat(latInput?.value || '');
        const initLng = parseFloat(lngInput?.value || '');

        if (!isNaN(initLat) && !isNaN(initLng) && (initLat !== 0 || initLng !== 0)) {
            pinpoint(initLat, initLng, raidInput?.value || 'Recorded Incident Location', true, 'Existing Record');
        } else if (raidInput && raidInput.value.trim().length >= 2) {
            // Geocode prefilled text (e.g. from draft restoration)
            setTimeout(function () {
                performGeocode(raidInput.value.trim(), true);
            }, 500);
        }
    })();
});
</script>

