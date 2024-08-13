<?php

namespace triboon\pubjet\includes;

defined('ABSPATH') || exit;

class Shortcodes extends Singleton {

    /**
     * @return void
     */
    public function init() {
        add_shortcode('pubjet_backlinks', [$this, 'renderBacklinks']);
    }

    /**
     * @return string
     */
    public function renderBacklinks($shortcode_atts) {
        $shortcode_atts = pubjet_parse_args($shortcode_atts, [
            'position' => 'all',
            'style'    => 'vertical',
        ]);
        $backlinks      = pubjet_db()->backlinks->findActives();
        if (!$backlinks) {
            return '';
        }
        ob_start();
        ?>
        <div class="pubjet-backlinks pubjet-backlinks-<?php echo esc_attr($shortcode_atts['style']); ?>">
            <?php
            foreach ($backlinks as $backlink_row) {
                ?>
                <a href="<?php echo esc_url($backlink_row->url); ?>" class="pubjet-backlinks__item" rel="nofollow">
                    <?php echo esc_html($backlink_row->text); ?>
                </a>
                <?php
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

}