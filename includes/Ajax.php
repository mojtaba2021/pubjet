<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumAjaxPrivType;
use triboon\pubjet\includes\enums\EnumOptions;
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
    }

    /**
     * @return void
     */
    public function deleteDebug() {
        $this->checkNonce($this->get('security'));

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
            $this->error('خطا در حذف فایل. دسترسی حذف فایل از سمت هاست محدود شده است');
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