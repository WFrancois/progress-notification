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
        $time = microtime(true);
        $rawPayload = file_get_contents("php://input");
        $sign = hash_hmac('md5', $rawPayload, Config::getInstance()->get('access_token'));

        if($request->getHeader('HTTP_X_RAIDERIO_SIGNATURE')[0] !== $sign) {
            return $response->withJson(['error' => 'incorrect-access-token'])->withStatus(401);
        }

        $payload = $request->getParams();
        Log::add('receive-payload', ['payload' => $payload]);

        if ($payload['type'] !== 'guild_progress_updated') {
            Log::add('incorrect-type', ['payload' => $payload]);
            return $response->withJson(['error' => 'incorrect-type'])->withStatus(400);
        }

        if ($payload['raid']['id'] != 8638) {
            Log::add('only-log-antorus', ['payload' => $payload]);
            return $response->withJson(['error' => 'only-log-antorus']);
        }

        if ($payload['difficulty'] !== 'mythic') {
            Log::add('only-log-mythic', ['payload' => $payload]);
            return $response->withJson(['error' => 'only-log-mythic']);
        }

        if (empty($payload['bossRanks']) || empty($payload['bossRanks']['world']['new']) ||
            empty($payload['bossRanks']['region']['new'])) {
            Log::add('missing-rank', ['payload' => $payload]);
            return $response->withJson(['error' => 'missing-rank'])->withStatus(400);
        }

        if (empty($payload['guild']['name'])) {
            Log::add('missing-guild', ['payload' => $payload]);
            return $response->withJson(['error' => 'missing-guild'])->withStatus(400);
        }

        if (empty($payload['boss']['id']) || empty(Util::getBossName(intval($payload['boss']['id'])))) {
            Log::add('incorrect-boss-id', ['payload' => $payload]);
            return $response->withJson(['error' => 'incorrect-boss-id'])->withStatus(400);
        }

        if(empty($payload['guildProfileUrl'])) {
            Log::add('missing-url', ['payload' => $payload]);
            return $response->withJson(['error' => 'missing-url'])->withStatus(400);
        }

        if(empty($payload['region']['slug'])) {
            Log::add('missing-region', ['payload' => $payload]);
            return $response->withJson(['error' => 'missing-region'])->withStatus(400);
        }

        if(empty($payload['region']['shortName'])) {
            Log::add('missing-region-name', ['payload' => $payload]);
            return $response->withJson(['error' => 'missing-region-short-name'])->withStatus(400);
        }

        $bossId = (int)$payload['boss']['id'];
        $rankWorld = (int)$payload['bossRanks']['world']['new'];
        $rankRegion = (int)$payload['bossRanks']['region']['new'];
        $guildName = $payload['guild']['name'];
        $region = $payload['region']['slug'];

        $bossName = Util::getBossName($bossId);
        if(!empty($payload['boss']['name'])) {
            $bossName = $payload['boss']['name'];
        }

        $message = $guildName . ' killed ' . $bossName .
            ' World ' . Util::getOrdinal($rankWorld) . ', ' . $payload['region']['shortName'] . ' ' . Util::getOrdinal($rankRegion);

        $query = 'SELECT * FROM subscribers WHERE ';

        $queryWhere[] = 'CAST(subscribers.subscribed_to->>\'world\' as int) >= ' .
            $rankWorld;
        $queryWhere[] = 'CAST(subscribers.subscribed_to->>\'' . $region . '\' as int) >= ' .
            $rankRegion;

        $stmt = PDO::getInstance()->query($query . implode(' OR ', $queryWhere));
        $stmt->execute();
        $subscribers = $stmt->fetchAll();

        $data = [
            'title' => 'Raid Progress Update!',
            'text' => $message,
            'icon' => '/img/' . $bossId . '.jpg',
            'url' => $payload['guildProfileUrl'],
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

        Log::add('done', ['payload' => $payload, 'time' => microtime(true) - $time]);
    }
}