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
    private $payload = [];

    public function submitKill(Request $request, Response $response)
    {
        $rawPayload = file_get_contents("php://input");
        $sign = hash_hmac('md5', $rawPayload, Config::getInstance()->get('access_token'));

        if ($request->getHeader('HTTP_X_RAIDERIO_SIGNATURE')[0] !== $sign) {
            return $response->withJson(['error' => 'incorrect-access-token'])->withStatus(401);
        }

        $this->payload = $request->getParams();

        Log::add('receive-payload', ['payload' => $this->payload]);

        if ($this->payload['type'] !== 'guild_progress_updated') {
            Log::add('incorrect-type', ['payload' => $this->payload]);
            return $response->withJson(['error' => 'incorrect-type'])->withStatus(400);
        }

        if ($this->payload['raid']['id'] != 8638) {
            Log::add('only-log-antorus', ['payload' => $this->payload]);
            return $response->withJson(['error' => 'only-log-antorus']);
        }

        if ($this->payload['difficulty'] !== 'mythic') {
            Log::add('only-log-mythic', ['payload' => $this->payload]);
            return $response->withJson(['error' => 'only-log-mythic']);
        }

        if (empty($this->payload['bossRanks']) || empty($this->payload['bossRanks']['world']['new']) ||
            empty($this->payload['bossRanks']['region']['new'])) {
            Log::add('missing-rank', ['payload' => $this->payload]);
            return $response->withJson(['error' => 'missing-rank'])->withStatus(400);
        }

        if (empty($this->payload['guild']['name'])) {
            Log::add('missing-guild', ['payload' => $this->payload]);
            return $response->withJson(['error' => 'missing-guild'])->withStatus(400);
        }

        if (empty($this->payload['boss']['id']) || empty(Util::getBossName(intval($this->payload['boss']['id'])))) {
            Log::add('incorrect-boss-id', ['payload' => $this->payload]);
            return $response->withJson(['error' => 'incorrect-boss-id'])->withStatus(400);
        }

        if (empty($this->payload['guildProfileUrl'])) {
            Log::add('missing-url', ['payload' => $this->payload]);
            return $response->withJson(['error' => 'missing-url'])->withStatus(400);
        }

        if (empty($this->payload['region']['slug'])) {
            Log::add('missing-region', ['payload' => $this->payload]);
            return $response->withJson(['error' => 'missing-region'])->withStatus(400);
        }

        if (empty($this->payload['region']['shortName'])) {
            Log::add('missing-region-name', ['payload' => $this->payload]);
            return $response->withJson(['error' => 'missing-region-short-name'])->withStatus(400);
        }

        $bossId = (int)$this->payload['boss']['id'];
        $rankWorld = (int)$this->payload['bossRanks']['world']['new'];
        $rankRegion = (int)$this->payload['bossRanks']['region']['new'];
        $guildName = $this->payload['guild']['name'];
        $region = $this->payload['region']['slug'];

        $bossName = Util::getBossName($bossId);
        if (!empty($this->payload['boss']['name'])) {
            $bossName = $this->payload['boss']['name'];
        }


        $this->sendPushApi($rankWorld, $rankRegion, $region, $this->payload['region']['shortName'], $bossId, $bossName, $this->payload['guildProfileUrl'], $guildName);
        $this->sendStreamlabs($rankWorld, $rankRegion, $region, $this->payload['region']['shortName'], $bossId, $bossName, $this->payload['guildProfileUrl'], $guildName);
    }

    private function sendPushApi($rankWorld, $rankRegion, $region, $regionShortName, $bossId, $bossName, $guildUrl, $guildName)
    {
        $messageText = $guildName . ' killed ' . $bossName . ' World ' . Util::getOrdinal($rankWorld) . ', ' . $regionShortName . ' ' . Util::getOrdinal($rankRegion);
        $time = microtime(true);

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
            'text' => $messageText,
            'icon' => '/img/' . $bossId . '.jpg',
            'url' => $guildUrl,
        ];

        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $channel->queue_declare('notification', false, true, false, false);
        $channel->exchange_declare('router', 'direct', false, true, false);
        $channel->queue_bind('notification', 'router');

        Log::add('send-notification', ['payload' => $this->payload, 'subscriber' => count($subscribers)]);
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

        Log::add('sent-push-api', ['payload' => $this->payload, 'time' => microtime(true) - $time]);
    }

    private function sendStreamlabs($rankWorld, $rankRegion, $region, $regionShortName, $bossId, $bossName, $guildUrl, $guildName)
    {
        $messageText = $bossName . ' has been killed by ' . $guildName . ' World ' . Util::getOrdinal($rankWorld) . ', ' . $regionShortName . ' ' . Util::getOrdinal($rankRegion);
        $time = microtime(true);

        $query = 'SELECT * FROM streamlabs WHERE ';

        $queryWhere[] = 'CAST(streamlabs.subscribed_to->>\'world\' as int) >= ' .
            $rankWorld;
        $queryWhere[] = 'CAST(streamlabs.subscribed_to->>\'' . $region . '\' as int) >= ' .
            $rankRegion;

        $stmt = PDO::getInstance()->query($query . implode(' OR ', $queryWhere));
        $stmt->execute();
        $subscribers = $stmt->fetchAll();

        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $channel->queue_declare('streamlabs', false, true, false, false);
        $channel->exchange_declare('router_streamlabs', 'direct', false, true, false);
        $channel->queue_bind('streamlabs', 'router_streamlabs');

        Log::add('send-notification', ['payload' => $this->payload, 'subscriber' => count($subscribers)]);
        foreach ($subscribers as $subscriber) {
            $options = \json_decode($subscriber['options'], true);
            $messageBroker = [
                'pushInfo' => $subscriber['twitch_id'],
                'message' => $messageText,
                'image' => 'https://prograce.info/img/' . $bossId . '_screen.jpg',
                'sound' => $options['sound'] ?? '',
                'type' => $options['type'] ?? 'follow'
            ];
            $message = new AMQPMessage(\json_encode($messageBroker));
            $channel->batch_basic_publish($message, 'router_streamlabs');
        }

        $channel->publish_batch();
        $channel->close();
        $connection->close();
        Log::add('sent-streamlabs', ['payload' => $this->payload, 'time' => microtime(true) - $time]);
    }
}