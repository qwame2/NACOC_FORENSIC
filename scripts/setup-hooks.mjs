#!/usr/bin/env node

import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

console.log('🔧 Configuring NACOC Git Security Hooks...');

try {
    // 1. Set git core.hooksPath to .githooks
    const res = spawnSync('git', ['config', 'core.hooksPath', '.githooks'], { encoding: 'utf8' });
    if (res.status === 0) {
        console.log('   ✅ Configured git core.hooksPath -> .githooks');
    }

    // 2. Also ensure .git/hooks/pre-commit has the hook for environments where core.hooksPath is bypassed
    const gitDir = path.resolve('.git');
    if (fs.existsSync(gitDir) && fs.lstatSync(gitDir).isDirectory()) {
        const hooksDir = path.join(gitDir, 'hooks');
        if (!fs.existsSync(hooksDir)) {
            fs.mkdirSync(hooksDir, { recursive: true });
        }
        const hookSrc = path.resolve('.githooks', 'pre-commit');
        const hookDest = path.join(hooksDir, 'pre-commit');
        if (fs.existsSync(hookSrc)) {
            fs.copyFileSync(hookSrc, hookDest);
            try {
                fs.chmodSync(hookDest, '755');
            } catch (e) {}
            console.log('   ✅ Installed pre-commit security hook in .git/hooks/pre-commit');
        }
    }
} catch (e) {
    console.warn(`   ⚠️  Could not automatically install git hook: ${e.message}`);
}

