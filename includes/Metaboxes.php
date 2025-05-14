<?php

namespace triboon\pubjet\includes;
use triboon\pubjet\includes\traits\Utils;

defined('ABSPATH') || exit;

class Metaboxes extends Singleton
{
    use Utils;

    /**
     * @return void
     */
    public function init()
    {
        add_action('admin_menu', [$this, 'reportage'], 15);

    }

    /**
     * @return void
     */
    public function reportage()
    {
        global $post;

        $post = $this->getPost();

        $metaboxes = $this->getMetaboxes();

        if (empty($metaboxes)) {
            return;
        }

        $this->registerMetaboxes($metaboxes);
    }

    /**
     * دریافت پست بر اساس شناسه
     *
     * @return WP_Post|null
     */
    protected function getPost()
    {
        $post_id = $this->get('post');
        return $post_id ? get_post($post_id) : null;
    }

    /**
     * گرفتن متاباکس‌ها از فیلتر
     *
     * @return array
     */
    private function getMetaboxes()
    {
        return apply_filters('pubjet_reportage_metabox', [
            $this->getDefaultMetabox(),
        ], $this,$this->getPost());
    }

    /**
     * متاباکس پیش‌فرض
     *
     * @return array
     */
    private function getDefaultMetabox()
    {
        return [
            'id'       => 'pubjet-reportage-data',
            'title'    => pubjet__('reportage-data'),
            'context'  => 'side',
            'callback' => [$this, 'renderMetaboxCallback'],
            'register' => [$this, 'shouldRegisterMetabox'],
        ];
    }

    /**
     * callback برای نمایش محتوای متاباکس
     *
     * @return void
     */
    public function renderMetaboxCallback()
    {
        global $post;
        ?>
        <div id="pubjet-reportage-panel-data" data-postid="<?php echo esc_attr($post->ID); ?>"></div>
        <?php
    }

    /**
     * بررسی اینکه آیا متاباکس باید ثبت شود یا خیر
     *
     * @param array $metabox
     * @return bool
     */
    public function shouldRegisterMetabox($metabox)
    {
        global $post;
        return $post && pubjet_is_reportage($post->ID);
    }

    /**
     * ثبت متاباکس‌ها در وردپرس
     *
     * @param array $metaboxes
     * @return void
     */
    private function registerMetaboxes($metaboxes)
    {
        $metaboxes = pubjet_array($metaboxes); // Convert to array

        foreach ($metaboxes as $metabox) {
            if ($this->shouldRegisterMetaboxFor($metabox)) {
                add_meta_box(
                    pubjet_isset_value($metabox['id']),
                    pubjet_isset_value($metabox['title']),
                    pubjet_isset_value($metabox['callback']),
                    pubjet_isset_value($metabox['screen'], 'post'),
                    pubjet_isset_value($metabox['context'], 'normal'),
                    pubjet_isset_value($metabox['priority'], 'low')
                );
            }
        }
    }

    /**
     * بررسی اینکه آیا متاباکس باید ثبت شود یا خیر
     *
     * @param array $metabox
     * @return bool
     */
    private function shouldRegisterMetaboxFor($metabox)
    {
        if (isset($metabox['register']) && is_callable($metabox['register'])) {
            return call_user_func($metabox['register'], $metabox);
        }
        return true;
    }
}
