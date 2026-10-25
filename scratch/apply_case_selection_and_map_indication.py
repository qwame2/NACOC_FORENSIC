import os
import re

script_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'build_clean_dashboards.py')

with open(script_path, 'r', encoding='utf-8') as f:
    code = f.read()

# 1. Update selectInvoiceById and add indicateCaseOnMap
old_select_fn = '''    function selectInvoiceById(caseId, centerMap = true) {
      selectedCaseId = caseId;
      const c = liveCases.find(item => item.id == caseId);
      if (!c) return;

      renderInvoicesList();
      renderTacticalCasesStream();
      renderDossierCard(c);

      if (centerMap && c.region && selectedRegionName !== c.region) {
        selectRegionByName(c.region, isTacticalModeOpen);
      }
    }

    // ──────────────────────────────────────────────────────────
    // 5. 3D GHANA MAP ENGINE & REGION DRILL-DOWN ARCHITECTURE
    // ──────────────────────────────────────────────────────────
    function selectRegionByName(regName, fromTac = false) {
      selectedRegionName = regName;
      focusRegionOnMap(regName, fromTac);
      renderInvoicesList();

      const matchedCase = liveCases.find(c => c.region && c.region.toLowerCase() === regName.toLowerCase());
      if (matchedCase) {
        selectInvoiceById(matchedCase.id, false);
      }
    }'''

# Note: In build_clean_dashboards.py, curly braces are doubled for Python f-string
old_select_fn_pattern = r"function selectInvoiceById\(caseId, centerMap = true\) \{\{.*?function selectRegionByName\(regName, fromTac = false\) \{\{.*?\}\}"

new_select_and_indicate = '''function selectInvoiceById(caseRef, centerMap = true) {{
      const c = liveCases.find(item => item.case_number === caseRef || String(item.id) === String(caseRef));
      if (!c) return;

      selectedCaseId = c.case_number;

      renderInvoicesList();
      renderTacticalCasesStream();
      renderDossierCard(c);

      if (centerMap && c.region) {{
        indicateCaseOnMap(c);
      }}
    }}

    function indicateCaseOnMap(c) {{
      if (!c || !c.region) return;
      selectedRegionName = c.region;
      focusRegionOnMap(c.region, isTacticalModeOpen);
      renderDistrictMap(c.region, c);
      renderTacDistrictMap(c.region, c);
    }}

    // ──────────────────────────────────────────────────────────
    // 5. 3D GHANA MAP ENGINE & REGION DRILL-DOWN ARCHITECTURE
    // ──────────────────────────────────────────────────────────
    function selectRegionByName(regName, fromTac = false) {{
      selectedRegionName = regName;
      focusRegionOnMap(regName, fromTac);

      const matchedCase = liveCases.find(c => c.region && c.region.toLowerCase() === regName.toLowerCase());
      if (matchedCase) {{
        selectInvoiceById(matchedCase.case_number, false);
      }} else {{
        renderDistrictMap(regName, null);
        renderTacDistrictMap(regName, null);
        renderInvoicesList();
      }}
    }}'''

code = re.sub(old_select_fn_pattern, new_select_and_indicate, code, count=1, flags=re.DOTALL)

# 2. Update renderInvoicesList to not hide cases in other regions, and use c.case_number
# Find renderInvoicesList block
pat_invoices_list = r"function renderInvoicesList\(\) \{\{.*?container\.innerHTML = filtered\.map\(c => \{\{.*?\}\}\)\.join\(''\);\s*\}\}"

new_invoices_list = '''function renderInvoicesList() {{
      const container = document.getElementById('invoicesListContainer');
      if (!container) return;

      let filtered = liveCases.filter(c => {{
        if (filterUnit !== 'All' && c.unit_name && !c.unit_name.toLowerCase().includes(filterUnit.toLowerCase())) {{
          return false;
        }}
        if (filterStatus !== 'All' && c.status_badge && c.status_badge.toLowerCase() !== filterStatus.toLowerCase()) {{
          return false;
        }}
        if (searchQuery) {{
          const q = searchQuery.toLowerCase();
          const caseMatch = c.case_number && c.case_number.toLowerCase().includes(q);
          const susMatch = c.suspect_name && c.suspect_name.toLowerCase().includes(q);
          const offMatch = c.submitting_officer && c.submitting_officer.toLowerCase().includes(q);
          const locMatch = c.raid_location && c.raid_location.toLowerCase().includes(q);
          if (!caseMatch && !susMatch && !offMatch && !locMatch) return false;
        }}
        return true;
      }});

      const countBadge = document.getElementById('listCasesCount');
      if (countBadge) countBadge.textContent = `${{filtered.length}} Dockets Active`;

      if (filtered.length === 0) {{
        container.innerHTML = `
          <div class="text-center py-10 px-4 rounded-xl border border-dashed border-slate-700/60 text-slate-400">
            <i data-lucide="folder-search" class="w-6 h-6 mx-auto mb-2 text-slate-500"></i>
            <p class="text-xs font-semibold">No forensic cases match filters</p>
            <button onclick="resetFilters()" class="mt-2 text-[10px] text-indigo-400 hover:underline">Reset filters</button>
          </div>
        `;
        lucide.createIcons();
        return;
      }}

      container.innerHTML = filtered.map(c => {{
        const isSelected = (c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId));
        const isOnRadar = (selectedRegionName && c.region && c.region.toLowerCase() === selectedRegionName.toLowerCase());

        const activeClasses = isSelected 
          ? 'bg-[#5c56df] text-white shadow-lg shadow-indigo-500/25 ring-1 ring-white/30' 
          : (isOnRadar ? 'bg-slate-800/90 hover:bg-slate-800 text-slate-100 border border-indigo-500/40' : 'bg-white/5 hover:bg-white/10 text-slate-200 border border-white/5');
        
        const statusBadgeClass = isSelected
          ? 'bg-white/20 text-white'
          : (c.status_badge === 'Approved' ? 'bg-emerald-500/20 text-emerald-300' : (c.status_badge === 'Pending' ? 'bg-amber-500/20 text-amber-300' : 'bg-cyan-500/20 text-cyan-300'));

        const isMulti = c.is_multiple && c.suspects && c.suspects.length > 1;
        const initials = c.suspect_name ? c.suspect_name.split(' ').map(function(n) {{ return n[0]; }}).join('').substring(0, 2) : 'TG';
        const exhibitCount = c.exhibits_count || (c.exhibits ? c.exhibits.length : 1);

        // Render stacked avatars if multiple suspects
        let avatarMarkup = '';
        if (isMulti) {{
          avatarMarkup = '<div class="flex -space-x-2 overflow-hidden flex-shrink-0">' +
            c.suspects.slice(0, 3).map(function(s) {{
              const sInit = s.name ? s.name.split(' ').map(function(n) {{ return n[0]; }}).join('').substring(0, 2) : 'S';
              const imgHtml = s.mugshot_front ? '<img src="' + s.mugshot_front + '" alt="" class="w-full h-full object-cover">' : sInit;
              return '<div class="w-8 h-8 rounded-full overflow-hidden bg-slate-700 ring-2 ring-slate-900 flex items-center justify-center text-[10px] font-bold text-white shadow-xs" title="' + (s.name || '') + ' (' + (s.role || '') + ')">' + imgHtml + '</div>';
            }}).join('') +
            '</div>';
        }} else {{
          avatarMarkup = `
            <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0 bg-slate-700 flex items-center justify-center font-bold text-xs ring-1 ring-white/20 text-slate-200">
              ${{c.mugshot_front ? `<img src="${{c.mugshot_front}}" alt="" class="w-full h-full object-cover">` : initials}}
            </div>
          `;
        }}

        // Region Radar Badge
        const regionBadgeHtml = isOnRadar
          ? `<span class="px-1.5 py-0.2 rounded bg-indigo-500/40 text-indigo-200 border border-indigo-400/50 font-bold font-mono-code text-[8.5px]">📍 ${{c.region || 'Greater Accra'}} (ON RADAR)</span>`
          : `<span class="text-[9.5px] ${{isSelected ? 'text-indigo-100' : 'text-slate-400'}} truncate">📍 ${{c.region || 'Greater Accra'}}</span>`;

        // Render suspect names
        let suspectsDisplay = '';
        if (isMulti) {{
          const namesHtml = c.suspects.map(function(s, idx) {{
            const isPrim = (idx === 0) ? 'underline decoration-indigo-300' : '';
            return '<span class="' + isPrim + '">' + (s.name || 'Suspect') + '</span>';
          }}).join(' & ');

          suspectsDisplay = `
            <div class="text-[10.5px] font-bold ${{isSelected ? 'text-white' : 'text-slate-100'}} truncate">
              👥 ${{namesHtml}}
            </div>
            <div class="text-[9.5px] truncate flex items-center space-x-1 mt-0.5">
              <span class="px-1.5 py-0.2 rounded bg-indigo-500/30 text-indigo-200 font-mono-code font-bold">${{c.suspects.length}} Suspects</span>
              <span>•</span>
              ${{regionBadgeHtml}}
            </div>
          `;
        }} else {{
          suspectsDisplay = `
            <div class="text-[10px] font-bold ${{isSelected ? 'text-white' : 'text-slate-200'}} truncate">
              👤 ${{c.suspect_name}}
            </div>
            <div class="mt-0.5 truncate">
              ${{regionBadgeHtml}}
            </div>
          `;
        }}

        return `
          <div onclick="selectInvoiceById('${{c.case_number}}', true)" 
               class="p-2.5 rounded-2xl cursor-pointer transition-all flex items-center justify-between gap-2.5 ${{activeClasses}} group">
            
            <div class="flex items-center space-x-2.5 min-w-0">
              ${{avatarMarkup}}
              <div class="truncate">
                <div class="flex items-center space-x-1.5">
                  <span class="font-bold text-xs font-mono-code">${{c.case_number}}</span>
                  ${{isMulti ? '<span class="text-[8.5px] px-1 py-0.2 bg-indigo-400/30 text-indigo-200 rounded font-bold">MULTI</span>' : ''}}
                </div>
                ${{suspectsDisplay}}
              </div>
            </div>

            <div class="flex items-center space-x-2 flex-shrink-0 text-right">
              <span class="text-[10px] px-2 py-0.5 rounded-full font-mono-code font-bold ${{statusBadgeClass}}">
                ${{c.status_badge}}
              </span>
              <span class="font-extrabold text-xs font-mono-code text-indigo-300">${{exhibitCount}} Exh</span>
            </div>

          </div>
        `;
      }}).join('');
    }}'''

code = re.sub(pat_invoices_list, new_invoices_list, code, count=1, flags=re.DOTALL)

# 3. Update renderTacticalCasesStream to use c.case_number and isSelected comparison
pat_tac_stream = r"container\.innerHTML = filtered\.map\(c => \{\{\s*const isSelected = \(c\.id == selectedCaseId\);.*?return `\s*<div onclick=\"selectInvoiceById\(\$\{\{c\.id\}\}, true\); selectRegionByName\('\$\{\{c\.region\}\}', true\);\""

new_tac_stream_start = '''container.innerHTML = filtered.map(c => {{
        const isSelected = (c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId));
        const cardStyle = isSelected 
          ? 'bg-red-500/20 border-red-500 text-white shadow-lg shadow-red-500/20 ring-1 ring-red-500' 
          : 'bg-black/40 border-slate-800 hover:border-slate-700 text-slate-300';
        
        const badgeStyle = isSelected 
          ? 'bg-red-500 text-white font-bold' 
          : (c.status_badge === 'Approved' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30');

        const isMulti = c.is_multiple && c.suspects && c.suspects.length > 1;
        const suspectsLine = isMulti
          ? '👥 ' + c.suspects.length + ' Targets: ' + c.suspects.map(function(s) {{ return s.name; }}).join(' & ')
          : '👤 ' + (c.suspect_name || 'Target');

        return `
          <div onclick="selectInvoiceById('${{c.case_number}}', true);"'''

code = re.sub(pat_tac_stream, new_tac_stream_start, code, count=1, flags=re.DOTALL)

# 4. Update initial selection in applyRealtimeData to select by case_number
pat_initial_select = r"if \(!selectedCaseId \|\| !liveCases\.some\(c => c\.id == selectedCaseId\)\) \{\{\s*selectInvoiceById\(liveCases\[0\]\.id, false\);\s*\}\} else \{\{\s*renderDossierCard\(liveCases\.find\(c => c\.id == selectedCaseId\)\);\s*\}\}"

new_initial_select = '''if (!selectedCaseId || !liveCases.some(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId))) {{
          selectInvoiceById(liveCases[0].case_number, true);
        }} else {{
          renderInvoicesList();
          renderTacticalCasesStream();
          renderDossierCard(liveCases.find(c => c.case_number === selectedCaseId || String(c.id) === String(selectedCaseId)));
        }}'''

code = re.sub(pat_initial_select, new_initial_select, code, count=1, flags=re.DOTALL)

# 5. In district nodes, replace selectInvoiceById(${primaryCase.id}, false) with case_number
code = code.replace("selectInvoiceById(${{primaryCase.id}}, false)", "selectInvoiceById('${{primaryCase.case_number}}', false)")
code = code.replace("selectInvoiceById(${{c.id}}, false)", "selectInvoiceById('${{c.case_number}}', false)")
code = code.replace("selectInvoiceById(${{firstCase.id}}, false)", "selectInvoiceById('${{firstCase.case_number}}', false)")

with open(script_path, 'w', encoding='utf-8') as f:
    f.write(code)

print("Updated build_clean_dashboards.py with unique case_number selection & dynamic map indication!")
