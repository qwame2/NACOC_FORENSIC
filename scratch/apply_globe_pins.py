import os

build_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'build_clean_dashboards.py')

with open(build_file, 'r', encoding='utf-8') as f:
    code = f.read()

# New refreshGlobusEntities implementation
new_refresh_globus = """    function refreshGlobusEntities(targetCase = null) {{
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

        // 2. Add HIGHER CASELOAD HOTSPOT MAP POINT PIN ICON (Beacon on highest caseload region/town)
        const highestGeo = GHANA_REGION_GEO[highestRegion] || {{ lon: -0.1969, lat: 5.6037 }};
        const hotspotEnt = new window.og.Entity({{
          name: `HOTSPOT_${{highestRegion}}`,
          lonlat: new window.og.LonLat(highestGeo.lon, highestGeo.lat, 1200),
          billboard: {{
            src: HOTSPOT_PIN_SVG,
            width: 34,
            height: 46,
            offset: [0, -23]
          }},
          label: {{
            text: `HIGHER CASELOAD HOTSPOT: ${{highestRegion.toUpperCase()}} (${{maxCaseload}} DOCKETS)`,
            size: 13,
            color: '#ffffff',
            outlineColor: '#991b1b',
            outline: 4,
            offset: [0, -32]
          }}
        }});
        globusMarkersLayer.add(hotspotEnt);

        // 3. Add REGIONAL & MMDA TOWN WAYPOINT PINS for all other sectors
        for (const [rName, rGeo] of Object.entries(GHANA_REGION_GEO)) {{
          if (rName === highestRegion) continue; // Already marked with HOTSPOT_PIN_SVG!
          const rData = regionsData[rName];
          const cases = rData ? rData.cases : 0;

          const regEnt = new window.og.Entity({{
            name: `REG_${{rName}}`,
            lonlat: new window.og.LonLat(rGeo.lon, rGeo.lat, 600),
            billboard: {{
              src: DISTRICT_PIN_SVG,
              width: 22,
              height: 30,
              offset: [0, -15]
            }},
            label: {{
              text: `[${{rName.toUpperCase()}}: ${{cases}} DOCKETS]`,
              size: 11,
              color: '#cbd5e1',
              outlineColor: '#0f172a',
              outline: 2,
              offset: [0, -24]
            }}
          }});
          globusMarkersLayer.add(regEnt);
        }}

        // 4. Add RAID LOCATION MAP POINT PINS for all active live cases
        const activeCase = targetCase || (selectedCaseId ? liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId)) : null);
        const activeCaseNum = activeCase ? activeCase.case_number : null;

        const renderedRaids = new Map();

        liveCases.forEach(c => {{
          // The selected case gets the prominent TARGET_PIN_SVG below
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
            billboard: {{
              src: RAID_PIN_SVG,
              width: 28,
              height: 38,
              offset: [0, -19]
            }},
            label: {{
              text: `RAID: ${{c.case_number}} • ${{c.suspect_name}} [${{townName}}]`,
              size: 11,
              color: '#ffffff',
              outlineColor: '#0c4a6e',
              outline: 3,
              offset: [0, -28]
            }}
          }});

          raidEnt.events.on("click", () => {{
            selectInvoiceById(c.case_number, false);
            if (typeof globusFlyToCase === 'function') globusFlyToCase(c);
          }});

          globusMarkersLayer.add(raidEnt);
        }});

        // 5. Add ACTIVELY TARGETED SUSPECT CASE PIN ICON (Ruby crosshair lock pin)
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
            billboard: {{
              src: TARGET_PIN_SVG,
              width: 36,
              height: 48,
              offset: [0, -24]
            }},
            label: {{
              text: `TARGET LOCK: ${{activeCase.case_number}} • ${{activeCase.suspect_name}} [${{townName}}]`,
              size: 14,
              color: '#ffffff',
              outlineColor: '#be123c',
              outline: 5,
              offset: [0, -34]
            }}
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

# Locate and replace refreshGlobusEntities
g_start = code.find('    function refreshGlobusEntities(targetCase = null) {{')
g_end = code.find('\n    // ──────────────────────────────────────────────────────────\n    // 0.1 TACTICAL THEATRE OPENGLOBUS', g_start)
if g_start != -1 and g_end != -1:
    code = code[:g_start] + new_refresh_globus + code[g_end:]
    print("Replaced refreshGlobusEntities by exact slice!")
else:
    print(f"Error finding refreshGlobusEntities: {g_start}, {g_end}")

# New refreshTacGlobusEntities implementation
new_refresh_tac = """    function refreshTacGlobusEntities(targetCase = null) {{
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

        // 2. Add HIGHER CASELOAD HOTSPOT MAP POINT PIN ICON (Beacon on highest caseload region/town)
        const highestGeo = GHANA_REGION_GEO[highestRegion] || {{ lon: -0.1969, lat: 5.6037 }};
        const hotspotEnt = new window.og.Entity({{
          name: `TAC_HOTSPOT_${{highestRegion}}`,
          lonlat: new window.og.LonLat(highestGeo.lon, highestGeo.lat, 1400),
          billboard: {{
            src: HOTSPOT_PIN_SVG,
            width: 36,
            height: 48,
            offset: [0, -24]
          }},
          label: {{
            text: `HIGHER CASELOAD HOTSPOT: ${{highestRegion.toUpperCase()}} (${{maxCaseload}} DOCKETS)`,
            size: 14,
            color: '#fecaca',
            outlineColor: '#991b1b',
            outline: 5,
            offset: [0, -34]
          }}
        }});
        tacGlobusMarkersLayer.add(hotspotEnt);

        // 3. Add REGIONAL & MMDA TOWN WAYPOINT PINS for all other sectors
        for (const [rName, rGeo] of Object.entries(GHANA_REGION_GEO)) {{
          if (rName === highestRegion) continue; // Already marked with HOTSPOT_PIN_SVG!
          const rData = regionsData[rName];
          const cases = rData ? rData.cases : 0;

          const regEnt = new window.og.Entity({{
            name: `TAC_REG_${{rName}}`,
            lonlat: new window.og.LonLat(rGeo.lon, rGeo.lat, 800),
            billboard: {{
              src: DISTRICT_PIN_SVG,
              width: 24,
              height: 32,
              offset: [0, -16]
            }},
            label: {{
              text: `[${{rName.toUpperCase()}}: ${{cases}} DOCKETS]`,
              size: 12,
              color: '#67e8f9',
              outlineColor: '#042f2e',
              outline: 3,
              offset: [0, -26]
            }}
          }});
          tacGlobusMarkersLayer.add(regEnt);
        }}

        // 4. Add RAID LOCATION MAP POINT PINS for all active live cases
        const activeCase = targetCase || (selectedCaseId ? liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId)) : null);
        const activeCaseNum = activeCase ? activeCase.case_number : null;

        const renderedRaids = new Map();

        liveCases.forEach(c => {{
          // The selected case gets the prominent TARGET_PIN_SVG below
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
            name: `TAC_RAID_${{c.case_number}}`,
            lonlat: new window.og.LonLat(coords.lon + lonJitter, coords.lat + latJitter, 1000),
            billboard: {{
              src: RAID_PIN_SVG,
              width: 30,
              height: 40,
              offset: [0, -20]
            }},
            label: {{
              text: `RAID: ${{c.case_number}} • ${{c.suspect_name}} [${{townName}}]`,
              size: 11.5,
              color: '#e0f2fe',
              outlineColor: '#032d42',
              outline: 3,
              offset: [0, -30]
            }}
          }});

          raidEnt.events.on("click", () => {{
            selectInvoiceById(c.case_number, false);
            if (typeof tacGlobusFlyToCase === 'function') tacGlobusFlyToCase(c);
          }});

          tacGlobusMarkersLayer.add(raidEnt);
        }});

        // 5. Add ACTIVELY TARGETED SUSPECT CASE PIN ICON (Ruby crosshair lock pin)
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
            billboard: {{
              src: TARGET_PIN_SVG,
              width: 38,
              height: 50,
              offset: [0, -25]
            }},
            label: {{
              text: `TARGET LOCK: ${{activeCase.case_number}} • ${{activeCase.suspect_name}} [${{townName}}]`,
              size: 15,
              color: '#ffffff',
              outlineColor: '#9f1239',
              outline: 5,
              offset: [0, -36]
            }}
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

# Locate and replace refreshTacGlobusEntities
tac_start = code.find('    function refreshTacGlobusEntities(targetCase = null) {{')
tac_end = code.find('\n    let selectedRegionName = null;', tac_start)
if tac_start != -1 and tac_end != -1:
    code = code[:tac_start] + new_refresh_tac + code[tac_end:]
    print("Replaced refreshTacGlobusEntities by exact slice!")
else:
    print(f"Error finding refreshTacGlobusEntities: {tac_start}, {tac_end}")

with open(build_file, 'w', encoding='utf-8') as f:
    f.write(code)

print("build_clean_dashboards.py updated successfully!")
