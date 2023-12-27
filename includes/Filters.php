<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumPostMetakeys;

defined('ABSPATH') || exit;

class Filters extends Singleton {

    /**
     * @return void
     */
    public function init() {
        add_filter("display_post_states", [$this, "displayPostStates"], 15, 2);
        add_filter('parse_query', [$this, "adminFilterPosts"], 15);
        add_filter("the_content", [$this, "filterTheContent"], 0, 2);
        add_filter('post_row_actions', [$this, 'regenerateThumbnail'], 15, 2);
    }

    /**
     * @return void
     */
    public function regenerateThumbnail($actions, $post) {
        if (!pubjet_is_reportage($post->ID)) {
            return $actions;
        }
        $actions['rethumb'] = sprintf('<button type="button" class="button-link pubjet-regthumb" data-post-id="%s">تولید مجدد تصویر شاخص</button>', $post->ID);
        return $actions;
    }

    /**
     * @param $post_states
     * @param $post
     *
     * @return mixed
     */
    public function displayPostStates($post_states, $post) {
        $reportage_id = get_post_meta($post->ID, EnumPostMetakeys::ReportageId, true);
        if (!empty($reportage_id)) {
            $post_states[] = "رپورتاژ - " . intval($reportage_id);
        }
        return $post_states;
    }

    /**
     * @param $content
     *
     * @return mixed|string
     */
    public function filterTheContent($content) {

        if (!pubjet_show_copyright()) {
            return $content;
        }

        if (!pubjet_is_reportage(get_the_ID())) {
            return $content;
        }

        $content .= '<div class="reportage_content_copyright"><p>منتشر شده توسط <img src="' . PUBJET_DIR_URL . 'assets/img/copyright-logo.png' . '"></img> تریبون</p></div>';

        return $content;

    }

    /**
     * @param $query
     *
     * @return void
     */
    public function adminFilterPosts($query) {
        global $pagenow;
        $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';

        if (is_admin() &&
            'post' == $post_type &&
            'edit.php' == $pagenow &&
            isset($_GET['reportage']) &&
            $_GET['reportage'] == 'true') {
            $query->query_vars['meta_key']     = 'pubjet_reportage_id';
            $query->query_vars['meta_value']   = '';
            $query->query_vars['meta_compare'] = '!=';
        }
    }

}