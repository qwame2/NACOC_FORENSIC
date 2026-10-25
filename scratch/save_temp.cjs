const fs = require('fs');

const content = fs.readFileSync('resources/views/Forensic_Head/head.blade.php', 'utf8');
const scriptMatches = content.match(/<script(?![^>]*src=)[^>]*>([\s\S]*?)<\/script>/gi);
let js = scriptMatches[1].replace(/<script[^>]*>/i, '').replace(/<\/script>/i, '');
js = js.replace(/\{!![\s\S]*?!!\}/g, '[]');
js = js.replace(/\"\{\{[\s\S]*?\}\}\"/g, '"test"');
js = js.replace(/\'\{\{[\s\S]*?\}\}\'/g, "'test'");
js = js.replace(/\{\{[\s\S]*?\}\}/g, '0');
js = js.replace(/@php[\s\S]*?@endphp/g, '');

fs.writeFileSync('scratch/temp_check.js', js, 'utf8');
console.log('Saved temp_check.js');
