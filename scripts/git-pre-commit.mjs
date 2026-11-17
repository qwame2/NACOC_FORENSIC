#!/usr/bin/env node

/**
 * Git Pre-Commit Security Hook
 * NACOC Forensic Directorate Portal
 *
 * Runs automatically before any git commit.
 * 1. Checks staged files for hardcoded secrets, API tokens, and private keys.
 * 2. If dependency files (package.json, composer.json, lockfiles) are modified,
 *    executes high-severity security audits before allowing the commit to proceed.
 */

import { spawnSync } from 'node:child_process';
import fs from 'node:fs';

// 1. Check which files are currently staged in git
const stagedDiff = spawnSync('git', ['diff', '--cached', '--name-only'], {
    encoding: 'utf8'
});

if (stagedDiff.status !== 0) {
    // If not in a git tree or git fails, pass through
    process.exit(0);
}

const stagedFiles = (stagedDiff.stdout || '').split(/\r?\n/).filter(Boolean);

if (stagedFiles.length === 0) {
    process.exit(0);
}

// 2. Secret & API Token Leak Detector
const secretPatterns = [
    { name: 'Private Key Block', regex: /-----BEGIN\s+(RSA|EC|OPENSSH|DSA)?\s*PRIVATE KEY-----/ },
    { name: 'AWS Access Key ID', regex: /\b(AKIA[0-9A-Z]{16})\b/ },
    { name: 'GitHub Personal Token', regex: /\b(ghp_[a-zA-Z0-9]{36}|github_pat_[a-zA-Z0-9_]{82})\b/ },
    { name: 'Slack Bot/User Token', regex: /\b(xox[baprs]-[0-9a-zA-Z]{10,48})\b/ },
    { name: 'Generic Live API Secret', regex: /\b(sk_live_[0-9a-zA-Z]{24,})\b/ },
];

let secretFound = false;

for (const file of stagedFiles) {
    if (file === '.env' || file.endsWith('.env')) {
        console.error(`\n🚨 CRITICAL SECURITY ALERT: Attempting to commit an environment file: "${file}".`);
        console.error(`   Environment files (.env) must NEVER be committed to Git.`);
        secretFound = true;
        continue;
    }

    // Skip scanning binaries, snapshots, or lock files
    if (file.endsWith('.lock') || file.endsWith('.png') || file.endsWith('.jpg') || file.endsWith('.pdf') || file.endsWith('.json')) {
        continue;
    }

    if (fs.existsSync(file)) {
        try {
            const content = fs.readFileSync(file, 'utf8');
            for (const { name, regex } of secretPatterns) {
                if (regex.test(content)) {
                    console.error(`\n🚨 CRITICAL SECURITY ALERT: Found potential unencrypted secret [${name}] in staged file "${file}".`);
                    secretFound = true;
                }
            }
        } catch (_) {}
    }
}

if (secretFound) {
    console.error('\n❌ Commit blocked by NACOC Security Hook. Please remove or rotate secrets before committing.\n');
    process.exit(1);
}

// 3. Dependency audit checks
const isNpmChanged = stagedFiles.some(f => f === 'package.json' || f === 'package-lock.json');
const isComposerChanged = stagedFiles.some(f => f === 'composer.json' || f === 'composer.lock');

if (!isNpmChanged && !isComposerChanged) {
    process.exit(0);
}

console.log('🛡️  NACOC Security Hook: Staged dependency changes detected. Running security validation...');

let failed = false;

if (isNpmChanged) {
    console.log('   🔍 Running NPM security audit (High / Critical check)...');
    const npmCmd = process.platform === 'win32' ? 'cmd' : 'npm';
    const npmArgs = process.platform === 'win32' ? ['/c', 'npm', 'audit', '--audit-level=high'] : ['audit', '--audit-level=high'];
    const res = spawnSync(npmCmd, npmArgs, { stdio: 'inherit', encoding: 'utf8' });
    if (res.status !== 0) {
        console.error('   ❌ NPM security audit failed: High or critical vulnerabilities detected.');
        failed = true;
    }
}

if (isComposerChanged) {
    console.log('   🔍 Running Composer security audit (Locked dependencies)...');
    const compCmd = process.platform === 'win32' ? 'cmd' : 'composer';
    const compArgs = process.platform === 'win32' ? ['/c', 'composer', 'audit', '--locked'] : ['audit', '--locked'];
    const res = spawnSync(compCmd, compArgs, { stdio: 'inherit', encoding: 'utf8' });
    if (res.status !== 0) {
        console.error('   ❌ Composer security audit failed: Vulnerable locked packages detected.');
        failed = true;
    }
}

if (failed) {
    console.error('\n🚨 COMMIT REJECTED: Security vulnerabilities detected in staged dependencies.');
    console.error('   Please run "npm audit fix" or "composer update <pkg>" before committing.\n');
    process.exit(1);
}

console.log('   ✅ All staged dependencies passed security audit.');
process.exit(0);
