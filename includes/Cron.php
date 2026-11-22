<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\enums\EnumPostMetakeys;


defined('ABSPATH') || exit;

class Cron extends Singleton
{

    /**
     * @return void
     */
    public function init()
    {
        add_filter('cron_schedules', [$this, 'registerInterval'], 10);
        // Events are registered on activation and update; the dashboard only restores a missing one
        add_action('admin_init', [$this, 'ensureCronEvents'], 20);
        // Without a working WP-Cron the publish links are sent from the administrator dashboard
        add_action('admin_init', [$this, 'sendPublishLinksFromDashboard'], 25);

        add_action('pubjet_sync_reportage_url', [$this, 'runSyncReportageUrl'], 15);
        add_action('pubjet_sync_reportage_url_now', [$this, 'runSyncReportageUrl'], 15);
        add_action('pubjet_schedule_delete_logs', [$this, 'deletePubjetLogs'], 15);
        add_action('pubjet_check_missed_posts', [$this, 'publishMissedSchedulePosts']);
        add_action('pubjet_verify_stored_token', [$this, 'verifyStoredToken'], 15);
        add_action('pubjet_send_status', [$this, 'sendStatusIfDue'], 15);
        add_action('pubjet_send_status_now', [$this, 'sendStatusNow'], 15);
        add_action('pubjet_sync_settings', 'pubjet_sync_settings', 15);
    }

    /**
     * Schedule a background check of the stored token.
     *
     * @return void
     * @since  5.4.9
     */
    public static function scheduleStoredTokenCheck()
    {
        if (empty(pubjet_token())) {
            return;
        }
        delete_option('pubjet_token_verify_attempts');
        if (!wp_next_scheduled('pubjet_verify_stored_token')) {
            wp_schedule_single_event(time() + 5 * MINUTE_IN_SECONDS, 'pubjet_verify_stored_token');
        }
    }

    /**
     * Check the stored token with Triboon. The token is removed only when Triboon
     * explicitly rejects it (JSON 403 with "detail", or is_valid false); any other
     * response or connection error keeps the token and the check is retried later.
     *
     * @return void
     * @since  5.4.9
     */
    public function verifyStoredToken()
    {
        $token = pubjet_token();
        if (empty($token)) {
            delete_option('pubjet_token_verify_attempts');
            return;
        }

        $result = pubjet_find_token_details(trim($token));

        if (!is_wp_error($result)) {
            delete_option('pubjet_token_verify_attempts');
            return;
        }

        $error_data = $result->get_error_data();
        if ('invalid-token' === $result->get_error_code() && !empty($error_data['rejected'])) {
            global $pubjet_settings;
            $pubjet_settings[EnumOptions::Token] = '';
            pubjet_update_setting(EnumOptions::Token, '');
            update_option('pubjet_token_reconnect_required', 'yes', false);
            delete_option('pubjet_token_verify_attempts');
            pubjet_log('Stored token check: token was rejected, reconnect required.');
            return;
        }

        $attempts     = (int) get_option('pubjet_token_verify_attempts', 0) + 1;
        $max_attempts = (int) apply_filters('pubjet_token_verify_max_attempts', 24);
        if ($attempts >= $max_attempts) {
            delete_option('pubjet_token_verify_attempts');
            update_option('pubjet_token_verify_stopped', time(), false);
            pubjet_log('Stored token check: stopped after ' . $attempts . ' failed attempts, token kept. Last error: ' . $result->get_error_message());
            return;
        }

        update_option('pubjet_token_verify_attempts', $attempts, false);
        wp_schedule_single_event(time() + HOUR_IN_SECONDS, 'pubjet_verify_stored_token');
    }

    /**
     * @return void
     */
    public function registerInterval($schedules)
    {

        $schedules['every_five_minutes'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            // Same label as before (the key has no translation); pubjet__() is not called because events
            // are also scheduled on plugins_loaded (update), before translations may be loaded
            'display' => 'every-five-minutes',
        ];

        return $schedules;
    }

    /**
     * Recurring Pubjet events and their schedules.
     *
     * @return array
     */
    public static function cronHooks()
    {
        $hooks = [
            'pubjet_sync_reportage_url'   => 'every_five_minutes',
            'pubjet_schedule_delete_logs' => 'daily',
            'pubjet_check_missed_posts'   => 'every_five_minutes',
            'pubjet_send_status'          => 'hourly',
        ];

        return (array) apply_filters('pubjet_cron_hooks', $hooks);
    }

    /**
     * Register the recurring events once (on activation and update): an event
     * registered more than once is removed and registered again, a missing one is added.
     *
     * @return void
     * @since  5.4.9
     */
    public static function scheduleAll()
    {
        // On activation plugins_loaded has already run: make sure the custom interval is registered
        self::getInstance();

        $hooks = self::cronHooks();
        $crons = _get_cron_array();
        $crons = is_array($crons) ? $crons : [];

        foreach ($hooks as $hook => $schedule) {
            $count = 0;
            foreach ($crons as $events) {
                if (isset($events[$hook]) && is_array($events[$hook])) {
                    $count += count($events[$hook]);
                }
            }
            if ($count > 1) {
                wp_clear_scheduled_hook($hook);
            }
            if (!wp_next_scheduled($hook)) {
                wp_schedule_event(time(), $schedule, $hook);
            }
        }
        do_action('pubjet_after_register_cron', $hooks);
    }

    /**
     * Restore a missing recurring event from the dashboard: only for administrators,
     * not in AJAX or cron requests, and at most once an hour. Nothing is written
     * when no event is missing, apart from the hourly marker.
     *
     * @return void
     * @since  5.4.9
     */
    public function ensureCronEvents()
    {
        if (wp_doing_ajax() || wp_doing_cron() || !current_user_can('manage_options')) {
            return;
        }
        if (get_transient('pubjet_cron_check')) {
            return;
        }
        set_transient('pubjet_cron_check', 1, HOUR_IN_SECONDS);

        foreach (self::cronHooks() as $hook => $schedule) {
            if (!wp_next_scheduled($hook)) {
                wp_schedule_event(time(), $schedule, $hook);
            }
        }
    }

    /**
     * @return void
     * @deprecated 5.4.9 Use Cron::scheduleAll(); no longer runs on page loads.
     */
    public function registerCron()
    {
        self::scheduleAll();
    }

    /**
     * Cron: send the publish links that are waiting (see syncPublishLinks()).
     *
     * @return void
     */
    public function runSyncReportageUrl()
    {
        $this->syncPublishLinks((int) apply_filters('pubjet_sync_batch_size', 10));
    }

    /**
     * Send the publish links of published reportages marked for it. One run at a time; a run
     * stops at the first connection error, the rest waits for the next run. The mark is removed
     * only when Triboon accepted the link. After 3 failed tries of a post, one warning is reported.
     *
     * @param int $limit Number of posts in this run.
     *
     * @return int Number of links sent.
     * @since  5.4.9
     */
    public function syncPublishLinks($limit)
    {
        $limit = (int) $limit;
        if ($limit <= 0) {
            return 0;
        }
        if (get_transient('pubjet_sync_url_lock')) {
            pubjet_log('[info] Publish link sending is already running');
            return 0;
        }
        set_transient('pubjet_sync_url_lock', time(), 5 * MINUTE_IN_SECONDS);

        $sent = 0;
        try {
            $post_ids = get_posts([
                'post_type'        => pubjet_post_type(),
                'post_status'      => 'publish',
                'posts_per_page'   => $limit,
                'orderby'          => 'date',
                'order'            => 'ASC',
                'fields'           => 'ids',
                'meta_key'         => EnumPostMetakeys::FailedSyncUrl, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- cron, small batch
                'no_found_rows'    => true,
            ]);

            foreach ($post_ids as $post_id) {
                $reportage_id = pubjet_find_reportage_id($post_id);
                if (!$reportage_id) {
                    // Not a reportage, nothing can be sent
                    delete_post_meta($post_id, EnumPostMetakeys::FailedSyncUrl);
                    continue;
                }

                $result = pubjet_publish_reportage($post_id, $reportage_id);
                if (pubjet_publish_reportage_succeeded($result)) {
                    delete_post_meta($post_id, EnumPostMetakeys::FailedSyncUrl);
                    delete_post_meta($post_id, EnumPostMetakeys::SyncUrlAttempts);
                    pubjet_log('Publish link of reportage ' . $reportage_id . ' (post ' . $post_id . ') sent');
                    $sent++;
                    continue;
                }

                $code     = is_array($result) && isset($result['code']) ? (int) $result['code'] : 0;
                $error    = is_array($result) && !empty($result['error']) ? (string) $result['error'] : '';
                $attempts = (int) get_post_meta($post_id, EnumPostMetakeys::SyncUrlAttempts, true) + 1;
                update_post_meta($post_id, EnumPostMetakeys::SyncUrlAttempts, $attempts);
                pubjet_log([
                    'Error'        => 'Publish link not sent',
                    'reportage_id' => $reportage_id,
                    'post_id'      => $post_id,
                    'code'         => $code,
                    'error'        => $error,
                    'attempts'     => $attempts,
                ]);
                if (3 === $attempts) {
                    pubjet_log_sentry(
                        sprintf('Publish link of reportage %d (post %d) not sent after %d tries (HTTP %d)', $reportage_id, $post_id, $attempts, $code),
                        [],
                        'warning',
                        0 === $code ? ['cause' => pubjet_connection_error_cause($error)] : []
                    );
                }
                if (0 === $code) {
                    break;
                }
            }
        } finally {
            delete_transient('pubjet_sync_url_lock');
        }

        return $sent;
    }

    /**
     * Whether WP-Cron does not run: turned off on page loads, or the publish link events are
     * more than 15 minutes late. Reads the cron option only (no query).
     *
     * @return bool
     * @since  5.4.9
     */
    public static function cronLooksStopped()
    {
        if (pubjet_wp_cron_disabled()) {
            return true;
        }
        $late = time() - 15 * MINUTE_IN_SECONDS;
        foreach (['pubjet_sync_reportage_url', 'pubjet_sync_reportage_url_now'] as $hook) {
            $next = wp_next_scheduled($hook);
            if ($next && $next < $late) {
                return true;
            }
        }
        return false;
    }

    /**
     * Send waiting publish links from the administrator dashboard when WP-Cron does not run.
     * Only for administrators, not in AJAX or cron requests, at most once every 5 minutes,
     * and only when a post waits for its link. Never on visitor pages.
     *
     * @return void
     * @since  5.4.9
     */
    public function sendPublishLinksFromDashboard()
    {
        if (wp_doing_ajax() || wp_doing_cron() || !current_user_can('manage_options')) {
            return;
        }
        if (!self::cronLooksStopped() || get_transient('pubjet_sync_fallback_check')) {
            return;
        }
        /**
         * Seconds between two checks from the dashboard.
         *
         * @since 5.4.9
         */
        $interval = max(MINUTE_IN_SECONDS, (int) apply_filters('pubjet_sync_fallback_interval', 5 * MINUTE_IN_SECONDS));
        set_transient('pubjet_sync_fallback_check', 1, $interval);

        if (!pubjet_has_pending_publish_link()) {
            return;
        }
        pubjet_log('[info] WP-Cron does not run, publish links are sent from the dashboard');
        $this->syncPublishLinks((int) apply_filters('pubjet_sync_fallback_batch_size', 3));
    }

    /**
     * @return void
     */
    public function deletePubjetLogs()
    {
        pubjet_rotate_log_file(pubjet_debug_dir());

    }

    public function publishMissedSchedulePosts()
    {
        global $pubjet_settings;

        $last_check = pubjet_isset_value($pubjet_settings[EnumOptions::LastCheckingMissedPosts] , 0);
        if (pubjet_now_ts() - $last_check < 60) {
            return;
        }

        pubjet_update_setting(EnumOptions::LastCheckingMissedPosts, pubjet_now_ts());

        $restApi = \triboon\pubjet\includes\RestApi::getInstance();
        $restApi->processCheckMissedReportage();
    }


    public function removePubjetCrons(array $hooks)
    {
        $crons = _get_cron_array();
        if (empty($crons) || !is_array($crons)) return false;

        $changed = false;
        foreach ($crons as $timestamp => $events) {
            foreach ($hooks as $hook) {
                if (isset($events[$hook])) {
                    unset($crons[$timestamp][$hook]);
                    $changed = true;
                }
            }
            if (empty($crons[$timestamp])) {
                unset($crons[$timestamp]);
            }
        }

        if ($changed) _set_cron_array($crons);

        return $changed;
    }

    /**
     * Hourly: send the plugin status when it is due (see pubjet_send_plugin_status_if_due()).
     *
     * @return void
     * @since  5.4.9
     */
    public function sendStatusIfDue()
    {
        pubjet_send_plugin_status_if_due();
    }

    /**
     * Send the plugin status now (after settings were saved, the plugin was activated or updated).
     *
     * @return void
     * @since  5.4.9
     */
    public function sendStatusNow()
    {
        pubjet_send_plugin_status_if_due(true);
    }

    public function deactivateAllCronJobs()
    {
        $hooks = apply_filters(
            'pubjet_cron_cleanup_hooks',
            [
                'pubjet_sync_reportage_url',
                'pubjet_sync_reportage_url_now',
                'pubjet_schedule_delete_logs',
                'pubjet_check_missed_posts',
                'pubjet_verify_stored_token',
                'pubjet_retry_thumbnail',
                'pubjet_send_status',
                'pubjet_send_status_now',
                'pubjet_sync_settings',
            ]
        );

        $this->removePubjetCrons($hooks);
    }



}