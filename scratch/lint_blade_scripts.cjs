const fs = require('fs');

const content = fs.readFileSync('resources/views/Evidence_Management/evidence_management.blade.php', 'utf8');
const scriptRegex = /<script\b[^>]*>([\s\S]*?)<\/script>/gi;
let match;
let scriptIndex = 0;
while ((match = scriptRegex.exec(content)) !== null) {
  scriptIndex++;
  const scriptContent = match[1];
  const before = content.slice(0, match.index);
  const startLine = before.split('\n').length;
  // Replace blade expressions
  const sanitized = scriptContent
    .replace(/\{\{!![\s\S]*?!!\}\}/g, '[]')
    .replace(/@json\([\s\S]*?\)/g, '[]')
    .replace(/\{\{[\s\S]*?\}\}/g, '""');
  try {
    new Function(sanitized);
    console.log(`Script #${scriptIndex} (line ${startLine}): VALID`);
  } catch (err) {
    console.log(`Script #${scriptIndex} (line ${startLine}) ERROR:`, err.message);
  }
}
