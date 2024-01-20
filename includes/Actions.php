<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumOptions;

defined('ABSPATH') || exit;

class Actions extends Singleton {

    /**
     * @return void
     */
    public function init() {
        add_action("admin_menu", [$this, "registerMenu"], 15);
        add_action("admin_footer", [$this, "adminFooterScripts"], 15);
        add_action("wp_head", [$this, "publishMissedSchedulePosts"], 15);
        add_action("wp_footer", [$this, "addScriptToReportage"], 15);
        add_action("admin_head", [$this, "pluginFont"], 15);
    }

    /**
     * @return void
     */
    public function pluginFont() {
        ?>
        <style>
            @font-face {
                font-family: 'Vazirmatn';
                src: url(<?php echo PUBJET_ASSETS_URL; ?>/fonts/Vazirmatn[wght].woff2) format('woff2 supports variations'),
                url(<?php echo PUBJET_ASSETS_URL; ?>/fonts/Vazirmatn[wght].woff2) format('woff2-variations');
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
    public function publishMissedSchedulePosts() {

        $last_check = (int)get_option(EnumOptions::LastCheckingMissedPosts, 1);

        if (time() - $last_check < 60) {
            return;
        }

        update_option(EnumOptions::LastCheckingMissedPosts, time());

        wp_remote_post(home_url('pubjet-api/check-missed-reportage'), [
            'headers'     => [
                'Authorization' => pubjet_token(),
                'Content-Type'  => 'application/json',
            ],
            'data_format' => 'body',
            'method'      => 'POST',
            'body'        => json_encode([]),
        ]);
    }

    /**
     * @return void
     */
    public function addScriptToReportage() {

        if (!is_singular() || !pubjet_is_reportage(get_the_ID())) {
            return;
        }

        ?>
        <style>
            body .pubjet-copyright p {
                display: inline-flex !important;
                align-items: center !important;
                background: #eee !important;
                padding: 12px 16px !important;
                border-radius: 8px !important;
                margin: 12px 0 !important;
                font-size: 14px !important;
            }

            body .pubjet-copyright p img {
                width: 24px !important;
                margin: 0 8px !important;
            }
        </style>
        <?php
    }

    /**
     * @return void
     */
    public function adminFooterScripts() {
        global $wpdb;

        if (get_current_screen()->id != 'edit-post') {
            return;
        }
        $posts      = "SELECT COUNT(*) FROM {$wpdb->posts} as posts JOIN {$wpdb->postmeta} as meta ON meta.post_id = posts.ID where posts.post_type = 'post' AND posts.post_status IN ('publish' , 'future' ,'draft') AND meta.meta_key = 'pubjet_reportage_id' ";
        $count_post = $wpdb->get_var($posts);
        ?>
        <script>
            jQuery(document).ready(function ($) {
                jQuery(".subsubsub").append("<li class='reportages'><a href='edit.php?post_type=post&reportage=true'> | <?php echo pubjet__('reportage'); ?> <span class='count'>(<?= intval($count_post) ?>)</span></a></li>")
            });
        </script>
        <?php
    }

    /**
     * @return void
     */
    public function registerMenu() {
        add_menu_page(
            pubjet__('pubjet'),
            pubjet__('pubjet'),
            'manage_options',
            'pubjet_settings',
            [$this, 'pubjetSettingsPageCallback'],
            PUBJET_IMAGES_URL . 'pubjet-icon.svg',
            100,
        );
    }

    /**
     * @return void
     */
    public function pubjetSettingsPageCallback() {
        pubjet_template('settings');
    }


}