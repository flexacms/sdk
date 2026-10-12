<?php

/**
 * Build the installable WordPress plugin ZIP (a "flexa/" folder):
 *
 *     php scripts/build-wordpress.php   →   packages/wordpress/build/flexa.zip
 *
 * README.md is for the repository; WordPress uses readme.txt.
 */

$source = dirname(__DIR__).'/packages/wordpress';
$build = $source.'/build';
$skip = ['README.md', 'build'];

@mkdir($build, 0777, true);

$zip = new ZipArchive;
$zip->open("{$build}/flexa.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));

foreach ($files as $file) {
    $relative = substr($file->getPathname(), strlen($source) + 1);

    if (in_array(strtok($relative, '/'), $skip, true)) {
        continue;
    }

    $zip->addFile($file->getPathname(), 'flexa/'.$relative);
}

$zip->close();

echo "Built {$build}/flexa.zip".PHP_EOL;
