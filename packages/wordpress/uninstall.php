<?php

/**
 * Remove the plugin's settings and cached responses.
 */
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('flexa_settings');

global $wpdb;

// Cached Flexa responses are transients named flexa_*.
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like('_transient_flexa_').'%',
        $wpdb->esc_like('_transient_timeout_flexa_').'%',
    ),
);
