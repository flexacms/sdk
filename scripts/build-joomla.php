<?php

/**
 * Build the Joomla package: one ZIP per extension, wrapped in pkg_flexa.zip.
 *
 *     php scripts/build-joomla.php   →   packages/joomla/build/pkg_flexa.zip
 */
$root = dirname(__DIR__).'/packages/joomla';
$build = $root.'/build';
$extensions = ['lib_flexacms', 'com_flexa', 'mod_flexa', 'plg_content_flexa'];

@mkdir($build.'/packages', 0777, true);

function zipDirectory(string $source, string $target, array $extra = []): void
{
    $zip = new ZipArchive;

    if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException("Cannot write {$target}");
    }

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));

    foreach ($files as $file) {
        $zip->addFile($file->getPathname(), substr($file->getPathname(), strlen($source) + 1));
    }

    foreach ($extra as $name => $path) {
        $zip->addFile($path, $name);
    }

    $zip->close();
}

foreach ($extensions as $extension) {
    zipDirectory("{$root}/{$extension}", "{$build}/packages/{$extension}.zip");
}

$zip = new ZipArchive;
$zip->open("{$build}/pkg_flexa.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFile("{$root}/pkg_flexa.xml", 'pkg_flexa.xml');
$zip->addFile("{$root}/script.php", 'script.php');

foreach ($extensions as $extension) {
    $zip->addFile("{$build}/packages/{$extension}.zip", "packages/{$extension}.zip");
}

$zip->close();

echo "Built {$build}/pkg_flexa.zip".PHP_EOL;
