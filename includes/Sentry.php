<?php

namespace triboon\pubjet\includes;

defined('ABSPATH') || exit;

class Sentry extends Singleton {

    /**
     * @return void
     */
    public function init() {
        \Sentry\init([
                         'dsn' => 'https://ea145de5084460f189b3bf2d66b6af06@sentry.hamravesh.com/6647',
                     ]);
    }

}