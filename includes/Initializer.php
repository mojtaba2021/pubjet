<?php

namespace triboon\pubjet\includes;

defined('ABSPATH') || exit;

class Initializer extends Singleton {

    /**
     * @return void
     */
    public function init() {
        Actions::getInstance();
        Filters::getInstance();
        ReportagePost::getInstance();
        AssetsLoader::getInstance();
        RewriteHooks::getInstance();
        RewriteRequest::getInstance();
        Metaboxes::getInstance();
    }

}