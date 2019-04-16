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
            146497 => 'The Restless Cabal',
            145371 => 'Uu\'nat, Harbinger of the Void',
        ];

        return $bossIds[$bossId] ?? '';
    }
}