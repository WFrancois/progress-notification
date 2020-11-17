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
            172145 => 'Shriekwing',
            165066 => 'Huntsman Altimor',
            164261 => 'Hungering Destroyer',
            166644 => 'Artificer Xy\'Mox',
            24664 => 'Sun King\'s Salvation',
            167517 => 'Lady Inerva Darkvein',
            166971 => 'The Council of Blood',
            174733 => 'Sludgefist',
            165318 => 'Stone Legion Generals',
            168938 => 'Sire Denathrius',
        ];

        return $bossIds[$bossId] ?? '';
    }
}
