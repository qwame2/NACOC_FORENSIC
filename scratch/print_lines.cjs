const fs = require('fs');
const content = fs.readFileSync('resources/views/Forensic_Head/head.blade.php', 'utf8');
const scriptMatches = content.match(/<script(?![^>]*src=)[^>]*>([\s\S]*?)<\/script>/gi);
let js = scriptMatches[1].replace(/<script[^>]*>/i, '').replace(/<\/script>/i, '');
const lines = js.split('\n');
for (let i = 20; i < 50; i++) {
  console.log(`${i+1}: ${lines[i]}`);
}
