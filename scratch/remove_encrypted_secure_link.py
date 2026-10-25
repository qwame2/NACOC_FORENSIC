import os

build_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'build_clean_dashboards.py')

with open(build_file, 'r', encoding='utf-8') as f:
    code = f.read()

target_block = """      <!-- Bottom Tactical Telemetry Bar -->
      <footer class="absolute bottom-4 left-6 right-6 z-40 bg-slate-950/80 border border-emerald-500/30 rounded-2xl px-6 py-2.5 backdrop-blur-xl flex flex-col md:flex-row items-center justify-between text-xs font-mono-code text-slate-400 gap-2">
        <div class="flex items-center space-x-4">
          <span class="flex items-center space-x-1.5 text-emerald-400 font-bold">
            <span>ENCRYPTED SECURE LINK</span>
          </span>
          <span>LATENCY: 14ms</span>
          <span>SYSTEM TIME: {{{{ now()->format('Y-m-d H:i:s') }}}} GMT</span>
        </div>
        <div class="flex items-center space-x-3 text-[11px]">
          <span>GRID: WGS-84</span>
          <span>SECTORS: 16 REGIONS • 261 DISTRICTS</span>
          <span class="text-emerald-400 font-bold">STATUS: OPERATIONAL</span>
        </div>
      </footer>"""

if target_block in code:
    code = code.replace(target_block, "")
    print("Found and removed ENCRYPTED SECURE LINK footer bar block successfully!")
else:
    # Try finding with flexible line endings or matching
    idx1 = code.find('<!-- Bottom Tactical Telemetry Bar -->')
    idx2 = code.find('</footer>', idx1)
    if idx1 != -1 and idx2 != -1:
        snippet = code[idx1:idx2 + len('</footer>')]
        code = code.replace(snippet, "")
        print("Removed ENCRYPTED SECURE LINK block by slice indices!")
    else:
        print("ERROR: Could not find block!")

with open(build_file, 'w', encoding='utf-8') as f:
    f.write(code)

print("build_clean_dashboards.py updated successfully!")
