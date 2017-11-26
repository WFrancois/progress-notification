<?php

namespace ProgressNotification\Controller;


use ProgressNotification\Service\PDO;
use Slim\Http\Request;
use Slim\Http\Response;

class RegisterController extends BaseController
{
    public function registerAction(Request $request, Response $response)
    {
        return $this->view->render($response, 'register.html.twig');
    }

    public function ajaxRegister(Request $request, Response $response)
    {
        var_dump($request->getParams());
        $subscription = json_encode($request->getParam('subscription'));

        $unRegister = $request->getParam('unsubscribe');

        $params = [
            'google_json' => $subscription,
        ];
        if($unRegister === 'true') {
            $sql = <<<SQL
DELETE FROM subscribers WHERE google_json = :google_json;
SQL;
        } else {
            $sql = <<<SQL
INSERT INTO subscribers(subscribed_to, google_json) VALUES(:subscribed_to, :google_json) ON CONFLICT (google_json) DO UPDATE SET subscribed_to = :subscribed_to;
SQL;
            $params['subscribed_to'] = '["test"]';
        }

        $stmt = PDO::getInstance()->prepare($sql);
        $stmt->execute($params);
    }
}