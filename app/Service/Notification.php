<?php

namespace ProgressNotification\Service;


use Minishlink\WebPush\WebPush;

class Notification
{
    /** @var  WebPush */
    private static $instance;

    public function __construct($config)
    {
        self::$instance = new WebPush([
            'VAPID' => $config,
        ]);
    }

    public static function getInstance()
    {
        return self::$instance;
    }
}