<?php

use Sentry\State\Scope;
use triboon\pubjet\includes\DBLoader;
use triboon\pubjet\includes\enums\EnumActions;
use triboon\pubjet\includes\enums\EnumHttpMethods;
use triboon\pubjet\includes\enums\EnumOldOptions;
use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\enums\EnumPostMetakeys;

defined('ABSPATH') || exit;

/**
 * @param $name
 * @param $args
 *
 * @since 1.0
 */
function pubjet_shortcode($name, $args) {
    $result = '[' . $name;
    foreach ($args as $key => $value) {
        $result .= " {$key}='" . $value . "' ";
    }
    $result .= ']';

    return $result;
}

/**
 * @return boolean
 * @author Triboon
 * @since  1.0
 */
function pubjet_is_prod_mode() {
    return !pubjet_is_dev_mode();
}

/**
 * Development mode is only for local development: it needs the PUBJET_DEV_MODE constant
 * and a "local" environment type.
 *
 * @return boolean
 * @author Triboon
 * @since  1.0
 */
function pubjet_is_dev_mode() {
    return defined('PUBJET_DEV_MODE') && true === PUBJET_DEV_MODE && 'local' === wp_get_environment_type();
}

/**
 * @param $arr_or_string
 *
 * @return string
 * @since  1.0
 * @author Triboon
 */
function pubjet_flat_string($arr_or_string, $separator = ' ') {
    return is_array($arr_or_string) ? implode($separator, $arr_or_string) : $arr_or_string;
}

/**
 * @param       $mixed
 * @param false $default
 *
 * @return false|mixed
 */
function pubjet_isset_value(&$mixed, $default = false) {
    return (isset($mixed) && !empty($mixed)) ? $mixed : $default;
}

/**
 * @since 1.0
 */
function pubjet_option($name, $default = '') {
    $value = get_option($name, $default);

    return $value ? $value : $default;
}

/**
 * @return string
 * @throws Exception
 */
function pubjet_get_random_api_key($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get JSON string
 *
 * @param string $string
 *
 * @return mixed|string
 * @since  1.0
 * @access public
 */
function pubjet_get_json($string) {
    if (!$string) {
        return '';
    }
    $string = str_replace("\n", "|NEWLINE|", $string);
    $string = str_replace('\\', '', $string);
    $string = str_replace("|NEWLINE|", "\r\n", $string);

    return json_decode($string, true);
}

/**
 * @param $data
 *
 * @return array
 * @since 1.0
 */
function pubjet_api_success($data = []) {
    return ['success' => true, 'payload' => $data,];
}

/**
 * @param string $error
 * @param array  $args
 *
 * @return array|bool[]|mixed[]|string[]
 * @since 3.3.4
 */
function pubjet_ajax_error($error = '', $status_code = 400) {
    if (is_wp_error($error)) {
        $error = $error->get_error_messages();
    } else {
        if (!is_array($error)) {
            $error = [$error];
        }
    }
    if (is_array($error) && count($error) == 1) {
        $error = reset($error);
    }
    wp_send_json(['success' => false, 'error' => $error,], $status_code);
}

/**
 * @param $data
 *
 * @return void
 * @since 3.3.4
 */
function pubjet_ajax_success($data = []) {
    wp_send_json(['success' => true, 'payload' => $data,], 200);
}

/**
 * @return integer
 * @since  1.0
 * @author Triboon
 */
function pubjet_now_ts() {
    return current_time('timestamp');
}

/**
 * @return string
 */
function pubjet_now_myql() {
    return current_time('mysql');
}

/**
 * @param $list
 *
 * @return string|array
 * @since 1.0
 */
function pubjet_class_names($list, $return_as_array = false) {
    $result = [];
    foreach ($list as $key => $value) {
        if (is_int($key)) {
            $result[] = $value;
        } else if ($value) {
            $result[] = $key;
        }
    }
    if ($return_as_array) {
        return $result;
    }

    return implode(' ', $result);
}

/**
 * @param $a
 * @param $b
 *
 * @return array
 * @since 1.0
 */
function pubjet_parse_args(&$a, $b) {
    $a      = (array)$a;
    $b      = (array)$b;
    $result = $b;
    foreach ($a as $k => &$v) {
        if (is_array($v) && isset($result[$k])) {
            $result[$k] = pubjet_parse_args($v, $result[$k]);
        } else {
            $result[$k] = $v;
        }
    }

    return $result;
}


/**
 * Check if current request is an ajax or not
 *
 * @return bool
 * @since 1.0
 *
 */
function pubjet_is_ajax() {
    return 'xmlhttprequest' === strtolower((string) pubjet_get_server_var('HTTP_X_REQUESTED_WITH', ''));
}

/**
 * @param string $user_id
 *
 * @return bool
 */
function pubjet_is_admin($user_id = '') {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    return user_can($user_id, 'manage_options');
}

/**
 * Get specific user roles
 *
 * @param string $user_id
 *
 * @author Triboon
 * @since  1.0
 */
function pubjet_find_user_roles($user_id = '') {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return [];
    }

    return $user->roles;
}

/**
 * @param $attributes
 */
function pubjet_html_tag_atts($attributes, $echo = true) {
    if (!is_array($attributes)) {
        return '';
    }
    $result = '';
    foreach ($attributes as $key => $value) {
        if ($value) {
            if (true === $value) {
                $result .= sprintf(' %s ', esc_attr($key));
            } else {
                if (!empty(trim($value))) {
                    $result .= sprintf(' %s = "%s" ', esc_attr($key), esc_attr($value));
                }
            }
        }
    }
    if ($echo) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- names and values are escaped above
        echo $result;
    }

    return $result;
}

/**
 * Allowed tags and attributes for inline SVG icons in admin notices.
 *
 * @return array
 */
function pubjet_allowed_svg_tags() {
    $shape = ['d' => true, 'cx' => true, 'cy' => true, 'r' => true, 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'points' => true, 'fill' => true, 'stroke' => true];
    return [
        'svg'      => ['xmlns' => true, 'width' => true, 'height' => true, 'viewbox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'class' => true, 'aria-hidden' => true],
        'path'     => $shape,
        'circle'   => $shape,
        'rect'     => $shape,
        'line'     => ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true],
        'polyline' => $shape,
        'g'        => ['fill' => true, 'stroke' => true],
    ];
}

/**
 * Sanitize the input.
 *
 * @param mixed $input  The input.
 * @param bool  $typefy Whether to convert strings to the appropriate data type.
 *
 * @return mixed
 * @since  1.0
 *
 */
function pubjet_sanitize($input, $typefy = false) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[sanitize_text_field($key)] = pubjet_sanitize($value, $typefy);
        }

        return $input;
    }

    // These are safe types.
    if (is_bool($input) || is_int($input) || is_float($input)) {
        return $input;
    }

    // Now we will treat it as string.
    $input = sanitize_text_field($input);

    // avoid numeric or boolean values as strings.
    if ($typefy) {
        return pubjet_typefy($input);
    }

    return $input;
}

/**
 * Load template part
 *
 * @param string $name
 * @param bool   $extend
 * @param bool   $include
 * @param array  $data
 *
 * @return bool|mixed|string|void
 * @since 1.0
 */
function pubjet_template($name, $extend = false, $include = true, $data = []) {

    if ($extend) {
        $name .= '-' . $extend;
    }

    $template     = false;
    $template_dir = [
        PUBJET_TPLS_DIR,
    ];

    /**
     * The pubjet_templates_dir filter.
     *
     * @since 1.0.0
     */
    $template_dir = apply_filters('pubjet_templates_dir', $template_dir);

    foreach ($template_dir as $temp_path) {
        if (file_exists($temp_path . $name . '.php')) {
            $template = $temp_path . $name . '.php';
            break;
        }
    }

    /**
     * The pubjet_load_template filter.
     *
     * @since 1.0.0
     */
    $template = apply_filters('pubjet_load_template', $template, $name);

    if (!$template || !file_exists($template)) {
        _doing_it_wrong(__FUNCTION__, sprintf('<strong>%s</strong> does not exists in <code>%s</code>.', esc_html($name), esc_html($template)), '1.4.0');

        return false;
    }

    if (!$include) {
        return $template;
    }

    extract($data, EXTR_SKIP);
    include $template;
}

/**
 * Convert the input into the proper data type
 *
 * @param mixed $input The input.
 *
 * @return mixed
 * @since  1.0
 *
 */
function pubjet_typefy($input) {
    if (is_numeric($input)) {
        return floatval($input);
    } else if (is_string($input) && preg_match('/^(?:true|false)$/i', $input)) {
        return 'true' === strtolower($input);
    }

    return $input;
}

/**
 * @return array
 * @author Triboon
 * @since  1.0
 */
function pubjet_get_page_templates() {
    return apply_filters('pubjet_page_templates', [
        'page-templates/thankyou.php' => 'بازخورد تریبون',
    ]);
}

/**
 * @param $entry
 * @param $method
 * @param $line
 *
 * @return false|int|void
 */
function pubjet_log($entry, $method = __METHOD__, $line = __LINE__) {

    if (!pubjet_is_debug_mode()) {
        return;
    }

    $entry = pubjet_redact_for_log($entry);
    if (is_array($entry) || is_object($entry)) {
        $entry = wp_json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $file = pubjet_debug_dir();
    if (!pubjet_prepare_log_dir(dirname($file))) {
        return false;
    }
    pubjet_rotate_log_file($file);

    $entry = pubjet_redact_for_log($method . "::" . current_time('mysql') . ":: line " . $line . "::" . $entry . "\n");

    return file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Remove credentials and personal data from a value before it is written to the debug log.
 * Credential fields are masked, personal fields are removed, and e-mail addresses, phone
 * numbers and tokens inside text are masked. JSON text is decoded, cleaned and encoded again.
 *
 * @param mixed   $value
 * @param boolean $api_response Keep only status codes, is_valid and technical ids (for API responses).
 *
 * @return mixed
 */
function pubjet_redact_for_log($value, $api_response = false) {
    if (is_string($value)) {
        $trimmed = ltrim($value);
        if ('' !== $trimmed && ('{' === $trimmed[0] || '[' === $trimmed[0])) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return wp_json_encode(pubjet_redact_for_log($decoded, $api_response));
            }
        }
        if ($api_response) {
            return '[response body, ' . strlen($value) . ' bytes]';
        }
        return pubjet_mask_secrets($value);
    }

    if ($value instanceof \WP_Error) {
        return ['wp_error' => $value->get_error_code(), 'message' => pubjet_mask_secrets($value->get_error_message())];
    }

    if (is_object($value)) {
        // Only plain data objects are logged; other objects (e.g. HTTP response objects) may hold raw bodies
        if (!$value instanceof \stdClass) {
            return '[' . get_class($value) . ']';
        }
        $value = get_object_vars($value);
    }

    if (!is_array($value)) {
        return $value;
    }

    $secret_key   = '/token|authori|api[-_]?key|password|secret|nonce|cookie/i';
    $personal_key = '/^(first_?name|last_?name|full_?name|display_?name|phone|mobile|tel|email|e_?mail|user_?email|user_?name|yn_username|user_?login|nickname|address|national_?code|postal_?code)$/i';
    $api_key      = '/^(code|status|status_?code|success|is_?valid|id|ids|[a-z_]+_id|[a-z_]+_ids)$/i';

    $result = [];
    foreach ($value as $key => $item) {
        $key_text = (string) $key;
        if (preg_match($secret_key, $key_text)) {
            $result[$key] = is_string($item) && '' !== $item ? pubjet_mask_secret($item) : '[redacted]';
            continue;
        }
        if (preg_match($personal_key, $key_text)) {
            $result[$key] = '[redacted]';
            continue;
        }
        $is_json_text = is_string($item) && preg_match('/^\s*[\[{]/', $item) && is_array(json_decode($item, true));
        if (is_array($item) || is_object($item) || $is_json_text) {
            $clean = pubjet_redact_for_log($item, $api_response);
            if (!$api_response || !in_array($clean, [[], '[]', '{}'], true)) {
                $result[$key] = $clean;
            }
            continue;
        }
        if ($api_response && (is_int($key) || !preg_match($api_key, $key_text))) {
            continue;
        }
        $result[$key] = is_string($item) ? pubjet_mask_secrets($item) : $item;
    }
    return $result;
}

/**
 * @param string $value
 *
 * @return string Only the last four characters of the value are kept.
 */
function pubjet_mask_secret($value) {
    $value = (string) $value;
    return '****' . (strlen($value) > 4 ? substr($value, -4) : '');
}

/**
 * Mask the stored token and authorization header values in a text.
 *
 * @param string $text
 *
 * @return string
 */
function pubjet_mask_secrets($text) {
    $text  = (string) $text;
    $token = pubjet_token();
    if (is_string($token) && strlen(trim($token)) > 4) {
        $text = str_replace(trim($token), pubjet_mask_secret(trim($token)), $text);
    }
    $secret = '[A-Za-z0-9._~+\/=\-]{16,}';
    // Authorization schemes, e.g. "Token xxx" or "api-key xxx"
    $text = preg_replace_callback('/\b(Token|api-key|Bearer)(\s+)(' . $secret . ')/i', function ($matches) {
        return $matches[1] . $matches[2] . pubjet_mask_secret($matches[3]);
    }, $text);
    // Raw values after an authorization key, e.g. in print_r() or JSON output
    $text = preg_replace_callback('/(authori[a-z]*["\']?\]?\s*(?:=>|:)\s*["\']?)(' . $secret . ')/i', function ($matches) {
        return $matches[1] . pubjet_mask_secret($matches[2]);
    }, $text);
    // Personal data inside free text
    $text = preg_replace('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', '[email]', $text);
    $text = preg_replace('/(?<!\d)(?:\+98|0098|0)?9\d{2}[\s\-]?\d{3}[\s\-]?\d{4}(?!\d)/', '[phone]', $text);
    return $text;
}

/**
 * Keep one previous copy of the log when it grows over the size limit.
 *
 * @param string $file
 *
 * @return void
 */
function pubjet_rotate_log_file($file) {
    /**
     * The pubjet_max_log_size filter.
     *
     * @since 5.4.9 Default is 5 MB.
     */
    $max_size = (int) apply_filters('pubjet_max_log_size', 5 * MB_IN_BYTES);
    if (!file_exists($file) || filesize($file) <= $max_size) {
        return;
    }
    $previous_file = $file . '.1';
    if (file_exists($previous_file)) {
        wp_delete_file($previous_file);
    }
    copy($file, $previous_file);
    wp_delete_file($file);
}

/**
 * Create the log directory and protect it from direct access.
 *
 * @param string $dir
 *
 * @return boolean
 */
function pubjet_prepare_log_dir($dir) {
    if (!is_dir($dir) && !wp_mkdir_p($dir)) {
        return false;
    }
    // Only add protection files to the plugin's own log directory
    if (0 !== strpos(basename($dir), 'pubjet-logs-')) {
        return wp_is_writable($dir);
    }
    $protection_files = [
        'index.php' => "<?php\n// Silence is golden.\n",
        '.htaccess' => "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n",
    ];
    foreach ($protection_files as $name => $content) {
        $path = trailingslashit($dir) . $name;
        if (!file_exists($path)) {
            file_put_contents($path, $content);
        }
    }
    return wp_is_writable($dir);
}

/**
 * Directory for the debug log, inside uploads with a random name.
 *
 * @return string
 */
function pubjet_log_dir() {
    $key = get_option('pubjet_log_dir_key');
    if (empty($key) || !is_string($key)) {
        $key = wp_generate_password(20, false);
        update_option('pubjet_log_dir_key', $key, false);
    }
    $uploads = wp_upload_dir(null, false);
    return trailingslashit($uploads['basedir']) . 'pubjet-logs-' . $key;
}

/**
 * Remove the debug log of versions before 5.4.9 from the plugin directory.
 *
 * @return void
 */
function pubjet_delete_legacy_debug_file() {
    $legacy_file = PUBJET_DIR_PATH . 'debug.txt';
    if (file_exists($legacy_file)) {
        wp_delete_file($legacy_file);
    }
}

/**
 * @return bool
 */
function pubjet_is_debug_mode() {
    global $pubjet_settings;
    if (!boolval(pubjet_isset_value($pubjet_settings[EnumOptions::DebugMode]))) {
        return false;
    }
    /**
     * The pubjet_debug_mode_ttl filter. Debug mode is turned off after this many seconds.
     *
     * @since 5.4.9
     */
    $ttl        = (int) apply_filters('pubjet_debug_mode_ttl', DAY_IN_SECONDS);
    $enabled_at = (int) pubjet_isset_value($pubjet_settings[EnumOptions::DebugModeEnabledAt], 0);
    if ($ttl > 0 && $enabled_at > 0 && time() - $enabled_at > $ttl) {
        pubjet_disable_debug_mode();
        return false;
    }
    return true;
}

/**
 * @return void
 */
function pubjet_disable_debug_mode() {
    global $pubjet_settings;
    $pubjet_settings[EnumOptions::DebugMode]          = false;
    $pubjet_settings[EnumOptions::DebugModeEnabledAt] = 0;
    $settings                                         = pubjet_settings();
    $settings[EnumOptions::DebugMode]                 = false;
    $settings[EnumOptions::DebugModeEnabledAt]        = 0;
    update_option(EnumOptions::Settings, $settings);
}

/**
 * Record when debug mode was turned on, so it can expire.
 *
 * @param array $settings Settings about to be saved.
 *
 * @return array
 */
function pubjet_stamp_debug_mode(array $settings) {
    global $pubjet_settings;
    if (!boolval(pubjet_isset_value($settings[EnumOptions::DebugMode]))) {
        $settings[EnumOptions::DebugModeEnabledAt] = 0;
    } elseif (boolval(pubjet_isset_value($pubjet_settings[EnumOptions::DebugMode])) && !empty($pubjet_settings[EnumOptions::DebugModeEnabledAt])) {
        $settings[EnumOptions::DebugModeEnabledAt] = (int) $pubjet_settings[EnumOptions::DebugModeEnabledAt];
    } else {
        $settings[EnumOptions::DebugModeEnabledAt] = time();
    }
    return $settings;
}

/**
 * @return array
 */
function pubjet_options() {
    return pubjet_settings();
}

/**
 * @return string
 */
function pubjet_debug_dir() {
    /**
     * The pubjet_debug_dir filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_debug_dir', trailingslashit(pubjet_log_dir()) . 'debug.log');
}

/**
 * @return string
 */
function pubjet_token() {
    global $pubjet_settings;
    /**
     * The pubjet_token filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_token', trim(pubjet_isset_value($pubjet_settings['token'], '')));
}

/**
 * @return void
 */
function pubjet_api_root() {
    /**
     * The pubjet_api_root filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_api_root', PUBJET_API_ROOT);
}

/**
 * @param $post_id
 *
 * @return integer
 */
function pubjet_find_reportage_id($post_id) {
    return get_post_meta($post_id, EnumPostMetakeys::ReportageId, true);
}

/**
 * @return string
 */
function pubjet_post_type() {
    /**
     * The pubjet_post_type filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_post_type', PUBJET_POST_TYPE);
}

/**
 * @param $post_id
 *
 * @return bool
 */
function pubjet_is_reportage($post_id) {
    $reportage_post_id = get_post_meta($post_id, \triboon\pubjet\includes\enums\EnumPostMetakeys::ReportageId, true);
    return !empty($reportage_post_id);
}

/**
 * @param $reportage_id
 *
 * @return void|bool|integer
 */
function pubjet_find_post_id_by_reportage_id($reportage_id) {
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- lookup by meta value, must not be cached
    return $wpdb->get_var($wpdb->prepare("SELECT `post_id` FROM {$wpdb->postmeta} WHERE `meta_key` = %s AND `meta_value` = %s LIMIT 1", \triboon\pubjet\includes\enums\EnumPostMetakeys::ReportageId, $reportage_id));
}

/**
 * @param $reportage_id
 *
 * @return array|bool|int|object|stdClass|void
 */
function pubjet_find_backlink_by_id($backlink_id) {
    global $wpdb;
    $table = esc_sql($wpdb->pubjet_backlinks);
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- table name escaped with esc_sql(), value uses a placeholder
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE `backlink_id` = %s LIMIT 1", $backlink_id));
}

/**
 * @return array
 */
function pubjet_plugin_status() {
    /**
     * The pubjet_plugin_status filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_plugin_status', [
        'title'         => get_bloginfo('name'),
        'description'   => get_bloginfo('description'),
        'wpVersion'     => get_bloginfo('version'),
        'phpVersion'    => phpversion(),
        'pubjetVersion' => PUBJ()->getVersion(),
        'restUrl'       => trailingslashit(site_url()) . rest_get_url_prefix(),
    ]);
}

/**
 * @since 1.0.0
 */
function pubjet_strings() {
    /**
     * The pubjet_strings hook.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_strings', [
        'every-minute'               => esc_html__('Every Minute', 'pubjet'),
        'backlink-not-found'         => esc_html__('Backlink not found', 'pubjet'),
        'horizontal'                 => esc_html__('Horizontal', 'pubjet'),
        'vertical'                   => esc_html__('Vertical', 'pubjet'),
        'pbqs'                       => esc_html__('Process Data By Query String', 'pubjet'),
        'pbqs-hints'                 => esc_html__('By default, the REST API method is used to transfer data from Tribune to Pubjet. If this method encounters problems for any reason, you can enable this option to use the Query String method to process the information.', 'pubjet'),
        'style'                      => esc_html__('Style', 'pubjet'),
        'backlinks'                  => esc_html__('Backlinks', 'pubjet'),
        'all-backlinks'              => esc_html__('All Backlinks', 'pubjet'),
        'footer_inner'               => esc_html__('Footer Inner', 'pubjet'),
        'footer_main'                => esc_html__('Footer Main', 'pubjet'),
        'footer_all'                 => esc_html__('Footer All', 'pubjet'),
        'sidebar_inner'              => esc_html__('Sidebar Inner', 'pubjet'),
        'sidebar_main'               => esc_html__('Sidebar Main', 'pubjet'),
        'sidebar_all'                => esc_html__('Sidebar All', 'pubjet'),
        'header_inner'               => esc_html__('Header Inner', 'pubjet'),
        'header_main'                => esc_html__('Header Main', 'pubjet'),
        'header_all'                 => esc_html__('Header All', 'pubjet'),
        'widget-title'               => esc_html__('Widget Title', 'pubjet'),
        'backlinks-position'         => esc_html__('Backlinks Position', 'pubjet'),
        'pubjet-backlinks'           => esc_html__('Pubjet Backlinks', 'pubjet'),
        'pubjet-backlinks-hints'     => esc_html__('Using this widget, you can display backlinks in different parts of your website', 'pubjet'),
        'version'                    => esc_html__('Version', 'pubjet'),
        'username'                   => esc_html__('Username', 'pubjet'),
        'displayname'                => esc_html__('Display Name', 'pubjet'),
        'author-name'                => esc_html__('Author Name', 'pubjet'),
        'manual-approve'             => esc_html__('Manual Approve', 'pubjet'),
        'manual-approve-hints'       => esc_html__('Enable this option if you want to review the reportage manually after review', 'pubjet'),
        'pmk-hints'                  => esc_html__('This feature is useful when you want to make Pubjet compatible with other plugins that perform actions on the text menu. By using this feature, users can easily add specific information and metadata to reports without the need for fundamental changes in other plugins and benefit from better integration and coordination between plugins.', 'pubjet'),
        'save'                       => esc_html__('Save', 'pubjet'),
        'delete'                     => esc_html__('Delete', 'pubjet'),
        'actions'                    => esc_html__('Actions', 'pubjet'),
        'keyname'                    => esc_html__('Key Name', 'pubjet'),
        'keyvalue'                   => esc_html__('Key Value', 'pubjet'),
        'define-post-metakeys'       => esc_html__('Defining Custom Metakeys for Posts', 'pubjet'),
        'add-metakey'                => esc_html__('Add Metakey', 'pubjet'),
        'metakeys'                   => esc_html__('Metakeys', 'pubjet'),
        'delete-first-image'         => esc_html__('Delete First Image', 'pubjet'),
        'align-center-images'        => esc_html__('Align Center Images', 'pubjet'),
        'align-center-images-help'   => esc_html__('If you want all the images in the reports to be displayed in the middle of the fold, activate this option. Please note that this option is only applied to reports and other writings are ignored.', 'pubjet'),
        'title'                      => esc_html__('Plan Name', 'pubjet'),
        'category'                   => esc_html__('Category', 'pubjet'),
        'pricing-plans'              => esc_html__('Pricing Plans', 'pubjet'),
        'plans-categories'           => esc_html__('Plans Categories', 'pubjet'),
        'check-token'                => esc_html__('Check Token', 'pubjet'),
        'checking-token'             => esc_html__('Checking Token', 'pubjet'),
        'default-category'           => esc_html__('Default Category', 'pubjet'),
        'triboon-token'              => esc_html__('Access Token', 'pubjet'),
        'congratulation'             => esc_html__('Congratulations !', 'pubjet'),
        'valid-token'                => esc_html__('The Access Token is Valid', 'pubjet'),
        'invalid-token'              => esc_html__('The Access Token is Invalid', 'pubjet'),
        'empty-token'                => esc_html__('The Access Token cannot be Empty', 'pubjet'),
        'pubjet'                     => esc_html__('Pubjet', 'pubjet'),
        'reportage'                  => esc_html__('Reportage', 'pubjet'),
        'triboon-reportage'          => esc_html__('Triboon Reportage', 'pubjet'),
        'enable'                     => esc_html__('Enable', 'pubjet'),
        'disable'                    => esc_html__('Disable', 'pubjet'),
        'copy'                       => esc_html__('Copy', 'pubjet'),
        'copied'                     => esc_html__('Copied !', 'pubjet'),
        'uninstall'                  => esc_html__('Clearing Plugin Data After Deletion', 'pubjet'),
        'no'                         => esc_html__('No', 'pubjet'),
        'yes'                        => esc_html__('Yes', 'pubjet'),
        'delete-log'                 => esc_html__('Delete Log', 'pubjet'),
        'delete-log-confirm'         => esc_html__('Are you sure to delete the log file?', 'pubjet'),
        'reload'                     => esc_html__('Refresh', 'pubjet'),
        'refresh-data'               => esc_html__('Refresh Data', 'pubjet'),
        'error-occured'              => esc_html__('An error has occurred. Try again', 'pubjet'),
        'post-not-found'             => esc_html__('Post not found', 'pubjet'),
        'rep-not-found'              => esc_html__('Reportage not found', 'pubjet'),
        'post-not-reportage'         => esc_html__('Unfortunately, the operation was not done. This post is not a reportage', 'pubjet'),
        'delete-permission-limit'    => esc_html__('Error deleting file. File deletion access is restricted from the host side', 'pubjet'),
        'missing-params'             => esc_html__('Some required parameters were not sent with the request', 'pubjet'),
        'permission-error'           => esc_html__('You do not have access to perform this operation', 'pubjet'),
        'missing-token'              => esc_html__('Please enter the access token in the pubjet plugin settings menu', 'pubjet'),
        'invalid-http-method'        => esc_html__('The http request method is wrong', 'pubjet'),
        'empty-reportage-content'    => esc_html__('The content of the reportage is empty', 'pubjet'),
        'general'                    => esc_html__('General', 'pubjet'),
        'debug'                      => esc_html__('Debugging', 'pubjet'),
        'enable-debugging'           => esc_html__('Enable Debugging', 'pubjet'),
        'advanced'                   => esc_html__('Advanced', 'pubjet'),
        'saved'                      => esc_html__('Saved !', 'pubjet'),
        'reportage-data'             => esc_html__('Pubjet :: Reportage Data', 'pubjet'),
        'reportage-options'          => esc_html__('Pubjet :: Reportage Options', 'pubjet'),
        'enable-nofollow'            => esc_html__('Enable NoFollow Links', 'pubjet'),
        'pwait'                      => esc_html__('Please Wait ...', 'pubjet'),
        'modules'                    => esc_html__('Modules', 'pubjet'),
        'curl-module'                => esc_html__('cUrl', 'pubjet'),
        'openssl-module'             => esc_html__('OpenSSL', 'pubjet'),
        'required-modules'           => esc_html__('Required Modules', 'pubjet'),
        'required-modules-help'      => esc_html__('Pubjet plugin requires the activation of the following modules for its proper functioning. If any of the following items are not active, ask your hosting support to activate the inactive items for you.', 'pubjet'),
        'check-now'                  => esc_html__('Check  Now', 'pubjet'),
        'update-settings'            => esc_html__('Update Settings', 'pubjet'),
        'settings-saved'             => esc_html__('Settings saved successfully', 'pubjet'),
        'gateway-error'              => esc_html__('Gateway 504 error', 'pubjet'),
        'pubjet-token'               => esc_html__('Pubjet Token', 'pubjet'),
        'enter-token-desc'           => esc_html__('Pubjet plugin needs an access token to work properly. Please enter the access token in the plugin settings.', 'pubjet'),
        'reconnect-token-desc'       => esc_html__('The Pubjet access token is no longer valid. Please enter a new access token in the plugin settings.', 'pubjet'),
        'remindme-later'             => esc_html__('Remindme Later', 'pubjet'),
        'permanent-hide'             => esc_html__('Permanent Hide', 'pubjet'),
        'select-categories-hints'    => esc_html__('By default, all your categories are sent to Triboon to determine the reportage category correctly. If you only want the reportage to be published in certain categories, select them in this section.', 'pubjet'),
        'categories'                 => esc_html__('Categories', 'pubjet'),
        'sync-categories'            => esc_html__('Sync Categories', 'pubjet'),
        'sync'                       => esc_html__('Synchronize', 'pubjet'),
        'select-rep-author'          => esc_html__('Select Reportage Post Author', 'pubjet'),
        'select-rep-author-hints'    => esc_html__('By default, when Pabjet publishes a reportage on your website, it uses the account of the site administrator as the author of the reportage, if you want to use another author, select it.', 'pubjet'),
        'add-authorCategory'         => esc_html__('Add author for Category', 'pubjet'),
        'add-reportage-source'       => esc_html__('Add source domain at the bottom of the reportage', 'pubjet'),
        'add-authorCategory-hints'   => esc_html__('You can assign a specific author to each report category.', 'pubjet'),
        /* translators: %s: plugin version number. */
        'pubjet-update-plugin-desc'  => esc_html__('Pubjet version %s has been released. This version includes performance improvements and fixes for previous issues. Please update to get the latest features.', 'pubjet'),
        'pubjet-update-plugin-title' => esc_html__('A new version of Pubjet has been released!', 'pubjet'),
        /* translators: %s: maximum number of categories. */
        'max-sync-categories'        => esc_html__('The number of categories that can be selected for synchronization exceeds the allowed limit (%s items).', 'pubjet'),
        'empty-sync-categories'      => esc_html__('Sync categories cannot be empty.', 'pubjet'),
        'empty-default-category'     => esc_html__('Default category cannot be empty.', 'pubjet'),
        'enable-debugging-hints'     => esc_html__("By enabling this option, Pubjet's performance history will be saved. In the event of an error, you can share this information with Triboon's technical team to make the troubleshooting process faster and more accurate.", 'pubjet'),
        'delete-first-image-hints'   => esc_html__('By activating this option, if the customer has not included a featured image, the first image of the report will be removed from the text and considered as the featured image.', 'pubjet'),
        'add-reportage-source-hints' => esc_html__('By activating this option, the link that is most frequently repeated in the report will be included as the root domain (main domain) as the news source at the end of the article.', 'pubjet'),
        'switch-rep-author-hints'    => esc_html__("By default, reportage articles are published on the site without the author's name. If you would like a specific author to be considered for these articles, you can specify the author's name in this field.", 'pubjet'),
        'uninstall-hints'            => esc_html__('By enabling this option, all Pubjet settings will be completely erased after uninstalling Pubjet.', 'pubjet'),
        'add-reportage-tags'         => esc_html__('Publish advertorials without tags', 'pubjet'),
        'add-reportage-tags-hints'   => esc_html__('By default, advertorials are published with customer-suggested tags. By enabling this option, advertorials will be published without tags.', 'pubjet'),
        /* translators: %s: pricing plan title. */
        'empty-pricingPlans-categories' => esc_html__("%s pricing plan category cannot be empty.", 'pubjet'),
        /* translators: %s: maximum number of categories. */
        'max-pricingPlans-categories' => esc_html__('The number of categories that can be selected for pricing plans exceeds the allowed limit (%s items).', 'pubjet'),
        'no-pricing-plans-available' => esc_html__('No pricing plans available!', 'pubjet'),
        'pubjet-triboon-connection-desc' => esc_html__('To connect Pubjet to the platform, enter pricing plan categories.', 'pubjet'),
        'misc-disable-tooltip'          => esc_html__('Please specify the category for each plan in the General tab.', 'pubjet'),
    ]);
}

/**
 * @return array
 */
function pubjet_find_authors() {
    $args = [
        'role__in' => ['author', 'administrator'],
        'orderby'  => 'display_name',
        'order'    => 'ASC',
    ];

    $users = get_users($args);

    $result = [];

    foreach ($users as $user) {
        $result[$user->ID] = [
            'ID'           => $user->ID,
            'user_login'   => $user->user_login,
            'display_name' => $user->display_name,
        ];
    }
    /**
     * The pubjet_authors_and_admins filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_authors', array_values($result));
}

/**
 * @param $key
 * @param $default
 *
 * @return mixed|null
 */
function pubjet_get_server_var($key, $default = null) {
    $candidates = [strtoupper($key), strtolower($key), 'HTTP_' . strtoupper($key), 'http_' . strtolower($key)];
    foreach ($candidates as $candidate) {
        if (isset($_SERVER[$candidate])) {
            return sanitize_text_field(wp_unslash($_SERVER[$candidate]));
        }
    }
    return $default;
}

/**
 * @param $key
 *
 * @return false|mixed|string
 */
function pubjet__($key) {
    $strings = pubjet_strings();
    return pubjet_isset_value($strings[$key], $key);
}

/**
 * @return string
 */
function pubjet_get_request_method() {
    return strtoupper((string) pubjet_get_server_var('REQUEST_METHOD', ''));
}

/**
 * @return boolean
 */
function pubjet_show_copyright() {
    global $pubjet_settings;
    $status = pubjet_isset_value($pubjet_settings[EnumOptions::CopyrightStatus]);
    if (empty($status)) {
        return true;
    }
    return $status !== 'hide';
}

/**
 * @param $data
 *
 * @return array
 */
function pubjet_array($data) {
    if (!$data) {
        return $data;
    }
    return is_array($data) ? $data : [$data];
}

/**
 * @return boolean|WP_Error
 */
function pubjet_is_request_token_valid() {
    if (pubjet_is_dev_mode()) {
        return true;
    }
    if (empty(pubjet_token())) {
        return new \WP_Error('missing-token', pubjet__('missing-token'));
    }
    $request_token = pubjet_get_server_var('authorization');
    if (pubjet_token_matches($request_token)) {
        return true;
    }
    return new WP_Error('invalid-token', pubjet__('invalid-token'));
}

/**
 * Compare a request token with the stored token in constant time.
 *
 * @param mixed $request_token
 *
 * @return boolean
 */
function pubjet_token_matches($request_token) {
    $stored_token  = is_string(pubjet_token()) ? pubjet_token() : '';
    $request_token = is_string($request_token) ? $request_token : '';
    if ('' === $stored_token || '' === $request_token) {
        return false;
    }
    return hash_equals($stored_token, $request_token);
}

/**
 * @param $token
 *
 * @return array|boolean|WP_Error
 */
function pubjet_find_token_details($token) {
    /**
     * The pubjet_check_token_url filter.
     *
     * @since 1.0.0
     */
    $url = apply_filters('pubjet_check_token_url', pubjet_api_root() . '/external/wp/token-validation/', $token);

    $headers = [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Token ' . trim($token),
    ];
    $args = [
        'timeout' => 30
    ];
    $result = pubjet_request($url, EnumHttpMethods::GET, $headers, [], $args);
    pubjet_log('====== Check Token ======');
    pubjet_log(pubjet_redact_for_log($result, true));
    pubjet_log('====== END Check Token ======');

    if (is_wp_error($result)) {
        return new \WP_Error('request-failed', $result->get_error_message());
    }

    if (isset($result['code']) && 403 == $result['code']) {
        // "rejected" is true only for the JSON error Triboon returns for a wrong token
        $is_json  = false !== stripos((string) pubjet_isset_value($result['content_type'], ''), 'application/json');
        $rejected = $is_json && is_object($result['body']) && isset($result['body']->detail);
        return new \WP_Error('invalid-token', pubjet__('invalid-token'), ['rejected' => $rejected]);
    }

    if (!isset($result['body']->is_valid)) { // Some error occured
        return new \WP_Error('request-failed', pubjet__('error-occured'));
    }

    // Check if token is valid or not
    if (!$result['body']->is_valid) {
        return new \WP_Error('invalid-token', pubjet__('invalid-token'), ['rejected' => true]);
    }

    // we must remove "Test" plan in production
    $pricing_plans = pubjet_isset_value($result['body']->pricing_plans, []);
    if ($pricing_plans) {
        $final_plans = [];
        foreach ($pricing_plans as $plan) {
            if ($plan->title !== 'تست پابجت') {
                $final_plans[] = $plan;
            }
        }
        $pricing_plans = $final_plans;
    }

    return [
        'valid'             => true,
        'first_name'        => pubjet_isset_value($result['body']->publisher->first_name),
        'last_name'         => pubjet_isset_value($result['body']->publisher->last_name),
        'phone'             => pubjet_isset_value($result['body']->publisher->phone),
        'email'             => pubjet_isset_value($result['body']->publisher->email),
        'pricing_plans'     => $pricing_plans,
        'website_id'        => pubjet_isset_value($result['body']->website_id),
        'website_url'       => pubjet_isset_value($result['body']->website_url),
        'publisherCategory' =>  pubjet_isset_value($result['body']->website_shop_quality_division,false)
    ];
}

/**
 * @param $url
 * @param $method
 * @param $headers
 * @param $body
 *
 * @return \WP_Error|array
 */
function pubjet_request($url, $method = 'GET', $headers = [], $body = [], $pargs = []) {
    $args = [
        'method'    => $method,
        'headers'   => $headers,
        'body'      => $body,
    ];

    if ($pargs) {
        $args = array_merge($args, $pargs);
    }

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response));

    return ['code' => $response_code, 'body' => $response_body, 'content_type' => wp_remote_retrieve_header($response, 'content-type')];
}

/**
 * @return boolean
 */
function pubjet_gateway_error($post_content) {
    $result = strpos($post_content, 'try again') === false;
    return !$result;
}

/**
 * @param $condition
 * @param $func_or_string
 */
function pubjet_render_condition($condition, $func_or_string) {
    if (!$condition) {
        return;
    }
    if (is_callable($func_or_string)) {
        call_user_func($func_or_string);

        return;
    }
    echo wp_kses_post($func_or_string);
}

/**
 * @param $parent_id
 *
 * @return array
 */
function pubjet_find_wp_categories($parent_id = 0, $hierarchy = true) {
    if ($hierarchy) {
        $categories = get_categories([
                                         'parent'     => $parent_id,
                                         'hide_empty' => false,
                                     ]);

        $categories_list = [];

        foreach ($categories as $category) {
            $category_item = [
                'name' => $category->name,
                'id'   => $category->term_id,
                'slug' => $category->slug,
            ];

            $children = pubjet_find_wp_categories($category->term_id, $hierarchy);

            if (!empty($children)) {
                $category_item['children'] = $children;
            }

            $categories_list[] = $category_item;
        }

        return $categories_list;
    }

    // Flat
    $result     = [];
    $categories = get_categories(['hide_empty' => false,]);
    foreach ($categories as $category) {
        $result[] = [
            'id'   => $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug,
        ];
    }

    return $result;
}

/**
 * @return array
 */
function pubjet_find_wp_tags() {
    $result = [];
    $tags   = get_tags(['hide_empty' => false,]);
    foreach ($tags as $tag) {
        $result[] = [
            'id'   => $tag->term_id,
            'name' => $tag->name,
        ];
    }

    return $result;
}


/**
 * @return void
 */
function pubjet_sync_settings() {
    pubjet_log("===== synSettingsAfterUpdate ===== ");
    $category = pubjet_is_dev_mode() ? 1 : null;
    if ($category === null) {
        $token = pubjet_token();
        if (!empty($token)) {
            $details = pubjet_find_token_details(trim($token));
            if (!is_wp_error($details) && isset($details['publisherCategory'])) {
                $category = $details['publisherCategory'];
            }
        }
    }
    if ($category !== null) {
        global $pubjet_settings;
        $pubjet_settings['publisherCategory'] = $category;
        if ($category !== 1) {
            $pubjet_settings['manualApprove'] = false;
        }
        update_option(EnumOptions::Settings, $pubjet_settings);
    }

}


/**
 * @return mixed|null
 */
function pubjet_default_settings() {
    /**
     * The pubjet_default_settings filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_default_settings', [
        'token'                    => '',
        'defaultCategory'          => '',
        'debug'                    => '',
        'debugEnabledAt'           => 0,
        'alignCenterImages'        => '',
        'nofollow'                 => '',
        'lastCategoriesSyncTime'   => '',
        'activationVersion'        => '',
        'copyrightStatus'          => '',
        'uninstallCleanup'         => '',
        'lastCheckingMissedPosts'  => '',
        'pricingPlans'             => [],
        'authorCategory'           => [],
        'addReportageSource'       => false,
        'manualApprove'            => false,
        'publisherCategory'        => false,
        'addReportageTags'         => false,
        'processDataByQueryString' => false,
        'pricingPlansChanged'      => false
    ]);
}

/**
 * @return array
 */
function pubjet_settings() {
    /**
     * The pubjet_default_settings filter.
     *
     * @since 1.0.0
     */
    $default_settings = pubjet_default_settings();
    $settings         = get_option(EnumOptions::Settings, []);
    $settings         = pubjet_parse_args($settings, $default_settings);

    /**
     * The pubjet_settings filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_settings', $settings);
}

/**
 * @param $option_name
 * @param $option_value
 *
 * @return bool
 */
function pubjet_update_setting($option_name, $option_value) {
    $settings               = pubjet_settings();
    $settings[$option_name] = $option_value;
    return update_option(EnumOldOptions::Settings, $settings);
}


/**
 * @param $message
 * @param $extra
 *
 * @return \Sentry\EventId|null
 */
function pubjet_log_sentry($message, $extra = []) {
    $extra = pubjet_parse_args($extra, [
        'website_url'    => site_url(),
        'website_title'  => get_bloginfo('name'),
        'pubjet_version' => PUBJ()->getVersion(),
    ]);
    \Sentry\init(['dsn' => 'https://ea145de5084460f189b3bf2d66b6af06@sentry.hamravesh.com/6647',]);
    \Sentry\configureScope(function (Scope $scope) use ($extra) {
        foreach ($extra as $key => $value) {
            $scope->setExtra($key, $value);
        }
    });
    return \Sentry\captureException(new \Exception($message));
}

/**
 * @return boolean
 */
function pubjet_should_publish_reportage_manually() {
    global $pubjet_settings;
    return pubjet_isset_value($pubjet_settings['manualApprove']);
}

/**
 * @return array
 */
function pubjet_find_backlink_positions() {
    /**
     * The pubjet_backlink_positions filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_backlink_positions', [
        'footer_inner',
        'footer_main',
        'footer_all',
        'sidebar_inner',
        'sidebar_main',
        'sidebar_all',
        'header_inner',
        'header_main',
        'header_all',
    ]);
}

/**
 * @param $status
 *
 * @return string
 */
function pubjet_send_plugin_status_to_api($status) {
    if (empty(trim(pubjet_token()))) { // Check if user enter token or not
        return;
    }
    $settings     = pubjet_settings();

    $url = apply_filters('pubjet_send_plugin_status_url', pubjet_api_root() . '/external/wp/pubjet-info/', pubjet_token());

    $request_data = [
        'status' => $status,
        'pubjet_version' => PUBJ()->getVersion(),
        'backlink_recipient_path'   => pubjet_get_path('pubjet/v1/backlink',        EnumActions::CreateBacklink, $settings),
        'reportage_recipient_path'  => pubjet_get_path('pubjet/v1/reportage',       EnumActions::CreateReportage, $settings),
        'status_recipient_path'     => pubjet_get_path('pubjet/v1/status',          EnumActions::PubjetStatus, $settings),
        'category_recipient_path'   => pubjet_get_path('pubjet/v1/site/categories', EnumActions::PubjetCategories, $settings),
        'pubjet_data' => [
            'manualApprove' => pubjet_isset_value($settings['manualApprove'], 0),
            'reportage_without_tags' => pubjet_isset_value($settings['addReportageTags'], 0),
        ]
    ];
    pubjet_log($request_data);
    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Token ' . pubjet_token(),
        ],
        'method'  => 'POST',
        'body'    => wp_json_encode($request_data),
        'timeout' => 30,
    ]);
    // بررسی پاسخ API برای اشکالات احتمالی
    if (is_wp_error($response)) {
        pubjet_log_sentry('Error sending status to API: ' . $response->get_error_message());
    }
    $result = wp_remote_retrieve_body($response);
    pubjet_log(pubjet_redact_for_log($result, true));
    return $result;
}


/**
 * @param $request_date
 *
 * @return string|WP_Error
 */
function pubjet_find_mysql_date_by_request_date($request_date) {
    try {
        $dt = new DateTime($request_date);
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        return $dt->format('Y-m-d H:i:s');
    } catch (\Exception $ex) {
        return new WP_Error('error-occured', $ex->getMessage());
    }
}

/**
 * @return DBLoader
 */
function pubjet_db() {
    return DBLoader::getInstance();
}

/**
 * @return mixed|null
 */
function pubjet_http_json_request_headers() {
    /*
     * The pubjet_http_json_requests filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_http_json_requests', [
        'Content-Type'  => 'application/json',
        'Authorization' => 'api-key ' . pubjet_token(),
    ]);
}

/**
 * @param $post_ID
 * @param $reportage_ID
 *
 * @return array|void
 */
function pubjet_publish_reportage($post_id, $reportage_id) {
    $url = pubjet_api_root() . '/external/wp/reportages/' . $reportage_id . '/publish';
    pubjet_log($url);

    if (pubjet_is_dev_mode()) {
        return;
    }

    $args = [
        'headers'     => [
            'Authorization' => 'api-key ' . pubjet_token(),
            'Content-Type'  => 'application/json',
        ],
        'method'      => 'POST',
        'data_format' => 'body',
        'body'        => wp_json_encode(['url' => get_permalink($post_id)]),
    ];

    $response = wp_remote_post($url, $args);

    pubjet_log(['url' => $url, 'response' => pubjet_redact_for_log($response, true)]);

    return [
        'code' => wp_remote_retrieve_response_code($response),
        'body' => json_decode(wp_remote_retrieve_body($response), true),
    ];
}

/**
 * @param $backlink_id
 *
 * @return array|WP_Error
 */
function pubjet_publish_backlink_request($backlink_id) {
    $url = pubjet_api_root() . '/external/wp/backlink/confirm';
    pubjet_log($url);

    if (pubjet_is_dev_mode()) {
        return new WP_Error('development-mode', pubjet__('dev-mode'));
    }

    $result = pubjet_request($url, 'POST', pubjet_http_json_request_headers(), wp_json_encode(['id' => $backlink_id, 'status' => 'publisher_published',]), ['data_format' => 'body']);

    pubjet_log(pubjet_redact_for_log($result, true));

    return $result;
}


/**
 * @return int|mixed
 */
function pubjet_check_new_version() {

    $plugin_updates = get_site_transient( 'update_plugins' );

    if ( empty( $plugin_updates ) || !isset( $plugin_updates->response['pubjet/pubjet.php'] ) ) {

        $pubjet_update_info = get_site_transient( 'pubjet_update_plugin_info' );

        if ( false === $pubjet_update_info ) {
            $api_url = 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=pubjet';
            $response = wp_remote_get($api_url, [
                'timeout'   => 30
            ]);

            if ( is_wp_error( $response ) ) {
                pubjet_log('Pubjet update api check failed: ' . $response->get_error_message());
                set_site_transient( 'pubjet_update_plugin_info', 'error', 12 * HOUR_IN_SECONDS );
                return PUBJ()->getVersion();
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body );

            if ( empty( $data ) || !isset( $data->version ) ) {
                pubjet_log('Pubjet update API returned invalid response.');
                set_site_transient( 'pubjet_update_plugin_info', 'error', 12 * HOUR_IN_SECONDS );
                return PUBJ()->getVersion();
            }

            set_site_transient( 'pubjet_update_plugin_info', $data, 12 * HOUR_IN_SECONDS );
            $new_version = $data->version ;
        } else {
            $new_version = ($pubjet_update_info === 'error') ? PUBJ()->getVersion() : ($pubjet_update_info->new_version ?? 0);
        }
    } else {
        $plugin = $plugin_updates->response['pubjet/pubjet.php'];
        $new_version = $plugin->new_version;
    }

    return $new_version;
}

/**
 * Send pricingPlans related categories to the API.
 *
 * @param array $pricingPlans Array of pricing plans with categories
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function pubjet_send_pricing_plans_to_api($pricingPlans,$token)
{
    if (empty($pricingPlans)) {
        return new WP_Error('invalid_input', 'Pricing plans cannot be empty');
    }

    // Helper function to ensure categories are in array format
    $normalize_categories = function($categories) {
        if (empty($categories)) {
            return [];
        }
        return is_array($categories) ? $categories : [$categories];
    };

    // Step 1: Collect all category IDs from all pricing plans
    $all_category_ids = [];
    foreach ($pricingPlans as $plan) {
        if (!isset($plan['categories'])) {
            continue;
        }
        $categories = $normalize_categories($plan['categories']);
        $all_category_ids = array_merge($all_category_ids, $categories);
    }

    if (empty($all_category_ids)) {
        return new WP_Error('no_categories', 'No categories found in pricing plans');
    }

    $all_category_ids = array_unique($all_category_ids);

    // Step 2: Bulk fetch all categories
    $terms = get_terms([
        'taxonomy'   => 'category',
        'slug'    => $all_category_ids,
        'hide_empty' => false,
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        pubjet_log('Failed to fetch terms or no terms returned.');
        return new WP_Error('terms_error', 'Failed to fetch categories');
    }
    
    // Step 3: Build a lookup map for category data
    $categoryLookup = [];
    foreach ($terms as $term) {
        $categoryLookup[urldecode($term->slug)] = [
            "id"          => $term->term_id,
            'title'       => $term->name,
            'unique_name' => urldecode($term->slug),
        ];
    }

    pubjet_log(["categoryLookup" => $categoryLookup]);

    // Step 4: Build final payload with all plans
    $pricingPlansData = [];
    foreach ($pricingPlans as $plan) {
        if (!isset($plan['categories']) || !isset($plan['id'])) {
            continue;
        }
        
        $categories = $normalize_categories($plan['categories']);
        $related_categories = [];

        foreach ($categories as $cat_slug) {
            if (isset($categoryLookup[$cat_slug])) {
                $related_categories[] = $categoryLookup[$cat_slug];
            }
        }

        if (!empty($related_categories)) {
            $pricingPlansData[] = [
                'id' => $plan['id'],
                'relative_categories' => $related_categories,
            ];
        }
    }

    if (empty($pricingPlansData)) {
        return new WP_Error('no_valid_plans', 'No valid pricing plans with categories found');
    }

    pubjet_log(['Pricing Plans Data' => $pricingPlansData]);

    // Step 5: Send POST request to the API
    $url = pubjet_api_root() . '/external/wp/pricing-plan-relative-category/';
    $response = wp_remote_post($url, [
        'method'    => 'POST',
        'body'      => wp_json_encode($pricingPlansData),
        'headers'   => [
            'Authorization' => 'Token ' . trim($token),
            'Content-Type'  => 'application/json',
        ],
    ]);

    // Step 6: Handle response
    if (is_wp_error($response)) {
        $error_message = 'Pricing Plans API Error: ' . $response->get_error_message();
        pubjet_log_sentry($error_message);
        pubjet_log(['Pricing Plans API Error' => $response->get_error_message()]);
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $result = wp_remote_retrieve_body($response);

    if ($status_code !== 200) {
        pubjet_log(['Pricing Plans API Status Code' => $status_code, 'Response' => pubjet_redact_for_log($result, true)]);
        return new WP_Error('api_error', 'API request failed with status: ' . $status_code);
    }

    // Validate the response
    $decoded_result = json_decode($result, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        pubjet_log(['Pricing Plans API Invalid JSON Response' => pubjet_redact_for_log($result, true)]);
        return new WP_Error('invalid_response', 'Invalid JSON response from API');
    }

    pubjet_log(['Pricing Plans API Response' => pubjet_redact_for_log($decoded_result, true)]);
    return true;
}

/**
 * @param array $settings
 * @param int $publisherCategory
 * @return array
 */
function pubjet_prepare_settings_to_save(array $settings, int $publisherCategory):array
{
    global $pubjet_settings;

    $pubjet_settings['publisherCategory'] = $publisherCategory;
    $settings['publisherCategory'] = $publisherCategory;

    if ($publisherCategory !== 1) {
        $settings['manualApprove'] = 0;
    }

    return $settings;
}


/**
 * @param array $pricingPlans
 * @return void
 */
function pubjet_validate_pricing_plans(array $pricingPlans): void
{
    if (empty($pricingPlans)) {
        return;
    }

    foreach ($pricingPlans as $plan) {
        $title = pubjet_isset_value($plan['title'], pubjet__('unnamed-plan'));

        if (empty($plan['categories'])) {
            pubjet_ajax_error(sprintf(
                pubjet__('empty-pricingPlans-categories'),
                $title
            ));
        }

        if (is_array($plan['categories']) && count($plan['categories']) > 10) {
            pubjet_ajax_error(sprintf(
                pubjet__('max-pricingPlans-categories'),
                10
            ));
        }
    }
}

/**
 * @param $post_id
 * @param $reportage_id
 * @param $new_url
 * @return array|void|WP_Error
 */
function pubjet_send_permalink_change_to_api($post_id, $reportage_id, $new_url) {
    if (empty(trim(pubjet_token()))) return;

    pubjet_log('====== START Update Permalink ======');
    pubjet_log('Post ID: ' . $post_id);
    pubjet_log('Reportage ID: ' . $reportage_id);
    pubjet_log('New URL: ' . $new_url);

    $url = apply_filters('pubjet_update_permalink_endpoint', pubjet_api_root() . '/external/wp/reportages/' . $reportage_id . '/update-url/', $post_id , $reportage_id);

    $headers = [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Token ' . trim(pubjet_token()),
    ];

    $body = ['url' => $new_url];

    $args = [
        'timeout' => 30,
        'body' => wp_json_encode($body),
        'headers' => $headers,
        'method' => 'POST'
    ];

    $result = pubjet_request($url, EnumHttpMethods::POST, $headers, $body, $args);

    pubjet_log('====== END Update Permalink ======');

    if (is_wp_error($result)) {
        pubjet_log('Error: ' . $result->get_error_message());
        return new \WP_Error('error', $result->get_error_message());
    }

    if (isset($result['code']) && 403 == $result['code']) {
        return new \WP_Error('error', pubjet__('invalid-token'));
    }

    if (isset($result['code']) && 404 == $result['code']) {
        return new \WP_Error('error', pubjet__('invalid-reportageId'));
    }

    pubjet_log('Success: Permalink updated successfully');
    return $result;
}

/**
 * @return int
 */
function pubjet_get_reportage_count(): int
{
    $count = get_transient('pubjet_reportage_count');

    if ($count === false) {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(p.ID) 
            FROM {$wpdb->posts} p 
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
            WHERE p.post_type = 'post' 
            AND p.post_status IN ('publish', 'future', 'draft', 'pending') 
            AND pm.meta_key = %s
        ", 'pubjet_reportage_id'));

        // کش برای 30 دقیقه
        set_transient('pubjet_reportage_count', $count, 30 * MINUTE_IN_SECONDS);
    }

    return (int) $count;
}


/**
 * @return mixed|string
 */
function pubjet_get_current_subdomain()
{
    $site_url = home_url('/');
    $parsed = wp_parse_url($site_url);
    return isset($parsed['path']) ? trim($parsed['path'], '/') : '';
}

/**
 * @param $endpoint
 * @param $action
 * @param $settings
 * @return string
 */

function pubjet_get_path($endpoint, $action, $settings)
{
    $base_url    = pubjet_get_current_subdomain();
    $rest_prefix = rest_get_url_prefix();
    $use_query   = pubjet_isset_value($settings['processDataByQueryString']);

    if ($use_query) {
        $full_path = ($base_url ? $base_url . '/' : '') . '?action=' . $action;
    } else {
        $full_path = ($base_url ? $base_url . '/' : '') . $rest_prefix . '/' . $endpoint;
    }

    return $full_path;
}

/**
 * @return void
 */
function pubjet_delete_first_image_option() {
    if (get_option('pubjet_migration_delete_first_image_v530') !== 'completed') {
        global $pubjet_settings;
        if (isset($pubjet_settings['deleteFirstImage'])) {
            pubjet_migrate_delete_first_image_option();
        } else {
            update_option('pubjet_migration_delete_first_image_v530', 'completed');
        }
    }
}

/**
 * @return void
 */
function pubjet_migrate_delete_first_image_option() {
    pubjet_log("migrate delete first image");

    global $pubjet_settings, $wpdb;

    $post_ids = $wpdb->get_col($wpdb->prepare("
                                SELECT DISTINCT p.ID 
                                FROM {$wpdb->posts} p
                                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                                WHERE p.post_type = %s
                                AND p.post_status IN ('publish', 'draft', 'private', 'pending')
                                AND pm.meta_key = %s
                            ", PUBJET_POST_TYPE, 'pubjet_reportage_id'));

    if (!empty($post_ids)) {
        $insert_values = array();
        $delete_first_image_value = !empty($pubjet_settings['deleteFirstImage']) ? '1' : '0';

        foreach ($post_ids as $post_id) {
            $insert_values[] = $wpdb->prepare(
                "(%d, %s, %s)",
                (int)$post_id,
                'pubjet_delete_first_image',
                $delete_first_image_value
            );
        }

        if (!empty($insert_values)) {
            $values_string = implode(',', $insert_values);
            $post_ids_string = implode(',', array_map('intval', $post_ids));

            try {
                $wpdb->query("
                            DELETE FROM {$wpdb->postmeta} 
                            WHERE post_id IN ({$post_ids_string}) 
                            AND meta_key = 'pubjet_delete_first_image'
                    ");

                $wpdb->query("
                            INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) 
                            VALUES {$values_string}
                        ");

                if (PUBJET_DEBUG_MODE) {
                    pubjet_log("Pubjet Migration v5.3.0: حذف اولین عکس - " . count($post_ids) . " پست پردازش شد");
                }

            } catch (Exception $e) {
                if (PUBJET_DEBUG_MODE) {
                    pubjet_log('Pubjet Migration Error: ' . $e->getMessage());
                }
            }
        }
    }

    update_option('pubjet_migration_delete_first_image_v530', 'completed');
    unset($pubjet_settings['deleteFirstImage']);
    update_option(EnumOptions::Settings,$pubjet_settings);
}