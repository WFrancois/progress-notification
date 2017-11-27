<?php

namespace ProgressNotification\Controller;


use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use ProgressNotification\Service\Config;
use ProgressNotification\Service\PDO;
use ProgressNotification\Service\Util;
use Slim\Http\Request;
use Slim\Http\Response;

class SubmitController extends BaseController
{
    public function submitKill(Request $request, Response $response)
    {
//        $payload = [
//            'type' => 'guild_watch',
//            'guildId' => 537672,
//            'bossId' => 118523,
//            'raidId' => 8524,
//            'bossCount' => 1,
//            'totalBosses' => 9,
//            'difficulty' => 'mythic',
//            'defeatedAt' => '2017-11-23T20:14:00.000Z',
//            'raidRanks' => array(
//                'world' => array(
//                    'old' => 0,
//                    'new' => 1,
//                ),
//                'region' => array(
//                    'old' => 0,
//                    'new' => 4658,
//                ),
//                'realm' => array(
//                    'old' => 0,
//                    'new' => 116,
//                ),
//            ),
//            'bossRanks' => array(
//                'world' => array(
//                    'old' => 0,
//                    'new' => 4,
//                ),
//                'region' => array(
//                    'old' => 0,
//                    'new' => 3106,
//                ),
//                'realm' => array(
//                    'old' => 0,
//                    'new' => 79,
//                ),
//            ),
//            'payloadParams' => array(
//                'guild' => 'Платинум',
//                'realm' => 'Soulflayer',
//                'region' => 'us',
//                'difficulty' => 'mythic',
//                'boss_ranks' => array(
//                    'world' => 7,
//                    'region' => 2,
//                    'realm' => 79,
//                ),
//            ),
//        ];

        if($request->getHeader('ACCESS_TOKEN') !== Config::getInstance()->get('access_token')) {
            return $response->withJson(['error' => 'incorrect-access-token'])->withStatus(401);
        }

        $payload = $request->getParam('payload');

        if ($payload['type'] !== 'guild_watch_buffered' && $payload['type'] !== 'guild_watch') {
            return $response->withJson(['error' => 'incorrect-type'])->withStatus(400);
        }

        if($payload['raidId'] != 8638) {
            return $response->withJson(['error' => 'incorrect-raid'])->withStatus(400);
        }

        if ($payload['difficulty'] !== 'mythic') {
            return $response->withJson(['error' => 'incorrect-difficulty'])->withStatus(400);
        }

        $message = $payload['payloadParams']['guild'] . ' killed bossId World ' . Util::getOrdinal($payload['payloadParams']['boss_ranks']['world']);

        $query = 'SELECT * FROM subscribers WHERE ';

        $queryWhere[] = 'CAST(subscribers.subscribed_to->>\'world\' as int) >= ' .
            intval($payload['payloadParams']['boss_ranks']['world']);
        $queryWhere[] = 'CAST(subscribers.subscribed_to->>\'' . $payload['payloadParams']['region'] . '\' as int) >= ' .
            intval($payload['payloadParams']['boss_ranks']['region']);

        $stmt = PDO::getInstance()->query($query . implode(' OR ', $queryWhere));
        $stmt->execute();
        $subscribers = $stmt->fetchAll();

        $data = [
            'title' => 'Yolo',
            'text' => $message,
        ];

        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $channel->queue_declare('notification', false, true, false, false);
        $channel->exchange_declare('router', 'direct', false, true, false);
        $channel->queue_bind('notification', 'router');

        foreach ($subscribers as $subscriber) {
            $messageBroker = [
                'pushInfo' => \json_decode($subscriber['google_json'], true),
                'message' => $data,
            ];
            $message = new AMQPMessage(\json_encode($messageBroker));
            $channel->batch_basic_publish($message, 'router');
        }

        $channel->publish_batch();
        $channel->close();
        $connection->close();
    }
}