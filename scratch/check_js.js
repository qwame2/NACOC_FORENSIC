const fs = require('fs');
const path = require('path');

function checkFile(filePath) {
    console.log("Checking file:", filePath);
    const content = fs.readFileSync(filePath, 'utf8');
    const scriptRegex = /<script.*?>([\s\S]*?)<\/script>/gi;
    let match;
    let count = 0;
    while ((match = scriptRegex.exec(content)) !== null) {
        count++;
        let js = match[1];
        // Clean blade tags for syntax validation
        js = js.replace(/@json\(.*?\)/g, '{}')
              .replace(/\{\{\s*[\s\S]*?\s*\}\}/g, '"blade_var"')
              .replace(/\{!!\s*[\s\S]*?\s*!!\}/g, '"blade_raw"');
        try {
            new Function(js);
            console.log(`  Script block ${count}: OK`);
        } catch (e) {
            console.error(`  Script block ${count}: ERROR - ${e.message}`);
            // Find line number of error
            const lines = js.split('\n');
            console.error(`  Error detail:`, e.stack ? e.stack.split('\n')[0] : e);
        }
    }
}

const viewsDir = path.join(__dirname, '../resources/views');
function walk(dir) {
    const files = fs.readdirSync(dir);
    for (const f of files) {
        const full = path.join(dir, f);
        if (fs.statSync(full).isDirectory()) {
            walk(full);
        } else if (f.endsWith('.blade.php')) {
            checkFile(full);
        }
    }
}

walk(viewsDir);
