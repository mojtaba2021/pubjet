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

        add_action('pubjet_sync_reportage_url', [$this, 'runSyncReportageUrl'], 15);
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
            'display' => pubjet__('every-five-minutes'),
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
     * @return void
     */
    public function runSyncReportageUrl()
    {
        $batch_size = (int) apply_filters('pubjet_sync_batch_size', 10);
        if ($batch_size <= 0) return;

        $args = [
            'post_type'             => 'post',
            'posts_per_page'        => $batch_size,
            'orderby'               => 'date',
            'order'                 => 'ASC',
            'meta_query'          => [
                [
                    'key'     => EnumPostMetakeys::FailedSyncUrl,
                    'compare' => 'EXISTS',
                ],
            ],
            'no_found_rows'         => true,
        ];

        $posts = get_posts($args);

        if (empty($posts)) return;

        foreach ($posts as $post) {
            $reportage_id = pubjet_find_reportage_id($post->ID);
            if (!$reportage_id) {
                continue;
            }
            $result = pubjet_publish_reportage($post->ID, $reportage_id);

            if (pubjet_publish_reportage_succeeded($result)) {
                delete_post_meta($post->ID, EnumPostMetakeys::FailedSyncUrl);
            }
        }
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