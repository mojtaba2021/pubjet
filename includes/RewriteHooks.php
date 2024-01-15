<?php

namespace triboon\pubjet\includes;

use DateTime;
use DateTimeZone;
use triboon\pubjet\includes\enums\EnumHttpMethods;
use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\traits\Utils;

if (!defined("ABSPATH")) exit;

class RewriteHooks extends Singleton {

    use Utils;

    /**
     * @return void
     */
    public function init() {
        $this->endpointHandler('reportage', [$this, 'reportageRequest'], 15);
        $this->endpointHandler('version', [$this, 'checkPluginVersion'], 15);;
        $this->endpointHandler('copyright', [$this, 'toggleCopyright'], 15);
        $this->endpointHandler('check-missed-reportage', [$this, 'checkMissedReportage'], 15);
        $this->endpointHandler('siteinfo', [$this, 'siteInfo'], 15);
        $this->endpointHandler('check-token', [$this, 'checkToken'], 15);
    }

    /**
     * @return void
     */
    public function checkToken() {
        $data = $this->check([EnumHttpMethods::GET], false, false);
        if (is_array($data) && isset($data['error'])) {
            $this->error($data['message']);
        }

        $token = $this->get('token');
        if (empty(trim($token))) {
            $this->error(pubjet__('missing-params'));
        }

        /**
         * The pubjet_check_token action.
         *
         * @since 1.0.0
         */
        do_action('pubjet_check_token');

        /**
         * The pubjet_check_token_url filter.
         *
         * @since 1.0.0
         */
        $url = apply_filters('pubjet_check_token_url', pubjet_api_root() . '/external/wp/token-validation/', $token);

        if (pubjet_is_dev_mode()) {
            $url = 'https://api-staging.triboon.net/external/wp/token-validation/';
        }

        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Token ' . trim($token),
        ];

        $result = $this->request($url, EnumHttpMethods::GET, $headers);

        if (is_wp_error($result)) {
            $this->error($result->get_error_message());
        }

        if (isset($result['code']) && 403 == $result['code']) {
            $this->error(pubjet__('invalid-token'));
        }

        if (!isset($result['body']->is_valid)) { // Some error occured
            $this->error(pubjet__('error-occured'));
        }

        // Check if token is valid or not
        if (!$result['body']->is_valid) {
            $this->error(pubjet__('invalid-token'), [
                'invalid' => true,
            ]);
        }

        $this->success([
                           'valid'      => true,
                           'first_name' => pubjet_isset_value($result['body']->extra->first_name),
                           'last_name'  => pubjet_isset_value($result['body']->extra->last_name),
                           'phone'      => pubjet_isset_value($result['body']->extra->phone),
                           'email'      => pubjet_isset_value($result['body']->extra->email),
                       ]);
    }

    /**
     * @return void
     */
    public function siteInfo() {

        $data = $this->check(['GET'], false);

        if (is_array($data) && isset($data['error'])) {
            wp_send_json_error(pubjet_isset_value($data['message']), pubjet_isset_value($data['status']));
        }

        /**
         * The pubjet_siteinfo filter.
         *
         * @since 1.0.0
         */
        $result = apply_filters('pubjet_siteinfo', [
            'title'     => get_bloginfo('name'),
            'descr'     => get_bloginfo('description'),
            'url'       => get_bloginfo('wpurl'),
            'wpversion' => get_bloginfo('version'),
        ]);

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

        pubjet_log("==== Delete Reportage Post ====");
        pubjet_log($reportage);
        $reportage_post_id = pubjet_find_post_id_by_reportage_id(pubjet_isset_value($reportage->id));
        pubjet_log('Post: ' . $reportage_post_id);

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

        pubjet_log('===== Get Reportage Post =====');
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
        if (!$this->isValidHttpMethod([EnumHttpMethods::POST, EnumHttpMethods::PATCH])) {
            wp_send_json_error(pubjet__('invalid-http-method'), 401);
        }

        if (!$this->isTokenValid()) {
            wp_send_json_error(pubjet__('invalid-token'), 401);
        }

        $reportage = (object)$this->getRequestData();

        pubjet_log($reportage);

        $wp_post_id = ReportagePost::insert($reportage);

        if (!$wp_post_id) {
            if (is_wp_error($wp_post_id)) {
                pubjet_log('Error: ' . $wp_post_id->get_error_message());
            }
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
    private function check($method, $get_request_data = true, $check_token = true) {
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

        if ($check_token && !$this->isTokenValid()) {
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