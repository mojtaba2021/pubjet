<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\enums\EnumPostMetakeys;
use triboon\pubjet\includes\enums\EnumPostTypes;

defined('ABSPATH') || exit;

class Cron extends Singleton
{

    /**
     * @return void
     */
    public function init()
    {
        add_filter('cron_schedules', [$this, 'registerInterval'], 10);
        add_action('wp', [$this, 'registerCron'], 15);
        add_action('pubjet_sync_reportage_url', [$this, 'runSyncReportageUrl'], 15);
        add_action('pubjet_schedule_delete_logs', [$this, 'deletePubjetLogs'], 15);
        add_action('pubjet_check_missed_posts', [$this, 'publishMissedSchedulePosts']);
    }

    /**
     * @return void
     */
    public function registerInterval($schedules)
    {
        $schedules['every_minute'] = [
            'interval' => MINUTE_IN_SECONDS,
            'display' => pubjet__('every-minute'),
        ];

        $schedules['every_five_minutes'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => pubjet__('every-five-minutes'),
        ];

        $schedules['daily'] = [
            'interval' => DAY_IN_SECONDS,
            'display' => pubjet__('every-day'),
        ];
        return $schedules;
    }

    /**
     * @return void
     */
    public function registerCron()
    {
        if (!wp_next_scheduled('pubjet_sync_reportage_url')) {
            wp_schedule_event(time(), 'every_minute', 'pubjet_sync_reportage_url');
        }
        if (!wp_next_scheduled('pubjet_clear_logs')) {
            wp_schedule_event(time(), 'daily', 'pubjet_schedule_delete_logs');
        }
        if (!wp_next_scheduled('pubjet_check_missed_posts')) {
            wp_schedule_event(time(), 'every_five_minutes', 'pubjet_check_missed_posts');
        }
    }

    /**
     * @return void
     */
    public function runSyncReportageUrl()
    {
        $batch_size = apply_filters('pubjet_sync_batch_size', 10);

        $args = [
            'post_type' => EnumPostTypes::Post,
            'meta_key' => EnumPostMetakeys::FailedSyncUrl,
            'posts_per_page' => $batch_size,
            'orderby' => 'date',
            'order' => 'ASC'
        ];

        $posts = get_posts($args);

        if (empty($posts)) return;

        foreach ($posts as $post) {
            $reportage_id = pubjet_find_reportage_id($post->ID);
            if (!$reportage_id) {
                continue;
            }
            $result = pubjet_publish_reportage($post->ID, $reportage_id);
            if (isset($result['code']) && in_array($result['code'], [200, 429])) {
                delete_post_meta($post->ID, EnumPostMetakeys::FailedSyncUrl);
            }
        }
    }

    /**
     * @return void
     */
    public function deletePubjetLogs()
    {
        $log_file = pubjet_debug_dir();
        $max_size = apply_filters('pubjet_max_log_size', 100 * 1024 * 1024);
        if (!file_exists($log_file)) return;

        $file_size = filesize($log_file);
        if ($file_size <= $max_size) return;

        if (is_writable(dirname($log_file)) && wp_delete_file($log_file)) {
            error_log('Pubjet Cron: Log file cleared automatically: ' . $log_file);
        } else {
            error_log('Pubjet Cron: Failed to delete the log file: ' . $log_file);
        }

    }

    public function publishMissedSchedulePosts()
    {
        global $pubjet_settings;

        $last_check = pubjet_isset_value($pubjet_settings[EnumOptions::LastCheckingMissedPosts]);
        if (pubjet_now_ts() - $last_check < 60) {
            return;
        }

        pubjet_update_setting(EnumOptions::LastCheckingMissedPosts, pubjet_now_ts());

        $restApi = \triboon\pubjet\includes\RestApi::getInstance();
        $restApi->processCheckMissedReportage();
    }

    public function deactivateAllCronJobs()
    {
        $cron_hooks = [
            'pubjet_sync_reportage_url',
            'pubjet_clear_logs',
            'pubjet_check_missed_posts',
        ];

        foreach ($cron_hooks as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        }
    }


}