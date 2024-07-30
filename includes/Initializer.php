<?php

namespace triboon\pubjet\includes;

defined('ABSPATH') || exit;

class Initializer extends Singleton {

    /**
     * @return void
     */
    public function init() {
        RestApi::getInstance();
        Actions::getInstance();
        Filters::getInstance();
        ReportagePost::getInstance();
        RewriteRequest::getInstance();
        AssetsLoader::getInstance();
        Ajax::getInstance();
        Metaboxes::getInstance();
        RestApi::getInstance();
        \triboon\pubjet\includes\notices\Initializer::getInstance();
    }

}