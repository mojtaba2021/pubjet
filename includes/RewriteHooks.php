<?php

namespace triboon\pubjet\includes;

use DateTime;
use DateTimeZone;
use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\traits\Utils;

if (!defined("ABSPATH")) exit;

class RewriteHooks extends Singleton {

    use Utils;

    /**
     * @return void
     */
    public function init() {
        add_action('pubjet-api_reportage', [$this, 'reportageRequest'], 15);
        add_action('pubjet-api_version', [$this, 'checkPluginVersion'], 15);;
        add_action('pubjet-api_copyright', [$this, 'toggleCopyright'], 15);
        add_action('pubjet-api_check-missed-reportage', [$this, 'checkMissedReportage'], 15);
        add_action('pubjet-api_siteinfo', [$this, 'siteInfo'], 15);
    }

    /**
     * @return void
     */
    public function siteInfo() {
        $data = $this->check(['GET'], false);
        if (is_array($data) && isset($data['error'])) {
            wp_send_json_error(pubjet_isset_value($data['message']), pubjet_isset_value($data['status']));
        }

        $result = [
            'title' => get_bloginfo('name'),
            'descr' => get_bloginfo('description'),
            'url'   => get_bloginfo('wpurl'),
        ];

        $this->success($result);
    }

    /**
     * @return void
     */
    public function toggleCopyright() {
        $data = $this->check(['POST', 'PATCH']);
        if (is_array($data) && isset($data['error'])) {
            wp_send_json_error(pubjet_isset_value($data['message']), pubjet_isset_value($data['status']));
        }

        pubjet_log('Change Copyright Status');
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
            update_option(EnumOptions::CopyrightStatus, 'hide');
        } else {
            // Show copyright
            delete_option(EnumOptions::CopyrightStatus);
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
    public function deleteReportage() {
        $reportage = $this->check(['DELETE']);
        if (is_array($reportage) && isset($reportage['error'])) {
            wp_send_json_error(pubjet_isset_value($reportage['message']), pubjet_isset_value($reportage['status']));
        }

        pubjet_log($reportage);
        $reportage_post_id = pubjet_find_post_id_by_reportage_id(pubjet_isset_value($reportage->id));
        pubjet_log('Delete Reportage Post: ' . $reportage_post_id);

        if (empty($reportage_post_id)) {
            wp_send_json_error(pubjet__('post-not-found'), 404);
        }

        $post = get_post($reportage_post_id);
        if ($post->post_type !== pubjet_post_type()) {
            wp_send_json_error(pubjet__('post-not-found'), 404);
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
    public function findReportage() {
        $request_data = $this->check(['GET'], false);
        if (is_array($request_data) && isset($request_data['error'])) {
            wp_send_json_error(pubjet_isset_value($request_data['message']), pubjet_isset_value($request_data['status']));
        }

        if (empty($this->get('id'))) {
            wp_send_json_error(pubjet__('post-not-found'), 404);
        }

        pubjet_log('Get Reportage Post');
        pubjet_log($_GET);

        $reportage_post_id = pubjet_find_post_id_by_reportage_id($this->get('id'));
        $reportage_post    = get_post($reportage_post_id);
        if (!$reportage_post_id || empty($reportage_post)) {
            wp_send_json_error(pubjet__('post-not-found'), 404);
        }

        $this->success([
                           'id'    => $reportage_post->ID,
                           'title' => $reportage_post->post_title,
                           'url'   => get_permalink($reportage_post->ID),
                       ]);
    }

    /**
     * @return void
     */
    public function checkPluginVersion() {
        $this->success([
                           'version' => PUBJ()->getVersion(),
                       ]);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function checkMissedReportage() {

        if (!$this->isValidHttpMethod(['POST'])) {
            wp_send_json_error(pubjet__('invalid-http-method'), 401);
        }

        if (!$this->isTokenValid()) {
            wp_send_json_error(pubjet__('invalid-token'), 401);
        }

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
            wp_publish_post($post->ID);
        }

    }

    public function reportageRequest() {

        // =================== Delete Reportage =================
        if ('DELETE' === pubjet_get_request_method()) {
            $this->deleteReportage();
            return;
        } else if ('GET' === pubjet_get_request_method()) {
            $this->findReportage();
            return;
        }

        // =================== Insert or Update ===================

        if (!$this->isValidHttpMethod(['POST', 'PATCH'])) {
            wp_send_json_error(pubjet__('invalid-http-method'), 401);
        }

        if (!$this->isTokenValid()) {
            wp_send_json_error(pubjet__('invalid-token'), 401);
        }

        $reportage = (object)$this->getRequestData();

        pubjet_log($reportage);

        $wp_post_id = ReportagePost::insert($reportage);

        if (!$wp_post_id) {
            pubjet_log('Error: ' . $wp_post_id->get_error_message());
            wp_send_json_error($wp_post_id);
        }

        // Log
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

    public function finishRequest() {
        ignore_user_abort(true);

        if (!headers_sent()) {
            header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
            header('Cache-Control: no-cache, must-revalidate, max-age=0');
        }

        if (PHP_VERSION_ID >= 70016 && function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
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

    /**
     * @return bool
     */
    public function isTokenValid() {

        if (pubjet_is_dev_mode()) {
            return true;
        }

        if (empty(pubjet_token())) {
            wp_send_json_error(pubjet__('missing-token'), 401);
        }

        $header_token = pubjet_isset_value($_SERVER['HTTP_AUTHORIZATION'], '');

        return pubjet_token() == $header_token;

    }

    public function isValidHttpMethod($valid_methods = ['POST']) {
        return in_array($_SERVER['REQUEST_METHOD'], $valid_methods);
    }

    public function getRequestData() {
        $stream = fopen('php://input', 'r');
        if ($stream) {
            $rawData = '';
            while ($chunk = fread($stream, pubjet_isset_value($_SERVER['CONTENT_LENGTH']))) {
                $rawData .= $chunk;
            }
            fclose($stream);
            return json_decode($rawData, true);
        }
        return [];
    }

    /**
     * @return array|bool|object
     */
    private function check($method, $get_request_data = true) {
        if (!is_array($method)) {
            $method = [$method];
        }

        if (!$this->isValidHttpMethod($method)) {
            return [
                'error'   => true,
                'status'  => 401,
                'message' => pubjet__('invalid-http-method'),
            ];
        }

        if (!$this->isTokenValid()) {
            return [
                'error'   => true,
                'status'  => 401,
                'message' => pubjet__('invalid-token'),
            ];
        }

        if ($get_request_data) {
            return (object)$this->getRequestData();
        }

        return true;
    }

}