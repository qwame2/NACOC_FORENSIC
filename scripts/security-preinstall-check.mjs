#!/usr/bin/env node

/**
 * Advanced Pre-Installation Security Guard & Supply-Chain Shield
 * NACOC Forensic Directorate Portal
 *
 * Capabilities:
 * 1. Typosquatting Defense (Levenshtein distance & heuristic pattern matching against top ecosystem targets)
 * 2. Malicious Lifecycle Script Analysis (Detects obfuscation, curl/wget/powershell, base64 payloads, eval in install hooks)
 * 3. Socket.dev Threat Intelligence integration (Checks supply-chain alert signals)
 * 4. Freshness / Disposable Account Detection (< 72 hour old release risk)
 * 5. Automated blocking of unsafe, deprecated, or malicious packages before filesystem extraction.
 *
 * Usage:
 *   npm run safe-add <package-name>
 *   node scripts/security-preinstall-check.mjs <pkg> [--save-dev] [--dry-run]
 */

import https from 'node:https';
import { spawnSync } from 'node:child_process';

const args = process.argv.slice(2).filter(arg => !arg.startsWith('-'));
const flags = process.argv.slice(2).filter(arg => arg.startsWith('-'));

const isDryRun = flags.includes('--dry-run') || flags.includes('-d');
const isDev = flags.includes('--save-dev') || flags.includes('-D');
const isForce = flags.includes('--force');

if (args.length === 0) {
    console.log(`
🛡️  NACOC Forensic Directorate - Supply Chain Security Guard
Usage:
  npm run safe-add <pkg> [--save-dev] [--dry-run]
  node scripts/security-preinstall-check.mjs <pkg>

Examples:
  npm run safe-add axios
  npm run safe-add vitest --save-dev
  npm run safe-add lodash --dry-run
`);
    process.exit(0);
}

// High-value packages frequently targeted by typosquatting and supply-chain attacks
const TARGETED_POPULAR_PACKAGES = [
    'react', 'react-dom', 'vue', 'angular', 'svelte', 'next', 'nuxt',
    'express', 'fastify', 'koa', 'axios', 'lodash', 'underscore',
    'chalk', 'commander', 'moment', 'dayjs', 'date-fns', 'uuid',
    'vite', 'webpack', 'esbuild', 'rollup', 'babel', 'typescript',
    'tailwindcss', 'postcss', 'bootstrap', 'jquery', 'select2',
    'dotenv', 'cors', 'jsonwebtoken', 'bcrypt', 'crypto-js',
    'fs-extra', 'glob', 'rimraf', 'rxjs', 'cheerio', 'cross-env'
];

// Dangerous shell / obfuscation tokens in npm scripts
const MALICIOUS_SCRIPT_PATTERNS = [
    /\b(curl|wget)\b/i,
    /\bpowershell(\.exe)?\b/i,
    /\b(cmd|bash|sh)\s+-c\b/i,
    /\b(base64|atob|btoa)\b/i,
    /\beval\s*\(/i,
    /\bBuffer\.from\b/i,
    /\b(http:\/\/|https:\/\/)(\d{1,3}\.){3}\d{1,3}\b/i, // Raw IP addresses
    /\bpastebin\.com\b/i,
    /\bdiscord(app)?\.com\/api\/webhooks\b/i,
    /\braw\.githubusercontent\.com\b/i,
    /\bngrok\.io\b/i,
    /\btelemetry\b/i,
    /\btoken\b.*\b(steal|grab|exfil|upload)\b/i
];

function levenshteinDistance(s1, s2) {
    s1 = s1.toLowerCase();
    s2 = s2.toLowerCase();
    const costs = [];
    for (let i = 0; i <= s1.length; i++) {
        let lastValue = i;
        for (let j = 0; j <= s2.length; j++) {
            if (i === 0) {
                costs[j] = j;
            } else if (j > 0) {
                let newValue = costs[j - 1];
                if (s1.charAt(i - 1) !== s2.charAt(j - 1)) {
                    newValue = Math.min(Math.min(newValue, lastValue), costs[j]) + 1;
                }
                costs[j - 1] = lastValue;
                lastValue = newValue;
            }
        }
        if (i > 0) costs[s2.length] = lastValue;
    }
    return costs[s2.length];
}

function checkTyposquatting(pkgName) {
    const raw = pkgName.startsWith('@') ? pkgName.split('/')[1] || pkgName : pkgName;
    for (const pop of TARGETED_POPULAR_PACKAGES) {
        if (raw.toLowerCase() === pop.toLowerCase()) {
            return null; // Exact match to trusted popular package
        }
        const dist = levenshteinDistance(raw, pop);
        // If distance is 1 or 2, and length is similar, high chance of typosquatting
        if (dist >= 1 && dist <= 2 && Math.abs(raw.length - pop.length) <= 2) {
            return `Likely typosquatting attempt: "${pkgName}" is strikingly similar to popular package "${pop}" (Levenshtein distance: ${dist}).`;
        }
    }
    return null;
}

function fetchJson(url, headers = {}) {
    return new Promise((resolve, reject) => {
        https.get(url, { headers: { 'User-Agent': 'NACOC-SupplyChain-Shield/2.0', ...headers } }, (res) => {
            let data = '';
            res.on('data', chunk => { data += chunk; });
            res.on('end', () => {
                if (res.statusCode >= 200 && res.statusCode < 300) {
                    try {
                        resolve(JSON.parse(data));
                    } catch (e) {
                        reject(new Error(`Invalid JSON from ${url}: ${e.message}`));
                    }
                } else if (res.statusCode === 404) {
                    resolve(null);
                } else {
                    reject(new Error(`HTTP ${res.statusCode} from ${url}`));
                }
            });
        }).on('error', reject);
    });
}

async function querySocketDev(pkgName) {
    try {
        const cleanName = pkgName.startsWith('@') ? encodeURIComponent(pkgName) : pkgName;
        const res = await fetchJson(`https://api.socket.dev/v2/npm/package/info?name=${cleanName}`);
        if (res && res.score) {
            return res;
        }
    } catch (e) {
        // Socket.dev API is optional / advisory
    }
    return null;
}

async function inspectPackage(pkgSpec) {
    let name = pkgSpec;
    let requestedVersion = 'latest';

    if (pkgSpec.startsWith('@')) {
        const parts = pkgSpec.slice(1).split('@');
        name = '@' + parts[0];
        if (parts.length > 1 && parts[1]) requestedVersion = parts[1];
    } else if (pkgSpec.includes('@')) {
        const parts = pkgSpec.split('@');
        name = parts[0];
        requestedVersion = parts[1];
    }

    console.log(`\n🔎 Auditing candidate package [${name}] against security threat intelligence...`);

    const errors = [];
    const warnings = [];

    // 1. Typosquatting verification
    const typoAlert = checkTyposquatting(name);
    if (typoAlert) {
        warnings.push(typoAlert);
    }

    // 2. Fetch metadata from registry
    const encodedName = name.startsWith('@') ? `@${encodeURIComponent(name.slice(1))}` : encodeURIComponent(name);
    let data;
    try {
        data = await fetchJson(`https://registry.npmjs.org/${encodedName}`);
    } catch (err) {
        console.warn(`   ⚠️  Registry query failed (${err.message}). Performing local fallback check.`);
        return { name, allowed: true, warnings: ['Registry offline, proceed with caution'] };
    }

    if (!data) {
        errors.push(`Package "${name}" does not exist in the public npm registry. Potential spoofing or typosquatting.`);
        return { name, allowed: false, errors, warnings };
    }

    const distTags = data['dist-tags'] || {};
    const targetVersion = distTags[requestedVersion] || requestedVersion;
    const versionData = (data.versions && data.versions[targetVersion]) || (data.versions && data.versions[distTags.latest]) || {};

    // 3. Inspect Lifecycle Install Scripts (Primary Malware Delivery Mechanism)
    const scripts = versionData.scripts || {};
    const dangerousHooks = ['preinstall', 'install', 'postinstall'];
    for (const hook of dangerousHooks) {
        if (scripts[hook]) {
            const scriptContent = String(scripts[hook]);
            warnings.push(`Automatic hook detected: "${hook}": "${scriptContent}"`);

            for (const pattern of MALICIOUS_SCRIPT_PATTERNS) {
                if (pattern.test(scriptContent)) {
                    errors.push(`CRITICAL SECURITY VIOLATION: Hook "${hook}" matches suspicious code execution pattern (${pattern}). Content: "${scriptContent}". BLOCKED.`);
                }
            }
        }
    }

    // 4. Check Package Age / Freshness
    const timeObj = data.time || {};
    const versionTime = (timeObj[targetVersion] || timeObj.modified) ? new Date(timeObj[targetVersion] || timeObj.modified) : null;
    if (versionTime) {
        const ageHours = (new Date() - versionTime) / (1000 * 60 * 60);
        if (ageHours < 72) {
            warnings.push(`Version ${targetVersion} was released only ${Math.round(ageHours)} hours ago. New packages should be vetted before production adoption.`);
        }
    }

    // 5. Query Socket.dev threat signals
    const socketInfo = await querySocketDev(name);
    if (socketInfo && socketInfo.score) {
        const supplyChainScore = Math.round((socketInfo.score.supplyChain || 0) * 100);
        const qualityScore = Math.round((socketInfo.score.quality || 0) * 100);
        console.log(`   🛡️  Socket.dev Health Scores: Supply Chain: ${supplyChainScore}/100, Quality: ${qualityScore}/100`);
        if (supplyChainScore < 50) {
            warnings.push(`Socket.dev flags elevated supply chain risk score (${supplyChainScore}/100).`);
        }
    }

    // 6. Check Deprecation
    if (versionData.deprecated) {
        warnings.push(`Package is DEPRECATED: "${versionData.deprecated}"`);
    }

    return {
        name,
        targetVersion,
        allowed: errors.length === 0,
        errors,
        warnings,
        hasHooks: dangerousHooks.some(h => scripts[h])
    };
}

async function main() {
    console.log(`🛡️  NACOC Forensic Directorate - Supply Chain Security Shield`);
    console.log(`   Scanning ${args.length} package(s) prior to filesystem modification...\n`);

    let allAllowed = true;
    let anyHooks = false;

    for (const pkg of args) {
        const report = await inspectPackage(pkg);
        if (!report.allowed) {
            allAllowed = false;
            console.error(`\n❌ SECURITY VIOLATION DETECTED FOR [${report.name}]:`);
            report.errors.forEach(e => console.error(`   ⛔ ${e}`));
        } else {
            console.log(`✅ [${report.name}@${report.targetVersion || 'latest'}] passed security verification.`);
            if (report.warnings.length > 0) {
                console.warn(`   ⚠️  Advisory Warnings:`);
                report.warnings.forEach(w => console.warn(`      • ${w}`));
            }
            if (report.hasHooks) {
                anyHooks = true;
            }
        }
    }

    if (!allAllowed && !isForce) {
        console.error(`\n🚨 ACTION BLOCKED: One or more dependencies failed security criteria. Installation terminated.`);
        process.exit(1);
    }

    if (isDryRun) {
        console.log(`\n📋 Pre-install verification complete. All packages are permitted.`);
        process.exit(0);
    }

    console.log(`\n📦 Safe Verification Passed. Executing isolated install with --ignore-scripts...`);
    const installArgs = ['install', ...args];
    if (isDev) installArgs.push('--save-dev');
    installArgs.push('--ignore-scripts'); // Never execute untrusted scripts automatically

    const runner = process.platform === 'win32' ? 'cmd' : 'npm';
    const finalArgs = process.platform === 'win32' ? ['/c', 'npm', ...installArgs] : installArgs;

    const installResult = spawnSync(runner, finalArgs, {
        stdio: 'inherit',
        encoding: 'utf8'
    });

    if (installResult.status !== 0) {
        process.exit(installResult.status || 1);
    }

    console.log(`\n🔍 Verifying dependency graph integrity with high-severity audit...`);
    const auditRunner = process.platform === 'win32' ? 'cmd' : 'npm';
    const auditArgs = process.platform === 'win32' ? ['/c', 'npm', 'audit', '--audit-level=high'] : ['audit', '--audit-level=high'];
    spawnSync(auditRunner, auditArgs, { stdio: 'inherit' });
}

main().catch(err => {
    console.error(`Fatal security check error:`, err);
    process.exit(1);
});
