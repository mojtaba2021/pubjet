<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumAjaxPrivType;
use triboon\pubjet\includes\enums\EnumHttpMethods;
use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\enums\EnumPostMetakeys;
use triboon\pubjet\includes\traits\Utils;

if (!defined("ABSPATH")) exit;

class Ajax extends Singleton
{

    use Utils;

    /**
     * @return void
     */
    public function init()
    {
        $this->ajax('save-options', [$this, 'saveOptions'], EnumAjaxPrivType::LoggedIn, 15);
        $this->ajax('get-debug', [$this, 'getDebug'], EnumAjaxPrivType::LoggedIn, 15);
        $this->ajax('delete-debug', [$this, 'deleteDebug'], EnumAjaxPrivType::LoggedIn, 15);
        $this->ajax('check-token', [$this, 'checkToken'], EnumAjaxPrivType::LoggedIn, 15);
        $this->ajax('check-required-php-modules', [$this, 'checkRequiredPhpModules'], EnumAjaxPrivType::LoggedIn, 15);
        $this->ajax('reg-images', [$this, 'regImages'], EnumAjaxPrivType::LoggedIn);

        $this->ajax('permanent-hide-admin-notice', [$this, 'permanentHideAdminNotice'], EnumAjaxPrivType::LoggedIn);
        $this->ajax('find-terms', [$this, 'findTerms'], EnumAjaxPrivType::LoggedIn);
        $this->ajax('categories', [$this, 'findWpCategories'], EnumAjaxPrivType::LoggedIn);
        $this->ajax('remind-admin-notice', [$this, 'remindAdminNotice'], EnumAjaxPrivType::LoggedIn);
        $this->ajax('find-authors', [$this, 'findAuthors'], EnumAjaxPrivType::LoggedIn);
        $this->ajax('save-reportage-author', [$this, 'saveReportageAuthor'], EnumAjaxPrivType::LoggedIn);
        $this->ajax('set-error-reporting', [$this, 'setErrorReporting'], EnumAjaxPrivType::LoggedIn);

        $this->endpointHandler('find-reportage-panel-data', [$this, 'findReportagePanelData']);
        $this->endpointHandler('find-reportage-options', [$this, 'findReportageOptions']);
        $this->endpointHandler('save-reportage-options', [$this, 'saveReportageOptions']);
    }

    /**
     * @return void
     */
    public function saveReportageAuthor()
    {
        global $pubjet_settings;
        $this->checkAdminPermission();
        $this->checkNonce();

        $author_id = sanitize_text_field($this->post('authorId'));
        $authorCategory = pubjet_get_json($this->post('authorCategory'));
        pubjet_log(['authorCategory', $authorCategory]);
        if (!$author_id) {
            $this->error(pubjet__('missing-params'));
        }

        /**
         * The pubjet_before_save_reportage_author action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_before_save_reportage_author', $author_id);

        pubjet_update_setting('repauthor', [
            'status' => pubjet_isset_value($pubjet_settings['repauthor']['status']),
            'authorId' => $author_id,
            'authorCategory' => $authorCategory
        ]);

        /**
         * The pubjet_after_save_reportage_author action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_after_save_reportage_author', $author_id);

        $this->success();
    }

    /**
     * @since 1.0.0
     */
    public function findAuthors()
    {
        $this->checkAdminPermission();
        $this->checkNonce();

        $authors = pubjet_find_authors();

        $this->success($authors);
    }

    /**
     * @return void
     */
    public function findWpCategories()
    {
        $this->checkAdminPermission();
        $this->checkNonce();

        $mode = $this->get('mode', 'flat');

        if ('hierarchy' === $mode) {
            $categories = pubjet_find_wp_categories();
        } else {
            $categories = pubjet_find_wp_categories(false, false);
        }

        $this->success($categories);
    }

    public function findTerms()
    {
        $this->checkAdminPermission();
        $this->checkNonce();

        $sanitized_taxonomy = sanitize_text_field($this->get('taxonomy'));
        $sanitized_taxonomy = $sanitized_taxonomy ? $sanitized_taxonomy : 'category';

        $search = sanitize_text_field($this->get('search'));

        /**
         * The pubjet_before_search_terms filter.
         *
         * @since 1.0.0
         */
        do_action('pubjet_before_search_terms', $sanitized_taxonomy);

        $terms = get_terms([
            'taxonomy' => $sanitized_taxonomy,
            'hide_empty' => false,
            'search' => $search,
        ]);

        /**
         * The pubjet_after_search_terms filter.
         *
         * @since 1.0.0
         */
        do_action('pubjet_after_search_terms', $sanitized_taxonomy, $terms);

        $terms = array_map(function ($term) {
            return [
                'id' => $term->term_id,
                'value' => urldecode($term->slug),
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
    public function permanentHideAdminNotice()
    {
        $this->checkAdminPermission();

        $notice_id = sanitize_text_field($this->post('noticeId'));
        $security = sanitize_text_field($this->post('security'));

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
    public function remindAdminNotice()
    {
        $this->checkAdminPermission();

        $notice_id = sanitize_text_field($this->post('noticeId'));
        $security = sanitize_text_field($this->post('security'));

        if (!wp_verify_nonce($security, 'pubjet-admin-notice')) {
            $this->permissionError();
        }

        set_transient('pubjet_remind_admin_notice_' . $notice_id, 'yes', 48 * HOUR_IN_SECONDS);
        $this->success();
    }

    /**
     * @return void
     */
    public function saveReportageOptions()
    {

        $this->checkAdminPermission();
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
    public function findReportageOptions()
    {
        $this->checkAdminPermission();
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
    public function findReportagePanelData()
    {
        $this->checkAdminPermission();
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
        $result = wp_json_encode($panel_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->success([
            'data' => $result,
        ]);
    }

    public function regImages()
    {
        $this->checkAdminPermission();
        $this->checkNonce();

        if (empty($this->post('postId'))) {
            $this->error(pubjet__('missing-params'));
        }

        // Get regeneration type: 'thumbnail', 'content', or 'both' (default)
        $type = $this->post('type', 'both');

        $post = get_post($this->post('postId'));
        if (!$post) {
            $this->error(pubjet__('post-not-found'));
        }

        // Check if this post is reportage
        if (!pubjet_is_reportage($post->ID)) {
            $this->error(pubjet__('post-not-reportage'));
        }

        // All error reports of this regeneration are sent as one report.
        pubjet_sentry_batch_start('Regenerate images, post ' . (int) $post->ID);

        /**
         * The pubjet_regenerate_images action.
         */
        do_action('pubjet_regenerate_images', $post->ID, $post, $type);

        $triboon_panel_reportage_data = get_post_meta($post->ID, EnumPostMetakeys::PanelData, true) ;
        if (empty($triboon_panel_reportage_data) || !is_object($triboon_panel_reportage_data)) {
            $this->error(pubjet__('empty-reportage-content'));
        }

        $triboon_panel_reportage_content = pubjet_isset_value($triboon_panel_reportage_data->content_file);
        if (empty($triboon_panel_reportage_content)) {
            $this->error(pubjet__('empty-reportage-content'));
        }

        $reportage = [
            'content_file' => $triboon_panel_reportage_content,
        ];
        $post_content = ReportagePost::get_content_file((object)$reportage);

        list($lead_image, $lead_image_alt) = ReportagePost::lead_image($triboon_panel_reportage_data);

        $response = [];

        // Process images based on type
        if ($type === 'thumbnail' || $type === 'both') {
            $thumbnail_result = $this->processThumbnail($post, $post_content, $lead_image ,$lead_image_alt);
            $response['thumbnail'] = $thumbnail_result;
        }

        if ($type === 'content' || $type === 'both') {
            $content_result = $this->processContentImages($post, $post_content);
            $response['content'] = $content_result;
        }

        $this->success($response);
    }

    private function processThumbnail($post, $post_content, $lead_image , $lead_image_alt = null)
    {
        $post_thumbnail = $lead_image ? ReportagePost::upload_from_url($lead_image, $lead_image_alt) : 0;
        if (empty($post_thumbnail)) {
            // Featured image address missing or not downloadable: use the first image of the content
            $post_thumbnail = intval(ReportagePost::handle_images($post_content, true)['featured_img_id'] ?? 0);
        }

        if (empty($post_thumbnail)) {
            pubjet_log('Error creating post thumbnail for post ID: ' . $post->ID);
            return [
                'success' => false,
                'error'   => 'Error creating post thumbnail.'
            ];
        }

        $post_attach_id = get_post_thumbnail_id($post->ID);
        if ($post_attach_id) {
            wp_delete_attachment($post_attach_id, true);
        }

        set_post_thumbnail($post->ID, $post_thumbnail);

        $alt_text = $lead_image_alt ?: $post->post_title;
        update_post_meta($post_thumbnail, '_wp_attachment_image_alt', sanitize_text_field($alt_text));

        return [
            'success'       => true,
            'attachment_id' => $post_thumbnail,
            'url'           => wp_get_attachment_url($post_thumbnail)
        ];
    }


    private function processContentImages($post, $post_content)
    {
        $result = ReportagePost::handle_images($post_content, false);

        if (empty($result['html_file'])) {
            pubjet_log('Error processing content images for post ID: ' . $post->ID);
            return ['success' => false, 'error' => 'Error processing content images.'];
        }

        // Update post content with new image URLs
        $updated_post = [
            'ID' => $post->ID,
            'post_content' => $result['html_file']
        ];

        $update_result = wp_update_post($updated_post);

        if (is_wp_error($update_result)) {
            return [
                'success' => false,
                'error' => 'Error updating post content: ' . $update_result->get_error_message()
            ];
        }

        return [
            'success' => true,
            'images_processed' => $result['images_processed'],
            'processed_urls' => $result['processed_urls'],
            'failed_images' => $result['failed_images'] ?? []
        ];

    }

    /**
     * @return void
     */
    public function deleteDebug()
    {
        $this->checkAdminPermission();
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

        if (wp_is_writable(pubjet_debug_dir())) {
            wp_delete_file(pubjet_debug_dir()); // Delete debug file
            wp_delete_file(pubjet_debug_dir() . '.1'); // Delete rotated copy
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
    public function getDebug()
    {
        $this->checkAdminPermission();
        $this->checkNonce();
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

        $content = pubjet_redact_for_log(file_get_contents(pubjet_debug_dir()));

        /**
         * The pubjet_debug_content filter.
         *
         * @since 1.0.0
         */
        $content = apply_filters('pubjet_debug_content', $content);

        $this->success(['text' => $content,]);
    }

    /**
     * @return void
     */
    public function saveOptions()
    {
        $this->checkAdminPermission();
        $this->checkNonce();

        // flush_rewrite_rules();

        /**
         * The pubjet_before_save_options action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_before_save_options');

        $settings = pubjet_get_json($this->post('settings'));
        $token = pubjet_isset_value($settings['token']);
        $pricingPlans = pubjet_isset_value($settings['pricingPlans']);

        if (!pubjet_is_dev_mode()) {
            if (!$token) {
                $this->error(pubjet__('empty-token'));
            }

            $token_data = pubjet_find_token_details(trim($token));
            if (is_wp_error($token_data)) {
                $this->error($token_data->get_error_message());
            }
            $publisherCategory = pubjet_isset_value($token_data['publisherCategory']);
        }else{
            $publisherCategory = 1;
        }
        pubjet_log(['pricingPlans' => $pricingPlans]);

        // pricingPlans validation errors
        pubjet_validate_pricing_plans($pricingPlans);


        $newSettings = pubjet_stamp_debug_mode(pubjet_prepare_settings_to_save($settings, $publisherCategory));

        // First try to send pricing plans to API
        pubjet_log('before pubjet_send_pricing_plans_to_api');
        $result = pubjet_send_pricing_plans_to_api($pricingPlans, $token);
        pubjet_log('after pubjet_send_pricing_plans_to_api');

        if (is_wp_error($result)) {
            pubjet_log("Error in pubjet_send_pricing_plans_to_api");
            $this->error($result->get_error_message());
            return;
        }

        // Only update options if API call was successful
        update_option(EnumOptions::Settings, $newSettings);

        pubjet_log('before pubjet_send_plugin_status_to_api');
        delete_option('pubjet_token_reconnect_required');
        pubjet_send_plugin_status_to_api('active');
        pubjet_log('after pubjet_send_plugin_status_to_api');
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
     * @since  1.0
     * @author Triboon
     */
    public function checkRequiredPhpModules()
    {
        $this->checkAdminPermission();
        $this->checkNonce();

        /**
         * The pubjet_required_php_modules hook.
         *
         * @since 1.0.0
         */
        $data = apply_filters('pubjet_required_php_modules', [
            'curl' => function_exists('curl_version'),
            'openssl' => function_exists('openssl_encrypt'),
            'php_soap' => class_exists('SoapClient'),
        ]);

        $this->success($data);
    }

    /**
     * @return void
     */
    public function checkToken()
    {
        $this->checkAdminPermission();
        $this->checkNonce();

        $data = $this->check([EnumHttpMethods::POST]);
        if (is_array($data) && isset($data['error'])) {
            $this->error($data['message'], 405);
        }

        $token = trim($this->post('token'));
        if (empty($token)) {
            $this->error(pubjet__('missing-params'));
        }

        /**
         * The pubjet_check_token action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_check_token');

        $response = pubjet_find_token_details($token);
        if (is_wp_error($response)) {
            $this->error($response->get_error_message());
        }

        // Store the token only after it has been verified
        global $pubjet_settings;
        pubjet_update_setting('token', $token);
        $pubjet_settings['token'] = $token;

        $publisherCategory = pubjet_isset_value($response['publisherCategory']);

        $newSettings = pubjet_prepare_settings_to_save($pubjet_settings, $publisherCategory);

        update_option(EnumOptions::Settings, $newSettings);

        delete_option('pubjet_token_reconnect_required');
        pubjet_send_plugin_status_to_api('active');

        // Keep asking about error reporting until the administrator answers.
        $response['errorReporting'] = pubjet_error_reporting_state(pubjet_should_ask_error_reporting());

        $this->success($response);
    }

    /**
     * Save the administrator's error reporting choice.
     *
     * @return void
     */
    public function setErrorReporting()
    {
        $this->checkAdminPermission();
        $this->checkNonce();

        $data = $this->check([EnumHttpMethods::POST]);
        if (is_array($data) && isset($data['error'])) {
            $this->error($data['message'], 405);
        }

        if (null !== pubjet_error_reporting_forced()) {
            $this->error(pubjet__('error-reporting-forced'), 403);
        }

        $choice = sanitize_key($this->post('choice'));
        if (!in_array($choice, ['yes', 'no'], true)) {
            $this->error(pubjet__('missing-params'));
        }

        pubjet_set_error_reporting($choice);
        $this->success(pubjet_error_reporting_state());
    }

    public function isValidHttpMethod($valid_methods = ['POST'])
    {
        return in_array(pubjet_get_request_method(), $valid_methods, true);
    }

    /**
     * @return array|bool|object
     */
    private function check($method)
    {
        if (!is_array($method)) {
            $method = [$method];
        }

        if (!$this->isValidHttpMethod($method)) {
            return [
                'error' => true,
                'status' => 401,
                'message' => pubjet__('invalid-http-method'),
            ];
        }

        return true;
    }

}