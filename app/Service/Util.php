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
            156818 => 'Wrathion, the Black Emperor',
            156523 => 'Maut',
            161901 => 'The Prophet Skitra',
            160229 => 'Dark Inquisitor Xanesh',
            157253 => 'The Hivemind',
            157231 => 'Shad\'har the Insatiable',
            157602 => 'Drest\'agath',
            158328 => 'Il\'gynoth, Corruption Reborn',
            157354 => 'Vexiona',
            156866 => 'Ra-den the Despoiled',
            157439 => 'Carapace of N\'Zoth',
            158041 => 'N\'Zoth the Corruptor',
        ];

        return $bossIds[$bossId] ?? '';
    }
}