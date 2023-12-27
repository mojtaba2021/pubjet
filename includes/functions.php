<?php

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
 * @author Pishook
 * @since  1.0
 */
function pubjet_is_prod_mode() {
    return !defined('WP_ENVIRONMENT') || "production" === WP_ENVIRONMENT;
}

/**
 * @return boolean
 * @author Pishook
 * @since  1.0
 */
function pubjet_is_dev_mode() {
    return defined('WP_ENVIRONMENT') && "development" === WP_ENVIRONMENT;
}

/**
 * @param $arr_or_string
 *
 * @return string
 * @since  1.0
 * @author Pishook
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
function pubjet_ajax_error($error = '', $args = []) {
    if (is_wp_error($error)) {
        $error = $error->get_error_messages();
    } else {
        if (!is_array($error)) {
            $error = [$error];
        }
    }
    wp_send_json(array_merge(['success' => false, 'error' => $error,], $args));
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
 * @return array|null|object
 * @since  1.0
 * @access public
 *
 */
function pubjet_get_site_admins() {
    global $wpdb;
    $query = "
		SELECT 
			u.ID, u.user_login, u.user_nicename, u.user_email, um2.meta_value as `mobile`
		    	FROM {$wpdb->users} u
		    INNER JOIN {$wpdb->usermeta} m ON m.user_id = u.ID
		    LEFT OUTER JOIN {$wpdb->usermeta} um2 ON um2.user_id = u.ID AND um2.meta_key = 'mobile'
		    WHERE m.meta_key = 'wp_capabilities'
		    AND m.meta_value LIKE '%administrator%'
		    ORDER BY u.user_registered
	";

    return $wpdb->get_results($query);
}

/**
 * @return array
 * @since 1.0
 */
function pubjet_get_site_admins_user_ids() {
    $admins = chapar_get_site_admins();
    if (!$admins) {
        return false;
    }

    return array_map(function ($admin) {
        return $admin->ID;
    }, $admins);
}

/**
 * @return integer
 * @since  1.0
 * @author Pishook
 */
function pubjet_now_ts() {
    return current_time('timestamp');
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
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
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
 * Get user ip address
 *
 * @return mixed|string
 * @since 1.0
 */
function pubjet_get_ip_address() {
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }

    return apply_filters('pubjet_user_ip', $ipaddress);
}

/**
 * @return void
 * @author Pishook
 * @since  1.0
 */
function pubjet_wp_log($data) {
    if (is_array($data) || is_object($data)) {
        error_log(print_r($data, true));

        return;
    }
    error_log($data);
}

/**
 * Get woocommerce checkout page url
 *
 * @return string
 * @author Pishook
 * @since  1.0
 */
function pubjet_find_woo_checkout_page_url() {
    return get_permalink(wc_get_page_id('checkout'));
}

/**
 * Get woocommerce shop page url
 *
 * @return string
 * @author Pishook
 * @since  1.0
 */
function pubjet_find_woo_shop_page_url() {
    return get_permalink(wc_get_page_id('shop'));
}

/**
 * @param       $item
 * @param array $args
 */
function pubjet_echo_or_call($item, $args = []) {
    if (is_callable($item)) {
        call_user_func($item, $args);

        return;
    }
    if (isset($args['return']) && $args['return']) {
        return $item;
    }
    echo is_array($item) ? implode(' ', $item) : $item;
}

/**
 * Get specific user roles
 *
 * @param string $user_id
 *
 * @author Pishook
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
                $result .= sprintf(' %s ', $key);
            } else {
                if (!empty(trim($value))) {
                    $result .= sprintf(' %s = "%s" ', $key, $value);
                }
            }
        }
    }
    if ($echo) {
        echo $result;
    }

    return $result;
}

/**
 * Conditionally render markup
 *
 * @param         $condition
 * @param Closure $render
 *
 * @since 1.0
 */
function pubjet_condition_render($condition, $render) {
    if (is_callable($condition)) {
        $condition = call_user_func($condition);
    }
    if ($condition) {
        if (is_callable($render)) {
            ob_start();
            call_user_func($render);
            $output = ob_get_clean();
        } else {
            $output = $render;
        }
        echo $output;
    }
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
        _doing_it_wrong(__FUNCTION__, sprintf("<strong>%s</strong> does not exists in <code>%s</code>.", $name, $template), '1.4.0');

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
 * @author Pishook
 * @since  1.0
 */
function pubjet_get_page_templates() {
    return apply_filters('pubjet_page_templates', [
        'page-templates/thankyou.php' => 'بازخورد تریبون',
    ]);
}

/**
 * @param      $args
 * @param bool $echo
 *
 * @since 1.0
 */
function pubjet_swiper($args, $echo = true) {
    $defaults = [
        'loop'             => true,
        'spaceBetween'     => 0,
        'slidesPerView'    => 1,
        'lazy'             => true,
        'autoHeight'       => false,
        'show_timebar'     => false,
        'autoPlay'         => [
            'delay'                => 3000,
            'disableOnInteraction' => true,
        ],
        'navigation'       => [
            'display' => true,
            'nextEl'  => '.swiper-button-next',
            'prevEl'  => '.swiper-button-prev',
        ],
        'pagination'       => [
            'display'   => true,
            'clickable' => true,
            'classes'   => '',
            'el'        => '.swiper-pagination',
        ],
        'scrollbar'        => [
            'display' => true,
            'el'      => '.swiper-scrollbar',
        ],
        'breakpoints'      => [
            '0'    => [
                'slidesPerView' => 1,
            ],
            '576'  => [
                'slidesPerView' => 2,
            ],
            '768'  => [
                'slidesPerView' => 3,
            ],
            '992'  => [
                'slidesPerView' => 4,
            ],
            '1200' => [
                'slidesPerView' => 5,
            ],
            '1400' => [
                'slidesPerView' => 6,
            ],
        ],
        'thumbs'           => [
            'show' => false,
            'data' => [],
        ],
        'data'             => [],
        'callback'         => [],
        'container_after'  => function () {
        },
        'container_before' => function () {
        },
    ];
    $args     = pubjet_parse_args($args, $defaults);
    if (isset ($args['id']) && $args['id']) {
        $args['id'] = str_replace("-", "_", $args['id']);
    } else {
        $args['id'] = 'carousel_' . uniqid(rand());
    }
    // Check if data is an array and is not empty
    if (!isset($args['data']) || empty($args['data']) || !is_array($args['data'])) {
        return;
    }
    // We need callbcak
    if (!isset($args['callback']) || empty($args['callback'])) {
        return;
    }
    ?>
    <div class="triboon-swiper-container">
        <?php
        if (isset($args['container_before']) && is_callable($args['container_before'])) {
            call_user_func($args['container_before']);
        }
        ?>
        <?php if (isset($args['thumbs']['show']) && $args['thumbs']['show']) { ?>
            <div class="thumbs thumbs-<?php echo esc_attr($args['id']) ?>">
                <div class="swiper-wrapper">
                    <?php
                    if (isset($args['thumbs']['data']) && $args['thumbs']['data'] && is_array($args['thumbs']['data'])) {
                        foreach ($args['thumbs']['data'] as $item) {
                            ?>
                            <div class="swiper-slide">
                                <span class="thumbs__title"><?php echo $item; ?></span>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        <?php } ?>
        <div id="<?php echo esc_attr($args['id']); ?>" class="swiper slides">
            <div class="swiper-wrapper">
                <?php foreach ($args['data'] as $index => $item) { ?>
                    <div class="swiper-slide">
                        <?php call_user_func($args['callback'], $item, $index); ?>
                    </div>
                <?php } ?>
            </div>
            <?php if (is_array($args['pagination']) && isset($args['pagination']['display']) && $args['pagination']['display']) { ?>
                <div class="swiper-pagination <?php echo esc_attr($args['pagination']['classes']); ?>"></div>
            <?php } ?>
            <?php if (is_array($args['scrollbar']) && $args['scrollbar'] && isset($args['scrollbar']['display']) && $args['scrollbar']['display']) { ?>
                <div class="swiper-scrollbar"></div>
            <?php } ?>
            <?php if (is_array($args['navigation']) && $args['navigation'] && isset($args['navigation']['display']) && $args['navigation']['display']) { ?>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            <?php } ?>
        </div>
        <?php
        if (is_array($args['container_after']) && isset($args['container_after']) && is_callable($args['container_after'])) {
            call_user_func($args['container_after']);
        }
        ?>
    </div>
    <script type="text/javascript">
        if (null === window.pubjet_swipers || undefined === window.pubjet_swipers) {
            window.pubjet_swipers = [];
        }
        window.pubjet_swipers.push(<?php echo json_encode($args); ?>);
    </script>
    <?php
}

/**
 * Wrap element with a tag
 *
 * @param $args
 *
 * @return string
 * @since 1.0
 */
function pubjet_wrap_a($args) {
    $args = wp_parse_args($args, [
        'href'    => '',
        'target'  => '_self',
        'classes' => '',
        'render'  => function () {
        },
        'elem'    => false,
    ]);

    ob_start();

    if (trim($args['href'])) {
        echo sprintf('<a href="%s" target="%s" class="%s">', $args['href'], $args['target'], $args['classes']);
    } else {
        if ($args['elem']) {
            echo sprintf('<%s class="%s">', $args['elem'], $args['classes']);
        }
    }

    call_user_func($args['render']);

    if (trim($args['href'])) {
        echo '</a>';
    } else {
        echo '</' . $args['elem'] . '>';
    }

    return ob_get_clean();
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

    if (is_array($entry) || is_object($entry)) {
        $entry = print_r($entry, true);
    }

    $file  = pubjet_debug_dir();
    $file  = fopen($file, 'a');
    $bytes = fwrite($file, $method . "::" . current_time('mysql') . ":: line " . $line . "::" . $entry . "\n");
    fclose($file);

    return $bytes;
}

/**
 * @return bool
 */
function pubjet_is_debug_mode() {
    return boolval(get_option(\triboon\pubjet\includes\enums\EnumOptions::DebugMode));
}

/**
 * @return void
 */
function pubjet_options() {
    /**
     * The  pubjet_options filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_options', [
        'token'    => get_option(\triboon\pubjet\includes\enums\EnumOptions::Token, ''),
        'debug'    => get_option(\triboon\pubjet\includes\enums\EnumOptions::DebugMode, false,),
        'category' => get_option(\triboon\pubjet\includes\enums\EnumOptions::DefaultCategory, ''),
    ]);
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
    return apply_filters('pubjet_debug_dir', PUBJET_DIR_PATH . 'debug.txt');
}

/**
 * @return string
 */
function pubjet_token() {
    /**
     * The pubjet_token filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_token', get_option(\triboon\pubjet\includes\enums\EnumOptions::Token));
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