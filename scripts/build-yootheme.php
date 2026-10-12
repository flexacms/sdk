<?php

/**
 * Build the YOOtheme Pro integration for both platforms, each bundling the
 * shared modules/ folder:
 *
 *     packages/yootheme/build/flexa-yootheme.zip              (WordPress plugin)
 *     packages/yootheme/build/plg_system_flexayootheme.zip    (Joomla plugin)
 */

$root = dirname(__DIR__).'/packages/yootheme';
$build = $root.'/build';
@mkdir($build, 0777, true);

function addDirectory(ZipArchive $zip, string $source, string $prefix): void
{
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));

    foreach ($files as $file) {
        $zip->addFile($file->getPathname(), $prefix.substr($file->getPathname(), strlen($source) + 1));
    }
}

$wordpress = new ZipArchive;
$wordpress->open("{$build}/flexa-yootheme.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);
addDirectory($wordpress, "{$root}/wordpress", 'flexa-yootheme/');
addDirectory($wordpress, "{$root}/modules", 'flexa-yootheme/modules/');
$wordpress->close();

$joomla = new ZipArchive;
$joomla->open("{$build}/plg_system_flexayootheme.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);
addDirectory($joomla, "{$root}/joomla", '');
addDirectory($joomla, "{$root}/modules", 'modules/');
$joomla->close();

echo "Built {$build}/flexa-yootheme.zip and {$build}/plg_system_flexayootheme.zip".PHP_EOL;
