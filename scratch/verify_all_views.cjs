const fs = require('fs');
const vm = require('vm');

const views = [
  'resources/views/Evidence_Management/evidence_management.blade.php',
  'resources/views/Forensic_Head/head.blade.php',
  'resources/views/Lab_Analysis/lab_analysis.blade.php',
  'resources/views/Crime_Scene_Support/head/head.blade.php',
  'resources/views/Digital_Forensics/digital_forensics.blade.php'
];

for (const v of views) {
  const content = fs.readFileSync(v, 'utf8');
  const scriptMatches = [...content.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/gi)];
  const lastScript = scriptMatches[scriptMatches.length - 1];
  const jsCode = lastScript[1];

  const domElements = {};
  function getEl(id) {
    if (!domElements[id]) {
      domElements[id] = {
        id: id,
        classList: {
          add: (...c) => {},
          remove: (...c) => {},
          contains: (c) => false
        },
        style: {},
        setAttribute: (k, v) => { domElements[id][k] = v; },
        getAttribute: (k) => domElements[id][k],
        children: [],
        innerHTML: '',
        textContent: '',
        querySelector: (sel) => null
      };
    }
    return domElements[id];
  }

  const mockDoc = {
    getElementById: (id) => getEl(id),
    querySelectorAll: (selector) => [],
    querySelector: (selector) => getEl(selector),
    documentElement: { requestFullscreen: async () => {} },
    addEventListener: () => {}
  };

  const context = vm.createContext({
    window: { addEventListener: () => {}, document: mockDoc },
    document: mockDoc,
    lucide: { createIcons: () => {} },
    Chart: function() { return { data: { datasets: [{ data: [] }] }, update: () => {} }; },
    console: console,
    setTimeout: setTimeout,
    setInterval: setInterval
  });

  try {
    vm.runInContext(jsCode, context);
    console.log(`[PASS] ${v}: Syntax & VM initialization successful`);

    // Simulate API payload
    const samplePayload = {
      success: true,
      metrics: { total_cases: 4 },
      region_counts: { "Greater Accra": 3, "Ashanti": 1 },
      cases: [
        { id: 24, case_number: "NCC-2026-2572", suspect_name: "BEN ASARE & ESI ADDO", region: "Greater Accra", raid_location: "LEGON" },
        { id: 25, case_number: "NCC-2026-3423", suspect_name: "CURRY SEIDA & COMFORT KINGS", region: "Greater Accra", raid_location: "ACCRA" },
        { id: 26, case_number: "NCC-2026-8687", suspect_name: "LINDA ASARE", region: "Ashanti", raid_location: "KUMASI" },
        { id: 27, case_number: "NCC-2026-1530", suspect_name: "VICTOR OZYO", region: "Greater Accra", raid_location: "Accra" }
      ]
    };

    context.applyRealtimeData(samplePayload);

    // Test clicking Linda Asare (Ashanti)
    context.selectInvoiceById("NCC-2026-8687", true);
    if (!getEl('districtDrillTitle').innerHTML.includes('NCC-2026-8687')) {
      throw new Error("districtDrillTitle did not contain target case NCC-2026-8687!");
    }
    if (!getEl('districtLayersGroup').innerHTML.includes('NCC-2026-8687')) {
      throw new Error("districtLayersGroup did not contain target MMDA for NCC-2026-8687!");
    }
    if (!getEl('ghanaMapSvg').viewBox.includes('150.8')) {
      throw new Error("ghanaMapSvg viewBox was not updated to Ashanti!");
    }

    // Test clicking Curry Seida (Accra)
    context.selectInvoiceById("NCC-2026-3423", true);
    if (!getEl('districtDrillTitle').innerHTML.includes('NCC-2026-3423')) {
      throw new Error("districtDrillTitle did not contain target case NCC-2026-3423!");
    }
    if (!getEl('districtLayersGroup').innerHTML.includes('NCC-2026-3423')) {
      throw new Error("districtLayersGroup did not contain target MMDA for NCC-2026-3423!");
    }

    // Test clicking Ben Asare (Legon)
    context.selectInvoiceById("NCC-2026-2572", true);
    if (!getEl('districtDrillTitle').innerHTML.includes('NCC-2026-2572')) {
      throw new Error("districtDrillTitle did not contain target case NCC-2026-2572!");
    }
    if (!getEl('districtLayersGroup').innerHTML.includes('NCC-2026-2572')) {
      throw new Error("districtLayersGroup did not contain target MMDA for NCC-2026-2572!");
    }

    console.log(`[PASS] ${v}: Active case selection, map indication & target MMDA verified!`);

  } catch (err) {
    console.error(`[FAIL] ${v}:`, err);
    process.exit(1);
  }
}

console.log("\nALL 5 DASHBOARDS PASSED VALIDATION PERFECTLY!");
