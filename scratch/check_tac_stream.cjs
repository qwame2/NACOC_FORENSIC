const fs = require('fs');
const content = fs.readFileSync('resources/views/Evidence_Management/evidence_management.blade.php', 'utf8');

const calls = [...content.matchAll(/renderTacticalCasesStream/g)];
console.log("Total occurrences of renderTacticalCasesStream:", calls.length);

const defs = [...content.matchAll(/function\s+renderTacticalCasesStream/g)];
console.log("Total definitions of renderTacticalCasesStream:", defs.length);
