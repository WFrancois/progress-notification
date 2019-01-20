<?php

namespace ProgressNotification\Service;


class Util
{
    const REGION = [
        'world' => 'World',
        'us' => 'US',
        'eu' => 'EU',
        'kr' => 'KR',
        'tw' => 'TW',
    ];

    public static function getOrdinal(int $number): string
    {
        $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        }
        return $number . $ends[$number % 10];
    }

    public static function getBossName(int $bossId): string
    {
        $bossIds = [
            144680 => 'Champion of the Light',
            148117 => 'Grong',
            148238 => 'Jadefire Masters',
            147564 => 'Opulence',
            144747 => 'Conclave of the Chosen',
            145616 => 'King Rastakhan',
            144838 => 'High Tinker Mekkatorque',
            146256 => 'Stormwall Blockade',
            149684 => 'Lady Jaina Proudmoore',
        ];

        return $bossIds[$bossId] ?? '';
    }
}