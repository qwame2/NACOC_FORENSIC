import os
import re

with open('scratch/build_clean_dashboards.py', 'r', encoding='utf-8') as f:
    code = f.read()

# 1. Update generate_blade signature
old_sig = "def generate_blade(unit_key, unit_name, unit_subtitle, default_head):"
new_sig = '''def generate_blade(unit_key, unit_name, unit_subtitle, default_head, nav_key, kpi4_title, kpi4_subtitle, kpi4_metric, kpi4_bays, kpi4_url, kpi4_btn):
    bay_blocks = []
    for i, (badge, title) in enumerate(kpi4_bays):
        if i == 1:
            bay_blocks.append(f\'\'\'
          <div class="flex-1 py-1 px-2 rounded-xl bg-[#5c56df] text-white shadow-sm flex flex-col items-center" title="{title}">
            <span class="font-mono-code text-[9px] text-indigo-200">{badge}</span>
            <span class="font-bold">{title}</span>
          </div>\'\'\')
        else:
            bay_blocks.append(f\'\'\'
          <div class="flex-1 py-1 px-2 rounded-xl border border-slate-200/80 bg-slate-50/50 text-slate-600 flex flex-col items-center" title="{title}">
            <span class="font-mono-code text-[9px] text-slate-400">{badge}</span>
            <span class="font-bold text-slate-700">{title}</span>
          </div>\'\'\')
    kpi4_bays_html = \'\'.join(bay_blocks)

    nav_head_class = 'bg-slate-900 text-white font-bold shadow-sm' if nav_key == 'head' else 'hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors'
    nav_emu_class = 'bg-slate-900 text-white font-bold shadow-sm' if nav_key == 'emu' else 'hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors'
    nav_lau_class = 'bg-slate-900 text-white font-bold shadow-sm' if nav_key == 'lau' else 'hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors'
    nav_cssu_class = 'bg-slate-900 text-white font-bold shadow-sm' if nav_key == 'cssu' else 'hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors'
    nav_dpfu_class = 'bg-slate-900 text-white font-bold shadow-sm' if nav_key == 'dpfu' else 'hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors'
'''

assert old_sig in code, "Could not find old generate_blade signature"
code = code.replace(old_sig, new_sig, 1)

# 2. Update Navigation bar
old_nav = '''    <nav class="bg-white/80 backdrop-blur-md rounded-2xl px-4 py-2.5 border border-slate-200/70 shadow-xs flex items-center justify-between overflow-x-auto text-xs font-semibold">
      <div class="flex items-center space-x-1.5 sm:space-x-3 text-slate-600">
        <a href="{{{{ url('/dashboard') }}}}" class="px-3 py-1.5 rounded-xl bg-slate-900 text-white font-bold flex items-center space-x-1.5">
          <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i>
          <span>Command Center</span>
        </a>
        <a href="{{{{ url('/Evidence_Management') }}}}" class="px-3 py-1.5 rounded-xl hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors flex items-center space-x-1.5">
          <i data-lucide="package-search" class="w-3.5 h-3.5 text-indigo-600"></i>
          <span>Evidence Vault (EMU)</span>
        </a>
        <a href="{{{{ url('/Lab_Analysis') }}}}" class="px-3 py-1.5 rounded-xl hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors flex items-center space-x-1.5">
          <i data-lucide="flask-conical" class="w-3.5 h-3.5 text-emerald-600"></i>
          <span>Lab Analysis (LAU)</span>
        </a>
        <a href="{{{{ url('/Crime_Scene_Support/head') }}}}" class="px-3 py-1.5 rounded-xl hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors flex items-center space-x-1.5">
          <i data-lucide="map-pin" class="w-3.5 h-3.5 text-amber-600"></i>
          <span>Crime Scene (CSSU)</span>
        </a>
        <a href="{{{{ url('/Digital_Forensics') }}}}" class="px-3 py-1.5 rounded-xl hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors flex items-center space-x-1.5">
          <i data-lucide="hard-drive" class="w-3.5 h-3.5 text-cyan-600"></i>
          <span>Digital Forensics (DPFU)</span>
        </a>
      </div>'''

new_nav = '''    <nav class="bg-white/80 backdrop-blur-md rounded-2xl px-4 py-2.5 border border-slate-200/70 shadow-xs flex items-center justify-between overflow-x-auto text-xs font-semibold">
      <div class="flex items-center space-x-1.5 sm:space-x-3 text-slate-600">
        <a href="{{{{ url('/Forensic_Head') }}}}" class="px-3 py-1.5 rounded-xl {nav_head_class} flex items-center space-x-1.5">
          <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i>
          <span>Command Center</span>
        </a>
        <a href="{{{{ url('/Evidence_Management') }}}}" class="px-3 py-1.5 rounded-xl {nav_emu_class} flex items-center space-x-1.5">
          <i data-lucide="package-search" class="w-3.5 h-3.5 {'text-white' if nav_key == 'emu' else 'text-indigo-600'}"></i>
          <span>Evidence Vault (EMU)</span>
        </a>
        <a href="{{{{ url('/Lab_Analysis') }}}}" class="px-3 py-1.5 rounded-xl {nav_lau_class} flex items-center space-x-1.5">
          <i data-lucide="flask-conical" class="w-3.5 h-3.5 {'text-white' if nav_key == 'lau' else 'text-emerald-600'}"></i>
          <span>Lab Analysis (LAU)</span>
        </a>
        <a href="{{{{ url('/Crime_Scene_Support/head') }}}}" class="px-3 py-1.5 rounded-xl {nav_cssu_class} flex items-center space-x-1.5">
          <i data-lucide="map-pin" class="w-3.5 h-3.5 {'text-white' if nav_key == 'cssu' else 'text-amber-600'}"></i>
          <span>Crime Scene (CSSU)</span>
        </a>
        <a href="{{{{ url('/Digital_Forensics') }}}}" class="px-3 py-1.5 rounded-xl {nav_dpfu_class} flex items-center space-x-1.5">
          <i data-lucide="hard-drive" class="w-3.5 h-3.5 {'text-white' if nav_key == 'dpfu' else 'text-cyan-600'}"></i>
          <span>Digital Forensics (DPFU)</span>
        </a>
      </div>'''

assert old_nav in code, "Could not find old nav markup"
code = code.replace(old_nav, new_nav, 1)

# 3. Update CARD 4
old_card4 = '''      <!-- CARD 4: Evidence Vault Inventory & Categories -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow">
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">Evidence Vault Inventory</span>
            <div class="flex items-center space-x-1">
              <span class="w-5 h-5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center text-[10px]">
                <i data-lucide="shield-check" class="w-3 h-3"></i>
              </span>
              <a href="{{{{ url('/Evidence_Management') }}}}" class="w-5 h-5 rounded-full hover:bg-slate-100 text-slate-400 flex items-center justify-center text-[10px]" title="Inspect Vault">
                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
              </a>
            </div>
          </div>
          <div class="flex items-baseline space-x-2">
            <span id="metricVaultAmount" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
              1,865 Items
            </span>
            <span class="text-[11px] font-medium text-emerald-600 font-bold">Vault Secured</span>
          </div>
        </div>
        <div class="mt-4 pt-2 flex items-center justify-between gap-1.5 text-[10px]">
          <div class="flex-1 py-1 px-2 rounded-xl border border-slate-200/80 bg-slate-50/50 text-slate-600 flex flex-col items-center" title="Narcotic Exhibits">
            <span class="font-mono-code text-[9px] text-slate-400">BAY A</span>
            <span class="font-bold text-slate-700">Narcotics</span>
          </div>
          <div class="flex-1 py-1 px-2 rounded-xl bg-[#5c56df] text-white shadow-sm flex flex-col items-center" title="Digital & Mobile Devices">
            <span class="font-mono-code text-[9px] text-indigo-200">BAY B</span>
            <span class="font-bold">Devices</span>
          </div>
          <div class="flex-1 py-1 px-2 rounded-xl border border-slate-200/80 bg-slate-50/50 text-slate-600 flex flex-col items-center" title="Weapons & Ballistics">
            <span class="font-mono-code text-[9px] text-slate-400">BAY C</span>
            <span class="font-bold text-slate-700">Weapons</span>
          </div>
          <a href="{{{{ url('/Evidence_Management') }}}}" class="px-2.5 py-1.5 rounded-xl bg-slate-900 hover:bg-black text-white text-[10px] font-bold shadow-xs transition-colors flex items-center">
            Inspect
          </a>
        </div>
      </div>'''

new_card4 = '''      <!-- CARD 4: {kpi4_title} -->
      <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-[0_10px_30px_rgba(30,41,59,0.03)] flex flex-col justify-between hover:shadow-md transition-shadow">
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500">{kpi4_title}</span>
            <div class="flex items-center space-x-1">
              <span class="w-5 h-5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center text-[10px]">
                <i data-lucide="shield-check" class="w-3 h-3"></i>
              </span>
              <a href="{kpi4_url}" class="w-5 h-5 rounded-full hover:bg-slate-100 text-slate-400 flex items-center justify-center text-[10px]" title="Inspect">
                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
              </a>
            </div>
          </div>
          <div class="flex items-baseline space-x-2">
            <span id="metricVaultAmount" class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight font-mono-code">
              {kpi4_metric}
            </span>
            <span class="text-[11px] font-medium text-emerald-600 font-bold">{kpi4_subtitle}</span>
          </div>
        </div>
        <div class="mt-4 pt-2 flex items-center justify-between gap-1.5 text-[10px]">
          {kpi4_bays_html}
          <a href="{kpi4_url}" class="px-2.5 py-1.5 rounded-xl bg-slate-900 hover:bg-black text-white text-[10px] font-bold shadow-xs transition-colors flex items-center">
            {kpi4_btn}
          </a>
        </div>
      </div>'''

assert old_card4 in code, "Could not find old card4 markup"
code = code.replace(old_card4, new_card4, 1)

# 4. Update Dossier default company name HTML
old_comp = '<span id="cardCompanyName" class="text-xs font-bold text-white">Crime Scene Support</span>'
new_comp = '<span id="cardCompanyName" class="text-xs font-bold text-white">{unit_name}</span>'
assert old_comp in code, "Could not find old cardCompanyName markup"
code = code.replace(old_comp, new_comp, 1)

# 5. Update Dossier action buttons
old_actions = '''              <!-- Actions Buttons -->
              <div class="flex items-center space-x-2">
                <a id="cardActionLink" href="{{{{ url('/Evidence_Management') }}}}" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors" title="Export Docket PDF">
                  <i data-lucide="file-down" class="w-4 h-4"></i>
                </a>
                <a id="cardActionVault" href="{{{{ url('/Evidence_Management') }}}}" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors" title="Evidence Vault Logs">
                  <i data-lucide="history" class="w-4 h-4"></i>
                </a>
                <a id="cardActionPrimary" href="{{{{ url('/Evidence_Management') }}}}" class="flex-1 py-2.5 rounded-xl bg-white text-slate-900 hover:bg-slate-100 font-extrabold text-xs text-center shadow-lg transition-all flex items-center justify-center space-x-1.5">
                  <span>View Full Docket</span>
                  <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
              </div>'''

new_actions = '''              <!-- Actions Buttons -->
              <div class="flex items-center space-x-2">
                <a id="cardActionLink" href="{{{{ url('/cases') }}}}" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors" title="Export Docket PDF">
                  <i data-lucide="file-down" class="w-4 h-4"></i>
                </a>
                <a id="cardActionVault" href="{{{{ url('/history') }}}}?unit={unit_key.replace(' ', '+')}" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors" title="Case Audit & History">
                  <i data-lucide="history" class="w-4 h-4"></i>
                </a>
                <a id="cardActionPrimary" href="{{{{ url('/cases') }}}}" class="flex-1 py-2.5 rounded-xl bg-white text-slate-900 hover:bg-slate-100 font-extrabold text-xs text-center shadow-lg transition-all flex items-center justify-center space-x-1.5">
                  <span>View Full Docket</span>
                  <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
              </div>'''

assert old_actions in code, "Could not find old actions markup"
code = code.replace(old_actions, new_actions, 1)

# 6. Update Tactical header
old_tac_header = '<span class="font-mono-code font-black text-sm tracking-wider text-white">NACOC TACTICAL OPERATIONS THEATRE</span>'
new_tac_header = '<span class="font-mono-code font-black text-sm tracking-wider text-white">NACOC TACTICAL OPERATIONS THEATRE • {unit_name.upper()}</span>'
assert old_tac_header in code, "Could not find old tac header markup"
code = code.replace(old_tac_header, new_tac_header, 1)

# 7. Update JS renderDossierCard fallback
old_js_comp = "if (elComp) elComp.textContent = c.unit_name || 'Crime Scene Support';"
new_js_comp = "if (elComp) elComp.textContent = c.unit_name || '{unit_name}';"
assert old_js_comp in code, "Could not find old js comp fallback"
code = code.replace(old_js_comp, new_js_comp, 1)

# 8. Update targets list and execution loop
old_targets = '''# List of all 5 dashboard files to update
targets = [
    {
        'file': 'resources/views/Evidence_Management/evidence_management.blade.php',
        'key': 'Evidence Management',
        'name': 'Evidence Management Unit',
        'subtitle': 'National Evidence Vault & Custody Directorate',
        'head': 'Chief Vault Custodian'
    },
    {
        'file': 'resources/views/Forensic_Head/head.blade.php',
        'key': 'Forensic Head',
        'name': 'Forensic Directorate',
        'subtitle': 'Forensic Directorate Supreme Command',
        'head': 'Anita Addo'
    },
    {
        'file': 'resources/views/Lab_Analysis/lab_analysis.blade.php',
        'key': 'Lab Analysis',
        'name': 'Lab Analysis Unit',
        'subtitle': 'Central Forensic Laboratory & Chemical Analysis',
        'head': 'Supt. Eric Mensah'
    },
    {
        'file': 'resources/views/Crime_Scene_Support/head/head.blade.php',
        'key': 'Crime Scene Support',
        'name': 'Crime Scene Support Unit',
        'subtitle': 'Rapid Field Deployment & Incident Command',
        'head': 'Commander Samuel Atta'
    },
    {
        'file': 'resources/views/Digital_Forensics/digital_forensics.blade.php',
        'key': 'Digital Forensics',
        'name': 'Digital & Physical Forensic Unit',
        'subtitle': 'Cyber Intelligence & Hardware Forensics',
        'head': 'Ofori Graham'
    }
]

for t in targets:
    content = generate_blade(t['key'], t['name'], t['subtitle'], t['head'])
    with open(t['file'], 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Successfully generated: {t['file']}")'''

new_targets = '''# List of all 5 dashboard files to update
targets = [
    {
        'file': 'resources/views/Evidence_Management/evidence_management.blade.php',
        'key': 'Evidence Management',
        'name': 'Evidence Management Unit',
        'subtitle': 'National Evidence Vault & Custody Directorate',
        'head': 'Chief Vault Custodian',
        'nav_key': 'emu',
        'kpi4_title': 'Evidence Vault Inventory',
        'kpi4_subtitle': 'Vault Custody Secured',
        'kpi4_metric': '{{ $totalExhibits }} Exhibits Logged',
        'kpi4_bays': [('BAY A', 'Narcotics'), ('BAY B', 'Devices'), ('BAY C', 'Weapons')],
        'kpi4_url': '{{ url("/Evidence_Management") }}',
        'kpi4_btn': 'Inspect'
    },
    {
        'file': 'resources/views/Forensic_Head/head.blade.php',
        'key': 'Forensic Head',
        'name': 'Forensic Directorate',
        'subtitle': 'Forensic Directorate Supreme Command',
        'head': 'Anita Addo',
        'nav_key': 'head',
        'kpi4_title': 'Directorate Case Portfolio',
        'kpi4_subtitle': 'Directorate Oversight',
        'kpi4_metric': '{{ $totalCases }} Dockets Active',
        'kpi4_bays': [('OPS', 'All Units'), ('16 REGIONS', 'Ghana Radar'), ('STATUS', 'Multi-Agency')],
        'kpi4_url': '{{ url("/Forensic_Head/cases") }}',
        'kpi4_btn': 'Dockets'
    },
    {
        'file': 'resources/views/Lab_Analysis/lab_analysis.blade.php',
        'key': 'Lab Analysis',
        'name': 'Lab Analysis Unit',
        'subtitle': 'Central Forensic Laboratory & Chemical Analysis',
        'head': 'Supt. Eric Mensah',
        'nav_key': 'lau',
        'kpi4_title': 'Chemical & Specimen Diagnostics',
        'kpi4_subtitle': 'Confirmatory Testing Active',
        'kpi4_metric': '{{ $totalCases }} Lab Tests',
        'kpi4_bays': [('GSA LAB', 'Drug Testing'), ('TOXICOLOGY', 'Specimens'), ('CHAIN', 'Verified')],
        'kpi4_url': '{{ url("/Lab_Analysis/examination") }}',
        'kpi4_btn': 'Exams'
    },
    {
        'file': 'resources/views/Crime_Scene_Support/head/head.blade.php',
        'key': 'Crime Scene Support',
        'name': 'Crime Scene Support Unit',
        'subtitle': 'Rapid Field Deployment & Incident Command',
        'head': 'Commander Samuel Atta',
        'nav_key': 'cssu',
        'kpi4_title': 'Field Incident Operations',
        'kpi4_subtitle': 'Rapid Deployments',
        'kpi4_metric': '{{ $totalCases }} Field Deployments',
        'kpi4_bays': [('TEAM A', 'Field Raids'), ('TEAM B', 'Interception'), ('CHAIN', 'Evidence')],
        'kpi4_url': '{{ url("/Crime_Scene_Support/head") }}',
        'kpi4_btn': 'Deploy'
    },
    {
        'file': 'resources/views/Digital_Forensics/digital_forensics.blade.php',
        'key': 'Digital Forensics',
        'name': 'Digital & Physical Forensic Unit',
        'subtitle': 'Cyber Intelligence & Hardware Forensics',
        'head': 'Ofori Graham',
        'nav_key': 'dpfu',
        'kpi4_title': 'Digital Artifacts & Devices',
        'kpi4_subtitle': 'Write-Block Acquisition',
        'kpi4_metric': '{{ $totalExhibits }} Devices Logged',
        'kpi4_bays': [('BAY D1', 'Mobile Devices'), ('BAY D2', 'Laptops & Media'), ('HASH', 'Verified')],
        'kpi4_url': '{{ url("/Digital_Forensics/forensic-status") }}',
        'kpi4_btn': 'Cyber Hub'
    }
]

for t in targets:
    content = generate_blade(
        t['key'], t['name'], t['subtitle'], t['head'],
        t['nav_key'], t['kpi4_title'], t['kpi4_subtitle'], t['kpi4_metric'],
        t['kpi4_bays'], t['kpi4_url'], t['kpi4_btn']
    )
    with open(t['file'], 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Successfully generated: {t['file']}")'''

assert old_targets in code, "Could not find old targets markup"
code = code.replace(old_targets, new_targets, 1)

with open('scratch/build_clean_dashboards.py', 'w', encoding='utf-8') as f:
    f.write(code)

print("scratch/build_clean_dashboards.py updated successfully!")
