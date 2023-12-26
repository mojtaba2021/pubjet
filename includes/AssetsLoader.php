<?php

namespace triboon\pubjet\includes;

defined('ABSPATH') || exit;

class AssetsLoader extends Singleton {

    /**
     * @var string
     */
    private $_webpack_ip_address = 'http://localhost:8090';

    /**
     * @return void
     */
    public function init() {
        add_action('admin_enqueue_scripts', [$this, 'loadAdminAssets'], 15);
        add_action('wp_enqueue_scripts', [$this, 'loadThemeAssets'], 15);
    }

    /**
     * @return void
     */
    public function loadThemeAssets() {
        if (pubjet_is_prod_mode()) {
            if (!apply_filters('pubjet_load_theme_assets', true, $this)) {
                return;
            }
            // ======================= Production =======================
            wp_enqueue_style('pubjet_styles', PUBJET_CSS_URL . $this->findCSSFile('theme'), [], PUBJ()->getScriptsVersion());
            wp_enqueue_script('pubjet_scripts', PUBJET_JS_URL . $this->findJSFile('theme'), ['jquery'], PUBJ()->getScriptsVersion(), true);
        } else {
            wp_enqueue_script('pubjet_scripts', $this->getWebpackIPAddress() . '/theme.js', ['jquery'], PUBJ()->getScriptsVersion(), true);
        }
        wp_localize_script('pubjet_scripts', 'pubjet_params', $this->getScriptVars());
    }

    /**
     * @return void
     * @since  1.0
     * @author Pishook
     */
    public function loadAdminAssets() {
        /**
         * The pubjet_load_admin_assets filter.
         *
         * @since 1.0.0
         */
        if (!apply_filters('pubjet_load_admin_assets', true, $this)) {
            return;
        }
        if (pubjet_is_prod_mode()) {
            wp_enqueue_style('pubjet_styles', PUBJET_CSS_URL . $this->findCSSFile('admin'), [], PUBJ()->getScriptsVersion());
            wp_enqueue_script('pubjet_scripts', PUBJET_JS_URL . $this->findJSFile('admin'), ['jquery'], PUBJ()->getScriptsVersion(), true);
        } else {
            wp_enqueue_script('pubjet_scripts', $this->getWebpackIPAddress() . '/admin.js', ['jquery'], PUBJ()->getScriptsVersion(), true);
        }
        wp_localize_script('pubjet_scripts', 'pubjet_params', $this->getScriptVars());
    }

    /**
     * @return array
     */
    private function getScriptVars() {
        /**
         * The pubjet_script_vars filter.
         *
         * @since 1.0.0
         */
        return apply_filters('pubjet_script_vars', [
            'ajaxurl'    => admin_url('admin-ajax.php'),
            'rest_url'   => rest_url() . 'triboon',
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'is_rtl'     => (bool)is_rtl(),
            'locale'     => get_locale(),
            'user'       => [
                'loggedin' => (bool)is_user_logged_in(),
            ],
            'assets'     => [
                'css'    => PUBJET_CSS_URL,
                'js'     => PUBJET_JS_URL,
                'images' => PUBJET_IMAGES_URL,
            ],
            'images_url' => PUBJET_IMAGES_URL,
            'nonce'      => wp_create_nonce('pubjet-nonce'),
            'i18n'       => [
                'refresh-data'  => 'بروزرسانی اطلاعات',
                'error-occured' => 'خطایی رخ داده است. مجددا تلاش کنید',
            ],
        ]);
    }

    /**
     * @param $pattern
     */
    protected function findJSFile($needle) {
        $allfiles = scandir(PUBJET_JS_DIR);
        foreach ($allfiles as $file) {
            if (strstr($file, $needle)) {
                return $file;
            }
        }

        return false;
    }

    /**
     * @param $pattern
     */
    protected function findCSSFile($needle) {
        $allfiles = scandir(PUBJET_CSS_DIR);
        foreach ($allfiles as $file) {
            if (strstr($file, $needle)) {
                return $file;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getWebpackIPAddress() {
        return $this->_webpack_ip_address;
    }

}