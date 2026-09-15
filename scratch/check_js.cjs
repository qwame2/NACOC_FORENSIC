const fs = require('fs');
const path = require('path');

const filePath = 'c:\\Users\\USER\\Desktop\\ForensicNacoc\\resources\\views\\Crime_Scene_Support\\staff\\events\\arrest_person.blade.php';
const content = fs.readFileSync(filePath, 'utf8');

const scriptMatches = content.match(/<script[\s\S]*?<\/script>/gi);
console.log(`Found ${scriptMatches ? scriptMatches.length : 0} script block(s).`);

if (scriptMatches) {
    scriptMatches.forEach((script, idx) => {
        const cleanScript = script.replace(/<script[^>]*>/i, '').replace(/<\/script>/i, '');
        // Replace blade directives inside script to validate JS syntax
        const parsedScript = cleanScript.replace(/\{\{.*?\}\}/g, '""').replace(/@\{.*?\}/g, '""');
        try {
            new Function(parsedScript);
            console.log(`Script block #${idx + 1}: Syntax OK`);
        } catch (err) {
            console.error(`Script block #${idx + 1}: Syntax Error:`, err.message);
        }
    });
}
