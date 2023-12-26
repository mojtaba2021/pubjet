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
        $this->ajax('find-categories', [$this, 'getCategories'], EnumAjaxPrivType::LoggedIn);
    }

    /**
     * @return void
     */
    public function getCategories() {
        $this->checkNonce($this->get('security'));


        $this->success($result);
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
            'categories' => $formatted_categories
        ]));

    }

}