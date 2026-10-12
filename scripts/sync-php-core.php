<?php

/**
 * Copy the flexacms/php client into a plugin under the plugin's own namespace,
 * so several Flexa plugins (or versions of them) can be active side by side.
 *
 *     php scripts/sync-php-core.php <target-dir> <Target\Namespace> [--check] [--exclude=Http/CurlTransport.php]
 *
 * With --check, nothing is written and the exit code says whether the copy is current.
 */
[$script, $target, $namespace] = array_pad(array_slice($argv, 0, 3), 3, null);
$check = in_array('--check', $argv, true);
$excluded = array_map(fn (string $arg): string => substr($arg, 10), array_filter($argv, fn (string $arg): bool => str_starts_with($arg, '--exclude=')));

if ($target === null || $namespace === null) {
    fwrite(STDERR, "Usage: php {$script} <target-dir> <Target\\Namespace> [--check]\n");
    exit(1);
}

$source = dirname(__DIR__).'/packages/php/src';
$header = "<?php\n\n// Generated from flexacms/php by scripts/sync-php-core.php. Do not edit: change packages/php instead.\n";
$expected = [];

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));

foreach ($files as $file) {
    $relative = substr($file->getPathname(), strlen($source) + 1);

    if (in_array($relative, $excluded, true)) {
        continue;
    }

    $code = str_replace('FlexaCms\\Client', trim($namespace, '\\'), file_get_contents($file->getPathname()));
    $expected[$relative] = preg_replace('/^<\?php\n/', $header, $code, 1);
}

ksort($expected);
$outdated = [];

foreach ($expected as $relative => $code) {
    $path = rtrim($target, '/').'/'.$relative;

    if (! is_file($path) || file_get_contents($path) !== $code) {
        $outdated[] = $relative;

        if (! $check) {
            @mkdir(dirname($path), 0777, true);
            file_put_contents($path, $code);
        }
    }
}

if (is_dir($target)) {
    $existing = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS));

    foreach ($existing as $file) {
        $relative = substr($file->getPathname(), strlen(rtrim($target, '/')) + 1);

        if (! isset($expected[$relative])) {
            $outdated[] = $relative;

            if (! $check) {
                unlink($file->getPathname());
            }
        }
    }
}

if ($check && $outdated !== []) {
    fwrite(STDERR, "Out of date in {$target}: ".implode(', ', $outdated)."\nRun: php scripts/sync-php-core.php {$target} {$namespace}".($excluded === [] ? '' : ' --exclude='.implode(' --exclude=', $excluded))."\n");
    exit(1);
}

echo ($check ? 'Up to date: ' : 'Synced: ').$target.PHP_EOL;
