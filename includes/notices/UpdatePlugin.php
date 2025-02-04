<?php

namespace triboon\pubjet\includes\notices;

// Exit if accessed directly
defined('ABSPATH') || exit;

class UpdatePlugin extends BaseNotice {

    public $current_version;
    public $new_version;
    public function __construct() {
        parent::__construct();
        $this->current_version = PUBJ()->getVersion();
        $this->new_version = pubjet_check_new_version();
    }

    /**
     * @return mixed|void
     */
    protected function getId() {
        return 'pubjet-enter-token';
    }

    /**
     * @return mixed|void
     */
    public function getTitle() {
        return pubjet__('pubjet-update-plugin-title');
    }

    /**
     * @return mixed|void
     */
    public function getText() {
        return sprintf(
            pubjet__('pubjet-update-plugin-desc'),
            esc_html($this->new_version)
        );
    }

    /**
     * @since 1.0.0
     */
    protected function showOnPages() {
        return ['index.php'];
    }

    /**
     * @return array
     */
    protected function getButtons() {
        return [
            [
                'text'  => 'بروزرسانی پابجت',
                'icon'  => '<svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 122.61 122.88" width="24" height="24" fill="white"><title>update</title><path d="M111.9,61.57a5.36,5.36,0,0,1,10.71,0A61.3,61.3,0,0,1,17.54,104.48v12.35a5.36,5.36,0,0,1-10.72,0V89.31A5.36,5.36,0,0,1,12.18,84H40a5.36,5.36,0,1,1,0,10.71H23a50.6,50.6,0,0,0,88.87-33.1ZM106.6,5.36a5.36,5.36,0,1,1,10.71,0V33.14A5.36,5.36,0,0,1,112,38.49H84.44a5.36,5.36,0,1,1,0-10.71H99A50.6,50.6,0,0,0,10.71,61.57,5.36,5.36,0,1,1,0,61.57,61.31,61.31,0,0,1,91.07,8,61.83,61.83,0,0,1,106.6,20.27V5.36Z"/></svg>',
                'url'   => admin_url('plugins.php'),
                'class' => ['button-primary'],
            ],
        ];
    }

    /**
     * @return boolean
     * @since 1.0.0
     */
    protected function shouldShowNotice() {
        $show = parent::shouldShowNotice();
        if (!$show) {
            return false;
        }
        pubjet_log('current version' . $this->current_version);
        pubjet_log('new version' . $this->new_version);

        if (isset($this->new_version) && version_compare($this->current_version, $this->new_version, '<')) {
            pubjet_log('need update');
            return true;
        }

        return false;
    }


}