<?php

namespace ProgressNotification\Controller;


use Curl\Curl;
use ProgressNotification\Service\Config;
use ProgressNotification\Service\Log;
use ProgressNotification\Service\PDO;
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
INSERT INTO streamlabs(twitch_id, access_token, refresh_token, token_type, expires_in) 
VALUES(:twitch_id, :access_token, :refresh_token, :token_type, :expires_in) 
ON CONFLICT (twitch_id) 
DO UPDATE SET access_token = :access_token, refresh_token = :refresh_token, token_type = :token_type, expires_in = :expires_in;
SQL;


        $stmt = PDO::getInstance()->prepare($sql);
        $stmt->execute([
            'twitch_id' => $userInfo['twitch']['id'],
            'access_token' => $tokenInfo['access_token'],
            'refresh_token' => $tokenInfo['refresh_token'],
            'token_type' => $tokenInfo['token_type'],
            'expires_in' => $tokenInfo['expires_in'],
        ]);

        $_SESSION['twitch_id'] = $userInfo['twitch']['id'];
    }
}