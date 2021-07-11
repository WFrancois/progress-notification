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
            179390 => "Fatescribe Roh-Kalo",
            175731 => "Guardian of the First Ones",
            15990 => "Kel'Thuzad",
            176523 => "Painsmith Raznal",
            175729 => "Remnant of Ner'zhul",
            175727 => "Soulrender Dormazain",
            179687 => "Sylvanas Windrunner",
            180018 => "The Eye of the Jailer",
            178738 => "The Nine",
            152253 => "The Tarragrue",
        ];

        return $bossIds[$bossId] ?? '';
    }
}
