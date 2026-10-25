const fs = require('fs');

const content = fs.readFileSync('resources/views/Evidence_Management/evidence_management.blade.php', 'utf8');

// Find the script tag
const scriptMatches = [...content.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/gi)];
const lastScript = scriptMatches[scriptMatches.length - 1];
const jsCode = lastScript[1];
console.log("Found JS code of length:", jsCode.length);

// Let's create a minimal DOM environment
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
      textContent: ''
    };
  }
  return domElements[id];
}

const mockDoc = {
  getElementById: (id) => getEl(id),
  querySelectorAll: (selector) => [],
  querySelector: (selector) => getEl(selector),
  documentElement: {
    requestFullscreen: async () => {},
  },
  addEventListener: () => {}
};

const mockWindow = {
  addEventListener: () => {},
  document: mockDoc
};

const vm = require('vm');
const context = vm.createContext({
  window: mockWindow,
  document: mockDoc,
  lucide: { createIcons: () => {} },
  Chart: function() { return { data: { datasets: [{ data: [] }] }, update: () => {} }; },
  console: console,
  setTimeout: setTimeout,
  setInterval: setInterval
});

try {
  vm.runInContext(jsCode, context);
  console.log("Successfully evaluated JS code!");

  // Now simulate data received from API
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
  console.log("Initial state after applyRealtimeData:");
  console.log("selectedCaseId:", context.selectedCaseId);
  console.log("selectedRegionName:", context.selectedRegionName);
  console.log("ghanaMapSvg viewBox:", getEl('ghanaMapSvg').viewBox);

  console.log("\nSimulating user clicking on Ashanti case (NCC-2026-8687 Linda Asare):");
  context.selectInvoiceById("NCC-2026-8687", true);
  console.log("After clicking NCC-2026-8687:");
  console.log("selectedCaseId:", context.selectedCaseId);
  console.log("selectedRegionName:", context.selectedRegionName);
  console.log("ghanaMapSvg viewBox:", getEl('ghanaMapSvg').viewBox);
  console.log("districtLayersGroup innerHTML length:", getEl('districtLayersGroup').innerHTML.length);
  console.log("districtDrillTitle textContent:", getEl('districtDrillTitle').textContent);

  console.log("\nSimulating user clicking on Accra case (NCC-2026-3423 Curry Seida):");
  context.selectInvoiceById("NCC-2026-3423", true);
  console.log("After clicking NCC-2026-3423:");
  console.log("selectedCaseId:", context.selectedCaseId);
  console.log("selectedRegionName:", context.selectedRegionName);
  console.log("districtDrillTitle textContent:", getEl('districtDrillTitle').textContent);

} catch (e) {
  console.error("Error running script:", e);
}
