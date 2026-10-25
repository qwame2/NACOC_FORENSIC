import os
import re

with open('scratch/build_clean_dashboards.py', 'r', encoding='utf-8') as f:
    code = f.read()

# 1. Update generate_blade signature and nav building
old_sig_block = '''def generate_blade(unit_key, unit_name, unit_subtitle, default_head, nav_key, kpi4_title, kpi4_subtitle, kpi4_metric, kpi4_bays, kpi4_url, kpi4_btn):
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

new_sig_block = '''def generate_blade(unit_key, unit_name, unit_subtitle, default_head, unit_buttons, kpi4_title, kpi4_subtitle, kpi4_metric, kpi4_bays, kpi4_url, kpi4_btn):
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

    nav_blocks = []
    for b in unit_buttons:
        if b.get('active'):
            cls = 'px-3 py-1.5 rounded-xl bg-slate-900 text-white font-bold flex items-center space-x-1.5 shadow-sm'
            icon_cls = 'w-3.5 h-3.5 text-white'
        else:
            cls = 'px-3 py-1.5 rounded-xl hover:bg-slate-100 text-slate-700 hover:text-slate-900 transition-colors flex items-center space-x-1.5 font-semibold'
            icon_cls = 'w-3.5 h-3.5 text-indigo-600'
        nav_blocks.append(f\'\'\'
        <a href="{b[\'url\']}" class="{cls}">
          <i data-lucide="{b[\'icon\']}" class="{icon_cls}"></i>
          <span>{b[\'title\']}</span>
        </a>\'\'\')
    nav_buttons_html = \'\'.join(nav_blocks)
'''

assert old_sig_block in code, "Could not find old_sig_block"
code = code.replace(old_sig_block, new_sig_block, 1)

# 2. Update navigation bar HTML
old_nav = '''    <!-- ====================================================== -->
    <!-- 2. QUICK NAVIGATION APP BAR                            -->
    <!-- ====================================================== -->
    <nav class="bg-white/80 backdrop-blur-md rounded-2xl px-4 py-2.5 border border-slate-200/70 shadow-xs flex items-center justify-between overflow-x-auto text-xs font-semibold">
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
      </div>

      <div class="flex items-center space-x-2 pl-4 text-slate-400">
        <span class="text-[11px] font-mono-code font-bold text-slate-500">SYSTEM SECURE</span>
        <i data-lucide="lock" class="w-3.5 h-3.5 text-emerald-500"></i>
      </div>
    </nav>'''

new_nav = '''    <!-- ====================================================== -->
    <!-- 2. QUICK NAVIGATION APP BAR (UNIT-SPECIFIC ACTIONS)     -->
    <!-- ====================================================== -->
    <nav class="bg-white/80 backdrop-blur-md rounded-2xl px-4 py-2.5 border border-slate-200/70 shadow-xs flex items-center justify-between overflow-x-auto text-xs font-semibold">
      <div class="flex items-center space-x-1 sm:space-x-2 text-slate-600">
        {nav_buttons_html}
      </div>

      <div class="flex items-center space-x-2 pl-4 text-slate-400 shrink-0">
        <span class="text-[11px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">{unit_name}</span>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-mono-code font-bold border border-emerald-200/60">
          ONLINE
        </span>
      </div>
    </nav>'''

assert old_nav in code, "Could not find old_nav"
code = code.replace(old_nav, new_nav, 1)

# 3. Update targets list with unit_buttons
old_targets_block = '''# List of all 5 dashboard files to update
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

new_targets_block = '''# List of all 5 dashboard files to update
targets = [
    {
        'file': 'resources/views/Evidence_Management/evidence_management.blade.php',
        'key': 'Evidence Management',
        'name': 'Evidence Management Unit',
        'subtitle': 'National Evidence Vault & Custody Directorate',
        'head': 'Chief Vault Custodian',
        'buttons': [
            {'title': 'Vault Overview', 'url': '{{ url("/Evidence_Management") }}', 'icon': 'package-search', 'active': True},
            {'title': 'Exhibit Lodgement (New)', 'url': '{{ url("/Evidence_Management/create") }}', 'icon': 'package-plus', 'active': False},
            {'title': 'Vault Case Directory', 'url': '{{ url("/cases?from=Evidence+Management") }}', 'icon': 'folder-search', 'active': False},
            {'title': 'Chain of Custody Logs', 'url': '{{ url("/history?unit=Evidence+Management") }}', 'icon': 'history', 'active': False},
            {'title': 'Vault Audit Report', 'url': '{{ url("/Report?unit=Evidence+Management") }}', 'icon': 'file-text', 'active': False},
        ],
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
        'buttons': [
            {'title': 'Executive Command', 'url': '{{ url("/Forensic_Head") }}', 'icon': 'layout-dashboard', 'active': True},
            {'title': 'Case Management', 'url': '{{ url("/Forensic_Head/cases") }}', 'icon': 'folder-kanban', 'active': False},
            {'title': 'Head Approvals', 'url': '{{ url("/Forensic_Head/approvals") }}', 'icon': 'clipboard-check', 'active': False},
            {'title': 'Master Case Directory', 'url': '{{ url("/cases") }}', 'icon': 'folder-search', 'active': False},
            {'title': 'Audit & Compliance', 'url': '{{ url("/Report") }}', 'icon': 'file-text', 'active': False},
        ],
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
        'buttons': [
            {'title': 'Lab Command', 'url': '{{ url("/Lab_Analysis") }}', 'icon': 'flask-conical', 'active': True},
            {'title': 'Chemical Assay Intake', 'url': '{{ url("/Lab_Analysis/create") }}', 'icon': 'plus-circle', 'active': False},
            {'title': 'Analysis Results', 'url': '{{ url("/Lab_Analysis/lab-result") }}', 'icon': 'microscope', 'active': False},
            {'title': 'Lab Examination Directory', 'url': '{{ url("/cases?from=Lab+Analysis") }}', 'icon': 'folder-search', 'active': False},
            {'title': 'GSA & Lab Certificates', 'url': '{{ url("/history?unit=Lab+Analysis") }}', 'icon': 'file-text', 'active': False},
        ],
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
        'buttons': [
            {'title': 'Incident Command', 'url': '{{ url("/Crime_Scene_Support/head") }}', 'icon': 'map-pin', 'active': True},
            {'title': 'Arrest & Seizure (New)', 'url': '{{ url("/Crime_Scene_Support/create") }}', 'icon': 'plus-circle', 'active': False},
            {'title': 'Field Case Directory', 'url': '{{ url("/cases?from=Crime+Scene+Support") }}', 'icon': 'folder-search', 'active': False},
            {'title': 'Field Deployment Logs', 'url': '{{ url("/history?unit=Crime+Scene+Support") }}', 'icon': 'crosshair', 'active': False},
            {'title': 'Incident Reports', 'url': '{{ url("/Report?unit=Crime+Scene+Support") }}', 'icon': 'file-text', 'active': False},
        ],
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
        'buttons': [
            {'title': 'Cyber Command', 'url': '{{ url("/Digital_Forensics") }}', 'icon': 'hard-drive', 'active': True},
            {'title': 'Device Intake (New)', 'url': '{{ url("/Digital_Forensics/create") }}', 'icon': 'laptop', 'active': False},
            {'title': 'Forensic & Investigation Status', 'url': '{{ url("/Digital_Forensics/forensic-status") }}', 'icon': 'cpu', 'active': False},
            {'title': 'Cyber Case Directory', 'url': '{{ url("/cases?from=Digital+Forensics") }}', 'icon': 'folder-search', 'active': False},
            {'title': 'Hardware & Hash Reports', 'url': '{{ url("/Report?unit=Digital+Forensics") }}', 'icon': 'file-text', 'active': False},
        ],
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
        t['buttons'], t['kpi4_title'], t['kpi4_subtitle'], t['kpi4_metric'],
        t['kpi4_bays'], t['kpi4_url'], t['kpi4_btn']
    )
    with open(t['file'], 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Successfully generated: {t['file']}")'''

assert old_targets_block in code, "Could not find old_targets_block"
code = code.replace(old_targets_block, new_targets_block, 1)

with open('scratch/build_clean_dashboards.py', 'w', encoding='utf-8') as f:
    f.write(code)

print("build_clean_dashboards.py updated with unit-specific navigation buttons successfully!")
