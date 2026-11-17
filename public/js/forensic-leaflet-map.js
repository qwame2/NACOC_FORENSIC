(function () {
    'use strict';

    window.ForensicLeafletMaps = window.ForensicLeafletMaps || {};

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function markerColor(priority, locationCount) {
        var p = String(priority || '').toUpperCase();
        if (p === 'CRITICAL' || p === 'HIGH' || Number(locationCount || 0) >= 3) {
            return '#dc2626';
        }
        if (p === 'MEDIUM') return '#0284c7';
        return '#0f766e';
    }

    function buildPopupHtml(marker, locationCount, isHighColor) {
        var title = escapeHtml(marker.title || marker.case_number || 'Forensic Docket');
        var caseNo = escapeHtml(marker.case_number || '');
        var unit = escapeHtml(marker.unit_name || '');
        var status = escapeHtml(marker.status || '');
        var location = escapeHtml(marker.location || marker.region || '');
        var priority = escapeHtml(String(marker.priority || 'HIGH').toUpperCase());
        var exhibits = Number(marker.exhibits_count || 0);
        var reviewHref = marker.review_url ? escapeHtml(marker.review_url) : '';

        var badgeBg = isHighColor ? '#fef2f2' : '#f1f5f9';
        var badgeText = isHighColor ? '#b91c1c' : '#334155';
        var areaNote = Number(locationCount || 1) > 1
            ? '<div style="font-size:11px;font-weight:600;color:#b91c1c;margin-top:3px;">' + Number(locationCount) + ' cases in this area</div>'
            : '';

        var actionHtml = reviewHref
            ? '<div style="margin-top:8px;"><a href="' + reviewHref + '" style="display:inline-block;padding:5px 12px;border-radius:9999px;background:#059669;color:#ffffff;font-size:11px;font-weight:600;text-decoration:none;">Inspect Docket</a></div>'
            : '';

        return '' +
            '<div style="min-width:200px;font-family:Inter,system-ui,sans-serif;color:#0f172a;">' +
                '<div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">' +
                    '<span style="font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;">' + caseNo + '</span>' +
                    '<span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:9999px;background:' + badgeBg + ';color:' + badgeText + ';">' + priority + '</span>' +
                '</div>' +
                '<div style="font-size:13px;font-weight:700;margin-top:3px;">' + title + '</div>' +
                '<div style="font-size:11px;color:#334155;margin-top:4px;">' + unit + ' &bull; ' + status + '</div>' +
                '<div style="font-size:11px;color:#334155;margin-top:2px;">' + location + ' (' + exhibits + ' Exhibits)</div>' +
                areaNote +
                actionHtml +
            '</div>';
    }

    function renderMarkers(layerGroup, markers, onMarkerSelect, map, shouldFit) {
        layerGroup.clearLayers();
        if (!Array.isArray(markers) || markers.length === 0) return;

        var groups = {};
        var validItems = [];
        markers.forEach(function (item) {
            if (item.latitude === null || item.latitude === undefined || item.latitude === '' ||
                item.longitude === null || item.longitude === undefined || item.longitude === '') {
                return;
            }
            var lat = Number(item.latitude);
            var lng = Number(item.longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            var key = lat.toFixed(4) + ',' + lng.toFixed(4);
            if (!groups[key]) groups[key] = [];
            groups[key].push(item);
            validItems.push({ item: item, key: key, lat: lat, lng: lng });
        });

        var seenByKey = {};
        var boundsCoords = [];

        validItems.forEach(function (entry) {
            var item = entry.item;
            var key = entry.key;
            var group = groups[key];
            var locationCount = group.length;
            var idx = seenByKey[key] || 0;
            seenByKey[key] = idx + 1;

            var plotLat = entry.lat;
            var plotLng = entry.lng;
            if (locationCount > 1 && idx > 0) {
                var ringAngle = ((idx - 1) / (locationCount - 1)) * Math.PI * 2;
                var ringRadius = 0.015 + Math.floor((idx - 1) / 8) * 0.012;
                plotLat = entry.lat + Math.sin(ringAngle) * ringRadius;
                plotLng = entry.lng + Math.cos(ringAngle) * ringRadius;
            }

            boundsCoords.push([plotLat, plotLng]);

            var color = markerColor(item.priority, locationCount);
            var isRed = color === '#dc2626';

            if (isRed && idx === 0) {
                window.L.circleMarker([entry.lat, entry.lng], {
                    radius: locationCount >= 3 ? 18 : 14,
                    color: '#dc2626',
                    weight: 1.5,
                    fillColor: '#dc2626',
                    fillOpacity: 0.16,
                    interactive: false
                }).addTo(layerGroup);
            }

            var circle = window.L.circleMarker([plotLat, plotLng], {
                radius: isRed ? 9 : 8,
                color: '#ffffff',
                weight: 2,
                fillColor: color,
                fillOpacity: 0.92
            });

            circle.bindPopup(buildPopupHtml(item, locationCount, isRed));
            circle.on('click', function () {
                if (typeof onMarkerSelect === 'function') {
                    onMarkerSelect(item);
                }
            });
            circle.addTo(layerGroup);
        });

        if (shouldFit && map && boundsCoords.length > 0) {
            var bounds = window.L.latLngBounds(boundsCoords);
            map.fitBounds(bounds.pad(0.18), { maxZoom: 10, animate: false });
        }
    }

    function markersSignature(markers) {
        if (!Array.isArray(markers) || markers.length === 0) return '';
        return markers.map(function (m) {
            var lat = m.latitude != null && m.latitude !== '' ? Number(m.latitude).toFixed(5) : '';
            var lng = m.longitude != null && m.longitude !== '' ? Number(m.longitude).toFixed(5) : '';
            return [
                m.id ?? '',
                m.case_number ?? '',
                lat,
                lng,
                String(m.priority || '').toUpperCase(),
                Number(m.exhibits_count || 0)
            ].join('|');
        }).join(';');
    }

    function initLeafletContainer(el) {
        if (!el || typeof window.L === 'undefined' || el.dataset.leafletInitialized === 'true') {
            return null;
        }

        var lat = Number(JSON.parse(el.dataset.latitude || 'null'));
        var lng = Number(JSON.parse(el.dataset.longitude || 'null'));
        var zoom = Number(JSON.parse(el.dataset.zoom || '7'));
        var initialMarkers = [];

        try {
            initialMarkers = JSON.parse(el.dataset.markers || '[]');
        } catch (e) {
            initialMarkers = [];
        }

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return null;
        }
        if (!Number.isFinite(zoom)) {
            zoom = 7;
        }

        var tileUrl = el.dataset.tileUrl;
        var attribution = el.dataset.attribution;
        if (!tileUrl) {
            return null;
        }

        var map = window.L.map(el, {
            center: [lat, lng],
            zoom: zoom,
            zoomControl: false
        });

        window.L.tileLayer(tileUrl, {
            maxZoom: 19,
            attribution: attribution || ''
        }).addTo(map);

        var markerLayer = window.L.layerGroup().addTo(map);
        var selectionHandler = function (item) {
            if (typeof window.selectDashboardDocket === 'function' && (item.case_number || item.id)) {
                window.selectDashboardDocket(String(item.case_number || item.id));
            }
        };

        var lastSignature = markersSignature(initialMarkers);
        var hasFittedInitialBounds = initialMarkers.length > 0;
        renderMarkers(markerLayer, initialMarkers, selectionHandler, map, hasFittedInitialBounds);

        var controller = {
            map: map,
            markerLayer: markerLayer,
            initialCenter: [lat, lng],
            initialZoom: zoom,
            currentMarkers: initialMarkers.slice(),
            setMarkers: function (nextMarkers) {
                var list = Array.isArray(nextMarkers) ? nextMarkers : [];
                var nextSig = markersSignature(list);
                if (nextSig === lastSignature) {
                    return;
                }
                lastSignature = nextSig;
                this.currentMarkers = list.slice();
                var shouldFit = !hasFittedInitialBounds && this.currentMarkers.length > 0;
                if (shouldFit) {
                    hasFittedInitialBounds = true;
                }
                renderMarkers(markerLayer, this.currentMarkers, selectionHandler, map, shouldFit);
            },
            focusCase: function (item) {
                if (!item) return;
                if (item.latitude === null || item.latitude === undefined || item.latitude === '' ||
                    item.longitude === null || item.longitude === undefined || item.longitude === '') {
                    return;
                }
                var cLat = Number(item.latitude);
                var cLng = Number(item.longitude);
                if (Number.isFinite(cLat) && Number.isFinite(cLng)) {
                    map.panTo([cLat, cLng], { animate: true });
                }
            },
            resetView: function () {
                if (this.currentMarkers.length > 0) {
                    var coords = this.currentMarkers
                        .filter(function (m) {
                            return m.latitude !== null && m.latitude !== undefined && m.latitude !== '' &&
                                   m.longitude !== null && m.longitude !== undefined && m.longitude !== '';
                        })
                        .map(function (m) { return [Number(m.latitude), Number(m.longitude)]; })
                        .filter(function (c) { return Number.isFinite(c[0]) && Number.isFinite(c[1]); });
                    if (coords.length > 0) {
                        map.fitBounds(window.L.latLngBounds(coords).pad(0.18), { maxZoom: 10, animate: true });
                        return;
                    }
                }
                map.setView(this.initialCenter, this.initialZoom, { animate: true });
            }
        };

        el.dataset.leafletInitialized = 'true';
        window.ForensicLeafletMaps[el.id] = controller;

        if (window.ForensicMapStage && typeof window.ForensicMapStage.mount === 'function') {
            window.ForensicMapStage.mount(map, {
                zoomIn: function () { map.zoomIn(); },
                zoomOut: function () { map.zoomOut(); },
                resetView: function () { controller.resetView(); }
            });
        }

        setTimeout(function () {
            map.invalidateSize();
        }, 120);

        window.addEventListener('resize', function () {
            map.invalidateSize();
        });

        if (typeof window.ResizeObserver !== 'undefined') {
            var ro = new ResizeObserver(function () {
                map.invalidateSize();
            });
            ro.observe(el);
        }

        return controller;
    }

    function bootAllMaps() {
        var nodes = document.querySelectorAll('[data-leaflet-map="true"]');
        nodes.forEach(function (node) {
            initLeafletContainer(node);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootAllMaps);
    } else {
        bootAllMaps();
    }

    window.initForensicLeafletMaps = bootAllMaps;
})();

