const fs = require('fs');
const content = fs.readFileSync('resources/views/Evidence_Management/evidence_management.blade.php', 'utf8');

const regex = /<script\b[^>]*>([\s\S]*?)<\/script>/gi;
let match;
let i = 0;
while ((match = regex.exec(content)) !== null) {
  i++;
  console.log(`Script tag #${i}: length=${match[1].length}, starts with: ${match[1].trim().substring(0, 60).replace(/\n/g, ' ')}`);
}
