<?php

namespace ProgressNotification\Service;


class Config
{
    /** @var  Config */
    private static $instance;

    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        self::$instance = $this;
    }

    public static function getInstance(): Config
    {
        return self::$instance;
    }

    public function get(string $access, $default = null)
    {
        return $this->config[$access] ?? $default;
    }
}