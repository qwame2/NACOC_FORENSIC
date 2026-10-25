const fs = require('fs');
const path = require('path');

const files = fs.readdirSync('scratch');
for (const f of files) {
  if (f.endsWith('.py') || f.endsWith('.js') || f.endsWith('.php')) {
    const txt = fs.readFileSync(path.join('scratch', f), 'utf8');
    if (txt.includes('renderTacticalCasesStream')) {
      console.log(`Found in scratch/${f}`);
      const lines = txt.split('\n');
      for (let i = 0; i < lines.length; i++) {
        if (lines[i].includes('renderTacticalCasesStream')) {
          console.log(`  Line ${i+1}: ${lines[i].trim()}`);
        }
      }
    }
  }
}
