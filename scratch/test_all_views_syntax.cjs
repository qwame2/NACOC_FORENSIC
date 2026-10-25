const fs = require('fs');
const vm = require('vm');

const views = [
  'resources/views/Evidence_Management/evidence_management.blade.php',
  'resources/views/Forensic_Head/head.blade.php',
  'resources/views/Lab_Analysis/lab_analysis.blade.php',
  'resources/views/Crime_Scene_Support/head/head.blade.php',
  'resources/views/Digital_Forensics/digital_forensics.blade.php'
];

let allPassed = true;

views.forEach(v => {
  const content = fs.readFileSync(v, 'utf8');
  const scriptMatches = content.match(/<script(?![^>]*src=)[^>]*>([\s\S]*?)<\/script>/gi);
  if (!scriptMatches) {
    console.log('No inline scripts in', v);
    return;
  }

  scriptMatches.forEach((s, i) => {
    let js = s.replace(/<script[^>]*>/i, '').replace(/<\/script>/i, '');
    js = js.replace(/\{!![\s\S]*?!!\}/g, '[]');
    js = js.replace(/\"\{\{[\s\S]*?\}\}\"/g, '"test"');
    js = js.replace(/\'\{\{[\s\S]*?\}\}\'/g, "'test'");
    js = js.replace(/\{\{[\s\S]*?\}\}/g, '0');
    js = js.replace(/@php[\s\S]*?@endphp/g, '');

    try {
      new vm.Script(js);
      console.log(`[PASS] ${v} script #${i} is 100% valid JavaScript!`);
    } catch (e) {
      allPassed = false;
      console.error(`[FAIL] ${v} script #${i}:`, e.message);
    }
  });
});

if (allPassed) {
  console.log('\n>>> ALL 5 VIEWS PASSED VALIDATION WITH ZERO ERRORS! <<<');
}
