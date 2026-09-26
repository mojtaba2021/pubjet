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

        $schedules['every_five_minutes'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => pubjet__('every-five-minutes'),
        ];

        return $schedules;
    }

    /**
     * @return void
     */
    public function registerCron()
    {
        if (get_transient('pubjet_register_cron_lock')) {
            return;
        }
        set_transient('pubjet_register_cron_lock', 1, 30);

        $hooks = [
            'pubjet_sync_reportage_url'   => 'every_five_minutes',
            'pubjet_schedule_delete_logs' => 'daily',
            'pubjet_check_missed_posts'   => 'every_five_minutes',
        ];

        $hooks = apply_filters('pubjet_cron_hooks', $hooks);

        foreach ($hooks as $hook => $schedule) {
            if (!wp_next_scheduled($hook)) {
                wp_schedule_event(time(), $schedule, $hook);
            }
        }
        do_action('pubjet_after_register_cron', $hooks);
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
            'suppress_filters'      => true,
        ];

        $posts = get_posts($args);

        if (empty($posts)) return;

        foreach ($posts as $post) {
            $reportage_id = pubjet_find_reportage_id($post->ID);
            if (!$reportage_id) {
                continue;
            }
            $result = pubjet_publish_reportage($post->ID, $reportage_id);

            // 200 = success | 429 = already registered (idempotent)
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
        if (!file_exists($log_file)) return;

        $max_size = (int) apply_filters('pubjet_max_log_size', 100 * 1024 * 1024);
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

    public function deactivateAllCronJobs()
    {
        $hooks = apply_filters(
            'pubjet_cron_cleanup_hooks',
            [
                'pubjet_sync_reportage_url',
                'pubjet_schedule_delete_logs',
                'pubjet_check_missed_posts',
            ]
        );

        $this->removePubjetCrons($hooks);
    }



}