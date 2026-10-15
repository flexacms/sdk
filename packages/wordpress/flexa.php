<?php

use FlexaCms\WordPress\Plugin;

/**
 * Plugin Name:       Flexa
 * Plugin URI:        https://github.com/flexacms/sdk/tree/main/packages/wordpress
 * Description:       Show content from your Flexa headless CMS in WordPress: blocks, shortcodes and template functions, cached and translated.
 * Version:           0.3.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Flexa
 * Author URI:        https://github.com/flexacms
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       flexa
 */
if (! defined('ABSPATH')) {
    exit;
}

define('FLEXA_VERSION', '0.3.0');
define('FLEXA_FILE', __FILE__);
define('FLEXA_DIR', plugin_dir_path(__FILE__));
define('FLEXA_URL_PATH', plugin_dir_url(__FILE__));

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'FlexaCms\\WordPress\\Core\\' => FLEXA_DIR.'lib/core/',
        'FlexaCms\\WordPress\\' => FLEXA_DIR.'src/',
    ];

    foreach ($prefixes as $prefix => $directory) {
        if (str_starts_with($class, $prefix)) {
            $file = $directory.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';

            if (is_file($file)) {
                require $file;
            }

            return;
        }
    }
});

require FLEXA_DIR.'src/functions.php';

Plugin::boot();
