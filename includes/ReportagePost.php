<?php

namespace triboon\pubjet\includes;

if (!defined("ABSPATH")) exit;

use DateTime;
use DateTimeZone;
use Statickidz\GoogleTranslate;
use triboon\pubjet\includes\enums\EnumPostMetakeys;
use triboon\pubjet\includes\enums\EnumPostStatus;

class ReportagePost extends Singleton
{

    /**
     * @since 1.0.0
     */
    public function __construct()
    {
        add_action("transition_post_status", [$this, "afterPublishReportage"], 15, 3);
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
        $dt = new DateTime(date('Y-m-d H:i:s e'));
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        $dt->modify('+3 minutes');
        $current_time = $dt->format('Y-m-d H:i:s');
        return strtotime($post_date) > strtotime($current_time) ? 'future' : 'publish';
    }

    /**
     * @param $thereportage
     *
     * @return bool
     */
    public static function update($thereportage)
    {

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
            $args['post_date_gmt'] = $post_date;
        }

        $reportage_url = get_post_meta($post_id, EnumPostMetakeys::ReportageContentUrl, true);
        if ($reportage_url !== $thereportage->content_file) {

            $post_content = !empty($thereportage->content_file_html) ? $thereportage->content_file_html : self::get_content_file($thereportage);
            $post_content = self::get_post_content($post_content, $thereportage->title);

            $args['post_title'] = $post_content['title'] ?? '';
            $args['post_content'] = $post_content['content'] ?? '';

            $args['meta_input'] = [
                EnumPostMetakeys::PanelData => $thereportage,
                EnumPostMetakeys::ReportageContentUrl => $thereportage->content_file,
            ];

        }

        $update = wp_update_post($args);

        return boolval($update);
    }

    /**
     * @param $reportage
     *
     * @return bool|int|\WP_Error
     */
    public static function insert($reportage)
    {

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

        $post_content = self::get_post_content($post_content, $reportage->title);
        $post_date = self::get_post_date($reportage->preferred_publish_date);
        $post_status = self::get_post_status($post_date);
        $post_excerpt = self::normalize_html($reportage->lead_content ?? '');

        $lead_image_obj = (object) ((array) ($reportage->lead_image_obj ?? []));
        $lead_image     = $lead_image_obj->image ?? ($reportage->lead_image ?? '');
        $lead_image_alt = sanitize_text_field($lead_image_obj->alt_tag ?? '');
        $post_thumbnail = $lead_image ? self::upload_from_url($lead_image, $lead_image_alt) : null;


        $args = [
            'post_type' => sanitize_text_field(pubjet_post_type()),
            'post_title' => isset($post_content['title']) ? sanitize_text_field($post_content['title']) : '',
            'post_status' => 'future' === $post_status ? $post_status : EnumPostStatus::Publish,
            'post_content' => $post_content['content'] ?? '',
            'post_excerpt' => $post_excerpt ?? '',
            'post_name' => sanitize_text_field(self::get_post_name($reportage)),
            'tags_input' => isset($reportage->tags) && is_array($reportage->tags) ? map_deep($reportage->tags, 'sanitize_text_field') : [],
            'post_category' => (int)$def_category > 0 ? [intval($def_category)] : '',
            'meta_input' => [
                EnumPostMetakeys::ReportageId => intval($reportage->id),
                EnumPostMetakeys::ReportageContentUrl => sanitize_url($reportage->content_file),
                EnumPostMetakeys::PanelData => $reportage,
                EnumPostMetakeys::Source => 'triboon',
                EnumPostMetakeys::MetaTitle => sanitize_text_field($reportage->meta_title) ?? '',
                EnumPostMetakeys::MetaDescription => sanitize_text_field($reportage->meta_description) ?? ''
            ],
        ];

        if ($post_status != EnumPostStatus::Publish) {
            $args['post_date'] = sanitize_text_field($post_date);
            $args['post_date_gmt'] = sanitize_text_field($post_date);
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
            $post_id = wp_insert_post($args);
        } finally {
            remove_filter('wp_kses_allowed_html', [Filters::class, "allowReportageIframe"], 10);
        }

        pubjet_log('======= New Post Result =======');
        pubjet_log($post_id);

        if (is_wp_error($post_id)) {
            pubjet_log_sentry(sprintf('%s: %s', 'خطا در ایجاد نوشته رپورتاژ', $post_id->get_error_message()), [
                'reportage_id' => $reportage->id,
                'reportage_title' => $reportage->title,
            ]);
            return new \WP_Error('insert-reportage', $post_id->get_error_message());
        }

        // =================== Success ===================

        // Set Post Thumbnail
        $final_thumbnail_id = $post_thumbnail ?: intval($post_content['featured_img_id'] ?? 0);
        if ($final_thumbnail_id) {
            set_post_thumbnail($post_id, $final_thumbnail_id);

            $file_path = get_attached_file($final_thumbnail_id);
            $data_attach = basename($file_path);
            update_post_meta($post_id, 'pubjet_thumbnail_data_attach', $data_attach);
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
        global $pubjet_settings;
        $post_name = $reportage->title;
        // Use Google Translate service for translating post title
        $use_google_translate = pubjet_isset_value($pubjet_settings['useGoogleTranslate']);
        if ($use_google_translate) {
            $trans = new GoogleTranslate();
            $post_name = $trans->translate('fa', 'en', $reportage->title);
            return sanitize_title_with_dashes($post_name, '', 'save');
        }
        return false;
    }

    public static function normalize_html($post_content)
    {
        $post_content = preg_replace('/\s*<a/', '<a', $post_content);
        $post_content = preg_replace('/<\/a>\s*/', '</a>', $post_content);
        $post_content = str_replace("\n\r", "", $post_content);
        return str_replace("\n", "", $post_content);
    }


    public static function handle_images($html_content, $just_thumbnail = false, $use_cdn = false)
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
            $path = parse_url($original_src, PHP_URL_PATH);
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

        // Download file to temp location
        $tmp = download_url($url);
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

    public static function get_content_file($reportage)
    {

        //$content_file = str_replace("https://cdn.triboon.net", "https://cdn.pubjet.ir", $reportage->content_file);

        pubjet_log(':: Content File ::');
        pubjet_log($reportage->content_file);
        $response = wp_remote_get($reportage->content_file, [
            'timeout' => 25,
            'redirection' => 5,
            'blocking' => true,
            'sslverify' => false,
        ]);
        $body = wp_remote_retrieve_body($response);

        pubjet_log(':: First ::');
        pubjet_log($body);

        // Check gateway error
        if (pubjet_gateway_error($body) || empty($body)) {
            $response = wp_remote_get($reportage->content_file);
            $body = wp_remote_retrieve_body($response);

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
            $h1Tag = strip_tags($matche);
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
        if (isset($result['code']) && !in_array($result['code'], [200, 429])) {
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