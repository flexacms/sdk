/**
 * Give every SDK package the same version, Laravel-style, and point the
 * internal @flexacms/* dependencies at it. Usage: node scripts/set-version.mjs 1.2.0
 */
import { readFileSync, writeFileSync } from 'node:fs';

const version = process.argv[2]?.replace(/^v/, '');

if (!version || !/^\d+\.\d+\.\d+(-[0-9A-Za-z.-]+)?$/.test(version)) {
    console.error('Usage: node scripts/set-version.mjs <semver>');
    process.exit(1);
}

const root = JSON.parse(
    readFileSync(new URL('../package.json', import.meta.url), 'utf8'),
);

for (const workspace of root.workspaces) {
    const file = new URL(`../${workspace}/package.json`, import.meta.url);
    const pkg = JSON.parse(readFileSync(file, 'utf8'));

    pkg.version = version;

    for (const field of ['dependencies', 'peerDependencies']) {
        for (const name of Object.keys(pkg[field] ?? {})) {
            if (name.startsWith('@flexacms/')) {
                pkg[field][name] = `^${version}`;
            }
        }
    }

    writeFileSync(file, `${JSON.stringify(pkg, null, 4)}\n`);
    console.log(`${pkg.name}@${version}`);
}
