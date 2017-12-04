<?php

namespace ProgressNotification\Controller;


use Curl\Curl;
use ProgressNotification\Service\Config;
use ProgressNotification\Service\Log;
use ProgressNotification\Service\PDO;
use ProgressNotification\Service\Util;
use Slim\Http\Request;
use Slim\Http\Response;

class StreamlabController extends BaseController
{
    public function errorOauth(Request $request, Response $response)
    {
        return $this->view->render($response, 'errorRegisterOauth.html.twig', [
            'application_public_key' => Config::getInstance()->get('webPush')['publicKey'] ?? '',
            'streamlabsRedirect' => Config::getInstance()->get('streamlabs')['redirect'] ?? '',
            'streamlabsClient' => Config::getInstance()->get('streamlabs')['clientId'] ?? '',
        ]);
    }

    public function confirmCode(Request $request, Response $response)
    {
        $error = $request->getParam('error');

        if (!empty($error)) {
            return $response->withHeader('Location', $this->container->router->pathFor('streamErrorPage'));
        }

        $code = $request->getParam('code');

        $curl = new Curl();
        $curl->setDefaultJsonDecoder($assoc = true);
        $curl->post('https://streamlabs.com/api/v1.0/token', [
            'grant_type' => 'authorization_code',
            'client_id' => Config::getInstance()->get('streamlabs')['clientId'] ?? '',
            'client_secret' => Config::getInstance()->get('streamlabs')['clientSecret'] ?? '',
            'redirect_uri' => Config::getInstance()->get('streamlabs')['redirect'] ?? '',
            'code' => $code,
        ]);

        $tokenInfo = $curl->response;

        if (!empty($tokenInfo['error'])) {
            Log::add('error-curl', [$tokenInfo]);
            return $response->withHeader('Location', $this->container->router->pathFor('streamErrorPage'));
        }

        $curl->get('https://streamlabs.com/api/v1.0/user', [
            'access_token' => $tokenInfo['access_token'],
        ]);


        $userInfo = $curl->response;

        if (!empty($userInfo['error'])) {
            Log::add('error-curl', [$userInfo]);
            return $response->withHeader('Location', $this->container->router->pathFor('streamErrorPage'));
        }

        $sql = <<<SQL
INSERT INTO streamlabs(twitch_id, access_token, refresh_token, token_type, expires_in, created_at) 
VALUES(:twitch_id, :access_token, :refresh_token, :token_type, :expires_in, NOW()) 
ON CONFLICT (twitch_id) 
DO UPDATE SET access_token = :access_token, refresh_token = :refresh_token, token_type = :token_type, expires_in = :expires_in, created_at = NOW();;
SQL;


        $stmt = PDO::getInstance()->prepare($sql);
        $stmt->execute([
            'twitch_id' => $userInfo['twitch']['id'],
            'username' => $userInfo['twitch']['name'],
            'access_token' => $tokenInfo['access_token'],
            'refresh_token' => $tokenInfo['refresh_token'],
            'token_type' => $tokenInfo['token_type'],
            'expires_in' => $tokenInfo['expires_in'],
        ]);

        $_SESSION['twitch_id'] = $userInfo['twitch']['id'];

        return $response->withHeader('Location', $this->container->router->pathFor('streamRegisterPage'));
    }

    public function streamRegisterAction(Request $request, Response $response)
    {
        $streamlab = PDO::getInstance()->select([])->from('streamlabs')->execute()->fetch();

        if (empty($streamlab)) {
            return $response->withHeader('Location', $this->container->router->pathFor('streamErrorPage'));
        }

        $subscribedTo = \json_decode($streamlab['subscribed_to'], true);
        $options = \json_decode($streamlab['options'], true);

        $regions = [];
        $howMuch = null;
        if(!empty($subscribedTo) && is_array($subscribedTo)) {
            foreach($subscribedTo as $region => $number) {
                if(in_array($region, ['eu', 'us', 'tw', 'kr', 'world'])) {
                    $regions[] = $region;
                    $howMuch = $number;
                }
            }
        }

        return $this->view->render($response, 'streamlabsRegister.html.twig', [
            'subscribedTo' => $subscribedTo,
            'regions' => $regions,
            'howMuch' => $howMuch,
            'type' => $options['type'] ?? '',
            'sound' => $options['sound'] ?? '',
        ]);
    }

    public function ajaxRegisterAction(Request $request, Response $response)
    {
        if (empty($_SESSION['twitch_id'])) {
            return $response->withStatus(400);
        }

        $unRegister = $request->getParam('unsubscribe');

        if ($unRegister === 'true') {
            $stmt = PDO::getInstance()->update(['subscribed_to' => null, 'options' => null]);

        } else {
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

            $sound = $request->getParam('sound');
            $type = $request->getParam('type');

            $options = [];
            if(!empty($type)) {
                $options['type'] = $type;
            }
            if(!empty($sound)) {
                $options['sound'] = $sound;
            }

            $stmt = PDO::getInstance()->update(['subscribed_to' => \json_encode($subscribed_to), 'options' => \json_encode($options)]);
        }

        $stmt->table('streamlabs')->where('twitch_id', '=', $_SESSION['twitch_id'])->execute();
    }
}