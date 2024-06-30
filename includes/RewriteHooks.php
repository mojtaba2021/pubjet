<?php

namespace triboon\pubjet\includes;

use DateTime;
use DateTimeZone;
use triboon\pubjet\includes\enums\EnumHttpMethods;
use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\enums\EnumPostMetakeys;
use triboon\pubjet\includes\traits\Utils;

if (!defined("ABSPATH")) exit;

class RewriteHooks extends Singleton {

    use Utils;

    /**
     * @return void
     */
    public function init() {
        $this->endpointHandler('reportage', [$this, 'reportageRequest'], 15);
        $this->endpointHandler('version', [$this, 'checkPluginVersion'], 15);;
        $this->endpointHandler('copyright', [$this, 'toggleCopyright'], 15);
        $this->endpointHandler('check-missed-reportage', [$this, 'checkMissedReportage'], 15);
        $this->endpointHandler('siteinfo', [$this, 'siteInfo'], 15);
        $this->endpointHandler('check-token', [$this, 'checkToken'], 15);
        $this->endpointHandler('check-required-php-modules', [$this, 'checkRequiredPhpModules'], 15);
        $this->endpointHandler('get-options', [$this, 'getOptions'], 15);
        $this->endpointHandler('save-options', [$this, 'saveOptions'], 15);
        $this->endpointHandler('get-debug', [$this, 'getDebug'], 15);
        $this->endpointHandler('delete-debug', [$this, 'deleteDebug'], 15);
        $this->endpointHandler('reg-thumb', [$this, 'regThumbnail']);

        $this->endpointHandler('find-reportage-panel-data', [$this, 'findReportagePanelData']);
        $this->endpointHandler('find-reportage-options', [$this, 'findReportageOptions']);
        $this->endpointHandler('save-reportage-options', [$this, 'saveReportageOptions']);

        $this->endpointHandler('remind-admin-notice', [$this, 'remindAdminNotice']);
        $this->endpointHandler('permanent-hide-admin-notice', [$this, 'permanentHideAdminNotice']);
        $this->endpointHandler('find-terms', [$this, 'findTerms']);

        // Get WordPress categories
        $this->endpointHandler('tags', [$this, 'findWpTags']);
        // Get WordPress tags
        $this->endpointHandler('categories', [$this, 'findWpCategories']);
    }

    /**
     * @return void
     */
    public function findWpTags() {
        $tags = pubjet_find_wp_tags();

        $this->success($tags);
    }

    /**
     * @return void
     */
    public function findWpCategories() {

        $mode = $this->get('mode', 'flat');

        if ('hierarchy' === $mode) {
            $categories = pubjet_find_wp_categories();
        } else {
            $categories = pubjet_find_wp_categories(false, false);
        }

        $this->success($categories);
    }

    public function findTerms() {
        $this->checkNonce();

        $sanitized_taxonomy = sanitize_text_field($this->get('taxonomy'));
        $sanitized_taxonomy = $sanitized_taxonomy ? $sanitized_taxonomy : 'category';

        /**
         * The pubjet_before_search_terms filter.
         *
         * @since 1.0.0
         */
        do_action('pubjet_before_search_terms', $sanitized_taxonomy);

        $terms = get_terms([
                               'taxonomy'   => $sanitized_taxonomy,
                               'hide_empty' => false,
                           ]);

        /**
         * The pubjet_after_search_terms filter.
         *
         * @since 1.0.0
         */
        do_action('pubjet_after_search_terms', $sanitized_taxonomy, $terms);

        $terms = array_map(function ($term) {
            return [
                'value' => $term->term_id,
                'label' => $term->name,
            ];
        }, $terms);

        $this->success($terms);
    }

    /**
     * @return void
     * @since  1.0
     * @author Triboon
     */
    public function permanentHideAdminNotice() {
        $notice_id = sanitize_text_field($this->post('noticeId'));
        $security  = sanitize_text_field($this->post('security'));

        if (!wp_verify_nonce($security, 'pubjet-admin-notice')) {
            $this->permissionError();
        }

        update_option('pubjet_permanent_hide_admin_notice_' . $notice_id, 'yes');

        $this->success();
    }

    /**
     * @return void
     * @since  1.0
     * @author Triboon
     */
    public function remindAdminNotice() {
        $notice_id = sanitize_text_field($this->post('noticeId'));
        $security  = sanitize_text_field($this->post('security'));

        if (!wp_verify_nonce($security, 'pubjet-admin-notice')) {
            $this->permissionError();
        }

        set_transient('pubjet_remind_admin_notice_' . $notice_id, 'yes', 48 * HOUR_IN_SECONDS);
        $this->success();
    }

    /**
     * @return void
     */
    public function saveReportageOptions() {

        $this->checkNonce();

        if (empty($this->post('postId')) || !pubjet_is_reportage($this->post('postId'))) {
            $this->error(pubjet__('missing-params'));
        }

        // Check if post found or not
        $post = get_post($this->post('postId'));
        if (empty($post)) {
            $this->error(pubjet__('reportage-not-found'));
        }

        update_post_meta($post->ID, EnumPostMetakeys::NoFollow, $this->formatBoolean($this->post('nofollow')));

        $this->success();
    }

    /**
     * @return void
     */
    public function findReportageOptions() {
        $this->checkNonce();

        if (empty($this->get('postId')) || !pubjet_is_reportage($this->get('postId'))) {
            $this->error(pubjet__('missing-params'));
        }

        $post = get_post($this->get('postId'));
        if (empty($post)) {
            $this->error(pubjet__('reportage-not-found'));
        }

        $nofollow = get_post_meta($post->ID, EnumPostMetakeys::NoFollow, true);

        $this->success([
                           'nofollow' => boolval($nofollow),
                       ]);
    }

    /**
     * @return void
     */
    public function findReportagePanelData() {
        $this->checkNonce();

        // Missing params
        if (empty($this->get('postId'))) {
            $this->error(pubjet__('missing-params'));
        }

        $post = get_post($this->get('postId'));
        if (empty($post) || !pubjet_is_reportage($post->ID)) {
            $this->error(pubjet__('post-not-found'));
        }

        $panel_data = get_post_meta($post->ID, EnumPostMetakeys::PanelData, true);
        $result     = print_r($panel_data, true);

        $this->success([
                           'data' => $result,
                       ]);
    }

    /**
     * @return void
     */
    public function regThumbnail() {
        $this->checkNonce();

        if (empty($this->post('postId'))) {
            $this->error(pubjet__('missing-params'));
        }

        $post = get_post($this->post('postId'));
        if (!$post) {
            $this->error(pubjet__('post-not-found'));
        }

        // Check if this post is reportage
        if (!pubjet_is_reportage($post->ID)) {
            $this->error(pubjet__('post-not-reportage'));
        }

        /**
         * The pubjet_generate_post_thumbnail action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_generate_post_thumbnail', $post->ID, $post);

        $triboon_panel_reportage_content = get_post_meta($post->ID, EnumPostMetakeys::ReportageContentUrl, true);
        if (empty($triboon_panel_reportage_content)) {
            $this->error(pubjet__('empty-reportage-content'));
        }

        $reportage      = [
            'content_file' => $triboon_panel_reportage_content,
        ];
        $post_content   = ReportagePost::get_content_file((object)$reportage);
        $post_thumbnail = ReportagePost::handle_images($post_content, true);

        if (empty($post_thumbnail['featured_img_id'])) {
            pubjet_log($post_thumbnail);
            $this->error('Error creating post thumbnail.');
        }

        $post_attach_id = get_post_thumbnail_id($post);
        if ($post_attach_id) {
            // Delete old featured image
            wp_delete_attachment($post_attach_id, true);
        }

        // Set new thumbnail
        set_post_thumbnail($post->ID, intval($post_thumbnail['featured_img_id']));

        $this->success($post_thumbnail);
    }

    /**
     * @return void
     */
    public function deleteDebug() {
        $this->checkNonce();

        /**
         * The pubjet_before_delete_debug action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_before_delete_debug');

        // Check if file exists
        if (!file_exists(pubjet_debug_dir())) {
            $this->success();
        }

        if (is_writeable(pubjet_debug_dir())) {
            unlink(pubjet_debug_dir()); // Delete debug file
        } else {
            $this->error(pubjet__('delete-permission-limit'));
        }

        /**
         * The pubjet_after_delete_debug action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_after_delete_debug');

        $this->success();
    }

    /**
     * @return void
     */
    public function getDebug() {
        $this->checkNonce();
//
//        pubjet_log(site_url() . '/pubjet-api/reportage');
//        $data = wp_remote_post(site_url() . '/pubjet-api/reportage', [
//            'headers'     => [
//                'Content-Type'  => 'application/json; charset=utf-8',
//                'Authorization' => '1f4383f17ee6a0fc23440b6cef1cf21d1ab2cdb9',
//            ],
//            'body'        => json_encode([
//                                             'id'                     => '253318',
//                                             'preferred_publish_date' => '2024-01-21T17:00:30.792740+03:30',
//                                             'state'                  => 'publisher_accepted',
//                                             'content_file'           => 'https://cdn.triboon.net/media/reportage_contents_html/e5687f50-30a4-4b9d-9bc0-b686983c2c16.html',
//                                             'title'                  => 'اقتصاد برتر جهان در سال 2023',
//                                             'tags'                   => [],
//                                         ]),
//            'method'      => 'POST',
//            'data_format' => 'body',
//        ]);
//        pubjet_log($data);

//        pubjet_log(file_get_contents('https://cdn.triboon.net/media/reportage_contents_html/e5687f50-30a4-4b9d-9bc0-b686983c2c16.html'));
//        pubjet_log(wp_remote_retrieve_body(wp_remote_get('https://cdn.triboon.net/media/reportage_contents_html/e5687f50-30a4-4b9d-9bc0-b686983c2c16.html')));

        /**
         * The pubjet_get_debug action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_get_debug');

        // Check if debug file exists
        if (!file_exists(pubjet_debug_dir()) || !is_readable(pubjet_debug_dir())) {
            $this->success(['text' => '',]);
        }

        $content = file_get_contents(pubjet_debug_dir());

        /**
         * The pubjet_debug_content filter.
         *
         * @since 1.0.0
         */
        $content = apply_filters('pubjet_debug_content', $content);

        $this->success([
                           'text' => $content,
                       ]);
    }

    /**
     * @return void
     */
    public function saveOptions() {
        $this->checkNonce();

        /**
         * The pubjet_before_save_options action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_before_save_options');

        $settings = pubjet_get_json($this->post('settings'));

        update_option(EnumOptions::Settings, $settings);

        flush_rewrite_rules();

        /**
         * The pubjet_after_save_options filter.
         *
         * @since 1.0.0
         */
        do_action('pubjet_after_save_options', $settings);

        $this->success();
    }

    /**
     * @return void
     */
    public function getOptions() {
        $this->checkNonce();

        /**
         * The pubjet_before_get_options action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_before_get_options');

        $options = pubjet_options();

        /**
         * The pubjet_after_get_options action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_after_get_options', $options);

        $this->success($options);

    }

    /**
     * @return void
     * @since  1.0
     * @author Triboon
     */
    public function checkRequiredPhpModules() {
        $this->checkNonce();

        /**
         * The pubjet_required_php_modules hook.
         *
         * @since 1.0.0
         */
        $data = apply_filters('pubjet_required_php_modules', [
            'curl'     => function_exists('curl_version'),
            'openssl'  => function_exists('openssl_encrypt'),
            'php_soap' => class_exists('SoapClient'),
        ]);

        $this->success($data);
    }

    /**
     * @return void
     */
    public function checkToken() {
        $data = $this->check([EnumHttpMethods::GET], false, false);
        if (is_array($data) && isset($data['error'])) {
            $this->error($data['message']);
        }

        $token = $this->get('token');
        if (empty(trim($token))) {
            $this->error(pubjet__('missing-params'));
        }

        /**
         * The pubjet_check_token action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_check_token');

        /**
         * The pubjet_check_token_url filter.
         *
         * @since 1.0.0
         */
        $url = apply_filters('pubjet_check_token_url', pubjet_api_root() . '/external/wp/token-validation/', $token);

        pubjet_log($url);

        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Token ' . trim($token),
        ];

        $result = $this->request($url, EnumHttpMethods::GET, $headers);

        pubjet_log($result);

        if (is_wp_error($result)) {
            $this->error($result->get_error_message());
        }

        if (isset($result['code']) && 403 == $result['code']) {
            $this->error(pubjet__('invalid-token'));
        }

        if (!isset($result['body']->is_valid)) { // Some error occured
            $this->error(pubjet__('error-occured'));
        }

        // Check if token is valid or not
        if (!$result['body']->is_valid) {
            $this->error(pubjet__('invalid-token'), [
                'invalid' => true,
            ]);
        }

        // we must remove "Test" plan in production
        $pricing_plans = pubjet_isset_value($result['body']->pricing_plans, []);
        if ($pricing_plans) {
            $final_plans = [];
            foreach ($pricing_plans as $plan) {
                if ($plan->title !== 'تست پابجت') {
                    $final_plans[] = $plan;
                }
            }
            $pricing_plans = $final_plans;
        }

        // Sync
        pubjet_sync_categories();

        $this->success([
                           'valid'         => true,
                           'first_name'    => pubjet_isset_value($result['body']->publisher->first_name),
                           'last_name'     => pubjet_isset_value($result['body']->publisher->last_name),
                           'phone'         => pubjet_isset_value($result['body']->publisher->phone),
                           'email'         => pubjet_isset_value($result['body']->publisher->email),
                           'pricing_plans' => $pricing_plans,
                       ]);
    }

    /**
     * @return void
     */
    public function siteInfo() {

        $data = $this->check(['GET'], false);

        if (is_array($data) && isset($data['error'])) {
            wp_send_json_error(pubjet_isset_value($data['message']), pubjet_isset_value($data['status']));
        }

        /**
         * The pubjet_siteinfo filter.
         *
         * @since 1.0.0
         */
        $result = apply_filters('pubjet_siteinfo', [
            'title'     => get_bloginfo('name'),
            'descr'     => get_bloginfo('description'),
            'url'       => get_bloginfo('wpurl'),
            'wpversion' => get_bloginfo('version'),
        ]);

        $this->success($result);
    }

    /**
     * @return void
     */
    public function toggleCopyright() {
        $data = $this->check(['POST', 'PATCH']);
        if (is_array($data) && isset($data['error'])) {
            wp_send_json_error(pubjet_isset_value($data['message']), pubjet_isset_value($data['status']));
        }

        pubjet_log('==== Change Copyright Status ====');
        pubjet_log($data);

        if (empty(pubjet_isset_value($data->id))) {
            wp_send_json_error(pubjet__('post-not-found'), 404);
        }

        $reportage_post_id = pubjet_find_post_id_by_reportage_id($data->id);
        if (empty($reportage_post_id)) {
            wp_send_json_error(pubjet__('post-not-found'), 404);
        }

        $new_status = pubjet_isset_value($data->status, 'show');
        if ('hide' === $new_status) {
            // Hide copyright
            pubjet_update_setting(EnumOptions::CopyrightStatus, 'hide');
        } else {
            // Show copyright
            pubjet_update_setting(EnumOptions::CopyrightStatus, '');
        }

        $this->success([
                           'wpPostId'    => $reportage_post_id,
                           'reportageId' => pubjet_isset_value($data->id),
                           'status'      => $new_status,
                       ]);
    }

    /**
     * @return void
     */
    public function deleteReportage() {
        $reportage = $this->check(['DELETE']);
        if (is_array($reportage) && isset($reportage['error'])) {
            wp_send_json_error(pubjet_isset_value($reportage['message']), pubjet_isset_value($reportage['status']));
        }

        pubjet_log("==== Delete Reportage Post ====");
        pubjet_log($reportage);
        $reportage_post_id = pubjet_find_post_id_by_reportage_id(pubjet_isset_value($reportage->id));
        pubjet_log('Post: ' . $reportage_post_id);

        if (empty($reportage_post_id)) {
            wp_send_json_error(pubjet__('post-not-found'), 404);
        }

        $post = get_post($reportage_post_id);
        if ($post->post_type !== pubjet_post_type()) {
            wp_send_json_error(pubjet__('post-not-found'), 404);
        }

        $result = wp_delete_post($reportage_post_id, true);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message(), 500);
        }

        $this->success([
                           'wpPostId'    => absint($reportage_post_id),
                           'reportageId' => absint(pubjet_isset_value($reportage->id)),
                       ]);
    }

    /**
     * @return void
     */
    public function findReportage() {
        $request_data = $this->check(['GET'], false);
        if (is_array($request_data) && isset($request_data['error'])) {
            wp_send_json_error(pubjet_isset_value($request_data['message']), pubjet_isset_value($request_data['status']));
        }

        if (empty($this->get('id'))) {
            wp_send_json_error(pubjet__('post-not-found'), 404);
        }

        pubjet_log('===== Get Reportage Post =====');
        pubjet_log($_GET);

        $reportage_post_id = pubjet_find_post_id_by_reportage_id($this->get('id'));
        $reportage_post    = get_post($reportage_post_id);
        if (!$reportage_post_id || empty($reportage_post)) {
            wp_send_json_error(pubjet__('post-not-found'), 404);
        }

        $this->success([
                           'id'    => $reportage_post->ID,
                           'title' => $reportage_post->post_title,
                           'url'   => get_permalink($reportage_post->ID),
                       ]);
    }

    /**
     * @return void
     */
    public function checkPluginVersion() {
        $this->success([
                           'version' => PUBJ()->getVersion(),
                       ]);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function checkMissedReportage() {

        if (!$this->isValidHttpMethod(['POST'])) {
            wp_send_json_error(pubjet__('invalid-http-method'), 401);
        }

        if (!$this->isTokenValid()) {
            wp_send_json_error(pubjet__('invalid-token'), 401);
        }

        $this->finishRequest();

        global $wpdb;

        $dt = new DateTime(date('Y-m-d H:i:s e'));
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        $now = $dt->format('Y-m-d H:i:s');

        $sql    = $wpdb->prepare("SELECT `ID` FROM $wpdb->posts WHERE `post_type` = %s AND post_status='future' AND post_date_gmt < %s", PUBJET_POST_TYPE, $now);
        $result = $wpdb->get_results($sql);

        if (!$result) {
            return;
        }

        foreach ($result as $post) {
            wp_publish_post($post->ID);
        }

    }

    public function reportageRequest() {

        // =================== Delete Reportage =================
        if ('DELETE' === pubjet_get_request_method()) {
            $this->deleteReportage();
            return;
        } else if ('GET' === pubjet_get_request_method()) {
            $this->findReportage();
            return;
        }

        // =================== Insert or Update ===================
        if (!$this->isValidHttpMethod([EnumHttpMethods::POST, EnumHttpMethods::PATCH])) {
            pubjet_log_sentry('متد ارسال اطلاعات رپورتاژ اشتباه است. نوع متد باید POST و یا PATCH باشد. متد فعلی:' . $_SERVER['REQUEST_METHOD']);
            wp_send_json_error(pubjet__('invalid-http-method'), 401);
        }

        if (!$this->isTokenValid()) {
            pubjet_log_sentry('توکن ارتباطی اشتباه است');
            wp_send_json_error(pubjet__('invalid-token'), 401);
        }

        $reportage = (object)$this->getRequestData();

        pubjet_log($reportage);

        $wp_post_id = ReportagePost::insert($reportage);

        if (!$wp_post_id || is_wp_error($wp_post_id)) {
            if (is_wp_error($wp_post_id)) {
                pubjet_log('Error: ' . $wp_post_id->get_error_message());
            }
            $sentry_error = is_wp_error($wp_post_id) ? $wp_post_id->get_error_message() : 'خطای نامشخصی در فرایند ثبت نوشته رپورتاژ رخ داده است.';
            pubjet_log_sentry($sentry_error, [
                'reportage_id'    => pubjet_isset_value($reportage->id),
                'reportage_title' => pubjet_isset_value($reportage->title),
            ]);
            wp_send_json_error($wp_post_id);
        }

        if (!empty($reportage->wp_post_id)) {
            // Update
            pubjet_log('Post updated successfully. Post ID: ' . $reportage->wp_post_id);
        } else {
            // Insert
            pubjet_log('Post created successfully. New Post ID: ' . $wp_post_id);
        }

        // Success
        wp_send_json_success($wp_post_id);
    }

    public function finishRequest() {
        ignore_user_abort(true);

        if (!headers_sent()) {
            header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
            header('Cache-Control: no-cache, must-revalidate, max-age=0');
        }

        if (PHP_VERSION_ID >= 70016 && function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else if (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        } else {
            ob_start();
            $size = ob_get_length();
            header("Content-Length: $size");
            header("Connection: close");
            ob_end_flush();
            ob_flush();
            flush();
        }

    }

    /**
     * @return bool
     */
    public function isTokenValid() {

        if (pubjet_is_dev_mode()) {
            return true;
        }

        if (empty(pubjet_token())) {
            wp_send_json_error(pubjet__('missing-token'), 401);
        }

        $header_token = pubjet_isset_value($_SERVER['HTTP_AUTHORIZATION'], '');

        return pubjet_token() == $header_token;

    }

    public function isValidHttpMethod($valid_methods = ['POST']) {
        return in_array($_SERVER['REQUEST_METHOD'], $valid_methods);
    }

    /**
     * @return array|mixed
     */
    public function getRequestData() {
        $stream = fopen('php://input', 'r');
        if ($stream) {
            $rawData = '';
            while ($chunk = fread($stream, pubjet_isset_value($_SERVER['CONTENT_LENGTH']))) {
                $rawData .= $chunk;
            }
            fclose($stream);
            return json_decode($rawData, true);
        }
        return [];
    }

    /**
     * @return array|bool|object
     */
    private function check($method, $get_request_data = true, $check_token = true) {
        if (!is_array($method)) {
            $method = [$method];
        }

        if (!$this->isValidHttpMethod($method)) {
            return [
                'error'   => true,
                'status'  => 401,
                'message' => pubjet__('invalid-http-method'),
            ];
        }

        if ($check_token && !$this->isTokenValid()) {
            return [
                'error'   => true,
                'status'  => 401,
                'message' => pubjet__('invalid-token'),
            ];
        }

        if ($get_request_data) {
            return (object)$this->getRequestData();
        }

        return true;
    }

}