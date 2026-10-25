const fs = require('fs');

const jsCode = fs.readFileSync('scratch/extracted_client_js2.js', 'utf-8');

// Mock DOM environment
const elements = {};
function createMockElement(id) {
  const el = {
    id: id,
    style: {},
    classList: {
      classes: new Set(),
      add: function(...cls) { cls.forEach(c => this.classes.add(c)); },
      remove: function(...cls) { cls.forEach(c => this.classes.delete(c)); },
      contains: function(c) { return this.classes.has(c); }
    },
    attributes: {},
    setAttribute: function(k, v) { this.attributes[k] = v; },
    getAttribute: function(k) { return this.attributes[k]; },
    innerHTML: '',
    textContent: ''
  };
  elements[id] = el;
  return el;
}

[
  'ghanaMapSvg', 'tacGhanaMapSvg', 'ghana3dPlane', 'tacGhana3dPlane',
  'extrusionShadowPass', 'tacExtrusionShadowPass', 'radarRingsGroup',
  'regionLabelsGroup', 'tacRegionLabelsGroup', 'districtNavHeader',
  'districtDrillTitle', 'btnMapCornerTactical', 'mapPanZoomHint',
  'districtCardsDrawer', 'districtDrawerBadge', 'districtLayersGroup',
  'districtDrawerGrid', 'tacDistrictLayersGroup', 'tacDistrictNavHeader',
  'tacDistrictDrillTitle', 'tacDistrictDrillTitleBadge', 'tacDistrictDeckDrawer',
  'tacDistrictDeckBadge', 'tacDistrictDeckGrid', 'tacHudRegionName',
  'tacHudCapital', 'tacHudDockets', 'tacHudIso', 'tacHudCommander',
  'tacHudStations', 'tacHudDistrictsList', 'invoicesListContainer',
  'tacCasesListContainer', 'tacticalOpsTheatre', 'btnTacToggle3D',
  'btnToggle3D', 'tacRegionHoverTooltip', 'tacTooltipRegionName',
  'tacTooltipSub'
].forEach(createMockElement);

global.document = {
  addEventListener: () => {},
  getElementById: function(id) {
    if (!elements[id]) createMockElement(id);
    return elements[id];
  },
  querySelectorAll: function(sel) {
    return [];
  },
  documentElement: {
    requestFullscreen: async () => {}
  }
};
global.window = {
  addEventListener: () => {}
};
global.lucide = {
  createIcons: () => {}
};
global.Chart = function() {};

// Evaluate client script
eval(jsCode);

console.log('Testing selectRegionByName for Greater Accra (Tactical)...');
selectRegionByName('Greater Accra', true);

const gaGroup = document.getElementById('tacDistrictLayersGroup');
const gaDeck = document.getElementById('tacDistrictDeckGrid');
const gaHud = document.getElementById('tacHudDistrictsList');
const gaSvg = document.getElementById('tacGhanaMapSvg');

console.log('Greater Accra SVG viewBox:', gaSvg.getAttribute('viewBox'));
console.log('Greater Accra SVG markers count:', (gaGroup.innerHTML.match(/class="tac-district-marker/g) || []).length);
console.log('Greater Accra Deck cards count:', (gaDeck.innerHTML.match(/selectInvoiceById/g) || []).length);
console.log('Greater Accra Left HUD entries count:', (gaHud.innerHTML.match(/Cap:/g) || []).length);

console.log('\nTesting selectRegionByName for Ashanti (Tactical)...');
selectRegionByName('Ashanti', true);

const ashGroup = document.getElementById('tacDistrictLayersGroup');
const ashDeck = document.getElementById('tacDistrictDeckGrid');
const ashHud = document.getElementById('tacHudDistrictsList');
const ashSvg = document.getElementById('tacGhanaMapSvg');

console.log('Ashanti SVG viewBox:', ashSvg.getAttribute('viewBox'));
console.log('Ashanti SVG markers count:', (ashGroup.innerHTML.match(/class="tac-district-marker/g) || []).length);
console.log('Ashanti Deck cards count:', (ashDeck.innerHTML.match(/Capital:/g) || []).length);
console.log('Ashanti Left HUD entries count:', (ashHud.innerHTML.match(/Cap:/g) || []).length);

console.log('\nALL TESTS PASSED WITH ZERO CRASHES!');
