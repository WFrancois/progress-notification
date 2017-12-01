<?php

namespace ProgressNotification\Controller;


use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use ProgressNotification\Service\Config;
use ProgressNotification\Service\Log;
use ProgressNotification\Service\PDO;
use ProgressNotification\Service\Util;
use Slim\Http\Request;
use Slim\Http\Response;

class SubmitController extends BaseController
{
    public function submitKill(Request $request, Response $response)
    {
        if ($request->getParam('ACCESS_TOKEN') !== Config::getInstance()->get('access_token')) {
            return $response->withJson(['error' => 'incorrect-access-token'])->withStatus(401);
        }

        $payload = $request->getParam('payload');
        Log::add('receive-payload', ['payload' => $payload]);

        if ($payload['type'] !== 'guild_watch_buffered' && $payload['type'] !== 'guild_watch') {
            Log::add('incorrect-type', ['payload' => $payload]);
            return $response->withJson(['error' => 'incorrect-type'])->withStatus(400);
        }

        if ($payload['raidId'] != 8638) {
            Log::add('incorrect-raid', ['payload' => $payload]);
            return $response->withJson(['error' => 'incorrect-raid'])->withStatus(400);
        }

        if ($payload['difficulty'] !== 'mythic') {
            Log::add('incorrect-difficulty', ['payload' => $payload]);
            return $response->withJson(['error' => 'incorrect-difficulty'])->withStatus(400);
        }

        if (empty($payload['payloadParams']) || empty($payload['payloadParams']['boss_ranks']['world']) ||
            empty($payload['payloadParams']['boss_ranks']['region'])) {
            Log::add('missing-rank', ['payload' => $payload]);
            return $response->withJson(['error' => 'missing-rank'])->withStatus(400);
        }

        if (empty($payload['payloadParams']['guild'])) {
            Log::add('missing-guild', ['payload' => $payload]);
            return $response->withJson(['error' => 'missing-guild'])->withStatus(400);
        }

        if (empty($payload['bossId']) || empty(Util::getBossName(intval($payload['bossId'])))) {
            Log::add('incorrect-boss-id', ['payload' => $payload]);
            return $response->withJson(['error' => 'incorrect-boss-id'])->withStatus(400);
        }

        if(empty($payload['payloadParams']['region'])) {
            Log::add('missing-region', ['payload' => $payload]);
            return $response->withJson(['error' => 'missing-region'])->withStatus(400);
        }

        $bossId = (int)$payload['bossId'];
        $rankWorld = (int)$payload['payloadParams']['boss_ranks']['world'];
        $rankRegion = (int)$payload['payloadParams']['boss_ranks']['region'];
        $guildName = $payload['payloadParams']['guild'];
        $region = $payload['payloadParams']['region'];

        $message = $guildName . ' killed ' . Util::getBossName($bossId) .
            ' World ' . Util::getOrdinal($rankWorld) . ', ' . Util::REGION[$region] . ' ' . Util::getOrdinal($rankRegion);

        $query = 'SELECT * FROM subscribers WHERE ';

        $queryWhere[] = 'CAST(subscribers.subscribed_to->>\'world\' as int) >= ' .
            $rankWorld;
        $queryWhere[] = 'CAST(subscribers.subscribed_to->>\'' . $region . '\' as int) >= ' .
            $rankRegion;

        $stmt = PDO::getInstance()->query($query . implode(' OR ', $queryWhere));
        $stmt->execute();
        $subscribers = $stmt->fetchAll();

        $data = [
            'title' => 'ProgRace',
            'text' => $message,
            'icon' => '/img/' . $bossId . '.jpg',
        ];

        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $channel->queue_declare('notification', false, true, false, false);
        $channel->exchange_declare('router', 'direct', false, true, false);
        $channel->queue_bind('notification', 'router');

        Log::add('send-notification', ['payload' => $payload, 'subscriber' => count($subscribers)]);
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