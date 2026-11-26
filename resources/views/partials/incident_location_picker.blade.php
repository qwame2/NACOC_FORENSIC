<!-- INCIDENT GEOLOCATION RADAR & COMPACT GPS COORDINATES COMPONENT -->
<div id="incidentLocationMapWrapper" class="shrink-0 flex items-center space-x-2 bg-slate-50 border border-slate-300 px-3.5 py-2.5 rounded-xl self-stretch lg:self-auto justify-between lg:justify-start">
    <div class="flex items-center space-x-2">
        <span class="relative flex h-2 w-2">
            <span id="incidentMapStatusPing" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 hidden"></span>
            <span id="incidentMapStatusDot" class="relative inline-flex rounded-full h-2 w-2 bg-slate-400"></span>
        </span>
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-700 whitespace-nowrap">
            GPS Radar:
        </span>
        <div id="incidentGpsBadge" class="font-mono-code text-[10.5px] font-bold text-slate-600 bg-white px-2 py-0.5 rounded border border-slate-200 whitespace-nowrap shadow-2xs">
            No GPS coordinates pinpointed
        </div>
        <span id="incidentMapSourceBadge" class="hidden text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wide whitespace-nowrap">
            Live Geocoded
        </span>
    </div>

    <div class="flex items-center space-x-1.5 text-xs">
        <button type="button" id="clearIncidentPinBtn" class="text-[10px] font-bold text-slate-400 hover:text-red-600 hover:underline cursor-pointer transition-colors ml-1 whitespace-nowrap" title="Clear pinpointed GPS coordinates">
            Clear
        </button>
    </div>

    <!-- Hidden GPS Latitude and Longitude Form Inputs (Maintains Storage Logic) -->
    <input type="hidden" id="incidentLatitude" name="latitude" value="{{ old('latitude', $case->latitude ?? '') }}">
    <input type="hidden" id="incidentLongitude" name="longitude" value="{{ old('longitude', $case->longitude ?? '') }}">
</div>

<style>
    /* Auto-Suggest Dropdown styling for Place of Arrest */
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

@php
    $geocodeEndpointPath = parse_url(url('/api/geocode'), PHP_URL_PATH) ?: '/api/geocode';
    $embeddedGhanaGazetteer = \App\Services\GeocodingService::GHANA_GAZETTEER;
    $embeddedRegionCentroids = \App\Services\GeocodingService::REGION_CENTROIDS;
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    (function () {
        const GEOCODE_ENDPOINT = @json($geocodeEndpointPath);
        const LOCAL_GAZETTEER = @json($embeddedGhanaGazetteer);
        const LOCAL_REGIONS = @json($embeddedRegionCentroids);

        // Elements
        const raidInput = document.getElementById('newArrestedRaidLocation');
        const latInput  = document.getElementById('incidentLatitude');
        const lngInput  = document.getElementById('incidentLongitude');
        const gpsBadge  = document.getElementById('gpsBadge') || document.getElementById('incidentGpsBadge');
        const statusDot = document.getElementById('incidentMapStatusDot');
        const statusPing= document.getElementById('incidentMapStatusPing');
        const srcBadge  = document.getElementById('incidentMapSourceBadge');
        const clearBtn  = document.getElementById('clearIncidentPinBtn');

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

        // Set pinpoint coordinates into hidden inputs and badge
        function setCoordinates(lat, lng, label, source = 'Live Geocoded') {
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
        }

        // Zero-network instant offline gazetteer lookup so Ghana towns/districts pinpoint even if server/network fails
        function searchEmbeddedGazetteer(rawQuery) {
            const q = (rawQuery || '').trim().toLowerCase();
            if (q.length < 2) return [];

            const coordMatch = q.match(/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/);
            if (coordMatch) {
                const lat = parseFloat(coordMatch[1]);
                const lng = parseFloat(coordMatch[2]);
                if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                    return [{
                        name: `${lat.toFixed(5)}, ${lng.toFixed(5)}`,
                        display_name: `Coordinates (${lat.toFixed(5)}, ${lng.toFixed(5)}), Ghana`,
                        latitude: lat,
                        longitude: lng,
                        type: 'GPS Coordinates',
                        source: 'Direct Coordinates'
                    }];
                }
            }

            const matches = [];
            if (LOCAL_GAZETTEER && typeof LOCAL_GAZETTEER === 'object') {
                Object.entries(LOCAL_GAZETTEER).forEach(([key, info]) => {
                    if (q.includes(key) || key.includes(q)) {
                        const titleName = key.replace(/\b\w/g, c => c.toUpperCase());
                        matches.push({
                            name: titleName,
                            display_name: `${titleName}, ${info.region} Region, Ghana`,
                            latitude: info.lat,
                            longitude: info.lng,
                            type: info.type || 'Town',
                            source: 'Ghana Official Gazetteer',
                            _exact: key === q ? 0 : 1,
                            _len: key.length
                        });
                    }
                });
            }

            matches.sort((a, b) => (a._exact - b._exact) || (b._len - a._len));

            if (matches.length === 0 && LOCAL_REGIONS && typeof LOCAL_REGIONS === 'object') {
                Object.entries(LOCAL_REGIONS).forEach(([regName, coords]) => {
                    if (q.includes(regName.toLowerCase())) {
                        matches.push({
                            name: `${regName} Region`,
                            display_name: `${regName} Region, Ghana`,
                            latitude: coords[0],
                            longitude: coords[1],
                            type: 'Administrative Region',
                            source: 'Ghana Gazetteer'
                        });
                    }
                });
            }

            return matches.slice(0, 6);
        }

        function fetchWithTimeout(url, options = {}, timeoutMs = 3500) {
            if (typeof AbortController === 'undefined') {
                return fetch(url, options);
            }
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), timeoutMs);
            return fetch(url, Object.assign({}, options, { signal: controller.signal }))
                .finally(() => clearTimeout(timer));
        }

        // Browser-side fallback when hosted server sits behind a firewall, subdirectory rewrite issue, or Nominatim datacenter IP block
        async function fetchClientNominatimFallback(trimmed) {
            try {
                const osmUrl = `https://nominatim.openstreetmap.org/search?format=json&countrycodes=gh&addressdetails=1&limit=6&q=${encodeURIComponent(trimmed)}`;
                const osmRes = await fetchWithTimeout(osmUrl, {
                    headers: { 'Accept': 'application/json' }
                }, 3500);
                if (!osmRes.ok) return [];
                const rawList = await osmRes.json();
                if (!Array.isArray(rawList)) return [];

                return rawList
                    .filter(item => item && item.lat !== undefined && item.lon !== undefined)
                    .map(item => {
                        const addr = item.address || {};
                        const shortName = item.name || addr.road || addr.suburb || addr.town || addr.city || trimmed;
                        return {
                            name: shortName,
                            display_name: item.display_name || shortName,
                            latitude: parseFloat(item.lat),
                            longitude: parseFloat(item.lon),
                            type: (item.type || 'Location').replace(/_/g, ' '),
                            source: 'Live Geocoded'
                        };
                    });
            } catch (e) {
                return [];
            }
        }

        function renderSuggestionList(list) {
            if (!suggestDropdown) return;
            if (!list || list.length === 0) {
                suggestDropdown.classList.add('hidden');
                return;
            }
            suggestDropdown.innerHTML = '';
            list.forEach((item) => {
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
                    setCoordinates(item.latitude, item.longitude, item.name, item.source || 'Live Geocoded');
                });
                suggestDropdown.appendChild(row);
            });
            suggestDropdown.classList.remove('hidden');
        }

        // Search Geocoding Query via embedded gazetteer + backend GeocodingService + client-side fallback
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

            // 1. Instant zero-network match from embedded Ghana gazetteer
            const instantMatches = searchEmbeddedGazetteer(trimmed);
            if (instantMatches.length > 0) {
                renderSuggestionList(instantMatches);
                const topInstant = instantMatches[0];
                setCoordinates(topInstant.latitude, topInstant.longitude, topInstant.name, topInstant.source || 'Ghana Official Gazetteer');
            }

            // 2. Enrich with live backend / client Nominatim (never fails or wipes instant coordinates)
            let list = [];
            try {
                const res = await fetchWithTimeout(`${GEOCODE_ENDPOINT}?q=${encodeURIComponent(trimmed)}`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }, 3500);
                if (res.ok) {
                    const json = await res.json();
                    list = (json && Array.isArray(json.data)) ? json.data : [];
                }
            } catch (backendErr) {
                list = [];
            }

            if (list.length === 0) {
                list = await fetchClientNominatimFallback(trimmed);
            }

            if (list.length === 0) {
                if (instantMatches.length === 0 && suggestDropdown) {
                    suggestDropdown.classList.add('hidden');
                }
                return;
            }

            renderSuggestionList(list);
            const top = list[0];
            setCoordinates(top.latitude, top.longitude, top.name, top.source || 'Live Geocoded');
        }

        // Keystroke listener on Place of Arrest input
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

        // Clear Pin button
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (latInput) latInput.value = '';
                if (lngInput) lngInput.value = '';
                updateGpsBadge(null, null);
            });
        }

        // Initialize state on page load (pre-existing coordinates or prefilled text)
        const initLat = parseFloat(latInput?.value || '');
        const initLng = parseFloat(lngInput?.value || '');

        if (!isNaN(initLat) && !isNaN(initLng) && (initLat !== 0 || initLng !== 0)) {
            setCoordinates(initLat, initLng, raidInput?.value || 'Recorded Incident Location', 'Existing Record');
        } else if (raidInput && raidInput.value.trim().length >= 2) {
            setTimeout(function () {
                performGeocode(raidInput.value.trim(), true);
            }, 500);
        }
    })();
});
</script>
