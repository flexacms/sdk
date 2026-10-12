<?php

namespace FlexaCms\YOOtheme;

// Loaded by WordPress or Joomla only.
if ( ! defined( 'ABSPATH' ) ) {
    defined('_JEXEC') or exit;
}


/**
 * The Flexa client configured by the platform plugin: "Flexa" in WordPress
 * (Settings → Flexa) or the Flexa package in Joomla (Components → Flexa).
 * The YOOtheme integration has no settings of its own.
 */
final class Connection
{
    /**
     * @return object|null A Flexa client (WordPress or Joomla build of flexacms/php).
     */
    public static function client(): ?object
    {
        if (function_exists('flexa')) {
            return flexa();
        }

        if (class_exists(\FlexaCms\Joomla\Flexa::class)) {
            return \FlexaCms\Joomla\Flexa::client();
        }

        return null;
    }
}
