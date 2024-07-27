<?php

namespace triboon\pubjet\includes;

use DateTime;
use DateTimeZone;
use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\traits\Utils;

defined('ABSPATH') || exit;

class RestApi extends Singleton {

    use Utils;

    /**
     * @return void
     */
    public function init() {
        add_action('rest_api_init', [$this, 'registerRoutes'], 15);
    }

    /**
     * @return void
     */
    public function registerRoutes() {
        $this->registerRoute('reportage', 'createOrUpdateReportage', ['POST', 'PATCH']);
        $this->registerRoute('reportage', 'deleteReportage', ['DELETE']);
        $this->registerRoute('version', 'getPluginVersion', ['GET']);
        $this->registerRoute('copyright', 'toggleCopyright', ['POST', 'PATCH']);
        $this->registerRoute('site/info', 'findSiteInfo', ['GET']);
        $this->registerRoute('site/tags', 'findSiteTags', ['GET']);
        $this->registerRoute('site/categories', 'findSiteCategories', ['GET']);
        $this->registerRoute('check-missed-reportage', 'checkMissedReportage', ['POST']);
    }

    /**
     * @return void
     */
    public function findSiteCategories() {
        $this->success(pubjet_find_wp_categories(0, false));
    }

    /**
     * @return void
     */
    public function findSiteTags(\WP_REST_Request $request) {
        $tags = pubjet_find_wp_tags();
        $this->success($tags);
    }

    /**
     * @return void
     */
    public function checkMissedReportage(\WP_REST_Request $request) {
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
            if (!pubjet_is_reportage($post->ID)) {
                continue; // Just publish reportage post
            }
            wp_publish_post($post->ID);
        }
    }

    /**
     * @return void
     */
    public function deleteReportage(\WP_REST_Request $request) {
        $reportage = (object)($request->get_json_params());
        pubjet_log("==== Delete Reportage Post ====");
        pubjet_log($reportage);
        $reportage_post_id = pubjet_find_post_id_by_reportage_id(pubjet_isset_value($reportage->id));
        pubjet_log('Post: ' . $reportage_post_id);

        if (empty($reportage_post_id)) {
            wp_send_json_error(pubjet__('rep-not-found'), 404);
        }

        $post = get_post($reportage_post_id);
        if ($post->post_type !== pubjet_post_type()) {
            wp_send_json_error(pubjet__('rep-not-found'), 404);
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
    public function findSiteInfo(\WP_REST_Request $request) {
        /**
         * The pubjet_siteinfo filter.
         *
         * @since 1.0.0
         */
        $result = apply_filters('pubjet_siteinfo', [
            'title'   => get_bloginfo('name'),
            'descr'   => get_bloginfo('description'),
            'url'     => get_bloginfo('wpurl'),
            'version' => [
                'site'   => get_bloginfo('version'),
                'pubjet' => PUBJ()->getVersion(),
            ],
        ]);
        $this->success($result);
    }

    /**
     * @return void
     */
    public function toggleCopyright(\WP_REST_Request $request) {
        $data = (object)$request->get_json_params();
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
    public function getPluginVersion(\WP_REST_Request $request) {
        $this->success(['version' => PUBJ()->getVersion(),]);
    }

    /**
     * @return void
     */
    public function createOrUpdateReportage(\WP_REST_Request $request) {
        $reportage = (object)$request->get_json_params();

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

    /**
     * @param $namespace
     * @param $callback
     * @param $method
     * @param $args
     *
     * @return void
     */
    public function registerRoute($namespace, $callback, $method = \WP_REST_Server::READABLE, $args = null) {
        register_rest_route('pubjet/v1', "/$namespace", [
            'methods'             => $method,
            'callback'            => [$this, $callback],
            'permission_callback' => [$this, 'permissonCallback'],
        ]);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return boolean
     */
    public function permissonCallback(\WP_REST_Request $request) {
        if (!$this->isTokenValid($request)) {
            wp_send_json_error(pubjet__('invalid-token'), 401);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isTokenValid(\WP_REST_Request $request) {
        if (pubjet_is_dev_mode()) {
            return true;
        }
        if (empty(pubjet_token())) {
            wp_send_json_error(pubjet__('missing-token'), 401);
        }
        $header_token = pubjet_isset_value($request['authorization'], '');
        return pubjet_token() == $header_token;
    }

    /**
     * @return void
     */
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

}