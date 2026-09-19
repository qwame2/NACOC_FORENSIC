const fs = require('fs');

const code = fs.readFileSync('scratch/extracted_script.js', 'utf8');
const lines = code.split('\n');

console.log('Total extracted lines:', lines.length);

let stack = [];
let inBacktick = false;
let inSingleQuote = false;
let inDoubleQuote = false;

for (let l = 0; l < lines.length; l++) {
    const line = lines[l];
    const lineNo = l + 1; // 1-indexed in extracted script

    for (let c = 0; c < line.length; c++) {
        const ch = line[c];
        const prev = c > 0 ? line[c-1] : '';
        const next = c + 1 < line.length ? line[c+1] : '';

        if (prev === '\\') continue; // skip escaped

        if (ch === '`' && !inSingleQuote && !inDoubleQuote) {
            inBacktick = !inBacktick;
            continue;
        }
        if (ch === "'" && !inBacktick && !inDoubleQuote) {
            inSingleQuote = !inSingleQuote;
            continue;
        }
        if (ch === '"' && !inBacktick && !inSingleQuote) {
            inDoubleQuote = !inDoubleQuote;
            continue;
        }

        if (inBacktick || inSingleQuote || inDoubleQuote) continue;

        // Skip line comments
        if (ch === '/' && next === '/') break;

        if (ch === '{') stack.push({ type: '{', line: lineNo, col: c+1, text: line.trim() });
        else if (ch === '}') {
            if (stack.length === 0) {
                console.log(`Unexpected } at line ${lineNo}:${c+1}`);
            } else {
                const top = stack[stack.length - 1];
                if (top.type === '{') stack.pop();
                else console.log(`Mismatched } at line ${lineNo}:${c+1}, expected ${top.type} from line ${top.line}`);
            }
        }
    }
}

console.log('\nRemaining unclosed braces at end of file:');
stack.forEach(item => {
    // Map line number back to original file line (script starts at 3198)
    const origLine = item.line + 3197;
    console.log(`  Extracted L${item.line} (File L${origLine}): ${item.text.substring(0, 100)}`);
});
