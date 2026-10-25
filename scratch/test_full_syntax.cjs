const fs = require('fs');

const content = fs.readFileSync('resources/views/Forensic_Head/head.blade.php', 'utf8');
const scriptMatches = content.match(/<script(?![^>]*src=)[^>]*>([\s\S]*?)<\/script>/gi);
let js = scriptMatches[1].replace(/<script[^>]*>/i, '').replace(/<\/script>/i, '');
js = js.replace(/\{!![\s\S]*?!!\}/g, '[]');
js = js.replace(/\{\{[\s\S]*?\}\}/g, '"test"');
js = js.replace(/@php[\s\S]*?@endphp/g, '');

try {
  new Function(js);
  console.log("No syntax error with new Function!");
} catch (e) {
  console.log("Error message:", e.message);
  console.log("Error stack:", e.stack);
}
