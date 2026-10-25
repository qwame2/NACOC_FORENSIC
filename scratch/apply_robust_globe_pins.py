import os
import re

build_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'build_clean_dashboards.py')

with open(build_file, 'r', encoding='utf-8') as f:
    code = f.read()

# 1. Update liveCases initialization to embed server-rendered $cases directly
old_live_cases = "    // State Variables\n    let liveCases = [];\n    let selectedCaseId = null;"
new_live_cases = """    // State Variables: Pre-populated directly from server-side $cases so pins render immediately!
    let liveCases = @json($cases->map(function($c) {
        $rLoc = $c->raid_location ?? '';
        $reg = !empty($c->region) ? $c->region : '';
        if (empty($reg)) {
            if (stripos($rLoc, 'kumasi') !== false) $reg = 'Ashanti';
            else if (stripos($rLoc, 'sekondi') !== false || stripos($rLoc, 'takoradi') !== false) $reg = 'Western';
            else if (stripos($rLoc, 'tamale') !== false) $reg = 'Northern';
            else $reg = 'Greater Accra';
        }
        return [
            'id' => $c->id,
            'case_number' => $c->case_number,
            'title' => $c->title ?? ($c->case_number . ' - ' . ($c->suspect_name ?? 'Forensic Case')),
            'suspect_name' => $c->suspect_name ?? 'Unknown Suspect',
            'unit_name' => $c->unit_name ?? 'Forensic Directorate',
            'region' => $reg,
            'raid_location' => !empty($rLoc) ? $rLoc : ($reg . ' Sector'),
            'status' => $c->status ?? 'Active',
            'status_badge' => $c->status_badge ?? 'In Progress',
            'exhibits_count' => $c->exhibits_count ?? 1,
            'is_multiple' => (bool)($c->is_multiple ?? false)
        ];
    }));
    let selectedCaseId = liveCases.length > 0 ? liveCases[0].case_number : null;"""

if old_live_cases in code:
    code = code.replace(old_live_cases, new_live_cases)
    print("Embedded initial liveCases from server-side $cases successfully!")
else:
    print("Could not find old_live_cases")

# 2. Update fetch('/api/dashboard/realtime') to use {{ url('/api/dashboard/realtime') }}
old_fetch = "const res = await fetch('/api/dashboard/realtime', {{"
new_fetch = 'const res = await fetch("{{ url(\'/api/dashboard/realtime\') }}", {{'
if old_fetch in code:
    code = code.replace(old_fetch, new_fetch)
    print("Fixed realtime fetch URL to use {{ url(...) }} successfully!")
else:
    print("Could not find old_fetch")

# 3. Replace the PIN SVG definitions with the composite billboard generator functions
composite_generators = """    // ══════════════════════════════════════════════════════════
    // TACTICAL 3D GLOBE MAP POINT ICONS & SYMBOLS (COMPOSITE BILLBOARDS)
    // ══════════════════════════════════════════════════════════
    function makeHotspotBillboard(regName, dockets) {{
      const labelText = `HOTSPOT: ${{regName.toUpperCase()}} (${{dockets}} DOCKETS)`;
      const pillWidth = Math.max(labelText.length * 7.4 + 20, 160);
      const halfPill = Math.round(pillWidth / 2);
      const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="${{pillWidth}}" height="64" viewBox="0 0 ${{pillWidth}} 64">
          <defs>
            <filter id="hGlow" x="-20%" y="-20%" width="140%" height="140%">
              <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#ef4444" flood-opacity="0.9"/>
            </filter>
          </defs>
          <rect x="2" y="2" width="${{pillWidth - 4}}" height="20" rx="4" fill="#150303" stroke="#ef4444" stroke-width="1.8" filter="url(#hGlow)"/>
          <text x="${{halfPill}}" y="15" text-anchor="middle" font-family="'JetBrains Mono', monospace, sans-serif" font-size="10.5" font-weight="900" fill="#fecaca">${{labelText}}</text>
          <path d="M${{halfPill}} 24 C${{halfPill - 9}} 24 ${{halfPill - 14}} 29 ${{halfPill - 14}} 36 C${{halfPill - 14}} 45 ${{halfPill}} 62 ${{halfPill}} 62 C${{halfPill}} 62 ${{halfPill + 14}} 45 ${{halfPill + 14}} 36 C${{halfPill + 14}} 29 ${{halfPill + 9}} 24 ${{halfPill}} 24 Z" fill="#dc2626" stroke="#ffffff" stroke-width="1.8"/>
          <circle cx="${{halfPill}}" cy="36" r="6" fill="#18181b"/>
          <circle cx="${{halfPill}}" cy="36" r="3.2" fill="#ef4444"/>
          <line x1="${{halfPill}}" y1="31" x2="${{halfPill}}" y2="41" stroke="#ffffff" stroke-width="1.4" stroke-linecap="round"/>
          <line x1="${{halfPill - 5}}" y1="36" x2="${{halfPill + 5}}" y2="36" stroke="#ffffff" stroke-width="1.4" stroke-linecap="round"/>
        </svg>
      `.trim();
      return {{
        src: 'data:image/svg+xml;utf8,' + encodeURIComponent(svg),
        width: pillWidth,
        height: 64,
        offset: [0, -32]
      }};
    }}

    function makeRaidBillboard(caseNum, suspectName, townName) {{
      const labelText = `RAID: ${{caseNum}} • ${{suspectName}} [${{townName}}]`;
      const pillWidth = Math.max(labelText.length * 6.8 + 18, 140);
      const halfPill = Math.round(pillWidth / 2);
      const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="${{pillWidth}}" height="58" viewBox="0 0 ${{pillWidth}} 58">
          <defs>
            <filter id="rGlow" x="-20%" y="-20%" width="140%" height="140%">
              <feDropShadow dx="0" dy="2" stdDeviation="2.5" flood-color="#06b6d4" flood-opacity="0.8"/>
            </filter>
          </defs>
          <rect x="2" y="2" width="${{pillWidth - 4}}" height="18" rx="4" fill="#031826" stroke="#06b6d4" stroke-width="1.6" filter="url(#rGlow)"/>
          <text x="${{halfPill}}" y="14" text-anchor="middle" font-family="'JetBrains Mono', monospace, sans-serif" font-size="9.5" font-weight="800" fill="#e0f2fe">${{labelText}}</text>
          <path d="M${{halfPill}} 22 C${{halfPill - 8}} 22 ${{halfPill - 12}} 27 ${{halfPill - 12}} 33 C${{halfPill - 12}} 41 ${{halfPill}} 56 ${{halfPill}} 56 C${{halfPill}} 56 ${{halfPill + 12}} 41 ${{halfPill + 12}} 33 C${{halfPill + 12}} 27 ${{halfPill + 8}} 22 ${{halfPill}} 22 Z" fill="#0284c7" stroke="#ffffff" stroke-width="1.6"/>
          <circle cx="${{halfPill}}" cy="33" r="5" fill="#082f49"/>
          <circle cx="${{halfPill}}" cy="33" r="3" stroke="#38bdf8" stroke-width="1.2" fill="none"/>
          <circle cx="${{halfPill}}" cy="33" r="1.4" fill="#ffffff"/>
        </svg>
      `.trim();
      return {{
        src: 'data:image/svg+xml;utf8,' + encodeURIComponent(svg),
        width: pillWidth,
        height: 58,
        offset: [0, -29]
      }};
    }}

    function makeTargetBillboard(caseNum, suspectName, townName) {{
      const labelText = `TARGET LOCK: ${{caseNum}} • ${{suspectName}} [${{townName}}]`;
      const pillWidth = Math.max(labelText.length * 7.5 + 24, 180);
      const halfPill = Math.round(pillWidth / 2);
      const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="${{pillWidth}}" height="68" viewBox="0 0 ${{pillWidth}} 68">
          <defs>
            <filter id="tGlow" x="-25%" y="-25%" width="150%" height="150%">
              <feDropShadow dx="0" dy="2" stdDeviation="3.5" flood-color="#f43f5e" flood-opacity="0.95"/>
            </filter>
          </defs>
          <rect x="2" y="2" width="${{pillWidth - 4}}" height="22" rx="5" fill="#20030c" stroke="#f43f5e" stroke-width="2" filter="url(#tGlow)"/>
          <text x="${{halfPill}}" y="16" text-anchor="middle" font-family="'JetBrains Mono', monospace, sans-serif" font-size="11" font-weight="900" fill="#fff">${{labelText}}</text>
          <path d="M${{halfPill}} 26 C${{halfPill - 10} 26 ${{halfPill - 15}} 31 ${{halfPill - 15}} 39 C${{halfPill - 15}} 49 ${{halfPill}} 66 ${{halfPill}} 66 C${{halfPill}} 66 ${{halfPill + 15}} 49 ${{halfPill + 15}} 39 C${{halfPill + 15}} 31 ${{halfPill + 10}} 26 ${{halfPill}} 26 Z" fill="#e11d48" stroke="#ffffff" stroke-width="2"/>
          <circle cx="${{halfPill}}" cy="39" r="6.5" fill="#4c0519"/>
          <circle cx="${{halfPill}}" cy="39" r="4.5" stroke="#fda4af" stroke-width="1.3" fill="none"/>
          <circle cx="${{halfPill}}" cy="39" r="2" fill="#ffffff"/>
          <line x1="${{halfPill}}" y1="33" x2="${{halfPill}}" y2="45" stroke="#ffffff" stroke-width="1.4" stroke-linecap="round"/>
          <line x1="${{halfPill - 6}}" y1="39" x2="${{halfPill + 6}}" y2="39" stroke="#ffffff" stroke-width="1.4" stroke-linecap="round"/>
        </svg>
      `.trim();
      return {{
        src: 'data:image/svg+xml;utf8,' + encodeURIComponent(svg),
        width: pillWidth,
        height: 68,
        offset: [0, -34]
      }};
    }}

    function makeDistrictBillboard(districtName, dockets) {{
      const labelText = `[${{districtName.toUpperCase()}}: ${{dockets}} DOCKETS]`;
      const pillWidth = Math.max(labelText.length * 6.5 + 16, 120);
      const halfPill = Math.round(pillWidth / 2);
      const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="${{pillWidth}}" height="48" viewBox="0 0 ${{pillWidth}} 48">
          <rect x="2" y="2" width="${{pillWidth - 4}}" height="16" rx="3" fill="#041a14" stroke="#10b981" stroke-width="1.2"/>
          <text x="${{halfPill}}" y="13" text-anchor="middle" font-family="'JetBrains Mono', monospace, sans-serif" font-size="9" font-weight="700" fill="#a7f3d0">${{labelText}}</text>
          <path d="M${{halfPill}} 20 C${{halfPill - 6}} 20 ${{halfPill - 10}} 24 ${{halfPill - 10}} 29 C${{halfPill - 10}} 35 ${{halfPill}} 46 ${{halfPill}} 46 C${{halfPill}} 46 ${{halfPill + 10}} 35 ${{halfPill + 10}} 29 C${{halfPill + 10}} 24 ${{halfPill + 6}} 20 ${{halfPill}} 20 Z" fill="#047857" stroke="#ffffff" stroke-width="1.3"/>
          <circle cx="${{halfPill}}" cy="29" r="4" fill="#064e3b"/>
          <circle cx="${{halfPill}}" cy="29" r="1.6" fill="#a7f3d0"/>
        </svg>
      `.trim();
      return {{
        src: 'data:image/svg+xml;utf8,' + encodeURIComponent(svg),
        width: pillWidth,
        height: 48,
        offset: [0, -24]
      }};
    }}"""

# Replace old PIN SVG definitions
pin_start = code.find('    // ══════════════════════════════════════════════════════════\n    // TACTICAL 3D GLOBE MAP POINT ICONS')
pin_end = code.find('    const MMDA_DISTRICT_GEO = {{', pin_start)
if pin_start != -1 and pin_end != -1:
    code = code[:pin_start] + composite_generators + "\n\n" + code[pin_end:]
    print("Replaced Pin SVGs with composite billboard generators successfully!")
else:
    print("Could not find pin_start or pin_end")

# 4. Update refreshGlobusEntities to use composite billboards without font atlas dependency
robust_refresh_globus = """    function refreshGlobusEntities(targetCase = null) {{
      if (!globusMarkersLayer || !window.og) return;
      try {{
        globusMarkersLayer.clear();

        // 1. Calculate higher caseload hotspot across regions/districts
        let maxCaseload = 0;
        let highestRegion = 'Greater Accra';
        Object.keys(regionsData).forEach(k => {{
          const count = regionsData[k] ? (regionsData[k].cases || 0) : 0;
          if (count > maxCaseload) {{
            maxCaseload = count;
            highestRegion = k;
          }}
        }});

        // 2. Add HIGHER CASELOAD HOTSPOT MAP POINT PIN (Beacon with flame/crosshair symbol & callout pill)
        const highestGeo = GHANA_REGION_GEO[highestRegion] || {{ lon: -0.1969, lat: 5.6037 }};
        const hotspotEnt = new window.og.Entity({{
          name: `HOTSPOT_${{highestRegion}}`,
          lonlat: new window.og.LonLat(highestGeo.lon, highestGeo.lat, 1200),
          billboard: makeHotspotBillboard(highestRegion, maxCaseload)
        }});
        globusMarkersLayer.add(hotspotEnt);

        // 3. Add REGIONAL & MMDA TOWN WAYPOINT PINS for all other sectors
        for (const [rName, rGeo] of Object.entries(GHANA_REGION_GEO)) {{
          if (rName === highestRegion) continue; // Already marked with Hotspot Beacon
          const rData = regionsData[rName];
          const cases = rData ? rData.cases : 0;

          const regEnt = new window.og.Entity({{
            name: `REG_${{rName}}`,
            lonlat: new window.og.LonLat(rGeo.lon, rGeo.lat, 600),
            billboard: makeDistrictBillboard(rName, cases)
          }});
          globusMarkersLayer.add(regEnt);
        }}

        // 4. Add RAID LOCATION MAP POINT PINS for all active live cases
        const activeCase = targetCase || (selectedCaseId ? liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId)) : null);
        const activeCaseNum = activeCase ? activeCase.case_number : null;

        const renderedRaids = new Map();

        liveCases.forEach(c => {{
          // The selected case gets the prominent TARGET_PIN below
          if (c.case_number === activeCaseNum) return;

          const locText = ((c.raid_location || '') + ' ' + (c.suspect_name || '') + ' ' + (c.region || '') + ' ' + (c.title || '')).toLowerCase();
          let coords = null;
          let townName = c.raid_location || c.region || 'Tactical Sector';

          for (const [k, v] of Object.entries(MMDA_DISTRICT_GEO)) {{
            if (locText.includes(k)) {{
              coords = v;
              townName = v.name || townName;
              break;
            }}
          }}
          if (!coords && c.region && GHANA_REGION_GEO[c.region]) {{
            coords = GHANA_REGION_GEO[c.region];
            townName = `${{c.region}} Sector`;
          }}
          if (!coords) {{
            coords = GHANA_REGION_GEO['Greater Accra'];
            townName = 'Accra Metro (AMA)';
          }}

          // Coordinate jitter if multiple raids occur within same MMDA town
          const geoKey = `${{coords.lon.toFixed(3)}}_${{coords.lat.toFixed(3)}}`;
          const count = renderedRaids.get(geoKey) || 0;
          renderedRaids.set(geoKey, count + 1);

          const lonJitter = count === 0 ? 0 : (count % 2 === 1 ? 0.014 : -0.014) * Math.ceil(count / 2);
          const latJitter = count === 0 ? 0 : (count % 2 === 1 ? 0.010 : -0.010) * Math.ceil(count / 2);

          const raidEnt = new window.og.Entity({{
            name: `RAID_${{c.case_number}}`,
            lonlat: new window.og.LonLat(coords.lon + lonJitter, coords.lat + latJitter, 800),
            billboard: makeRaidBillboard(c.case_number, c.suspect_name, townName)
          }});

          raidEnt.events.on("click", () => {{
            selectInvoiceById(c.case_number, false);
            if (typeof globusFlyToCase === 'function') globusFlyToCase(c);
          }});

          globusMarkersLayer.add(raidEnt);
        }});

        // 5. Add ACTIVELY TARGETED SUSPECT CASE PIN (Ruby lock reticle billboard)
        if (activeCase) {{
          const locText = ((activeCase.raid_location || '') + ' ' + (activeCase.suspect_name || '') + ' ' + (activeCase.region || '') + ' ' + (activeCase.title || '')).toLowerCase();
          let coords = null;
          let townName = activeCase.raid_location || activeCase.region || 'Tactical Sector';

          for (const [k, v] of Object.entries(MMDA_DISTRICT_GEO)) {{
            if (locText.includes(k)) {{
              coords = v;
              townName = v.name || townName;
              break;
            }}
          }}
          if (!coords && activeCase.region && GHANA_REGION_GEO[activeCase.region]) {{
            coords = GHANA_REGION_GEO[activeCase.region];
            townName = `${{activeCase.region}} Sector`;
          }}
          if (!coords) {{
            coords = GHANA_REGION_GEO['Greater Accra'];
            townName = 'Accra Metro';
          }}

          const targetEnt = new window.og.Entity({{
            name: `TARGET_${{activeCase.case_number}}`,
            lonlat: new window.og.LonLat(coords.lon, coords.lat, 2200),
            billboard: makeTargetBillboard(activeCase.case_number, activeCase.suspect_name, townName)
          }});

          targetEnt.events.on("click", () => {{
            selectInvoiceById(activeCase.case_number, false);
            if (typeof globusFlyToCase === 'function') globusFlyToCase(activeCase);
          }});

          globusMarkersLayer.add(targetEnt);
        }}
      }} catch (err) {{
        console.warn('[OpenGlobus] Error refreshing markers:', err);
      }}
    }}"""

g_start = code.find('    function refreshGlobusEntities(targetCase = null) {{')
g_end = code.find('\n    // ──────────────────────────────────────────────────────────\n    // 0.1 TACTICAL THEATRE OPENGLOBUS', g_start)
if g_start != -1 and g_end != -1:
    code = code[:g_start] + robust_refresh_globus + code[g_end:]
    print("Replaced refreshGlobusEntities with composite billboards successfully!")
else:
    print(f"Error finding refreshGlobusEntities: {g_start}, {g_end}")

# 5. Update refreshTacGlobusEntities to use composite billboards without font atlas dependency
robust_refresh_tac = """    function refreshTacGlobusEntities(targetCase = null) {{
      if (!tacGlobusMarkersLayer || !window.og) return;
      try {{
        tacGlobusMarkersLayer.clear();

        // 1. Calculate higher caseload hotspot across regions/districts
        let maxCaseload = 0;
        let highestRegion = 'Greater Accra';
        Object.keys(regionsData).forEach(k => {{
          const count = regionsData[k] ? (regionsData[k].cases || 0) : 0;
          if (count > maxCaseload) {{
            maxCaseload = count;
            highestRegion = k;
          }}
        }});

        // 2. Add HIGHER CASELOAD HOTSPOT MAP POINT PIN (Beacon on highest caseload region/town)
        const highestGeo = GHANA_REGION_GEO[highestRegion] || {{ lon: -0.1969, lat: 5.6037 }};
        const hotspotEnt = new window.og.Entity({{
          name: `TAC_HOTSPOT_${{highestRegion}}`,
          lonlat: new window.og.LonLat(highestGeo.lon, highestGeo.lat, 1400),
          billboard: makeHotspotBillboard(highestRegion, maxCaseload)
        }});
        tacGlobusMarkersLayer.add(hotspotEnt);

        // 3. Add REGIONAL & MMDA TOWN WAYPOINT PINS for all other sectors
        for (const [rName, rGeo] of Object.entries(GHANA_REGION_GEO)) {{
          if (rName === highestRegion) continue;
          const rData = regionsData[rName];
          const cases = rData ? rData.cases : 0;

          const regEnt = new window.og.Entity({{
            name: `TAC_REG_${{rName}}`,
            lonlat: new window.og.LonLat(rGeo.lon, rGeo.lat, 800),
            billboard: makeDistrictBillboard(rName, cases)
          }});
          tacGlobusMarkersLayer.add(regEnt);
        }}

        // 4. Add RAID LOCATION MAP POINT PINS for all active live cases
        const activeCase = targetCase || (selectedCaseId ? liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId)) : null);
        const activeCaseNum = activeCase ? activeCase.case_number : null;

        const renderedRaids = new Map();

        liveCases.forEach(c => {{
          if (c.case_number === activeCaseNum) return;

          const locText = ((c.raid_location || '') + ' ' + (c.suspect_name || '') + ' ' + (c.region || '') + ' ' + (c.title || '')).toLowerCase();
          let coords = null;
          let townName = c.raid_location || c.region || 'Tactical Sector';

          for (const [k, v] of Object.entries(MMDA_DISTRICT_GEO)) {{
            if (locText.includes(k)) {{
              coords = v;
              townName = v.name || townName;
              break;
            }}
          }}
          if (!coords && c.region && GHANA_REGION_GEO[c.region]) {{
            coords = GHANA_REGION_GEO[c.region];
            townName = `${{c.region}} Sector`;
          }}
          if (!coords) {{
            coords = GHANA_REGION_GEO['Greater Accra'];
            townName = 'Accra Metro (AMA)';
          }}

          const geoKey = `${{coords.lon.toFixed(3)}}_${{coords.lat.toFixed(3)}}`;
          const count = renderedRaids.get(geoKey) || 0;
          renderedRaids.set(geoKey, count + 1);

          const lonJitter = count === 0 ? 0 : (count % 2 === 1 ? 0.014 : -0.014) * Math.ceil(count / 2);
          const latJitter = count === 0 ? 0 : (count % 2 === 1 ? 0.010 : -0.010) * Math.ceil(count / 2);

          const raidEnt = new window.og.Entity({{
            name: `TAC_RAID_${{c.case_number}}`,
            lonlat: new window.og.LonLat(coords.lon + lonJitter, coords.lat + latJitter, 1000),
            billboard: makeRaidBillboard(c.case_number, c.suspect_name, townName)
          }});

          raidEnt.events.on("click", () => {{
            selectInvoiceById(c.case_number, false);
            if (typeof tacGlobusFlyToCase === 'function') tacGlobusFlyToCase(c);
          }});

          tacGlobusMarkersLayer.add(raidEnt);
        }});

        // 5. Add ACTIVELY TARGETED SUSPECT CASE PIN (Ruby crosshair lock pin)
        if (activeCase) {{
          const locText = ((activeCase.raid_location || '') + ' ' + (activeCase.suspect_name || '') + ' ' + (activeCase.region || '') + ' ' + (activeCase.title || '')).toLowerCase();
          let coords = null;
          let townName = activeCase.raid_location || activeCase.region || 'Tactical Sector';

          for (const [k, v] of Object.entries(MMDA_DISTRICT_GEO)) {{
            if (locText.includes(k)) {{
              coords = v;
              townName = v.name || townName;
              break;
            }}
          }}
          if (!coords && activeCase.region && GHANA_REGION_GEO[activeCase.region]) {{
            coords = GHANA_REGION_GEO[activeCase.region];
            townName = `${{activeCase.region}} Sector`;
          }}
          if (!coords) {{
            coords = GHANA_REGION_GEO['Greater Accra'];
            townName = 'Accra Metro';
          }}

          const targetEnt = new window.og.Entity({{
            name: `TAC_TARGET_${{activeCase.case_number}}`,
            lonlat: new window.og.LonLat(coords.lon, coords.lat, 2500),
            billboard: makeTargetBillboard(activeCase.case_number, activeCase.suspect_name, townName)
          }});

          targetEnt.events.on("click", () => {{
            selectInvoiceById(activeCase.case_number, false);
            if (typeof tacGlobusFlyToCase === 'function') tacGlobusFlyToCase(activeCase);
          }});

          tacGlobusMarkersLayer.add(targetEnt);
        }}
      }} catch (err) {{
        console.warn('[OpenGlobus Tactical] Error refreshing markers:', err);
      }}
    }}"""

tac_start = code.find('    function refreshTacGlobusEntities(targetCase = null) {{')
tac_end = code.find('\n    let selectedRegionName = null;', tac_start)
if tac_start != -1 and tac_end != -1:
    code = code[:tac_start] + robust_refresh_tac + code[tac_end:]
    print("Replaced refreshTacGlobusEntities with composite billboards successfully!")
else:
    print(f"Error finding refreshTacGlobusEntities: {tac_start}, {tac_end}")

with open(build_file, 'w', encoding='utf-8') as f:
    f.write(code)

print("build_clean_dashboards.py updated successfully!")
