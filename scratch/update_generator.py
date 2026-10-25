import os

scratch_dir = os.path.dirname(os.path.abspath(__file__))
compiler_path = os.path.join(scratch_dir, 'compile_all_dashboards.py')

# We will read git or original file or read compile_all_dashboards.py
# Let's see if git has the clean version or if we can restore and update cleanly
with open(compiler_path, 'r', encoding='utf-8') as f:
    code = f.read()

# Replace single braces in CSS keyframes
code = code.replace(
    '''    @keyframes hotspotPulse {
      0%, 100% { filter: drop-shadow(0 0 20px rgba(220, 38, 38, 0.75)); }
      50% { filter: drop-shadow(0 0 35px rgba(239, 68, 68, 1)); }
    }
    @keyframes tacHotspotPulse {
      0%, 100% { filter: drop-shadow(0 0 25px rgba(239, 68, 68, 0.8)); }
      50% { filter: drop-shadow(0 0 45px rgba(239, 68, 68, 1)); }
    }''',
    '''    @keyframes hotspotPulse {{
      0%, 100% {{ filter: drop-shadow(0 0 20px rgba(220, 38, 38, 0.75)); }}
      50% {{ filter: drop-shadow(0 0 35px rgba(239, 68, 68, 1)); }}
    }}
    @keyframes tacHotspotPulse {{
      0%, 100% {{ filter: drop-shadow(0 0 25px rgba(239, 68, 68, 0.8)); }}
      50% {{ filter: drop-shadow(0 0 45px rgba(239, 68, 68, 1)); }}
    }}'''
)

# Now fix JS template literals in renderDistrictMap and renderTacDistrictMap
# Replace ${ with ${{ and } with }} inside the template strings of renderDistrictMap and renderTacDistrictMap
old_render_district_map = '''    // Renders all Official Districts and their linked cases on the Standard Map
    function renderDistrictMap(regName) {'''

# Let's replace the whole renderDistrictMap and renderTacDistrictMap block with properly escaped double-braces
map_funcs_replacement = """    // Renders all Official Districts and their linked cases on the Standard Map
    function renderDistrictMap(regName) {{
      const group = document.getElementById('districtLayersGroup');
      if (!group) return;

      const districts = districtsData[regName] || [];
      const regionCases = liveCases.filter(c => c.region && c.region.toLowerCase() === regName.toLowerCase());

      // Match cases to districts using keyword matching
      const districtWithCases = districts.map(d => {{
        const linked = regionCases.filter(c => {{
          const locText = ((c.raid_location || '') + ' ' + (c.title || '') + ' ' + (c.suspect_name || '')).toLowerCase();
          return d.keywords.some(kw => locText.includes(kw.toLowerCase()));
        }});
        return {{ district: d, cases: linked }};
      }});

      // Fallback: If region cases have no keyword match, assign to capital/first MMDA district
      const matchedCaseIds = new Set();
      districtWithCases.forEach(dc => dc.cases.forEach(c => matchedCaseIds.add(c.id)));
      const unassigned = regionCases.filter(c => !matchedCaseIds.has(c.id));
      if (unassigned.length > 0 && districtWithCases.length > 0) {{
        districtWithCases[0].cases.push(...unassigned);
      }}

      // Build SVG nodes for each MMDA District
      group.innerHTML = districtWithCases.map(item => {{
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;

        if (hasCases) {{
          // CRITICAL RED HOTSPOT DISTRICT: High case presence
          const primaryCase = linked[0];
          return `
            <g class="district-node cursor-pointer group" onclick="event.stopPropagation(); selectInvoiceById(${{primaryCase.id}}, false);">
              <!-- Glowing Red Radar Ping -->
              <circle cx="${{d.x}}" cy="${{d.y}}" r="18" fill="none" stroke="#ef4444" stroke-width="2" class="animate-ping opacity-75" />
              <circle cx="${{d.x}}" cy="${{d.y}}" r="26" fill="rgba(239, 68, 68, 0.15)" stroke="#ef4444" stroke-width="1" stroke-dasharray="2, 3" />
              <!-- Red District Core Node -->
              <circle cx="${{d.x}}" cy="${{d.y}}" r="8.5" fill="#ef4444" stroke="#ffffff" stroke-width="2.5" filter="drop-shadow(0 0 16px rgba(239,68,68,0.9))" />
              
              <!-- MMDA District Header Tag -->
              <text x="${{d.x}}" y="${{d.y - 14}}" text-anchor="middle" font-size="11.5" font-weight="900" fill="#ffffff" stroke="#000000" stroke-width="3" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none tracking-wide">
                [${{d.code}}] ${{d.short_name.toUpperCase()}}
              </text>

              <!-- Linked Case Docket Card Attached to District -->
              <g transform="translate(${{d.x - 75}}, ${{d.y + 12}})">
                <rect width="150" height="38" rx="8" fill="#181926" stroke="#ef4444" stroke-width="2" filter="drop-shadow(0 8px 20px rgba(239,68,68,0.5))" class="group-hover:fill-[#2d1b24] transition-colors" />
                <!-- Red Alert Badge inside pill -->
                <circle cx="14" cy="19" r="5" fill="#ef4444" />
                <text x="24" y="16" font-size="9.5" font-weight="bold" fill="#fca5a5" font-family="'JetBrains Mono', monospace">
                  ${{primaryCase.case_number}}
                </text>
                <text x="24" y="29" font-size="8.5" font-weight="bold" fill="#ffffff" class="truncate">
                  ${{primaryCase.suspect_name.substring(0, 16)}} • ${{primaryCase.exhibits_count || 1}} Exh
                </text>
                <text x="142" y="16" text-anchor="end" font-size="8" font-weight="extrabold" fill="#ef4444" font-family="'JetBrains Mono', monospace">
                  HOTSPOT
                </text>
              </g>
            </g>
          `;
        }} else {{
          // Standard MMDA District Post (No current cases)
          return `
            <g class="district-node cursor-pointer group" onclick="event.stopPropagation();">
              <circle cx="${{d.x}}" cy="${{d.y}}" r="5" fill="#334155" stroke="#94a3b8" stroke-width="1.8" class="group-hover:fill-indigo-500 group-hover:stroke-white transition-colors" />
              <text x="${{d.x}}" y="${{d.y - 10}}" text-anchor="middle" font-size="9.5" font-weight="bold" fill="#cbd5e1" stroke="#0f172a" stroke-width="2.5" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none group-hover:fill-white">
                [${{d.code}}] ${{d.short_name}}
              </text>
              <text x="${{d.x}}" y="${{d.y + 15}}" text-anchor="middle" font-size="8" font-weight="semibold" fill="#64748b" font-family="'JetBrains Mono', monospace" class="select-none">
                0 Dockets Active
              </text>
            </g>
          `;
        }}
      }}).join('');
    }}

    // Renders Tactical Radar MMDA Districts & Hotspots
    function renderTacDistrictMap(regName) {{
      const group = document.getElementById('tacDistrictLayersGroup');
      if (!group) return;

      const districts = districtsData[regName] || [];
      const regionCases = liveCases.filter(c => c.region && c.region.toLowerCase() === regName.toLowerCase());

      const districtWithCases = districts.map(d => {{
        const linked = regionCases.filter(c => {{
          const locText = ((c.raid_location || '') + ' ' + (c.title || '') + ' ' + (c.suspect_name || '')).toLowerCase();
          return d.keywords.some(kw => locText.includes(kw.toLowerCase()));
        }});
        return {{ district: d, cases: linked }};
      }});

      const matchedCaseIds = new Set();
      districtWithCases.forEach(dc => dc.cases.forEach(c => matchedCaseIds.add(c.id)));
      const unassigned = regionCases.filter(c => !matchedCaseIds.has(c.id));
      if (unassigned.length > 0 && districtWithCases.length > 0) {{
        districtWithCases[0].cases.push(...unassigned);
      }}

      // Populate Tactical Left HUD MMDA list
      const tacDistList = document.getElementById('tacHudDistrictsList');
      if (tacDistList) {{
        tacDistList.innerHTML = districtWithCases.map(item => {{
          const d = item.district;
          const count = item.cases.length;
          const isRed = count > 0;
          return `
            <div onclick="if (${{count}} > 0) selectInvoiceById(${{item.cases[0].id}}, false);" 
                 class="flex items-center justify-between p-1.5 rounded ${{isRed ? 'bg-red-950/60 border border-red-500/50 hover:bg-red-900/40 text-red-200' : 'bg-slate-900/60 hover:bg-emerald-500/20 text-slate-300'}} cursor-pointer text-[10.5px]">
              <span class="flex items-center space-x-1.5 truncate">
                <span class="w-1.5 h-1.5 rounded-full ${{isRed ? 'bg-red-500 animate-ping' : 'bg-emerald-400'}}"></span>
                <span class="font-bold truncate text-white">${{d.short_name}}</span>
              </span>
              <span class="text-[9px] font-mono-code font-bold ${{isRed ? 'text-red-400' : 'text-slate-400'}}">
                ${{isRed ? `🚨 ${{count}} DOCKET` : '0 Active'}}
              </span>
            </div>
          `;
        }}).join('');
      }}

      // Render Tactical SVG Markers
      group.innerHTML = districtWithCases.map(item => {{
        const d = item.district;
        const linked = item.cases;
        const hasCases = linked.length > 0;

        if (hasCases) {{
          const c = linked[0];
          return `
            <g class="tac-district-marker cursor-pointer" onclick="event.stopPropagation(); selectInvoiceById(${{c.id}}, false);">
              <circle cx="${{d.x}}" cy="${{d.y}}" r="22" fill="none" stroke="#ef4444" stroke-width="2" class="animate-ping opacity-85" />
              <line x1="${{d.x - 14}}" y1="${{d.y}}" x2="${{d.x + 14}}" y2="${{d.y}}" stroke="#fca5a5" stroke-width="1.2" />
              <line x1="${{d.x}}" y1="${{d.y - 14}}" x2="${{d.x}}" y2="${{d.y + 14}}" stroke="#fca5a5" stroke-width="1.2" />
              <circle cx="${{d.x}}" cy="${{d.y}}" r="9" fill="#ef4444" stroke="#ffffff" stroke-width="2.5" />
              
              <text x="${{d.x}}" y="${{d.y - 16}}" text-anchor="middle" font-size="12" font-weight="900" fill="#fca5a5" stroke="#000" stroke-width="3" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                TARGET SECTOR: ${{d.short_name.toUpperCase()}}
              </text>

              <g transform="translate(${{d.x - 80}}, ${{d.y + 12}})">
                <rect width="160" height="38" rx="6" fill="#0b0f19" stroke="#ef4444" stroke-width="2" filter="drop-shadow(0 0 20px rgba(239,68,68,0.7))" />
                <circle cx="12" cy="19" r="4.5" fill="#ef4444" class="animate-ping" />
                <text x="22" y="16" font-size="10" font-weight="bold" fill="#ef4444" font-family="'JetBrains Mono', monospace">
                  INTERCEPT: ${{c.case_number}}
                </text>
                <text x="22" y="29" font-size="8.5" font-weight="bold" fill="#ffffff" font-family="'JetBrains Mono', monospace" class="truncate">
                  ${{c.suspect_name.substring(0, 16)}} [${{c.exhibits_count || 1}} EXH]
                </text>
              </g>
            </g>
          `;
        }} else {{
          return `
            <g class="tac-district-marker cursor-pointer" onclick="event.stopPropagation();">
              <circle cx="${{d.x}}" cy="${{d.y}}" r="5.5" fill="#10b981" stroke="#ffffff" stroke-width="1.8" />
              <text x="${{d.x}}" y="${{d.y - 10}}" text-anchor="middle" font-size="9" font-weight="bold" fill="#a7f3d0" stroke="#000" stroke-width="2" paint-order="stroke" font-family="'JetBrains Mono', monospace" class="select-none">
                [${{d.code}}] ${{d.short_name}}
              </text>
            </g>
          `;
        }}
      }}).join('');
    }}"""

# Find where renderDistrictMap starts and replace up to resetMapView
start_idx = code.find('    // Renders all Official Districts and their linked cases on the Standard Map')
end_idx = code.find('    function resetMapView(isTac = false)')
if start_idx != -1 and end_idx != -1:
    code = code[:start_idx] + map_funcs_replacement + '\n\n' + code[end_idx:]

with open(compiler_path, 'w', encoding='utf-8') as f:
    f.write(code)

print("compiler_path successfully fixed with proper double-braced template literals!")
