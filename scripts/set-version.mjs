/**
 * Give every SDK package the same version, Laravel-style: the npm packages
 * (and their internal @flexacms/* dependencies), the PHP client and the
 * WordPress, Joomla and YOOtheme plugins. Then re-sync the PHP client copies
 * bundled in the plugins. Usage: node scripts/set-version.mjs 1.2.0
 */
import { execFileSync } from 'node:child_process';
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

/**
 * Version strings outside package.json, as [file, pattern] pairs. Each pattern
 * captures the text before the version; changelog entries are left alone.
 */
const versioned = [
    ['packages/php/src/Client.php', /(public const VERSION = ')[^']+/],
    ['packages/wordpress/flexa.php', /( \* Version:\s+)\S+/],
    ['packages/wordpress/flexa.php', /(define\('FLEXA_VERSION', ')[^']+/],
    ['packages/wordpress/readme.txt', /(Stable tag: )\S+/],
    [
        'packages/wordpress/blocks/entries/index.asset.php',
        /('version' => ')[^']+/,
    ],
    [
        'packages/wordpress/blocks/entry/index.asset.php',
        /('version' => ')[^']+/,
    ],
    ['packages/joomla/pkg_flexa.xml', /(<version>)[^<]+/],
    ['packages/joomla/lib_flexacms/flexacms.xml', /(<version>)[^<]+/],
    [
        'packages/joomla/lib_flexacms/src/Flexa.php',
        /(public const VERSION = ')[^']+/,
    ],
    ['packages/joomla/com_flexa/flexa.xml', /(<version>)[^<]+/],
    [
        'packages/joomla/com_flexa/media/joomla.asset.json',
        /("version": ")[^"]+/,
    ],
    ['packages/joomla/mod_flexa/mod_flexa.xml', /(<version>)[^<]+/],
    ['packages/joomla/plg_content_flexa/flexa.xml', /(<version>)[^<]+/],
    ['packages/yootheme/wordpress/flexa-yootheme.php', /( \* Version:\s+)\S+/],
    ['packages/yootheme/wordpress/readme.txt', /(Stable tag: )\S+/],
    ['packages/yootheme/joomla/flexayootheme.xml', /(<version>)[^<]+/],
];

for (const [path, pattern] of versioned) {
    const file = new URL(`../${path}`, import.meta.url);
    const contents = readFileSync(file, 'utf8');

    if (!pattern.test(contents)) {
        console.error(`No version found in ${path}`);
        process.exit(1);
    }

    writeFileSync(file, contents.replace(pattern, `$1${version}`));
    console.log(`${path}@${version}`);
}

const sync = new URL('./sync-php-core.php', import.meta.url).pathname;
const sdkRoot = new URL('..', import.meta.url).pathname;

for (const [target, namespace] of [
    ['packages/wordpress/lib/core', 'FlexaCms\\WordPress\\Core'],
    ['packages/joomla/lib_flexacms/src/Core', 'FlexaCms\\Joomla\\Core'],
]) {
    execFileSync(
        'php',
        [sync, target, namespace, '--exclude=Http/CurlTransport.php'],
        { cwd: sdkRoot, stdio: 'inherit' },
    );
}
