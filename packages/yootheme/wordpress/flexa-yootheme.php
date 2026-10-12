<?php
/**
 * Plugin Name:       Flexa for YOOtheme Pro
 * Plugin URI:        https://github.com/flexacms/sdk/tree/main/packages/yootheme
 * Description:       Use Flexa content models as dynamic content sources in the YOOtheme Pro page builder.
 * Version:           0.2.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  flexa
 * Author:            Flexa
 * Author URI:        https://github.com/flexacms
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       flexa-yootheme
 */

if (! defined('ABSPATH')) {
    exit;
}

// YOOtheme Pro boots with the theme; load the Flexa source module once it is available.
add_action('after_setup_theme', static function (): void {
    if (! class_exists(YOOtheme\Application::class, false) || ! function_exists('flexa')) {
        return;
    }

    YOOtheme\Application::getInstance()->load(__DIR__.'/modules/*/bootstrap.php');
});
