<?php
/**
 * Created by PhpStorm.
 * User: Francois
 * Date: 24/11/2017
 * Time: 22:03
 */

namespace ProgressNotification\Controller;


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
                    'new' => 8067,
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
                    'new' => 5338,
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
                    'world' => 5338,
                    'region' => 3106,
                    'realm' => 79,
                ),
            ),
        ];

        if($payload['difficulty'] !== 'mythic' || $payload['type'] !== 'guild_watch') {
            return;
        }


    }
}