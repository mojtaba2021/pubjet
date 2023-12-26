<?php

namespace triboon\pubjet\includes;

if (!defined("ABSPATH")) exit;

use DateTime;
use DateTimeZone;
use Statickidz\GoogleTranslate;

class ReportagePost extends Singleton {

    /**
     * @since 1.0.0
     */
    public function __construct() {
        add_action("publish_" . PUBJET_POST_TYPE, [$this, "afterPublishReportage"]);
    }

    public static function get_post_date($prefrred_date) {
        $dt = new DateTime($prefrred_date);
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        return $dt->format('Y-m-d H:i:s');
    }


    public static function get_post_status($post_date) {

        $dt = new DateTime(date('Y-m-d H:i:s e'));
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        $current_time = $dt->format('Y-m-d H:i:s');

        return strtotime($post_date) > strtotime($current_time) ? 'future' : 'publish';

    }

    public static function update($thereportage) {

        if (!isset($thereportage->wp_post_id)) {
            return false;
        }

        $post_id      = intval($thereportage->wp_post_id);
        $reportage_id = get_post_meta($post_id, 'pubjet_reportage_id', true);

        if (!get_post($thereportage->wp_post_id) || $reportage_id != $thereportage->id) {
            return false;
        }

        $post_date   = self::get_post_date($thereportage->preferred_publish_date);
        $post_status = self::get_post_status($post_date);

        $args = [
            'ID'          => $post_id,
            'post_type'   => PUBJET_POST_TYPE,
            'post_status' => $post_status,
            'tags_input'  => isset($thereportage->tags) && is_array($thereportage->tags) ? map_deep($thereportage->tags, 'sanitize_text_field') : [],
        ];

        if ($post_status !== 'publish') {
            $args['post_date']     = $post_date;
            $args['post_date_gmt'] = $post_date;
        }

        $reportage_url = get_post_meta($post_id, 'pubjet_reportage_content_url', true);
        if ($reportage_url !== $thereportage->content_file) {

            $post_content = self::get_content_file($thereportage);
            $post_content = self::get_post_content($post_content, $thereportage->title);

            $args['post_title']   = $post_content['title'] ?? '';
            $args['post_content'] = $post_content['content'] ?? '';

            $args['meta_input'] = [
                'pubjet_reportage_content_url' => $thereportage->content_file,
            ];

        }

        $update = wp_update_post($args);

        return boolval($update);
    }

    public static function insert($reportage) {

        if ($reportage->wp_post_id = self::reportage_exists($reportage->id)) {
            return self::update($reportage);
        }

        $def_category = get_option('pubjet_default_category');

        $post_content = self::get_content_file($reportage);
        $post_content = self::get_post_content($post_content, $reportage->title);

        $post_date   = self::get_post_date($reportage->preferred_publish_date);
        $post_status = self::get_post_status($post_date);

        $args = [
            'post_type'     => sanitize_text_field(PUBJET_POST_TYPE),
            'post_title'    => isset($post_content['title']) ? sanitize_text_field($post_content['title']) : '',
            'post_status'   => sanitize_text_field($post_status),
            'post_content'  => $post_content['content'] ?? '',
            'post_name'     => sanitize_text_field(self::get_post_name($reportage)),
            'tags_input'    => isset($reportage->tags) && is_array($reportage->tags) ? map_deep($reportage->tags, 'sanitize_text_field') : [],
            'post_category' => (int)$def_category > 0 ? [intval($def_category)] : '',
            'meta_input'    => [
                'pubjet_reportage_id'          => intval($reportage->id),
                'pubjet_reportage_content_url' => sanitize_url($reportage->content_file),
            ],
        ];

        if ($post_status !== 'publish') {
            $args['post_date']     = sanitize_text_field($post_date);
            $args['post_date_gmt'] = sanitize_text_field($post_date);
        }

        $post_id = wp_insert_post($args);

        if (!is_wp_error($post_id)) {
            if (isset($post_content['featured_img_id'])) {
                set_post_thumbnail($post_id, intval($post_content['featured_img_id']));
            }

            return $post_id;
        }

        return false;
    }

    public static function get_time_format($utc_datetime_str) {
        $dt = new DateTime($utc_datetime_str, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        return $dt->format('Y-m-d H:i:s');
    }

    public static function reportage_exists($reportage_id) {
        global $wpdb;
        $tbl     = $wpdb->prefix . 'postmeta';
        $post_id = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM $tbl where meta_key ='pubjet_reportage_id' and meta_value like '%d' LIMIT 1", $reportage_id));

        return is_array($post_id) ? reset($post_id) : $post_id;
    }

    public static function get_post_content($content, $title) {
        $content                         = self::handle_images($content);
        $post_content                    = self::nomalize_html($content['html_file']);
        $post_content                    = self::remove_repeate_headeing_title_in_content($post_content, $title);
        $post_content['featured_img_id'] = $content['featured_img_id'];
        return $post_content;
    }

    public static function get_post_name($reportage) {
        $post_name = $reportage->title;
        $trans     = new GoogleTranslate();
        $post_name = $trans->translate('fa', 'en', $reportage->title);
        return sanitize_title_with_dashes($post_name, '', 'save');
    }

    public static function nomalize_html($post_content) {
        $post_content = preg_replace('/\s*<a/', '<a', $post_content);
        $post_content = preg_replace('/<\/a>\s*/', '</a>', $post_content);
        $post_content = str_replace("\n\r", "", $post_content);
        $post_content = str_replace("\n", "", $post_content);

        return $post_content;
    }

    public static function handle_images($html_content) {
        preg_match_all('/<img[^>]+>/i', $html_content, $result);
        $featured_image_isset = false;
        $featured_image_id    = null;

        foreach ($result[0] as $img) {

            $pattern = '/<img\s+[^>]*src="([^"]+)"[^>]*>/i';
            if (preg_match($pattern, $img, $matches)) {

                $src = $matches[1];

                $attach_id = self::upload_from_url(str_replace('\\"', '', $src));
                if ($featured_image_isset == false) {
                    $featured_image_id    = $attach_id;
                    $featured_image_isset = true;
                }

                $html_content = str_replace($src, wp_get_attachment_url($attach_id), $html_content);
            }

        }

        return ['html_file' => $html_content, 'featured_img_id' => $featured_image_id];
    }

    public static function upload_from_url($url, $title = null) {
        require_once(ABSPATH . "/wp-load.php");
        require_once(ABSPATH . "/wp-admin/includes/image.php");
        require_once(ABSPATH . "/wp-admin/includes/file.php");
        require_once(ABSPATH . "/wp-admin/includes/media.php");

        // Download url to a temp file
        $tmp = download_url($url);
        if (is_wp_error($tmp)) return false;

        // Get the filename and extension ("photo.png" => "photo", "png")
        $filename  = pathinfo($url, PATHINFO_FILENAME);
        $extension = pathinfo($url, PATHINFO_EXTENSION);

        // An extension is required or else WordPress will reject the upload
        if (!$extension) {
            // Look up mime type, example: "/photo.png" -> "image/png"
            $mime = mime_content_type($tmp);
            $mime = is_string($mime) ? sanitize_mime_type($mime) : false;

            // Only allow certain mime types because mime types do not always end in a valid extension (see the .doc example below)
            $mime_extensions = [
                // mime_type         => extension (no period)
                'text/plain'         => 'txt',
                'text/csv'           => 'csv',
                'application/msword' => 'doc',
                'image/jpg'          => 'jpg',
                'image/jpeg'         => 'jpeg',
                'image/gif'          => 'gif',
                'image/png'          => 'png',
                'video/mp4'          => 'mp4',
            ];

            if (isset($mime_extensions[$mime])) {
                // Use the mapped extension
                $extension = $mime_extensions[$mime];
            } else {
                // Could not identify extension
                @unlink($tmp);
                return false;
            }
        }


        // Upload by "sideloading": "the same way as an uploaded file is handled by media_handle_upload"
        $args = [
            'name'     => "$filename.$extension",
            'tmp_name' => $tmp,
        ];

        // Do the upload
        $attachment_id = media_handle_sideload($args, 0, $title);

        // Cleanup temp file
        @unlink($tmp);

        // Error uploading
        if (is_wp_error($attachment_id)) return false;

        // Success, return attachment ID (int)
        return (int)$attachment_id;
    }

    public static function get_content_file($reportage) {

        $content_file = str_replace("https://cdn.triboon.net", "https://cdn.pubjet.ir", $reportage->content_file);

        $response = wp_remote_get($content_file);
        $body     = wp_remote_retrieve_body($response);

        $body = str_replace("https://cdn.triboon.net", "https://cdn.pubjet.ir", $body);

        return $body;
    }

    public static function remove_repeate_headeing_title_in_content($content, $object_title) {

        preg_match('/<h1\b[^>]*>(.*?)<\/h1>/i', $content, $matches);

        foreach ($matches as $index => $matche) {
            $h1Tag = strip_tags($matche);
            if ($index == 0) {
                $object_title = empty(trim($object_title)) ? trim($h1Tag) : $object_title;
                $content      = str_replace($matche, '', $content);
            } else {
                $object_title = empty(trim($object_title)) ? trim($h1Tag) : $object_title;
                $matche2      = str_replace('<h1', '<h2', $matche);
                $matche2      = str_replace('</h1', '</h2', $matche2);
                $content      = str_replace($matche, $matche2, $content);
            }
        }

        $content = str_replace('<a', ' <a', $content);
        $content = str_replace('</a>', '</a> ', $content);

        return [
            'content' => $content,
            'title'   => $object_title,
        ];
    }

    /**
     * @param $post_id
     *
     * @return void
     */
    public function afterPublishReportage($post_id) {
        $reportage_id = get_post_meta($post_id, 'pubjet_reportage_id', true);

        if (!empty($reportage_id)) {
            $this->publish_reportage_request($post_id, $reportage_id);
        }
    }

    /**
     * @param $post_id
     * @param $reportage_id
     *
     * @return mixed
     */
    public function publish_reportage_request($post_id, $reportage_id) {
        $url = PUBJET_API_ROOT . '/exsternal/wp/reportages/' . $reportage_id . '/publish';

        $args = [
            'headers'     => [
                'Authorization' => 'api-key ' . PUBJET_API_TOKEN,
                'Content-Type'  => 'application/json',
            ],
            'body'        => json_encode(['url' => get_the_permalink($post_id)]),
            'method'      => 'POST',
            'data_format' => 'body',
        ];

        $response = wp_remote_post($url, $args);

        pubjet_log([$response, $url, $args]);

        return json_decode(wp_remote_retrieve_body($response), true);

    }
}