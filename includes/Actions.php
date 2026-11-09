<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumActions;
use triboon\pubjet\includes\enums\EnumBacklinkStatus;
use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\enums\EnumPostTypes;
use triboon\pubjet\includes\enums\EnumTransients;
use triboon\pubjet\includes\traits\Utils;
use triboon\pubjet\includes\widgets\Backlinks;

defined('ABSPATH') || exit;

class Actions extends Singleton
{

    use Utils;

    /**
     * @return void
     */
    public function init()
    {
        add_action("admin_menu", [$this, "registerMenu"], 15);
        add_action("admin_head", [$this, "pluginFont"], 15);
        add_action("wp_head", [$this, "alignReportageImagesCenter"], 15);

        add_action('pubjet_new_reportage', [$this, 'reportageCustomFields'], 15, 2);
        add_action('upgrader_process_complete', [$this, 'syncSettingsAfterUpdate'], 15, 2);
        // add_action('init', [$this, 'publishFutureBacklinks'], 25);
        // Change Reportage Author
        add_action('pubjet_new_reportage', [$this, 'changeReportageAuthor'], 15, 2);
        // Create database tables
        add_action('admin_init', [$this, 'createDbTables'], 15);
        // Register Widgets
        add_action('widgets_init', [$this, 'registerWidgets'], 15);
        // Elementor Widgets
        add_action('elementor/widgets/widgets_registered', [$this, 'registerElementorWidgets'], 15);
        // Process reportage by query string
        add_action('init', [$this, 'createReportageByActionQueryString'], 15);
        add_action('init', [$this, 'createBacklinkByActionQueryString'], 15);

        // get pubjet status by action query string
        add_action('init', [$this, 'showPluginStatus'], 15);

        add_action('init', [$this, 'showSiteCategories'], 15);
        add_action('pubjet_create_reportage', [$this, 'processCreateReportage'], 15);
        // Files of the Triboon CDN are downloaded without automatic decoding
        add_action('http_api_curl', [ReportagePost::class, 'disableCurlDecodingForCdn'], 10, 3);
        // Try again to set a missing featured image
        add_action('pubjet_retry_thumbnail', [ReportagePost::class, 'retry_thumbnail'], 10, 2);
        add_action('save_post', [$this, 'savePubjetMetaData']);
        add_action('wp_head', [$this, 'addMetaDataToFrontPages'], 15);


//        add_action('pre_post_update', [$this, 'updateOldReportagePermalink'] );
        add_action('wp_after_insert_post', [$this, 'sendPermalinkUpdateToApi'], 15, 4);

        add_action('save_post', [$this, 'pubjet_reportage_count_clear_cache']);
        add_action('delete_post', [$this, 'pubjet_reportage_count_clear_cache']);
        add_action('updated_postmeta', [$this, 'pubjet_clear_cache_on_meta_update'], 10, 3);
        add_action('added_postmeta', [$this, 'pubjet_clear_cache_on_meta_update'], 10, 3);
        add_action('deleted_postmeta', [$this, 'pubjet_clear_cache_on_meta_delete'], 10, 1);

        add_action('save_post', [$this, 'clear_reportage_cdn_cache'], 10, 3);
        add_action('save_post', [$this, 'save_cdn_checkbox'],9);
        add_action('save_post', [$this, 'clearMetaCacheOnSave'], 10, 3);

        add_action('pubjet_after_register_cron',[$this, 'cleanupPubjetRegisteredCrons'], 99, 1);


    }

    /**
     * @return void
     */
    public function showPluginStatus()
    {
        $action = $this->get('action');
        if (EnumActions::PubjetStatus !== $action) {
            return;
        }
        // Check token
        $check_token = pubjet_is_request_token_valid();
        if (is_wp_error($check_token)) {
            $this->error($check_token->get_error_message(), 403);
        }
        $this->success(pubjet_plugin_status());
    }

    /**
     * @return void
     */
    public function showSiteCategories()
    {
        $action = $this->get('action');
        if (EnumActions::PubjetCategories !== $action) {
            return;
        }
        // Check token
        $check_token = pubjet_is_request_token_valid();
        if (is_wp_error($check_token)) {
            $this->error($check_token->get_error_message(), 403);
        }
        $this->success(pubjet_find_wp_categories(0, false));
    }


    /**
     * @return void
     */
    public function createBacklinkByActionQueryString()
    {
        $action = $this->get('action');
        if (EnumActions::CreateBacklink !== $action) {
            return;
        }
        // Check token
        $check_token = pubjet_is_request_token_valid();
        if (is_wp_error($check_token)) {
            $this->error($check_token->get_error_message(), 403);
        }
        try {
            // Get backlink data
            $backlink_data = file_get_contents("php://input");
            $backlink_data = json_decode($backlink_data);
            if (empty($backlink_data)) {
                $this->error(pubjet__('missing-params'), 400);
            }
            /**
             * Hooked [Backlink, 'createBacklink'] - 15
             *
             * @since 4.0.0
             */
            do_action('pubjet_create_backlink', $backlink_data);
        } catch (\Exception $ex) {
            $this->error($ex->getMessage(), 500);
        }
    }

    /**
     * @return void
     */
    public function createReportageByActionQueryString()
    {
        $action = $this->get('action');
        if (EnumActions::CreateReportage !== $action) {
            return;
        }
        // Check token
        $check_token = pubjet_is_request_token_valid();
        if (is_wp_error($check_token)) {
            $this->error($check_token->get_error_message(), 403);
        }
        try {
            // Get reportage data
            $reportage_data = file_get_contents("php://input");

            $reportage_data = json_decode($reportage_data, true);
            $reportage_data = (object)$reportage_data;
            

            pubjet_log(['Query String Reportage : ' => $reportage_data]);

            if (empty($reportage_data)) {
                $this->error(pubjet__('missing-params'), 400);
            }
            /**
             * Hooked [Actions, 'processCreateReportage'] - 15
             *
             * @since 4.0.0
             */
            do_action('pubjet_create_reportage', $reportage_data);
        } catch (\Exception $ex) {
            $this->error($ex->getMessage(), 500);
        }
    }

    /**
     * @return void
     * @throws \Exception
     * @since 4.0.0
     */
    public function processCreateReportage($reportage)
    {
        // The id and title are read from the request, not from the result of the insert
        $reportage    = ReportagePost::normalize($reportage);
        $reportage_id = intval($reportage->id);

        $log_data = [
            'reportage_id' => $reportage_id,
            'reportage_title' => $reportage->title,
        ];

        if ($reportage_id <= 0) {
            pubjet_log(['Error' => 'Reportage request without an id'] + $log_data);
            $this->error(pubjet__('missing-params'), 400);
            return;
        }

        $lock_key = 'pubjet_reportage_creation_lock_' . $reportage_id;
        $lock_time = 30;

        if (get_transient($lock_key)) {
            $duplicate_error_message = 'در حال پردازش درخواست مشابه. لطفاً منتظر بمانید.';
            // Expected when the panel sends the same reportage again: local log only, not an error report
            pubjet_log('[info] Duplicate request while reportage ' . $reportage_id . ' is being processed');
            $this->error($duplicate_error_message, 429);
            return;
        }

        // All error reports of this reportage are sent as one report.
        pubjet_sentry_batch_start('Reportage ' . $reportage_id);

        set_transient($lock_key, time(), $lock_time);
        // The publish link is sent from cron, the response does not wait for it
        ReportagePost::$queue_publish_link = true;
        try {
            $wp_post_id = ReportagePost::insert($reportage);
        } catch (\Exception $e) {
            $wp_post_id = new \WP_Error('insert-exception', $e->getMessage());
        }
        ReportagePost::$queue_publish_link = false;
        // success() and error() end the request, so the lock is released first
        delete_transient($lock_key);

        if (!$wp_post_id || is_wp_error($wp_post_id)) {
            $error = is_wp_error($wp_post_id) ? $wp_post_id : new \WP_Error('insert-reportage', 'خطای نامشخصی در ثبت نوشته رپورتاژ.');
            $code  = (string) $error->get_error_code();
            pubjet_log(['Error' => $error->get_error_message(), 'code' => $code] + $log_data);
            pubjet_log_sentry(sprintf('Create failed (%s): %s', $code, $error->get_error_message()));
            if ('reportage-exists' === $code) {
                $this->queuePublishLinkOfDuplicate($reportage_id);
            }
            // 409 for a duplicate reportage, 400 for other errors (unchanged)
            $this->error($error->get_error_message(), 'reportage-exists' === $code ? 409 : 400);
            return;
        }

        pubjet_log($reportage->wp_post_id ? 'Post updated: ' . $reportage->wp_post_id : 'Post created: ' . $wp_post_id);

        // A published reportage queued its publish link: start cron now instead of waiting for a visit
        if (wp_next_scheduled('pubjet_sync_reportage_url_now')) {
            pubjet_run_cron_now();
        }

        $this->success([
            'postId' => $wp_post_id,
            'postStatus' => get_post_status($wp_post_id) ?: 'Unknown',
            'reportageId' => $reportage->id,
        ]);
    }

    /**
     * Triboon sent a reportage that is already published here, so its publish link is sent
     * again from cron. Scheduled, draft and trashed posts are left alone. The response stays the same.
     *
     * @param int $reportage_id
     *
     * @return void
     * @since  5.4.9
     */
    private function queuePublishLinkOfDuplicate($reportage_id)
    {
        $post_id = (int) ReportagePost::reportage_exists($reportage_id);
        if ($post_id <= 0 || 'publish' !== get_post_status($post_id) || pubjet_post_type() !== get_post_type($post_id)) {
            return;
        }
        pubjet_log('[info] Reportage ' . $reportage_id . ' is already published as post ' . $post_id . ', its publish link is sent again');
        pubjet_queue_publish_link($post_id);
        pubjet_run_cron_now();
    }

    /**
     * @return void
     */
    public function registerElementorWidgets()
    {
        /**
         * The pubjet_elementor_widgets_instances filter.
         *
         * @since 1.0.0
         */
        $instances = apply_filters('pubjet_elementor_widgets_instances', [
            new \triboon\pubjet\includes\elementor\Backlinks(),
        ]);
        foreach ($instances as $instance) {
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type($instance);
        }
    }

    /**
     * @return void
     */
    public function registerWidgets()
    {
        /**
         * The pubjet_widgets_classes filter.
         *
         * @since 1.0.0
         */
        $instances = apply_filters('pubjet_widgets_instances', [
            new Backlinks(),
        ]);
        foreach ($instances as $instance) {
            register_widget($instance);
        }
    }

    /**
     * @return void
     */
    public function createDbTables()
    {
        /**
         * The pubjet_database_tables filter.
         *
         * @since 1.0.0
         */
        $tables = DBLoader::getInstance()->getTables();
        foreach ($tables as $table) {
            $table->createTable();
        }
    }

    /**
     * @return void
     */
    public function changeReportageAuthor($reportage_post_id, $reportage)
    {


        global $pubjet_settings;
        $status = pubjet_isset_value($pubjet_settings['repauthor']['status']);
        $default_author_id = pubjet_isset_value($pubjet_settings['repauthor']['authorId']);
        $authorCategory = pubjet_isset_value($pubjet_settings['repauthor']['authorCategory'], []);

        if (!$default_author_id || !$status) {
            return;
        }
        pubjet_log("======= Change Reportage Author =======");

        $reportage_post = get_post($reportage_post_id);
        $reportage_post->post_author = $default_author_id;

        $reportage_category = wp_get_post_categories($reportage_post_id);

        pubjet_log(["Reportage Category : " => $reportage_category]);

        pubjet_log(["Author Category list: " => $authorCategory]);

        $matchingCategories = array_values(array_filter($authorCategory, function ($item) use ($reportage_category) {
            return in_array($item['category'], $reportage_category);
        }));

        pubjet_log(["Matched Categories : " => $matchingCategories]);

        $author = pubjet_isset_value($matchingCategories[0]['author'], $default_author_id);
        if (count($matchingCategories) > 0 && $author) {
            $reportage_post->post_author = $author;
        }

        pubjet_log(["Reportage Author: " => $author]);

        wp_update_post($reportage_post);

        pubjet_log("======= Reportage Author Updated =======");
    }

    /**
     * @return void
     */
    public function publishFutureBacklinks()
    {
        // بررسی اگر transient وجود دارد یا نه
        if (false === get_transient(EnumTransients::PublishFutureBacklinks)) {
            // ارسال ورژن افزونه به API
            $futures_backlinks = pubjet_db()->backlinks->findFutures();
            pubjet_log($futures_backlinks);
            if ($futures_backlinks && is_array($futures_backlinks)) {
                foreach ($futures_backlinks as $row_item) {
                    // Notify Triboon
                    pubjet_publish_backlink_request($row_item->backlink_id);
                    // Update Database
                    pubjet_db()->backlinks->update($row_item->id, [
                        'status' => EnumBacklinkStatus::Publish,
                    ]);
                }
            }
            // تنظیم transient برای 1 دقیقه
            set_transient(EnumTransients::PublishFutureBacklinks, true, 60);
        }
    }

    /**
     * @return void
     */
    public function syncSettingsAfterUpdate($upgrader_object, $options)
    {
        if (in_array($options['action'], ['update', 'install'], true)
            && $options['type'] === 'plugin'
            && isset($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {
                if ($plugin === PUBJET_PLUGIN_BASE) {
                    pubjet_schedule_sync_settings();
                    pubjet_delete_first_image_option();
                }
            }
        }
    }

    /**
     * @return void
     */
    public function reportageCustomFields($post_id, $reportage_data)
    {
        global $pubjet_settings;
        $status = pubjet_isset_value($pubjet_settings['metakeys']['status']);
        if (!$status) {
            return;
        }
        $items = pubjet_isset_value($pubjet_settings['metakeys']['items']);
        if (!$items || !is_array($items)) {
            return;
        }
        foreach ($items as $item) {
            if (empty(trim($item['name']))) {
                continue;
            }
            update_post_meta($post_id, $item['name'], $item['value']);
        }
    }

    /**
     * @return void
     */
    public function alignReportageImagesCenter()
    {
        global $pubjet_settings;
        if (!is_singular('post')) {
            return;
        }
        global $post;
        if ($post->post_type !== EnumPostTypes::Post || !pubjet_is_reportage($post->ID)) {
            return;
        }
        $status = pubjet_isset_value($pubjet_settings[EnumOptions::AlignCenterImages]);
        if (!$status) {
            return;
        }
        ?>
        <style>
            .pj-rpt img {
                display: block !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }
        </style>
        <?php
    }

    /**
     * @return void
     */
    public function pluginFont()
    {
        ?>
        <style>
            @font-face {
                font-family: 'Vazirmatn';
                src: url(<?php echo esc_url(PUBJET_ASSETS_URL . 'fonts/Vazirmatn-wght.woff2'); ?>) format('woff2 supports variations'),
                url(<?php echo esc_url(PUBJET_ASSETS_URL . 'fonts/Vazirmatn-wght.woff2'); ?>) format('woff2-variations');
                font-weight: 100 900;
                font-style: normal;
                font-display: swap;
            }

            .mF0ELrBPqJ9R6bt1N3mw *,
            .pubjet-notice *,
            .ant-tooltip-inner,
            .ant-select-dropdown div,
            .ant-modal *,
            .ant-popover *,
            .ant-message *,
            #pubjet-reportage-data *,
            #pubjet-reportage-options *,
            #pubjet-page-settings-content noscript {
                font-family: 'Vazirmatn';
            }
        </style>
        <?php
    }

    /**
     * @return void
     */
    public function publishMissedSchedulePosts()
    {
        global $pubjet_settings;

        $last_check = pubjet_isset_value($pubjet_settings[EnumOptions::LastCheckingMissedPosts]);

        if (pubjet_now_ts() - $last_check < 60) {
            return;
        }

        pubjet_update_setting(EnumOptions::LastCheckingMissedPosts, pubjet_now_ts());
        wp_remote_post(home_url(rest_get_url_prefix() . '/pubjet/v1/check-missed-reportage'), [
            'headers' => [
                'Authorization' => pubjet_token(),
                'Content-Type' => 'application/json',
            ],
            'data_format' => 'body',
            'method' => 'POST',
            'body' => wp_json_encode([]),
        ]);
    }

    /**
     * @return void
     */
    public function registerMenu()
    {
        add_menu_page(
            pubjet__('pubjet'),
            pubjet__('pubjet'),
            'manage_options',
            'pubjet_settings',
            [$this, 'pubjetSettingsPageCallback'],
            PUBJET_IMAGES_URL . 'pubjet-icon.svg',
            100
        );
    }

    /**
     * @return void
     */
    public function pubjetSettingsPageCallback()
    {
        pubjet_template('settings');
    }

    /**
     * @param $post_id
     * @return void
     */
    public function savePubjetMetaData($post_id)
    {
        if (!isset($_POST['pubjet_reportage_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pubjet_reportage_nonce'])), 'pubjet_reportage_nonce_action')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (pubjet_is_reportage($post_id)) {
            if (isset($_POST['pubjet_meta_title'])) {
                update_post_meta($post_id, 'pubjet_meta_title', sanitize_text_field(wp_unslash($_POST['pubjet_meta_title'])));
            }

            if (isset($_POST['pubjet_meta_description'])) {
                update_post_meta($post_id, 'pubjet_meta_description', sanitize_textarea_field(wp_unslash($_POST['pubjet_meta_description'])));
            }
        }
    }

    /**
     * @return void
     */
    public function addMetaDataToFrontPages()
    {
        if (!is_single()) return;

        global $post;
        if (pubjet_is_reportage($post->ID)) {
            if (!empty($post->pubjet_meta_title)) {
                echo '<meta name="title" content="' . esc_attr($post->pubjet_meta_title) . '" />' . "\n";
            }
            if (!empty($post->pubjet_meta_description)) {
                echo '<meta name="description" content="' . esc_attr($post->pubjet_meta_description) . '" />' . "\n";
            }
        }
    }


    /**
     * @param $post_id
     * @param $post
     * @param $update
     * @param $post_before
     * @return void
     */
    public function sendPermalinkUpdateToApi($post_id, $post, $update, $post_before)
    {
        if (!$update || $post->post_type !== 'post' || $post->post_status !== 'publish') return;

        $reportage_id = pubjet_find_reportage_id($post_id);
        if (empty($reportage_id)) return;

        $old_slug = $post_before->post_name;
        $new_slug = $post->post_name;

        if ($old_slug !== $new_slug) {
            $new_url = get_permalink($post_id);
            pubjet_send_permalink_change_to_api($post_id, $reportage_id, $new_url);
        }
    }

    public function pubjet_reportage_count_clear_cache()
    {
        delete_transient('pubjet_reportage_count');
    }

    public function pubjet_clear_cache_on_meta_update($meta_id, $post_id, $meta_key)
    {
        if ($meta_key === 'pubjet_reportage_id') {
            $this->pubjet_reportage_count_clear_cache();
        }
    }

    public function pubjet_clear_cache_on_meta_delete($meta_ids) {
        if (is_array($meta_ids)) {
            foreach ($meta_ids as $meta_data) {
                if (isset($meta_data['key']) && $meta_data['key'] === 'pubjet_reportage_id') {
                    $this->pubjet_reportage_count_clear_cache();
                    break;
                }
            }
        }
    }

    public function clear_reportage_cdn_cache($post_id) {
        if (wp_is_post_revision($post_id)) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $cache_key = 'pubjet_cdn_content_' . $post_id;
        delete_transient($cache_key);
    }
    public function save_cdn_checkbox($post_id) {

        if (!isset($_POST['pubjet_cdn_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pubjet_cdn_nonce'])), 'pubjet_cdn_nonce_action')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['pubjet_use_cdn']) && '1' === sanitize_text_field(wp_unslash($_POST['pubjet_use_cdn']))) {
            update_post_meta($post_id, 'pubjet_use_cdn', true);
        } else {
            delete_post_meta($post_id, 'pubjet_use_cdn');
        }
    }

    public function clearMetaCacheOnSave($post_id, $post, $update) {
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        delete_transient("pubjet_meta_$post_id");
    }

    /**
     * Remove all Pubjet registered cron events in one pass (runs only once per site).
     *
     * @param array $registered_hooks [hook_name => schedule]
     * @return void
     */
    public function cleanupPubjetRegisteredCrons($registered_hooks)
    {
        if (get_option('pubjet_cron_cleanup_done')) {
            return;
        }

        $hook_names = is_array($registered_hooks) ? array_keys($registered_hooks) : [];

        try {
            $removed = Cron::getInstance()->removePubjetCrons($hook_names);
            pubjet_log('[Pubjet Cron] Cleanup finished. ' . ($removed ? 'Removed hooks: ' . implode(', ', $hook_names) : 'Nothing to remove.'));
        } catch (\Throwable $e) {
            pubjet_log('[Pubjet Cron][ERROR] ' . $e->getMessage());
            pubjet_log_sentry($e->getMessage(), [
                'hook_count' => count($hook_names),
                'function'   => __METHOD__,
            ]);
        }

        // Mark as done in every case, so this never runs (or fails) on every request.
        update_option('pubjet_cron_cleanup_done', 1, false);
    }


}