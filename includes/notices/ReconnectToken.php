<?php

namespace triboon\pubjet\includes\notices;

// Exit if accessed directly
defined('ABSPATH') || exit;

class ReconnectToken extends BaseNotice {

    /**
     * @return mixed|void
     */
    protected function getId() {
        return 'pubjet-reconnect-token';
    }

    /**
     * @return mixed|void
     */
    public function getTitle() {
        return pubjet__('pubjet-token');
    }

    /**
     * @return mixed|void
     */
    public function getText() {
        return pubjet__('reconnect-token-desc');
    }

    /**
     * @return boolean
     */
    protected function showPermanentHide() {
        return false;
    }

    /**
     * @return array
     */
    protected function getButtons() {
        return [
            [
                'text'  => 'تنظیمات پابجت',
                'url'   => admin_url('admin.php?page=pubjet_settings'),
                'class' => ['button-primary'],
            ],
        ];
    }

    /**
     * @return boolean
     * @since 5.4.9
     */
    protected function shouldShowNotice() {
        if (!parent::shouldShowNotice()) {
            return false;
        }

        return 'yes' === get_option('pubjet_token_reconnect_required');
    }

}
