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
            151881 => 'Abyssal Commander Sivara',
            150653 => 'Blackwater Behemoth',
            150859 => 'Za\'qul',
            152128 => 'Orgozoa',
            152364 => 'Radiance of Azshara',
            153142 => 'Lady Ashvane',
            152853 => 'The Queen\'s Court',
            152910 => 'Queen Azshara',
        ];

        return $bossIds[$bossId] ?? '';
    }
}