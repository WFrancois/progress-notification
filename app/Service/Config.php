<?php
/**
 * Created by PhpStorm.
 * User: isak
 * Date: 11/27/17
 * Time: 1:34 PM
 */

namespace ProgressNotification\Service;


class Config
{
    /** @var  Config */
    private static $instance;

    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function getInstance(): Config
    {
        return self::$instance;
    }

    public function get(string $access) {
        return $this->config[$access] ?? null;
    }
}