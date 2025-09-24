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
        $use_cdn = get_post_meta($post->ID, 'pubjet_use_cdn', true);
        ?>
        <div class="pubjet-metabox-wrapper">

            <!-- تنظیمات نمایش -->
            <fieldset style="margin-top: 20px; margin-bottom:20px;">
                <legend><strong>تنظیمات نمایش تصاویر</strong></legend>
                <label style="display: flex; align-items: center; gap: 8px; margin-top: 5px;">
                    <input type="checkbox"
                           name="pubjet_use_cdn"
                           value="1"
                        <?php checked(1, $use_cdn); ?> />
                    استفاده از آدرس اصلی تصاویر (CDN خارجی)
                </label>
                <p class="description">
                    در صورت فعال بودن، تصاویر از آدرس اصلی خود (CDN) بارگذاری می‌شوند و از فضای هاست سایت مصرف نخواهد شد.
                </p>
            </fieldset>

            <!-- تولید مجدد تصاویر -->
            <fieldset style="margin-top: 20px; margin-bottom:20px;">
                <legend><strong>تولید مجدد تصاویر</strong></legend>
                <p class="description">
                    در صورت نیاز می‌توانید تصاویر این پست را دوباره تولید کنید.
                </p>
                <div class="pubjet-actions" style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="button button-secondary pubjet-regthumb" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        تولید مجدد تصویر شاخص
                    </button>
                    <button type="button" class="button button-secondary pubjet-regcontent" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        تولید مجدد تصاویر محتوا
                    </button>
                    <button type="button" class="button button-primary pubjet-regall" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        تولید همه تصاویر پست
                    </button>
                </div>
            </fieldset>

            <?php wp_nonce_field('pubjet_cdn_nonce_action', 'pubjet_cdn_nonce'); ?>
            <fieldset style="margin-top: 20px; margin-bottom:20px;">
                <legend><strong>اطلاعات رپورتاژ</strong></legend>
                <p class="description">
                    اطلاعات اولیه رپورتاژ ارسال شده از پنل تریبون به سایت شما
                </p>
                <div id="pubjet-reportage-panel-data" data-postid="<?php echo esc_attr($post->ID); ?>"></div>

            </fieldset>
        </div>

        <style>
            .pubjet-metabox-wrapper fieldset {
                border: 1px solid #ddd;
                padding: 12px 14px;
                background: #fdfdfd;
                border-radius: 6px;
            }
            .pubjet-metabox-wrapper legend {
                padding: 0 5px;
                font-size: 14px;
                color: #333;
            }
            .pubjet-actions .button {
                min-width: 160px;
                text-align: center;
            }
        </style>
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
