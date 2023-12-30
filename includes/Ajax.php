<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumAjaxPrivType;
use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\enums\EnumPostMetakeys;
use triboon\pubjet\includes\traits\Utils;

class Ajax extends Singleton {

    use Utils;

    /**
     * @return void
     */
    public function init() {
        $this->ajax('get-options', [$this, 'getOptions'], EnumAjaxPrivType::LoggedIn);
        $this->ajax('update-options', [$this, 'updateOptions'], EnumAjaxPrivType::LoggedIn);
        $this->ajax('save-options', [$this, 'saveOptions'], EnumAjaxPrivType::LoggedIn);
        $this->ajax('get-debug', [$this, 'getDebug'], EnumAjaxPrivType::LoggedIn);
        $this->ajax('delete-debug', [$this, 'deleteDebug'], EnumAjaxPrivType::LoggedIn);
        $this->ajax('reg-thumb', [$this, 'regThumbnail'], EnumAjaxPrivType::LoggedIn);
        $this->ajax('find-reportage-panel-data', [$this, 'findReportagePanelData'], EnumAjaxPrivType::LoggedIn);
        $this->ajax('find-reportage-options', [$this, 'findReportageOptions'], EnumAjaxPrivType::LoggedIn);
        $this->ajax('save-reportage-options', [$this, 'saveReportageOptions'], EnumAjaxPrivType::LoggedIn);
    }

    /**
     * @return void
     */
    public function saveReportageOptions() {

        $this->checkNonce($this->post('security'));

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
        $this->checkNonce($this->get('security'));

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
        $this->checkNonce($this->get('security'));

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
        $this->checkNonce($this->post('security'));

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
        $this->checkNonce($this->post('security'));

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
        $this->checkNonce($this->get('security'));

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
        $this->checkNonce($this->get('security'));

        /**
         * The pubjet_before_save_options action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_before_save_options');

        update_option(EnumOptions::Token, $this->post('token'));
        update_option(EnumOptions::DebugMode, $this->formatBoolean($this->post('debug')));
        update_option(EnumOptions::DefaultCategory, $this->post('category'));
        update_option(EnumOptions::UninstallCleanup, $this->formatBoolean($this->post('uninstall')));

        /**
         * The pubjet_after_save_options filter.
         *
         * @since 1.0.0
         */
        do_action('pubjet_after_save_options');

        $this->success();
    }

    /**
     * @return void
     */
    public function updateOptions() {
        $this->checkNonce($this->get('security'));

        /**
         * The pubjet_before_update_options action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_before_update_options');

        /**
         * The pubjet_after_update_options action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_after_update_options');

        $this->success();
    }

    /**
     * @return void
     */
    public function getOptions() {
        $this->checkNonce($this->get('security'));

        /**
         * The pubjet_before_get_options action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_before_get_options');

        $formatted_categories = [];
        $categories           = get_categories([
                                                   'hide_empty' => false,
                                               ]);
        foreach ($categories as $item) {
            $formatted_categories[] = [
                'value' => $item->term_id,
                'label' => $item->name,
            ];
        }

        $options = pubjet_options();

        /**
         * The pubjet_after_get_options action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_after_get_options', $options);

        $this->success(array_merge($options, [
            'categories' => $formatted_categories,
        ]));

    }

}