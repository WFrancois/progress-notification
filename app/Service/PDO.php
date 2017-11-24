<?php

namespace ProgressNotification\Service;


use Slim\PDO\Database;

class PDO
{
    private static $pdo;

    public function __construct($config)
    {
        try {
            $dsn = 'pgsql:host=' . $config['host'] . ';dbname=' . $config['dbname'];

            self::$pdo = new Database($dsn, $config['user'], $config['password']);
        } catch (\Exception $e) {
            die('Error database');
        }
    }

    public static function getInstance()
    {
        return self::$pdo;
    }
}