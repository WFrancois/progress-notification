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
            125075 => 'Varimathras',
            124691 => 'Aggramar',
            124393 => 'Portal Keeper Hasabel',
            122468 => 'The Coven of Shivarra',
            126916 => 'Felhounds of Sargeras',
            123371 => 'Garothi Worldbreaker',
            122367 => 'Antoran High Command',
            125050 => 'Kin\'garoth',
            125055 => 'Imonar the Soulhunter',
            125562 => 'Eonar the Life-Binder',
            124828 => 'Argus the Unmaker',
        ];

        return $bossIds[$bossId] ?? '';
    }
}