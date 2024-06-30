<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumHttpMethods;
use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\enums\EnumPostTypes;
use triboon\pubjet\includes\traits\Utils;

defined('ABSPATH') || exit;

class Actions extends Singleton {

    use Utils;

    /**
     * @return void
     */
    public function init() {
        add_action("admin_menu", [$this, "registerMenu"], 15);
        add_action("admin_footer", [$this, "adminFooterScripts"], 15);
        add_action("wp_head", [$this, "publishMissedSchedulePosts"], 15);
        add_action("wp_footer", [$this, "addScriptToReportage"], 15);
        add_action("admin_head", [$this, "pluginFont"], 15);
        add_action("wp_head", [$this, "alignReportageImagesCenter"], 15);
        add_action('created_term', [$this, 'createCategory'], 15, 5);
        add_action('delete_term', [$this, 'deleteCategory'], 15, 4);
        add_action('pubjet_new_reportage', [$this, 'reportageCustomFields'], 15, 2);
    }

    /**
     * @return void
     */
    public function reportageCustomFields($post_id, $reportage_data) {
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
    public function createCategory($term_id, $tt_id, $taxonomy, $args) {
        if ('category' !== $taxonomy) {
            return;
        }
        $term = get_term_by('term_id', $term_id, $taxonomy);
        if (!$term) {
            return;
        }
        $response = pubjet_sync_category([
                                             [
                                                 'title'       => $term->name,
                                                 'unique_name' => $term->slug,
                                             ],
                                         ]);
        pubjet_log($response);
    }

    /**
     * @return void
     */
    public function deleteCategory($term, $tt_id, $taxonomy, $deleted_term) {
        if ('category' !== $taxonomy) {
            return;
        }
        $response = pubjet_sync_category([
                                             [
                                                 'title' => $deleted_term->name,
                                                                                                                                                                                                                                                                                                          'unique_name' => $deleted_term->slug,
                                             ],
                                         ], EnumHttpMethods::DELETE);
        pubjet_log($response);
    }

    /**
     * @return void
     */
    public function alignReportageImagesCenter() {
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
            .pubjet-reportage img {
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
        global $pubjet_settings;

        $last_check = pubjet_isset_value($pubjet_settings[EnumOptions::LastCheckingMissedPosts]);

        if (pubjet_now_ts() - $last_check < 60) {
            return;
        }

        pubjet_update_setting(EnumOptions::LastCheckingMissedPosts, pubjet_now_ts());

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