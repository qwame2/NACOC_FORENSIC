const fs = require('fs');
const vm = require('vm');

const code = fs.readFileSync('scratch/extracted_script.js', 'utf8');
const lines = code.split('\n');

console.log('Total extracted lines:', lines.length);

// Let's test chunks of functions or lines to locate which function fails to parse
for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    // test function declarations
    if (line.match(/^\s*(async\s+)?function\s+([a-zA-Z0-9_]+)/)) {
        const funcName = line.match(/^\s*(async\s+)?function\s+([a-zA-Z0-9_]+)/)[2];
        // find function end or next function
    }
}

// Let's test binary search of lines: check if prefix of code up to line N + closing braces parses or where braces mismatch
let openBraces = 0;
let openParens = 0;

// Simple stack trace tool
let stack = [];
let inBacktick = false;

for (let l = 0; l < lines.length; l++) {
    const line = lines[l];
    const lineNo = l + 1; // 1-indexed in file

    for (let c = 0; c < line.length; c++) {
        const ch = line[c];
        const next = line[c+1];

        // Handle strings / backticks
        if (ch === '`') {
            inBacktick = !inBacktick;
            continue;
        }

        if (inBacktick) continue;

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

console.log('Remaining unclosed braces at end of file:');
stack.forEach(item => {
    console.log(`  Line ${item.line} (${item.type}): ${item.text.substring(0, 80)}`);
});
