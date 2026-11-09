<?php

namespace triboon\pubjet\includes;

if (!defined("ABSPATH")) exit;

use DateTime;
use DateTimeZone;
use triboon\pubjet\includes\enums\EnumPostMetakeys;
use triboon\pubjet\includes\enums\EnumPostStatus;

class ReportagePost extends Singleton
{

    /**
     * No new image download starts later than this many seconds after the start of the
     * request, so creating a reportage stays under the usual 30 second limit.
     */
    const IMAGE_DOWNLOAD_START_LIMIT = 13;

    /**
     * Number of images not downloaded in this request because of the time limit.
     *
     * @var int
     */
    private static $skipped_images = 0;

    /**
     * @since 1.0.0
     */
    public function __construct()
    {
        add_action("transition_post_status", [$this, "afterPublishReportage"], 15, 3);
    }

    /**
     * Fill the optional fields of the panel data with default values, so a
     * missing field does not cause PHP warnings.
     *
     * @param mixed $reportage Panel data.
     *
     * @return object
     */
    public static function normalize($reportage)
    {
        $reportage = is_object($reportage) ? $reportage : (object) (is_array($reportage) ? $reportage : []);

        $defaults = [
            'id'                             => 0,
            'title'                          => '',
            'content_file'                   => '',
            'content_file_html'              => '',
            'lead_content'                   => '',
            'lead_image'                     => '',
            'lead_image_obj'                 => [],
            'meta_title'                     => '',
            'meta_description'               => '',
            'tags'                           => [],
            'preferred_publish_date'         => '',
            'is_publish_without_triboon_tag' => false,
            'relative_category'              => null,
            'pricing_plan_title'             => '',
        ];
        foreach ($defaults as $key => $default) {
            if (!property_exists($reportage, $key) || (null === $reportage->$key && null !== $default)) {
                $reportage->$key = $default;
            }
        }
        foreach (['title', 'content_file', 'content_file_html', 'lead_content', 'meta_title', 'meta_description', 'preferred_publish_date', 'pricing_plan_title'] as $key) {
            if (!is_scalar($reportage->$key)) {
                $reportage->$key = '';
            }
        }
        if (!is_array($reportage->tags)) {
            $reportage->tags = [];
        }

        return $reportage;
    }

    public static function get_post_date($prefrred_date)
    {
        $dt = new DateTime($prefrred_date);
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * @param $post_date
     *
     * @return string
     * @throws \Exception
     */
    public static function get_post_status($post_date)
    {
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        $dt->modify('+3 minutes');
        $current_time = $dt->format('Y-m-d H:i:s');
        return strtotime($post_date) > strtotime($current_time) ? 'future' : 'publish';
    }

    /**
     * @param $thereportage
     *
     * @return bool|\WP_Error
     */
    public static function update($thereportage)
    {
        $thereportage = self::normalize($thereportage);

        if (!isset($thereportage->wp_post_id) || !$thereportage->wp_post_id) {
            return false;
        }

        $post_id = intval($thereportage->wp_post_id);
        $reportage_id = get_post_meta($post_id, EnumPostMetakeys::ReportageId, true);

        if (!get_post($thereportage->wp_post_id) || $reportage_id != $thereportage->id) {
            return false;
        }

        $post_date = self::get_post_date($thereportage->preferred_publish_date);
        $post_status = self::get_post_status($post_date);

        $args = [
            'ID' => $post_id,
            'post_type' => pubjet_post_type(),
            'post_status' => $post_status,
            'tags_input' => isset($thereportage->tags) && is_array($thereportage->tags) ? map_deep($thereportage->tags, 'sanitize_text_field') : [],
        ];

        if ($post_status !== 'publish') {
            $args['post_date'] = $post_date;
            // post_date is the site's local time
            $args['post_date_gmt'] = get_gmt_from_date($post_date);
        }

        $reportage_url = get_post_meta($post_id, EnumPostMetakeys::ReportageContentUrl, true);
        if ($reportage_url !== $thereportage->content_file) {

            $post_content = !empty($thereportage->content_file_html) ? $thereportage->content_file_html : self::get_content_file($thereportage);
            if ('' === trim((string) $post_content)) {
                // The current content is kept
                return new \WP_Error('empty-content', pubjet__('empty-reportage-content'));
            }
            $post_content = self::get_post_content($post_content, $thereportage->title);
            $post_content['content'] = Filters::filterReportageHtml($post_content['content'] ?? '', $thereportage->id);

            $args['post_title'] = $post_content['title'] ?? '';
            $args['post_content'] = $post_content['content'] ?? '';

            $args['meta_input'] = [
                EnumPostMetakeys::PanelData => $thereportage,
                EnumPostMetakeys::ReportageContentUrl => $thereportage->content_file,
            ];

        }

        add_filter('wp_kses_allowed_html', [Filters::class, "allowReportageIframe"], 10, 2);
        try {
            $update = wp_update_post($args);
        } finally {
            remove_filter('wp_kses_allowed_html', [Filters::class, "allowReportageIframe"], 10);
        }

        return boolval($update);
    }

    /**
     * @param $reportage
     *
     * @return bool|int|\WP_Error
     */
    public static function insert($reportage)
    {

        $reportage = self::normalize($reportage);
        pubjet_log('================== Insert ===================');
        if ($reportage->wp_post_id = self::reportage_exists($reportage->id)) {
//            pubjet_log('==================== Updating ===================');
//            return self::update($reportage);
            return new \WP_Error('reportage-exists', 'رپورتاژ قبلا در رسانه منتشر شده است و امکان ثبت مجدد آن نیست');
        }

        $def_category = self::findReportageCategory($reportage);
        $post_content = !empty($reportage->content_file_html) ? $reportage->content_file_html : self::get_content_file($reportage);

        // Check if we have gateway error
        if (pubjet_gateway_error($post_content)) {
            return new \WP_Error('gateway-error', pubjet__('gateway-error'));
        }

        // Content not received from the CDN or empty: no post is published, the panel can send it again
        if ('' === trim((string) $post_content)) {
            return new \WP_Error('empty-content', pubjet__('empty-reportage-content'));
        }

        $post_content = self::get_post_content($post_content, $reportage->title);
        $post_content['content'] = Filters::filterReportageHtml($post_content['content'] ?? '', $reportage->id);
        $post_date = self::get_post_date($reportage->preferred_publish_date);
        $post_status = self::get_post_status($post_date);
        $post_excerpt = self::normalize_html($reportage->lead_content ?? '');

        list($lead_image, $lead_image_alt) = self::lead_image($reportage, true);
        $post_thumbnail = $lead_image ? self::upload_from_url($lead_image, $lead_image_alt) : null;


        $args = [
            'post_type' => sanitize_text_field(pubjet_post_type()),
            'post_title' => isset($post_content['title']) ? sanitize_text_field($post_content['title']) : '',
            'post_status' => 'future' === $post_status ? $post_status : EnumPostStatus::Publish,
            'post_content' => $post_content['content'] ?? '',
            'post_excerpt' => $post_excerpt ?? '',
            'tags_input' => isset($reportage->tags) && is_array($reportage->tags) ? map_deep($reportage->tags, 'sanitize_text_field') : [],
            'post_category' => (int)$def_category > 0 ? [intval($def_category)] : '',
            'meta_input' => [
                EnumPostMetakeys::ReportageId => intval($reportage->id),
                EnumPostMetakeys::ReportageContentUrl => sanitize_url($reportage->content_file),
                EnumPostMetakeys::PanelData => $reportage,
                EnumPostMetakeys::Source => 'triboon',
                EnumPostMetakeys::MetaTitle => sanitize_text_field($reportage->meta_title ?? ''),
                EnumPostMetakeys::MetaDescription => sanitize_text_field($reportage->meta_description ?? '')
            ],
        ];

        if ($post_status != EnumPostStatus::Publish) {
            $args['post_date'] = sanitize_text_field($post_date);
            // post_date is the site's local time
            $args['post_date_gmt'] = get_gmt_from_date(sanitize_text_field($post_date));
        }

        /**
         * The pubjet_new_reportage_post_args filter.
         *
         * @since 1.0.0
         */
        $args = apply_filters('pubjet_new_reportage_post_args', $args, $reportage);

        pubjet_log('======= Reportage =======');
        pubjet_log($reportage);
        pubjet_log('======= New Post Args =======');
        pubjet_log($args);

        add_filter('wp_kses_allowed_html', [Filters::class, "allowReportageIframe"], 10, 2);
        try {
            $post_id = wp_insert_post($args, true);
        } finally {
            remove_filter('wp_kses_allowed_html', [Filters::class, "allowReportageIframe"], 10);
        }

        pubjet_log('======= New Post Result =======');
        pubjet_log($post_id);

        if (is_wp_error($post_id) || !$post_id) {
            // Reported by the caller with the WordPress error code
            return is_wp_error($post_id) ? $post_id : new \WP_Error('insert-reportage', 'wp_insert_post returned 0');
        }

        // =================== Success ===================

        // Set Post Thumbnail
        $final_thumbnail_id = $post_thumbnail ?: intval($post_content['featured_img_id'] ?? 0);
        if ($final_thumbnail_id) {
            set_post_thumbnail($post_id, $final_thumbnail_id);

            $file_path = get_attached_file($final_thumbnail_id);
            $data_attach = basename($file_path);
            update_post_meta($post_id, 'pubjet_thumbnail_data_attach', $data_attach);
        } elseif (self::has_retry_source($reportage, $post_content['content'] ?? '')) {
            pubjet_log('No featured image for post ' . $post_id . ', retry scheduled');
            self::schedule_thumbnail_retry($post_id, 1);
        } else {
            pubjet_log('No featured image for post ' . $post_id . ' and no image to try');
        }
        if (self::$skipped_images > 0) {
            pubjet_log(self::$skipped_images . ' image(s) of post ' . $post_id . ' left for the retry (time limit)');
            self::schedule_thumbnail_retry($post_id, 1);
        }

        // Publish without Triboon tag
        if (isset($reportage->is_publish_without_triboon_tag) && $reportage->is_publish_without_triboon_tag) {
            update_post_meta($post_id, EnumPostMetakeys::WithoutTriboonTag, true);
        }

        /**
         * The pubjet_new_reportage action.
         *
         * Hooked [Actions, 'changeReportageAuthor'] - 15
         *
         * @since 1.0.0
         */
        do_action('pubjet_new_reportage', $post_id, $reportage);

        return $post_id;
    }

    /**
     * @param $utc_datetime_str
     *
     * @return string
     * @throws \Exception
     */
    public static function get_time_format($utc_datetime_str)
    {
        $dt = new DateTime($utc_datetime_str, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        return $dt->format('Y-m-d H:i:s');
    }

    public static function reportage_exists($reportage_id)
    {
        global $wpdb;
        $post_id = $wpdb->get_col($wpdb->prepare("SELECT `post_id` FROM {$wpdb->postmeta} where `meta_key` = %s AND `meta_value` = %s LIMIT 1", EnumPostMetakeys::ReportageId, $reportage_id));
        if (!$post_id) {
            return false;
        }
        return is_array($post_id) ? reset($post_id) : $post_id;
    }

    /**
     * @param $content
     * @param $title
     *
     * @return array
     */
    public static function get_post_content($content, $title)
    {
        $content = self::handle_images($content);
        pubjet_log($content);
        $post_content = self::normalize_html($content['html_file']);
        $post_content = self::remove_repeate_headeing_title_in_content($post_content, $title);
        $post_content['featured_img_id'] = $content['featured_img_id'];
        return $post_content;
    }

    /**
     * @param $reportage
     *
     * @return string
     */
    public static function get_post_name($reportage)
    {
        // Legacy compatibility: English slug generation is intentionally disabled.
        // Let WordPress generate the slug from the title to avoid conflicts with
        // external translation services and duplicate publish behavior.
        return false;
    }

    public static function normalize_html($post_content)
    {
        $post_content = preg_replace('/\s*<a/', '<a', $post_content);
        $post_content = preg_replace('/<\/a>\s*/', '</a>', $post_content);
        $post_content = str_replace("\n\r", "", $post_content);
        return str_replace("\n", "", $post_content);
    }


    /**
     * @param string        $html_content
     * @param bool          $just_thumbnail Stop after the first image.
     * @param bool          $use_cdn
     * @param string[]|null $only_hosts     Only process images from these hosts.
     *
     * @return array
     */
    public static function handle_images($html_content, $just_thumbnail = false, $use_cdn = false, $only_hosts = null)
    {
        pubjet_log("Starting handle_images", [
            'just_thumbnail' => $just_thumbnail,
            'use_cdn' => $use_cdn,
            'content_length' => strlen($html_content)
        ]);

        if (empty($html_content)) {
            pubjet_log("Empty HTML content provided");
            return ['html_file' => $html_content, 'featured_img_id' => null];
        }

        preg_match_all('/<img[^>]+>/i', $html_content, $result);
        $images_found = count($result[0]);

        if ($images_found === 0) {
            pubjet_log("No images found in HTML");
            return ['html_file' => $html_content, 'featured_img_id' => null];
        }

        pubjet_log("Found {$images_found} images to process");

        $featured_image_isset = false;
        $featured_image_id = null;
        $processed_count = 0;

        foreach ($result[0] as $index => $img) {
            $pattern = '/<img\s+[^>]*src="([^"]+)"[^>]*>/i';
            if (!preg_match($pattern, $img, $matches)) {
                pubjet_log_sentry('Invalid img tag - no src found', [
                    'image_index' => $index,
                    'tag' => $img
                ]);
                continue;
            }

            $src = str_replace('\\"', '', $matches[1]);

            if (empty($src) || !filter_var($src, FILTER_VALIDATE_URL)) {
                pubjet_log_sentry('Invalid image URL', [
                    'image_index' => $index,
                    'src' => $src
                ]);
                continue;
            }

            if (is_array($only_hosts) && !self::url_on_hosts($src, $only_hosts)) {
                continue;
            }

            $alt_text = '';
            if (preg_match('/alt=["\']([^"\']*)["\']/', $img, $alt_matches)) {
                $alt_text = $alt_matches[1];
            }

            $attach_id = self::upload_from_url($src,$alt_text);
            if (!$attach_id) {
                continue; // Error already logged in upload_from_url
            }

            $processed_count++;

            if ($just_thumbnail) {
                $featured_image_id = $attach_id;
                $new_img_tag = self::create_new_img_tag($img, $attach_id, $use_cdn, $src);
                if ($new_img_tag) {
                    $html_content = str_replace($img, $new_img_tag, $html_content);
                }
                break;
            } else {
                if (!$featured_image_isset) {
                    $featured_image_id = $attach_id;
                    $featured_image_isset = true;
                }

                $new_img_tag = self::create_new_img_tag($img, $attach_id, $use_cdn, $src);
                if ($new_img_tag) {
                    $html_content = str_replace($img, $new_img_tag, $html_content);
                }
            }
        }

        pubjet_log(["Images processing completed" => [
            'total_found' => $images_found,
            'processed' => $processed_count,
            'failed' => $images_found - $processed_count,
            'featured_image_id' => $featured_image_id
        ]]);

        return [
            'html_file' => $html_content,
            'featured_img_id' => $featured_image_id,
            'images_processed' => $images_found,
            'processed_urls' => $processed_count,
            'failed_images' => $images_found - $processed_count,
            ];
    }


    public static function create_new_img_tag($original_tag, $attachment_id, $use_cdn = false, $original_src = '')
    {
        if (!$attachment_id || !is_numeric($attachment_id)) {
            pubjet_log_sentry('Invalid attachment ID for img tag creation', [
                'attachment_id' => $attachment_id
            ]);
            return $original_tag;
        }

        $file_path = get_attached_file($attachment_id);
        if (!$file_path) {
            pubjet_log_sentry('Attachment file not found', [
                'attachment_id' => $attachment_id
            ]);
            return $original_tag;
        }

        $data_attach = '';
        if (!empty($original_src)) {
            $path = wp_parse_url($original_src, PHP_URL_PATH);
            if ($path !== null) {
                $data_attach = rawurldecode(basename($path));
            }
        }

        if (empty($data_attach)) {
            $data_attach = basename($file_path);
        }

        // Determine new image URL
        if ($use_cdn && !empty($original_src)) {
            if (!defined('PUBJET_CDN_ROOT')) {
                pubjet_log_sentry('PUBJET_CDN_ROOT not defined, falling back to WP URL', [
                    'attachment_id' => $attachment_id
                ]);
                $new_image_url = wp_get_attachment_url($attachment_id);
            } else {
                $new_image_url = PUBJET_CDN_ROOT . "/" . $data_attach;
            }
        } else {
            $new_image_url = wp_get_attachment_url($attachment_id);
        }

        if (empty($new_image_url)) {
            pubjet_log_sentry('Failed to get attachment URL', [
                'attachment_id' => $attachment_id,
                'use_cdn' => $use_cdn
            ]);
            return $original_tag;
        }

        // Extract attributes from original tag
        $attributes = [];
        preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $original_tag, $attr_matches, PREG_SET_ORDER);

        foreach ($attr_matches as $attr) {
            $attributes[$attr[1]] = $attr[2];
        }

        // Set new attributes
        $attributes['src'] = $new_image_url;
        $attributes['data-attach'] = $data_attach;

        // if alt tag not exists in attributes , get from wordpress image meta
        if (empty($attributes['alt'])) {
            $wp_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            if (!empty($wp_alt)) {
                $attributes['alt'] = $wp_alt;
            }
        }

        // Add loading="lazy" for performance if not set
        if (!isset($attributes['loading'])) {
            $attributes['loading'] = 'lazy';
        }

        // Build new tag
        $new_tag = '<img';
        foreach ($attributes as $key => $value) {
            $new_tag .= ' ' . $key . '="' . esc_attr($value) . '"';
        }
        $new_tag .= '>';

        pubjet_log("Image tag created successfully", [
            'attachment_id' => $attachment_id,
            'cdn_used' => $use_cdn,
            'has_alt' => !empty($attributes['alt'])
        ]);

        return $new_tag;
    }

    /**
     * Hosts of the Triboon CDN ("host" or "host:port").
     *
     * @return string[]
     */
    public static function cdn_hosts()
    {
        /**
         * The pubjet_cdn_hosts filter.
         *
         * @since 5.4.9
         */
        $hosts = apply_filters('pubjet_cdn_hosts', ['cdn.triboon.net', 'cdn.pubjet.ir']);

        return array_map('strtolower', array_filter((array) $hosts, 'is_string'));
    }

    /**
     * Whether the URL is on one of the given hosts ("host" or "host:port").
     *
     * @param string   $url
     * @param string[] $hosts
     *
     * @return bool
     */
    public static function url_on_hosts($url, array $hosts)
    {
        $host = strtolower((string) wp_parse_url((string) $url, PHP_URL_HOST));
        if ('' === $host) {
            return false;
        }
        $port = wp_parse_url((string) $url, PHP_URL_PORT);

        return in_array($host, $hosts, true) || ($port && in_array($host . ':' . $port, $hosts, true));
    }

    /**
     * The Triboon CDN may send "Content-Encoding: aws-chunked" with plain files.
     * cURL then fails with error 61, so automatic decoding is turned off for
     * the Triboon CDN hosts only. The body is the plain file.
     *
     * @param resource|\CurlHandle $handle
     * @param array                $args
     * @param string               $url
     *
     * @return void
     */
    public static function disableCurlDecodingForCdn($handle, $args, $url)
    {
        if (!self::url_on_hosts($url, self::cdn_hosts())) {
            return;
        }
        // PHP 7.4 treats null as an empty string here, which keeps decoding on,
        // so content decoding is also turned off directly.
        // The http_api_curl action gives the cURL handle of a WordPress HTTP API request;
        // there is no request argument for this option.
        curl_setopt($handle, CURLOPT_ENCODING, null); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- handle of the WordPress HTTP API (http_api_curl).
        if (defined('CURLOPT_HTTP_CONTENT_DECODING')) {
            curl_setopt($handle, CURLOPT_HTTP_CONTENT_DECODING, false); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- handle of the WordPress HTTP API (http_api_curl).
        }
    }

    /**
     * Featured image address and alt text from the panel data.
     * A missing or invalid address gives an empty address, so the post is created
     * without it and the first image of the content is used instead.
     *
     * @param object $reportage Panel data.
     * @param bool   $report    Report a missing or invalid address (warning).
     *
     * @return array [address, alt text]
     */
    public static function lead_image($reportage, $report = false)
    {
        $lead_image_obj = (object) ((array) ($reportage->lead_image_obj ?? []));
        $lead_image     = $lead_image_obj->image ?? ($reportage->lead_image ?? '');
        $lead_image     = is_string($lead_image) ? trim($lead_image) : '';
        $lead_image_alt = sanitize_text_field($lead_image_obj->alt_tag ?? '');

        if (self::is_http_url($lead_image)) {
            return [$lead_image, $lead_image_alt];
        }

        if ($report) {
            $text = sprintf('%s featured image address for reportage %d', '' === $lead_image ? 'No' : 'Invalid', intval($reportage->id ?? 0));
            pubjet_log($text);
            pubjet_log_sentry($text, [], 'warning');
        }

        return ['', $lead_image_alt];
    }

    /**
     * Whether the value is an http(s) URL with a host. Checked without a DNS lookup;
     * download_url() checks the address again before connecting.
     *
     * @param mixed $url
     *
     * @return bool
     */
    public static function is_http_url($url)
    {
        if (!is_string($url) || '' === $url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $scheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) && '' !== (string) wp_parse_url($url, PHP_URL_HOST);
    }

    public static function upload_from_url($url, $alt_text = null)
    {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            pubjet_log_sentry('Invalid URL for upload', [
                'url' => $url
            ]);
            return false;
        }

        // Load required WordPress functions
        if (!function_exists('media_handle_sideload')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
        }

        // Download file to temp location. Two tries of 12 seconds keep the request
        // under the usual 30 second limit; the background retry covers longer outages.
        $tmp = null;
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            if (!self::can_start_download()) {
                if (1 === $attempt) {
                    self::$skipped_images++;
                }
                pubjet_log('Download skipped (time limit): ' . $url);
                return false;
            }
            $tmp = download_url($url, 12);
            if (!is_wp_error($tmp)) {
                break;
            }
            pubjet_log(sprintf('Download failed (attempt %d/2): %s => %s', $attempt, $url, $tmp->get_error_message()));
        }
        if (is_wp_error($tmp)) {
            pubjet_log_sentry('Failed to download file from URL', [
                'url' => $url,
                'error' => $tmp->get_error_message()
            ]);
            return false;
        }

        // Get filename and extension
        $filename = pathinfo($url, PATHINFO_FILENAME);
        $extension = pathinfo($url, PATHINFO_EXTENSION);

        // Handle missing extension
        if (!$extension) {
            $mime = mime_content_type($tmp);
            $mime = is_string($mime) ? sanitize_mime_type($mime) : false;

            $mime_extensions = [
                'text/plain' => 'txt',
                'text/csv' => 'csv',
                'application/msword' => 'doc',
                'image/jpg' => 'jpg',
                'image/jpeg' => 'jpeg',
                'image/gif' => 'gif',
                'image/png' => 'png',
                'video/mp4' => 'mp4',
                'image/webp' => 'webp',
            ];

            if (isset($mime_extensions[$mime])) {
                $extension = $mime_extensions[$mime];
            } else {
                pubjet_log_sentry('Could not determine file extension', [
                    'url' => $url,
                    'mime_type' => $mime
                ]);
                wp_delete_file($tmp);
                return false;
            }
        }

        // Use alt_text as title if provided, otherwise use filename
        $title = !empty($alt_text) ? $alt_text : null;

        // Upload file
        $args = [
            'name' => "$filename.$extension",
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload($args, 0, $title);

        // Cleanup temp file
        wp_delete_file($tmp);

        // Check for upload errors
        if (is_wp_error($attachment_id)) {
            pubjet_log('Sideload failed: ' . $url . ' => ' . $attachment_id->get_error_message());
            pubjet_log_sentry('Failed to upload file to media library', [
                'url' => $url,
                'filename' => "$filename.$extension",
                'error' => $attachment_id->get_error_message()
            ]);
            return false;
        }

        // Set alt text for image if provided
        if (!empty($alt_text)) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
        }

        pubjet_log("File uploaded successfully", [
            'url' => $url,
            'attachment_id' => $attachment_id,
            'filename' => "$filename.$extension"
        ]);

        return (int)$attachment_id;
    }

    /**
     * Whether a new download can start within the time limit of this request.
     *
     * @return bool
     */
    private static function can_start_download()
    {
        $start = isset($_SERVER['REQUEST_TIME_FLOAT']) ? (float) $_SERVER['REQUEST_TIME_FLOAT'] : 0.0;
        if ($start <= 0) {
            return true;
        }
        /**
         * The pubjet_image_download_start_limit filter.
         *
         * @since 5.4.9
         */
        $limit = (float) apply_filters('pubjet_image_download_start_limit', self::IMAGE_DOWNLOAD_START_LIMIT);

        return (microtime(true) - $start) <= $limit;
    }

    /**
     * Whether the HTML has images that are still on the Triboon CDN.
     *
     * @param string $html
     *
     * @return bool
     */
    public static function has_cdn_images($html)
    {
        if (!preg_match_all('/<img\s+[^>]*src="([^"]+)"/i', (string) $html, $matches)) {
            return false;
        }
        $hosts = self::cdn_hosts();
        foreach ($matches[1] as $src) {
            if (self::url_on_hosts($src, $hosts)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Whether a later try could find an image: a valid featured image address,
     * or images on the Triboon CDN in the post content or the saved panel HTML.
     *
     * @param object $reportage Panel data.
     * @param string $content   Post content.
     *
     * @return bool
     */
    public static function has_retry_source($reportage, $content)
    {
        list($lead_image) = self::lead_image($reportage);

        return '' !== $lead_image
            || self::has_cdn_images($content)
            || (!empty($reportage->content_file_html) && self::has_cdn_images((string) $reportage->content_file_html));
    }

    /**
     * Schedule another try to set the featured image of a reportage post:
     * up to 5 tries, 5, 10, 15, 20 and 25 minutes apart.
     *
     * @param int $post_id
     * @param int $attempt 1 to 5.
     *
     * @return void
     */
    public static function schedule_thumbnail_retry($post_id, $attempt)
    {
        $post_id = intval($post_id);
        $attempt = intval($attempt);
        if ($post_id <= 0) {
            return;
        }
        if ($attempt > 5) {
            pubjet_log('Thumbnail retry gave up for post ' . $post_id);
            return;
        }
        $args = [$post_id, $attempt];
        if (wp_next_scheduled('pubjet_retry_thumbnail', $args)) {
            return;
        }
        wp_schedule_single_event(time() + 5 * MINUTE_IN_SECONDS * $attempt, 'pubjet_retry_thumbnail', $args);
    }

    /**
     * Try again to set the featured image of a reportage post and to store the
     * content images left on the CDN (cron). The content is read from the post
     * and the saved panel data; the HTML file is not downloaded again.
     *
     * @param int $post_id
     * @param int $attempt
     *
     * @return void
     */
    public static function retry_thumbnail($post_id, $attempt = 1)
    {
        $post_id = intval($post_id);
        $attempt = max(1, intval($attempt));
        if ($post_id <= 0 || !get_post($post_id) || !pubjet_is_reportage($post_id)) {
            return;
        }
        $content       = (string) get_post_field('post_content', $post_id);
        $needs_thumb   = !has_post_thumbnail($post_id);
        $needs_content = self::has_cdn_images($content);
        if (!$needs_thumb && !$needs_content) {
            return;
        }

        $reportage = (object) ((array) get_post_meta($post_id, EnumPostMetakeys::PanelData, true));
        if (!$needs_content && !self::has_retry_source($reportage, $content)) {
            pubjet_log('Thumbnail retry stopped for post ' . $post_id . ': no image to try');
            return;
        }

        // Errors of a try are reported only when the last try fails.
        pubjet_sentry_batch_start('Featured image retry ' . $attempt . '/5, post ' . $post_id);

        $thumb_id = 0;
        if ($needs_thumb) {
            list($lead_image, $lead_image_alt) = self::lead_image($reportage);
            $thumb_id = $lead_image ? intval(self::upload_from_url($lead_image, $lead_image_alt)) : 0;
        }

        // Content images left on the CDN
        if ($needs_content) {
            $result = self::handle_images($content, false, false, self::cdn_hosts());
            if (!empty($result['processed_urls']) && $result['html_file'] !== $content) {
                add_filter('wp_kses_allowed_html', [Filters::class, "allowReportageIframe"], 10, 2);
                try {
                    wp_update_post(wp_slash(['ID' => $post_id, 'post_content' => $result['html_file']]));
                } finally {
                    remove_filter('wp_kses_allowed_html', [Filters::class, "allowReportageIframe"], 10);
                }
            }
            $needs_content = self::has_cdn_images($result['html_file']);
            if ($needs_thumb && !$thumb_id) {
                $thumb_id = intval($result['featured_img_id'] ?? 0);
            }
        }

        if ($needs_thumb && !$thumb_id && !empty($reportage->content_file_html)) {
            $thumb_id = intval(self::handle_images((string) $reportage->content_file_html, true, false, self::cdn_hosts())['featured_img_id'] ?? 0);
        }

        if ($needs_thumb && $thumb_id) {
            set_post_thumbnail($post_id, $thumb_id);
            update_post_meta($post_id, 'pubjet_thumbnail_data_attach', basename((string) get_attached_file($thumb_id)));
            $needs_thumb = false;
        }

        if (!$needs_thumb && !$needs_content) {
            pubjet_sentry_batch_discard();
            pubjet_log('Thumbnail retry succeeded for post ' . $post_id . ' (attempt ' . $attempt . ')');
            return;
        }

        pubjet_log('Thumbnail retry failed for post ' . $post_id . ' (attempt ' . $attempt . ')');
        if ($attempt < 5) {
            pubjet_sentry_batch_discard();
        }
        self::schedule_thumbnail_retry($post_id, $attempt + 1);
    }

    /**
     * Body of a content file response; empty unless the request succeeded (HTTP 200),
     * so an error page of the CDN is never published as content.
     *
     * @param array|\WP_Error $response
     *
     * @return string
     */
    private static function content_file_body($response)
    {
        if (is_wp_error($response)) {
            pubjet_log('Content file download failed: ' . $response->get_error_message());
            return '';
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if (200 !== $code) {
            pubjet_log('Content file download failed: HTTP ' . $code);
            return '';
        }
        return (string) wp_remote_retrieve_body($response);
    }

    public static function get_content_file($reportage)
    {

        //$content_file = str_replace("https://cdn.triboon.net", "https://cdn.pubjet.ir", $reportage->content_file);

        pubjet_log(':: Content File ::');
        pubjet_log($reportage->content_file);
        $response = wp_remote_get($reportage->content_file, [
            'timeout' => 25,
            'redirection' => 5,
            'blocking' => true,
        ]);
        $body = self::content_file_body($response);

        pubjet_log(':: First ::');
        pubjet_log($body);

        // Check gateway error
        if (pubjet_gateway_error($body) || empty($body)) {
            $response = wp_remote_get($reportage->content_file);
            $body = self::content_file_body($response);

            pubjet_log(':: Second ::');
            pubjet_log($body);
        }

        pubjet_log($body);

        //$body = str_replace("https://cdn.triboon.net", "https://cdn.pubjet.ir", $body);

        return $body;
    }

    public static function remove_repeate_headeing_title_in_content($content, $object_title)
    {

        preg_match('/<h1\b[^>]*>(.*?)<\/h1>/i', $content, $matches);

        foreach ($matches as $index => $matche) {
            $h1Tag = wp_strip_all_tags($matche);
            if ($index == 0) {
                $object_title = empty(trim($object_title)) ? trim($h1Tag) : $object_title;
                $content = str_replace($matche, '', $content);
            } else {
                $object_title = empty(trim($object_title)) ? trim($h1Tag) : $object_title;
                $matche2 = str_replace('<h1', '<h2', $matche);
                $matche2 = str_replace('</h1', '</h2', $matche2);
                $content = str_replace($matche, $matche2, $content);
            }
        }

        $content = str_replace('<a', ' <a', $content);
        $content = str_replace('</a>', '</a> ', $content);

        return [
            'content' => $content,
            'title' => $object_title,
        ];
    }

    /**
     * @param string $new_status New post status.
     * @param string $old_status Old post status.
     * @param \WP_Post $post Post object.
     *
     * @return void
     */
    public function afterPublishReportage($new_status, $old_status, $post)
    {
        if ($post->post_type !== pubjet_post_type() || EnumPostStatus::Publish !== $new_status) {
            return;
        }
        $reportage_id = pubjet_find_reportage_id($post->ID);
        if (empty($reportage_id)) {
            return;
        }
        $result = $this->publishReportageRequest($post->ID, $reportage_id);
        if (!pubjet_publish_reportage_succeeded($result)) {
            update_post_meta($post->ID, EnumPostMetakeys::FailedSyncUrl, true);
        }
    }

    /**
     * @param $post_id
     * @param $reportage_id
     *
     * @return mixed
     */
    public function publishReportageRequest($post_id, $reportage_id)
    {
        return pubjet_publish_reportage($post_id, $reportage_id);
    }

    /**
     * @param $reportage
     *
     * @return string|integer
     */
    public static function findReportageCategory($reportage)
    {
        global $pubjet_settings;
        $result = false;
        if (!empty($reportage->relative_category)) {
            $found = get_term_by('slug', $reportage->relative_category['unique_name'], 'category');
            $result = $found ? $found->term_id : false;
        } else {
            // Find category id based on pricing plans
            if (!empty($pubjet_settings['pricingPlans']) && !empty($reportage->pricing_plan_title)) {
                foreach ($pubjet_settings['pricingPlans'] as $pricingPlan) {
                    if (!empty($pricingPlan['title']) && $pricingPlan['title'] === $reportage->pricing_plan_title) {
                        if (!empty($pricingPlan['relative_categories'][0]['unique_name'])) {
                            $slug = $pricingPlan['relative_categories'][0]['unique_name'];
                            $found = get_term_by('slug', $slug, 'category');
                            if ($found !== false && !is_wp_error($found)) {
                                return $found->term_id;
                            }
                        }
                        break;
                    }
                }
            }
        }
        // publish reportage in random category
        $categories = pubjet_find_wp_categories();
        $random_category = !empty($categories) ? $categories[array_rand($categories)]['id'] : 1;

        return $result ? $result : $random_category;
    }

}