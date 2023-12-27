<?php

namespace triboon\pubjet\includes;

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
                src: url(<?php echo PUBJET_ASSETS_URL; ?>/font/Vazirmatn[wght].woff2) format('woff2 supports variations'),
                url(<?php echo PUBJET_ASSETS_URL; ?>/font/Vazirmatn[wght].woff2) format('woff2-variations');
                font-weight: 100 900;
                font-style: normal;
                font-display: swap;
            }

            .mF0ELrBPqJ9R6bt1N3mw *,
            .ant-tooltip-inner,
            .ant-select-dropdown div,
            .ant-modal *,
            .ant-popover * {
                font-family: 'Vazirmatn';
            }
        </style>
        <?php
    }

    /**
     * @return void
     */
    public function publishMissedSchedulePosts() {

        $last_check = (int)get_option("pubjet_last_checking_missed_post", 1);

        if (time() - $last_check < 60) {
            return;
        }

        update_option("pubjet_last_checking_missed_post", time());

        $url = home_url('pubjet-api/check-missed-reportage');

        wp_remote_post($url, [
            'headers'     => [
                'Authorization' => PUBJET_API_TOKEN,
                'Content-Type'  => 'application/json',
            ],
            'body'        => json_encode([]),
            'method'      => 'POST',
            'data_format' => 'body',
        ]);
    }

    /**
     * @return void
     */
    public function addScriptToReportage() {

        if (!is_singular()) {
            return;
        }
        $reportage_id = get_post_meta(get_the_ID(), 'pubjet_reportage_id', true);
        if (empty($reportage_id)) {
            return;
        }
        ?>
        <style>
            .reportage_content_copyright p {
                margin: 0;
                display: inline-flex;
                align-items: center;
                background: #eee;
                padding: 8px;
                border-radius: 8px;
                margin: 12px 0;
                font-size: 14px;
            }

            .reportage_content_copyright p img {
                width: 24px;
                margin: 0 4px;
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
        $post_table      = $wpdb->prefix . 'posts';
        $post_meta_table = $wpdb->prefix . 'postmeta';

        $posts      = "SELECT COUNT(*) FROM $post_table as  posts JOIN $post_meta_table as meta ON meta.post_id = posts.ID where posts.post_type = 'post' AND posts.post_status IN ('publish' , 'future' ,'draft') AND meta.meta_key = 'pubjet_reportage_id' ";
        $count_post = $wpdb->get_var($posts);
        ?>
        <script>
            jQuery(".subsubsub").append("<li class='reportages'><a href='edit.php?post_type=post&reportage=true'> | رپورتاژ <span class='count'>(<?= intval($count_post) ?>)</span></a></li>")
        </script>
        <?php
    }

    /**
     * @return void
     */
    public function registerMenu() {
        add_menu_page(
            'تنظیمات پاب‌جت',
            'تنظیمات پاب‌جت',
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