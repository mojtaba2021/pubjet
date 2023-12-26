<?php

namespace triboon\pubjet\includes;

use DateTime;
use DateTimeZone;

if (!defined("ABSPATH")) exit;

class RewriteHooks extends Singleton {

    /**
     * @return void
     */
    public function init() {
        add_action('pubjet-api_reportage', [$this, 'reportageRequest'], 15);
        add_action('pubjet-api_check-missed-reportage', [$this, 'checkMissedReportage'], 15);
    }

    public function isTokenValid() {

        if (empty(get_option('pubjet_token'))) {
            wp_send_json_error("No token has been set in the settings!", 401);
        }

        $header_token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        return get_option('pubjet_token') == $header_token;

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

        $post_type = sanitize_text_field(PUBJET_POST_TYPE);
        $sql       = "SELECT ID FROM $wpdb->posts WHERE post_type='$post_type' AND post_status='future' AND post_date_gmt<'$now'";
        $result    = $wpdb->get_results($sql);

        pubjet_log($result);

        if ($result) {
            foreach ($result as $post) {
                wp_publish_post($post->ID);
            }
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

        if ($response) {
            // Success
            wp_send_json_success($response);
        }

        // Error
        wp_send_json_error($response);

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

}