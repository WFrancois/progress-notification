<?php

namespace ProgressNotification\Controller;


use ProgressNotification\Service\Config;
use ProgressNotification\Service\PDO;
use ProgressNotification\Service\Util;
use Slim\Http\Request;
use Slim\Http\Response;

class RegisterController extends BaseController
{
    public function registerAction(Request $request, Response $response)
    {
        return $this->view->render($response, 'register.html.twig', [
            'application_public_key' => Config::getInstance()->get('webPush')['publicKey'] ?? '',
        ]);
    }

    public function ajaxRegister(Request $request, Response $response)
    {
        $subscription = json_encode($request->getParam('subscription'));

        $unRegister = $request->getParam('unsubscribe');

        $params = [
            'google_json' => $subscription,
        ];
        if ($unRegister === 'true') {
            $sql = <<<SQL
DELETE FROM subscribers WHERE google_json = :google_json;
SQL;
        } else {
            $sql = <<<SQL
INSERT INTO subscribers(subscribed_to, google_json) VALUES(:subscribed_to, :google_json) ON CONFLICT (google_json) DO UPDATE SET subscribed_to = :subscribed_to;
SQL;

            $subTo = $request->getParam('subTo');
            $subscribed_to = ['world' => 3];
            if (is_array($subTo)) {
                $subscribed_to = [];
                foreach ($subTo as $region => $number) {
                    if (!empty(Util::REGION[$region]) && $number > 0 && $number <= 20) {
                        $subscribed_to[$region] = (int)$number;
                    }
                }
            }

            $params['subscribed_to'] = \json_encode($subscribed_to);
        }

        $stmt = PDO::getInstance()->prepare($sql);
        $stmt->execute($params);
    }

    public function ajaxGetCurrentSubscription(Request $request, Response $response)
    {
        $subscription = $request->getParam('subscription');

        if (empty($subscription)) {
            return $response->withStatus(400);
        }

        $subscriber = PDO::getInstance()
            ->select(['subscribed_to'])
            ->from('subscribers')
            ->where('google_json', '=', \json_encode($subscription))
            ->execute()
            ->fetch();

        if(empty($subscriber)) {
            return $response->withStatus(404);
        }

        $regions = [];
        $howMuch = 0;

        $subscribedTo = json_decode($subscriber['subscribed_to'], true);

        if(empty($subscribedTo) || !is_array($subscribedTo)) {
            return $response->withStatus(400);
        }

        foreach($subscribedTo as $region => $number) {
            if(in_array($region, ['eu', 'us', 'tw', 'kr', 'world'])) {
                $regions[] = $region;
                $howMuch = $number;
            }
        }

        return $response->withJson([
            'regions' => $regions,
            'howMuch' => $howMuch,
        ]);
    }
}