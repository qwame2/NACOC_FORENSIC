const fs = require('fs');

const code = fs.readFileSync('scratch/extracted_script.js', 'utf8');
const lines = code.split('\n');

let funcStack = [];

for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const fileLine = i + 3198;

    // Detect function start
    if (line.match(/^\s*(async\s+)?function\s+([a-zA-Z0-9_]+)/)) {
        const name = line.match(/^\s*(async\s+)?function\s+([a-zA-Z0-9_]+)/)[2];
        funcStack.push({ name, line: fileLine, startIdx: i });
    }
}

console.log('Found ' + funcStack.length + ' functions:');
funcStack.forEach(f => console.log(` - ${f.name} at line ${f.line}`));

// Now test each function individually by parsing it in node vm!
const vm = require('vm');

funcStack.forEach((f, idx) => {
    const nextStartLine = (idx + 1 < funcStack.length) ? funcStack[idx + 1].startIdx : lines.length;
    const funcCode = lines.slice(f.startIdx, nextStartLine).join('\n');

    try {
        new vm.Script(funcCode);
        console.log(`✅ ${f.name} (L${f.line}) parses cleanly alone.`);
    } catch (err) {
        console.log(`❌ ${f.name} (L${f.line}) FAILS standalone parse: ${err.message}`);
    }
});
