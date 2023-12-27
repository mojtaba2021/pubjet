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
        add_action('pubjet-api_check-missed-reportage', [$this, 'checkMissedReportage'], 15);
        add_action('pubjet-api_version', [$this, 'checkPluginVersion'], 15);;
    }

    /**
     * @return void
     */
    public function checkPluginVersion() {
        $this->success([
                           'version' => PUBJ()->getVersion(),
                       ]);
    }


    public function isValidHttpMethod($valid_methods = ['POST']) {
        return in_array($_SERVER['REQUEST_METHOD'], $valid_methods);
    }

    public function getRequestData() {
        $stream = fopen('php://input', 'r');
        if ($stream) {
            $rawData = '';
            while ($chunk = fread($stream, $_SERVER['CONTENT_LENGTH'])) {
                $rawData .= $chunk;
            }
            fclose($stream);
            return json_decode($rawData, true);
        }
        return [];
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function checkMissedReportage() {

        if (!$this->isValidHttpMethod(['POST'])) {
            wp_send_json_error("The request method is invalid", 401);
        }

        if (!$this->isTokenValid()) {
            wp_send_json_error("The token is not valid!", 401);
        }


        $this->finishRequest();

        global $wpdb;

        $dt = new DateTime(date('Y-m-d H:i:s e'));
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        $now = $dt->format('Y-m-d H:i:s');

        $sql    = $wpdb->prepare("SELECT `ID` FROM $wpdb->posts WHERE `post_type` = %s AND post_status='future' AND post_date_gmt < %s", PUBJET_POST_TYPE, $now);
        $result = $wpdb->get_results($sql);

        pubjet_log($result);

        if (!$result) {
            return;
        }

        foreach ($result as $post) {
            wp_publish_post($post->ID);
        }

    }

    public function reportageRequest() {

        if (!$this->isValidHttpMethod(['POST', 'PATCH'])) {
            wp_send_json_error("The request method is invalid", 401);
        }

        if (!$this->isTokenValid()) {
            wp_send_json_error("The token is not valid!", 401);
        }

        $reportage = (object)$this->getRequestData();

        $response = ReportagePost::insert($reportage);
        if (!$response) {
            wp_send_json_error($response);
        }

        // Success
        wp_send_json_success($response);
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

        if (pubjet_is_debug_mode()) {
            return true;
        }

        if (empty(pubjet_token())) {
            wp_send_json_error("No token has been set in the settings!", 401);
        }

        $header_token = pubjet_isset_value($_SERVER['HTTP_AUTHORIZATION'], '');

        return pubjet_token() == $header_token;

    }

}