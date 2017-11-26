<?php
/**
 * Created by PhpStorm.
 * User: Francois
 * Date: 24/11/2017
 * Time: 22:03
 */

namespace ProgressNotification\Controller;


use Minishlink\WebPush\WebPush;
use ProgressNotification\Service\Notification;
use ProgressNotification\Service\PDO;
use ProgressNotification\Service\Util;
use Slim\Http\Request;
use Slim\Http\Response;

class SubmitController extends BaseController
{
    public function submitKill(Request $request, Response $response)
    {
        $payload = [
            'type' => 'guild_watch',
            'guildId' => 537672,
            'bossId' => 118523,
            'raidId' => 8524,
            'bossCount' => 1,
            'totalBosses' => 9,
            'difficulty' => 'mythic',
            'defeatedAt' => '2017-11-23T20:14:00.000Z',
            'raidRanks' => array(
                'world' => array(
                    'old' => 0,
                    'new' => 1,
                ),
                'region' => array(
                    'old' => 0,
                    'new' => 4658,
                ),
                'realm' => array(
                    'old' => 0,
                    'new' => 116,
                ),
            ),
            'bossRanks' => array(
                'world' => array(
                    'old' => 0,
                    'new' => 4,
                ),
                'region' => array(
                    'old' => 0,
                    'new' => 3106,
                ),
                'realm' => array(
                    'old' => 0,
                    'new' => 79,
                ),
            ),
            'payloadParams' => array(
                'guild' => 'Платинум',
                'realm' => 'Soulflayer',
                'region' => 'eu',
                'difficulty' => 'mythic',
                'boss_ranks' => array(
                    'world' => 1,
                    'region' => 3106,
                    'realm' => 79,
                ),
            ),
        ];

        if ($payload['type'] !== 'guild_watch_buffered' && $payload['type'] !== 'guild_watch') {
            return;
        }

        if ($payload['difficulty'] !== 'mythic') {
            return;
        }

        if ($payload['payloadParams']['boss_ranks']['world'] > 5) {
            return;
        }

        $message = $payload['payloadParams']['guild'] . ' killed bossId World ' . Util::getOrdinal($payload['payloadParams']['boss_ranks']['world']);

        $webPush = Notification::getInstance();
        $subscribers = PDO::getInstance()->select()->from('subscribers')->execute()->fetchAll();

        $data = [
            'title' => 'Yolo',
            'text' => $message,
        ];

        foreach ($subscribers as $subscriber) {
            $time = microtime(true);
            $json = json_decode($subscriber['google_json'], true);
            $notification['endpoint'] = $json['endpoint'];
            $webPush->sendNotification(
                $json['endpoint'] ?? '',
                json_encode($data),
                $json['keys']['p256dh'] ?? null,
                $json['keys']['auth'] ?? null
            );
            echo 'time: ' . (microtime(true) - $time) . '<br />';
        }

        $time = microtime(true);
        var_dump($webPush->flush());
        echo 'time: ' . (microtime(true) - $time) . '<br />';
    }
}