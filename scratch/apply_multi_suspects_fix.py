import os
import re

with open('scratch/build_clean_dashboards.py', 'r', encoding='utf-8') as f:
    code = f.read()

# 1. Update HTML suspect profile container in the dossier
old_profile_html = '''              <!-- Suspect Identity Profile -->
              <div class="flex items-center space-x-3 bg-white/5 p-3 rounded-2xl border border-white/5 mb-4">
                <div id="cardCustomerAvatar" class="w-12 h-12 rounded-xl bg-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center text-white font-bold text-sm ring-1 ring-white/20">
                  KM
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex items-center justify-between">
                    <span class="text-[10px] font-mono-code text-indigo-300 uppercase font-bold tracking-wider">Primary Target</span>
                    <span class="text-[10px] font-mono-code text-slate-400" id="cardSuspectId">GHA-7829104-1</span>
                  </div>
                  <div id="cardCustomerName" class="text-sm font-extrabold text-white truncate mt-0.5">
                    Kofi Mensah
                  </div>
                  <div id="cardCustomerRole" class="text-[10px] text-slate-300 truncate">
                    Ghanaian • 34 yrs • Narcotics Concealment
                  </div>
                </div>
              </div>'''

new_profile_html = '''              <!-- Suspect Identity Profile Container (Dynamic Single or Multi-Suspect) -->
              <div id="cardSuspectsProfileContainer" class="mb-4">
                <div class="flex items-center space-x-3 bg-white/5 p-3 rounded-2xl border border-white/5">
                  <div id="cardCustomerAvatar" class="w-12 h-12 rounded-xl bg-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center text-white font-bold text-sm ring-1 ring-white/20">
                    KM
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                      <span class="text-[10px] font-mono-code text-indigo-300 uppercase font-bold tracking-wider">Primary Target</span>
                      <span class="text-[10px] font-mono-code text-slate-400" id="cardSuspectId">GHA-7829104-1</span>
                    </div>
                    <div id="cardCustomerName" class="text-sm font-extrabold text-white truncate mt-0.5">
                      Kofi Mensah
                    </div>
                    <div id="cardCustomerRole" class="text-[10px] text-slate-300 truncate">
                      Ghanaian • 34 yrs • Narcotics Concealment
                    </div>
                  </div>
                </div>
              </div>'''

assert old_profile_html in code, "Could not find old_profile_html"
code = code.replace(old_profile_html, new_profile_html, 1)

# 2. Update renderInvoicesList to display all suspects in multiple cases
old_render_list = '''      container.innerHTML = filtered.map(c => {{
        const isSelected = (c.id == selectedCaseId);
        const activeClasses = isSelected 
          ? 'bg-[#5c56df] text-white shadow-lg shadow-indigo-500/25 ring-1 ring-white/30' 
          : 'bg-white/5 hover:bg-white/10 text-slate-200 border border-white/5';
        
        const statusBadgeClass = isSelected
          ? 'bg-white/20 text-white'
          : (c.status_badge === 'Approved' ? 'bg-emerald-500/20 text-emerald-300' : (c.status_badge === 'Pending' ? 'bg-amber-500/20 text-amber-300' : 'bg-cyan-500/20 text-cyan-300'));

        const initials = c.suspect_name ? c.suspect_name.split(' ').map(n => n[0]).join('').substring(0, 2) : 'TG';
        const exhibitCount = c.exhibits_count || (c.exhibits ? c.exhibits.length : 1);

        return `
          <div onclick="selectInvoiceById(${{c.id}}, true)" 
               class="p-2.5 rounded-2xl cursor-pointer transition-all flex items-center justify-between gap-2.5 ${{activeClasses}} group">
            
            <div class="flex items-center space-x-2.5 min-w-0">
              <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0 bg-slate-700 flex items-center justify-center font-bold text-xs ring-1 ring-white/20 text-slate-200">
                ${{c.mugshot_front ? `<img src="${{c.mugshot_front}}" alt="" class="w-full h-full object-cover">` : initials}}
              </div>
              <div class="truncate">
                <div class="flex items-center space-x-1.5">
                  <span class="font-bold text-xs font-mono-code">${{c.case_number}}</span>
                </div>
                <div class="text-[10px] ${{isSelected ? 'text-indigo-100' : 'text-slate-400'}} truncate">
                  ${{c.suspect_name}} • ${{c.region || 'Greater Accra'}}
                </div>
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
      }}).join('');'''

new_render_list = '''      container.innerHTML = filtered.map(c => {{
        const isSelected = (c.id == selectedCaseId);
        const activeClasses = isSelected 
          ? 'bg-[#5c56df] text-white shadow-lg shadow-indigo-500/25 ring-1 ring-white/30' 
          : 'bg-white/5 hover:bg-white/10 text-slate-200 border border-white/5';
        
        const statusBadgeClass = isSelected
          ? 'bg-white/20 text-white'
          : (c.status_badge === 'Approved' ? 'bg-emerald-500/20 text-emerald-300' : (c.status_badge === 'Pending' ? 'bg-amber-500/20 text-amber-300' : 'bg-cyan-500/20 text-cyan-300'));

        const isMulti = c.is_multiple && c.suspects && c.suspects.length > 1;
        const initials = c.suspect_name ? c.suspect_name.split(' ').map(n => n[0]).join('').substring(0, 2) : 'TG';
        const exhibitCount = c.exhibits_count || (c.exhibits ? c.exhibits.length : 1);

        // Render stacked avatars if multiple suspects
        let avatarMarkup = '';
        if (isMulti) {{
          avatarMarkup = `
            <div class="flex -space-x-2 overflow-hidden flex-shrink-0">
              ${{c.suspects.slice(0, 3).map(s => {
                const sInit = s.name ? s.name.split(' ').map(n => n[0]).join('').substring(0, 2) : 'S';
                return `
                  <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-700 ring-2 ring-slate-900 flex items-center justify-center text-[10px] font-bold text-white shadow-xs" title="${{s.name}} (${{s.role}})">
                    ${{s.mugshot_front ? `<img src="${{s.mugshot_front}}" alt="" class="w-full h-full object-cover">` : sInit}}
                  </div>
                `;
              }).join('')}}
            </div>
          `;
        }} else {{
          avatarMarkup = `
            <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0 bg-slate-700 flex items-center justify-center font-bold text-xs ring-1 ring-white/20 text-slate-200">
              ${{c.mugshot_front ? `<img src="${{c.mugshot_front}}" alt="" class="w-full h-full object-cover">` : initials}}
            </div>
          `;
        }}

        // Render suspect names
        let suspectsDisplay = '';
        if (isMulti) {{
          suspectsDisplay = `
            <div class="text-[10.5px] font-bold ${{isSelected ? 'text-white' : 'text-slate-100'}} truncate">
              👥 ${{c.suspects.map((s, idx) => `<span class="${{idx === 0 ? 'underline decoration-indigo-300' : ''}}">${{s.name}}</span>`).join(' & ')}}
            </div>
            <div class="text-[9.5px] ${{isSelected ? 'text-indigo-100' : 'text-slate-400'}} truncate flex items-center space-x-1 mt-0.5">
              <span class="px-1.5 py-0.2 rounded bg-indigo-500/30 text-indigo-200 font-mono-code font-bold">${{c.suspects.length}} Suspects</span>
              <span>•</span>
              <span>📍 ${{c.region || 'Greater Accra'}}</span>
            </div>
          `;
        }} else {{
          suspectsDisplay = `
            <div class="text-[10px] font-bold ${{isSelected ? 'text-white' : 'text-slate-200'}} truncate">
              👤 ${{c.suspect_name}}
            </div>
            <div class="text-[9.5px] ${{isSelected ? 'text-indigo-100' : 'text-slate-400'}} truncate mt-0.5">
              📍 ${{c.region || 'Greater Accra'}}
            </div>
          `;
        }}

        return `
          <div onclick="selectInvoiceById(${{c.id}}, true)" 
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
      }}).join('');'''

assert old_render_list in code, "Could not find old_render_list"
code = code.replace(old_render_list, new_render_list, 1)

# 3. Update renderTacticalCasesStream
old_tac_render = '''        return `
          <div onclick="selectInvoiceById(${{c.id}}, true); selectRegionByName('${{c.region}}', true);" 
               class="p-2.5 rounded-xl border cursor-pointer transition-all flex items-center justify-between text-xs font-mono-code ${{cardStyle}}">
            <div class="flex items-center space-x-2 truncate">
              <div class="truncate">
                <div class="flex items-center space-x-1.5">
                  <span class="font-bold text-white">${{c.case_number}}</span>
                  ${{isSelected ? '<span class="text-[9px] px-1 bg-red-600 text-white rounded font-black">TARGET</span>' : ''}}
                </div>
                <span class="text-[10.5px] text-red-300 font-bold block truncate mt-0.5">👤 ${{c.suspect_name}}</span>
                <span class="text-[9.5px] text-slate-400 block truncate">📍 ${{c.region}} (${{c.exhibits_count || 1}} Exh)</span>
              </div>
            </div>
            <span class="text-[10px] px-2 py-0.5 rounded ${{badgeStyle}}">${{c.status_badge || 'Active'}}</span>
          </div>
        `;'''

new_tac_render = '''        const isMulti = c.is_multiple && c.suspects && c.suspects.length > 1;
        const suspectsLine = isMulti
          ? `👥 ${{c.suspects.length}} Targets: ` + c.suspects.map(s => s.name).join(' & ')
          : `👤 ${{c.suspect_name}}`;

        return `
          <div onclick="selectInvoiceById(${{c.id}}, true); selectRegionByName('${{c.region}}', true);" 
               class="p-2.5 rounded-xl border cursor-pointer transition-all flex items-center justify-between text-xs font-mono-code ${{cardStyle}}">
            <div class="flex items-center space-x-2 truncate">
              <div class="truncate">
                <div class="flex items-center space-x-1.5">
                  <span class="font-bold text-white">${{c.case_number}}</span>
                  ${{isSelected ? '<span class="text-[9px] px-1 bg-red-600 text-white rounded font-black">TARGET</span>' : ''}}
                  ${{isMulti ? '<span class="text-[9px] px-1 bg-indigo-600 text-white rounded font-black">MULTI-TARGET</span>' : ''}}
                </div>
                <span class="text-[10.5px] text-red-300 font-bold block truncate mt-0.5">${{suspectsLine}}</span>
                <span class="text-[9.5px] text-slate-400 block truncate">📍 ${{c.region}} (${{c.exhibits_count || 1}} Exh)</span>
              </div>
            </div>
            <span class="text-[10px] px-2 py-0.5 rounded ${{badgeStyle}}">${{c.status_badge || 'Active'}}</span>
          </div>
        `;'''

assert old_tac_render in code, "Could not find old_tac_render"
code = code.replace(old_tac_render, new_tac_render, 1)

# 4. Update renderDossierCard with multi-suspect profile and strict exhibit ownership
old_dossier_card = '''    // ──────────────────────────────────────────────────────────
    // 4. RENDER FORENSIC DOSSIER DETAILS CARD (Right Panel)
    // ──────────────────────────────────────────────────────────
    function renderDossierCard(c) {{
      if (!c) return;

      const elInv = document.getElementById('cardInvoiceNumber');
      if (elInv) elInv.textContent = c.case_number || '# NCC-2026-2572';

      const elBadge = document.getElementById('cardInvoiceStatusBadge');
      if (elBadge) elBadge.textContent = c.status_badge || 'Under Investigation';

      const elComp = document.getElementById('cardCompanyName');
      if (elComp) elComp.textContent = c.unit_name || '{unit_name}';

      const elCustName = document.getElementById('cardCustomerName');
      if (elCustName) elCustName.textContent = c.suspect_name || 'Kofi Mensah';

      const elCustRole = document.getElementById('cardCustomerRole');
      if (elCustRole) {{
        const parts = [c.nationality || 'Ghanaian', c.age, c.suspect_role].filter(Boolean);
        elCustRole.textContent = parts.join(' • ') || 'Primary Target';
      }}

      const elAvatar = document.getElementById('cardCustomerAvatar');
      if (elAvatar) {{
        const initials = c.suspect_name ? c.suspect_name.split(' ').map(n => n[0]).join('').substring(0, 2) : 'KM';
        elAvatar.innerHTML = c.mugshot_front 
          ? `<img src="${{c.mugshot_front}}" class="w-full h-full object-cover" alt="">`
          : initials;
      }}

      const elOfficer = document.getElementById('cardOfficerName');
      if (elOfficer) elOfficer.textContent = c.submitting_officer || 'Insp. K. Mensah';

      const elLoc = document.getElementById('cardLocationName');
      if (elLoc) elLoc.textContent = (c.raid_location ? c.raid_location.substring(0, 26) : 'Kotoka Airport');

      const exhCount = c.exhibits_count || (c.exhibits ? c.exhibits.length : 3);
      const elSub = document.getElementById('cardSubTotal');
      if (elSub) elSub.textContent = `${{exhCount}} Items Logged`;

      const elTot = document.getElementById('cardTotal');
      if (elTot) elTot.textContent = `${{exhCount}} Items Logged`;

      const elBal = document.getElementById('cardBalanceDue');
      if (elBal) elBal.textContent = (c.status_badge === 'Approved' ? 'Court Order Signed' : 'Judicial Review Active');

      const elPrimary = document.getElementById('cardActionPrimary');
      if (elPrimary && c.id) elPrimary.href = `/cases/${{c.id}}/review`;

      const elLink = document.getElementById('cardActionLink');
      if (elLink && c.id) elLink.href = `/cases/${{c.id}}/pdf`;

      const grid = document.getElementById('exhibitCardsGrid');
      if (grid) {{
        const exhibits = (c.exhibits && c.exhibits.length > 0) ? c.exhibits.slice(0, 3) : [
          {{ title: 'Heroin (Brown Powder)', quantity: '2.5 kg', category: 'Narcotics' }},
          {{ title: 'Apple iPhone 15 Pro', quantity: '3 Units', category: 'Electronic Devices' }},
          {{ title: 'Concealment Cargo', quantity: '1 Parcel', category: 'Physical Evidence' }}
        ];

        let cardsHtml = exhibits.map(ex => `
          <div class="bg-white/10 hover:bg-white/15 rounded-xl p-3 border border-white/10 transition-colors flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-300">
              <span class="text-xs font-mono-code font-black text-indigo-200 truncate">${{ex.quantity || '1 Unit'}}</span>
              <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-indigo-300"></i>
            </div>
            <div class="text-[10px] text-white font-bold truncate mt-2">${{ex.title}}</div>
            <div class="text-[9px] text-slate-400 font-mono-code truncate mt-0.5">${{ex.category || 'Evidence Item'}}</div>
          </div>
        `).join('');

        cardsHtml += `
          <a href="/cases/${{c.id || ''}}/edit" class="rounded-xl p-3 border border-dashed border-white/20 hover:border-indigo-400 bg-white/5 hover:bg-white/10 transition-all flex flex-col items-center justify-center text-center group">
            <div class="w-5 h-5 rounded-full bg-white/10 group-hover:bg-[#5c56df] flex items-center justify-center mb-1 text-white transition-colors">
              <i data-lucide="plus" class="w-3 h-3"></i>
            </div>
            <span class="text-[10px] font-bold text-slate-300 group-hover:text-white">Log Exhibit</span>
          </a>
        `;

        grid.innerHTML = cardsHtml;
        lucide.createIcons();
      }}
    }}'''

new_dossier_card = '''    // ──────────────────────────────────────────────────────────
    // 4. RENDER FORENSIC DOSSIER DETAILS CARD (Right Panel)
    // ──────────────────────────────────────────────────────────
    let dossierSelectedSuspectId = 'all';

    function setDossierSuspect(suspectId) {{
      dossierSelectedSuspectId = suspectId;
      const c = liveCases.find(item => item.id == selectedCaseId);
      if (c) {{
        renderSuspectsProfile(c);
        renderDossierExhibits(c);
      }}
    }}

    function renderSuspectsProfile(c) {{
      const container = document.getElementById('cardSuspectsProfileContainer');
      if (!container) return;

      const isMulti = c.is_multiple && c.suspects && c.suspects.length > 1;

      if (!isMulti) {{
        const s = (c.suspects && c.suspects.length > 0) ? c.suspects[0] : {{
          name: c.suspect_name || 'Kofi Mensah',
          role: c.suspect_role || 'Primary Target',
          ghana_card: c.ghana_card || 'GHA-XXXXXXXXX-X',
          nationality: c.nationality || 'Ghanaian',
          age: c.age || '34 yrs',
          gender: c.gender || 'Male',
          mugshot_front: c.mugshot_front
        }};
        const initials = s.name ? s.name.split(' ').map(n => n[0]).join('').substring(0, 2) : 'KM';

        container.innerHTML = `
          <div class="flex items-center space-x-3 bg-white/5 p-3 rounded-2xl border border-white/5">
            <div class="w-12 h-12 rounded-xl bg-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center text-white font-bold text-sm ring-1 ring-white/20">
              ${{s.mugshot_front ? `<img src="${{s.mugshot_front}}" class="w-full h-full object-cover" alt="">` : initials}}
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between">
                <span class="text-[10px] font-mono-code text-indigo-300 uppercase font-bold tracking-wider">${{s.role}}</span>
                <span class="text-[10px] font-mono-code text-slate-400">${{s.ghana_card || ''}}</span>
              </div>
              <div class="text-sm font-extrabold text-white truncate mt-0.5">${{s.name}}</div>
              <div class="text-[10px] text-slate-300 truncate">${{[s.gender, s.age, s.nationality].filter(Boolean).join(' • ')}}</div>
            </div>
          </div>
        `;
        return;
      }}

      // MULTIPLE SUSPECTS CASE: Show suspect selection bar + cards
      let selectorHtml = `
        <div class="flex items-center space-x-1.5 mb-2.5 overflow-x-auto pb-1">
          <button type="button" onclick="setDossierSuspect('all')" 
                  class="px-2.5 py-1 rounded-lg text-[10px] font-mono-code font-bold transition-all ${{dossierSelectedSuspectId === 'all' ? 'bg-[#5c56df] text-white shadow ring-1 ring-white/30' : 'bg-white/10 text-slate-300 hover:bg-white/20'}}">
            ALL TARGETS (${{c.suspects.length}})
          </button>
          ${{c.suspects.map(s => `
            <button type="button" onclick="setDossierSuspect('${{s.id}}')" 
                    class="px-2.5 py-1 rounded-lg text-[10px] font-mono-code font-bold transition-all ${{String(dossierSelectedSuspectId) === String(s.id) ? 'bg-[#5c56df] text-white shadow ring-1 ring-white/30' : 'bg-white/10 text-slate-300 hover:bg-white/20'}}">
              ${{s.name.split(' ')[0]}} <span class="text-[9px] opacity-75 font-normal">(${{s.role.replace(' Suspect', '')}})</span>
            </button>
          `).join('')}}
        </div>
      `;

      let cardsHtml = '';
      if (dossierSelectedSuspectId === 'all') {{
        cardsHtml = `
          <div class="space-y-2">
            ${{c.suspects.map(s => {
              const initials = s.name ? s.name.split(' ').map(n => n[0]).join('').substring(0, 2) : 'S';
              return `
                <div class="flex items-center space-x-3 bg-white/5 p-2.5 rounded-2xl border border-white/5 hover:border-white/15 transition-all">
                  <div class="w-10 h-10 rounded-xl bg-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center text-white font-bold text-xs ring-1 ring-white/20">
                    ${{s.mugshot_front ? `<img src="${{s.mugshot_front}}" class="w-full h-full object-cover" alt="">` : initials}}
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                      <span class="text-[9px] font-mono-code ${{s.role.includes('Primary') ? 'text-indigo-300 font-bold' : 'text-slate-400'}} uppercase">${{s.role}}</span>
                      <span class="text-[9px] font-mono-code text-slate-400">${{s.ghana_card || ''}}</span>
                    </div>
                    <div class="text-xs font-extrabold text-white truncate mt-0.5">${{s.name}}</div>
                    <div class="text-[10px] text-slate-300 truncate">${{[s.gender, s.age, s.nationality].filter(Boolean).join(' • ')}}</div>
                  </div>
                </div>
              `;
            }).join('')}}
          </div>
        `;
      }} else {{
        const s = c.suspects.find(item => String(item.id) === String(dossierSelectedSuspectId)) || c.suspects[0];
        const initials = s.name ? s.name.split(' ').map(n => n[0]).join('').substring(0, 2) : 'S';
        cardsHtml = `
          <div class="flex items-center space-x-3 bg-white/5 p-3 rounded-2xl border border-white/5">
            <div class="w-12 h-12 rounded-xl bg-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center text-white font-bold text-sm ring-1 ring-white/20">
              ${{s.mugshot_front ? `<img src="${{s.mugshot_front}}" class="w-full h-full object-cover" alt="">` : initials}}
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between">
                <span class="text-[10px] font-mono-code text-indigo-300 uppercase font-bold tracking-wider">${{s.role}}</span>
                <span class="text-[10px] font-mono-code text-slate-400">${{s.ghana_card || ''}}</span>
              </div>
              <div class="text-sm font-extrabold text-white truncate mt-0.5">${{s.name}}</div>
              <div class="text-[10px] text-slate-300 truncate">${{[s.gender, s.age, s.nationality].filter(Boolean).join(' • ')}}</div>
            </div>
          </div>
        `;
      }}

      container.innerHTML = selectorHtml + cardsHtml;
    }}

    function renderDossierExhibits(c) {{
      const grid = document.getElementById('exhibitCardsGrid');
      if (!grid) return;

      let allExhibits = (c.exhibits && c.exhibits.length > 0) ? c.exhibits : [];
      let filteredExhibits = allExhibits;
      let filterLabel = '';

      if (c.is_multiple && c.suspects && dossierSelectedSuspectId !== 'all') {{
        const targetSuspect = c.suspects.find(s => String(s.id) === String(dossierSelectedSuspectId));
        if (targetSuspect) {{
          filterLabel = `(${targetSuspect.name.split(' ')[0]})`;
          filteredExhibits = allExhibits.filter(ex => {{
            // Strictly check ownership - do NOT associate exhibits that belong to someone else
            if (ex.suspect_id && String(ex.suspect_id) === String(targetSuspect.id)) return true;
            if (ex.associated_person) {{
              const ap = ex.associated_person.toLowerCase();
              if (ap.includes(targetSuspect.name.toLowerCase()) || ap === 'all suspects') {{
                return true;
              }}
            }}
            return false;
          }});
        }}
      }}

      const elSub = document.getElementById('cardSubTotal');
      if (elSub) elSub.textContent = `${{filteredExhibits.length}} Exhibits ${{filterLabel}}`;

      if (filteredExhibits.length === 0) {{
        grid.innerHTML = `
          <div class="col-span-2 py-5 px-3 text-center rounded-xl bg-white/5 border border-dashed border-white/10 text-slate-400 text-xs font-mono-code">
            <i data-lucide="shield-alert" class="w-5 h-5 mx-auto mb-1 text-slate-500"></i>
            <span>No exhibits associated with this target</span>
          </div>
        `;
        lucide.createIcons();
        return;
      }}

      let cardsHtml = filteredExhibits.map(ex => {{
        const ownerName = ex.associated_person || (c.is_multiple ? 'All Suspects' : c.suspect_name);
        return `
          <div class="bg-white/10 hover:bg-white/15 rounded-xl p-3 border border-white/10 transition-colors flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-300">
              <span class="text-xs font-mono-code font-black text-indigo-200 truncate">${{ex.quantity || '1 Unit'}}</span>
              <span class="text-[9px] px-1.5 py-0.5 rounded bg-white/10 text-emerald-300 font-mono-code font-bold truncate max-w-[85px]">${{ex.category || 'Evidence'}}</span>
            </div>
            <div class="text-[11px] text-white font-bold truncate mt-2" title="${{ex.title}}">${{ex.title}}</div>
            <div class="mt-2 pt-1.5 border-t border-white/10 flex items-center justify-between text-[9px] font-mono-code">
              <span class="text-indigo-200 truncate flex items-center space-x-1" title="Belongs to: ${{ownerName}}">
                <i data-lucide="user-check" class="w-3 h-3 text-indigo-400 shrink-0"></i>
                <span class="truncate">${{ownerName}}</span>
              </span>
              <span class="text-emerald-400 font-bold ml-1">${{ex.value || ''}}</span>
            </div>
          </div>
        `;
      }}).join('');

      cardsHtml += `
        <a href="/cases/${{c.id || ''}}/edit" class="rounded-xl p-3 border border-dashed border-white/20 hover:border-indigo-400 bg-white/5 hover:bg-white/10 transition-all flex flex-col items-center justify-center text-center group">
          <div class="w-5 h-5 rounded-full bg-white/10 group-hover:bg-[#5c56df] flex items-center justify-center mb-1 text-white transition-colors">
            <i data-lucide="plus" class="w-3 h-3"></i>
          </div>
          <span class="text-[10px] font-bold text-slate-300 group-hover:text-white">Log Exhibit</span>
        </a>
      `;

      grid.innerHTML = cardsHtml;
      lucide.createIcons();
    }}

    function renderDossierCard(c) {{
      if (!c) return;

      const elInv = document.getElementById('cardInvoiceNumber');
      if (elInv) elInv.textContent = c.case_number || '# NCC-2026-2572';

      const elBadge = document.getElementById('cardInvoiceStatusBadge');
      if (elBadge) elBadge.textContent = c.status_badge || 'Under Investigation';

      const elComp = document.getElementById('cardCompanyName');
      if (elComp) elComp.textContent = c.unit_name || '{unit_name}';

      const elOfficer = document.getElementById('cardOfficerName');
      if (elOfficer) elOfficer.textContent = c.submitting_officer || 'Insp. K. Mensah';

      const elLoc = document.getElementById('cardLocationName');
      if (elLoc) elLoc.textContent = (c.raid_location ? c.raid_location.substring(0, 26) : 'Kotoka Airport');

      const exhCount = c.exhibits_count || (c.exhibits ? c.exhibits.length : 3);

      const elTot = document.getElementById('cardTotal');
      if (elTot) elTot.textContent = `${{exhCount}} Items Logged`;

      const elBal = document.getElementById('cardBalanceDue');
      if (elBal) elBal.textContent = (c.status_badge === 'Approved' ? 'Court Order Signed' : 'Judicial Review Active');

      const elPrimary = document.getElementById('cardActionPrimary');
      if (elPrimary && c.id) elPrimary.href = `/cases/${{c.id}}/review`;

      const elLink = document.getElementById('cardActionLink');
      if (elLink && c.id) elLink.href = `/cases/${{c.id}}/pdf`;

      // Reset suspect filter to 'all' on case change and render
      dossierSelectedSuspectId = 'all';
      renderSuspectsProfile(c);
      renderDossierExhibits(c);
    }}'''

assert old_dossier_card in code, "Could not find old_dossier_card"
code = code.replace(old_dossier_card, new_dossier_card, 1)

with open('scratch/build_clean_dashboards.py', 'w', encoding='utf-8') as f:
    f.write(code)

print("Updated build_clean_dashboards.py successfully with multi-suspect support and strict exhibit ownership!")
