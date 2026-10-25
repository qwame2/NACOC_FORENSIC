const fs = require('fs');
const content = fs.readFileSync('resources/views/Forensic_Head/head.blade.php', 'utf8');
const scriptMatches = content.match(/<script(?![^>]*src=)[^>]*>([\s\S]*?)<\/script>/gi);
let js = scriptMatches[1].replace(/<script[^>]*>/i, '').replace(/<\/script>/i, '');
js = js.replace(/\{!![\s\S]*?!!\}/g, '[]');
js = js.replace(/\{\{[\s\S]*?\}\}/g, '"test"');
js = js.replace(/@php[\s\S]*?@endphp/g, '');

const lines = js.split('\n');
let acc = '';
for (let i = 0; i < lines.length; i++) {
  acc += lines[i] + '\n';
  try {
    new Function(acc);
  } catch (e) {
    if (!e.message.includes('Unexpected end of input')) {
      console.log(`Error on line ${i + 1}: ${e.message}`);
      console.log(`Line content: ${lines[i]}`);
      break;
    }
  }
}
