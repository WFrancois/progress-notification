<?php
/**
 * Created by PhpStorm.
 * User: isak
 * Date: 11/27/17
 * Time: 2:47 PM
 */

namespace ProgressNotification\Service;


class Log
{
    public static function add(string $type, array $data)
    {
        PDO::getInstance()->insert(['log_type' => $type, 'log_data' => \json_encode($data)])->into('log')->execute();
    }
}