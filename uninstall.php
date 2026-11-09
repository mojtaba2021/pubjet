<?php
/**
 * Pubjet Uninstall
 *
 * When "uninstall cleanup" is enabled in the settings, removes the plugin's
 * options, transients, scheduled events, database table and log directory.
 * Posts and post meta created by the plugin are kept.
 *
 * @package Pubjet\Uninstaller
 */

use triboon\pubjet\includes\enums\EnumOptions;

defined('WP_UNINSTALL_PLUGIN') || exit;

// Load Pubjet file.
include_once __DIR__ . '/pubjet.php';

global $wpdb;

$pubjet_uninstall_settings = pubjet_settings();
if (!pubjet_isset_value($pubjet_uninstall_settings[EnumOptions::UninstallCleanup])) {
    return;
}

// Scheduled events.
foreach (['pubjet_sync_reportage_url', 'pubjet_sync_reportage_url_now', 'pubjet_schedule_delete_logs', 'pubjet_check_missed_posts', 'pubjet_verify_stored_token', 'pubjet_retry_thumbnail', 'pubjet_send_status', 'pubjet_send_status_now', 'pubjet_sync_settings'] as $pubjet_hook) {
    wp_clear_scheduled_hook($pubjet_hook);
}

// Log directory (inside uploads, random name).
$pubjet_log_key = get_option('pubjet_log_dir_key');
if (!empty($pubjet_log_key) && is_string($pubjet_log_key) && preg_match('/^[A-Za-z0-9]+$/', $pubjet_log_key)) {
    $pubjet_uploads = wp_upload_dir(null, false);
    $pubjet_log_dir = trailingslashit($pubjet_uploads['basedir']) . 'pubjet-logs-' . $pubjet_log_key;
    if (is_dir($pubjet_log_dir)) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
        $pubjet_fs = new WP_Filesystem_Direct(null);
        $pubjet_fs->delete($pubjet_log_dir, true, 'd');
    }
}

// Database table and its version option.
$pubjet_table = $wpdb->prefix . 'pubjet_backlinks';
// The table name comes from $wpdb->prefix and is escaped; identifiers cannot be passed through prepare().
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query('DROP TABLE IF EXISTS `' . esc_sql($pubjet_table) . '`');
delete_option($pubjet_table . '_db_version');

// Options.
$pubjet_options = [
    'pubjet_settings',
    'pubjet_log_dir_key',
    'pubjet_cron_cleanup_done',
    'pubjet_migration_delete_first_image_v530',
    'pubjet_token_reconnect_required',
    'pubjet_token_verify_attempts',
    'pubjet_token_verify_stopped',
    'pubjet_error_reporting',
    'pubjet_status_state',
    'pubjet_flush_rewrite_rules',
    'widget_pubjet_backlinks',
    // Options of older versions.
    'pubjet_token',
    'pubjet_default_category',
    'pubjet_debug_mode',
    'pubjet_last_checking_missed_post',
    'pubjet_activation_version',
    'pubjet_copyright_status',
    'pubjet_uninstall_cleanup',
    'pubjet_nofollow',
    'pubjet_align_center_images',
    'pubjet_last_categories_sync_time',
    'triboon_token',
    'triboon_default_category',
    'triboon_debug_mode',
];
foreach ($pubjet_options as $pubjet_option) {
    delete_option($pubjet_option);
}

// Transients with a fixed name.
foreach (['pubjet_daily_plugin_status_check', 'pubjet_log_write_failed', 'pubjet_status_ping_logged', 'pubjet_cron_check', 'pubjet_register_cron_lock', 'pubjet_reportage_count', 'pubjet_publish_future_backlinks'] as $pubjet_transient) {
    delete_transient($pubjet_transient);
}
delete_site_transient('pubjet_update_plugin_info');

// Options and transients with a dynamic suffix (notice, post and reportage IDs).
$pubjet_prefixes = [
    'pubjet_permanent_hide_admin_notice_',
    '_transient_pubjet_remind_admin_notice_',
    '_transient_timeout_pubjet_remind_admin_notice_',
    '_transient_pubjet_cdn_content_',
    '_transient_timeout_pubjet_cdn_content_',
    '_transient_pubjet_meta_',
    '_transient_timeout_pubjet_meta_',
    '_transient_pubjet_reportage_creation_lock_',
    '_transient_timeout_pubjet_reportage_creation_lock_',
];
foreach ($pubjet_prefixes as $pubjet_prefix) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like($pubjet_prefix) . '%'));
}

wp_cache_flush();
