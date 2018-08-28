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
            133298 => 'Fetid Devourer',
            132998 => 'G\'huun',
            140853 => 'MOTHER',
            136383 => 'Mythrax the Unraveler',
            137119 => 'Taloc',
            134442 => 'Vectis',
            134445 => 'Zek\'voz, Herald of N\'zoth',
            138967 => 'Zul, Reborn',
        ];

        return $bossIds[$bossId] ?? '';
    }
}