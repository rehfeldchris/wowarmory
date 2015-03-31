<?php

namespace WowArmory;

class UrlBuilder
{
    static function characterUrl($region, $realm, $name)
    {
        return sprintf(
            "http://%s.battle.net/api/wow/character/%s/%s?fields=guild,pvp,achievements,titles",
            rawurlencode($region),
            rawurlencode($realm),
            rawurlencode($name)
        );
    }

    static function guildUrl($region, $realm, $name)
    {
        return sprintf(
            "http://%s.battle.net/api/wow/guild/%s/%s?fields=members",
            rawurlencode($region),
            rawurlencode($realm),
            rawurlencode($name)
        );
    }

    static function arenateamUrl($region, $realm, $name, $teamsize)
    {
        return sprintf(
            "http://%s.battle.net/api/wow/arena/%s/%s/%s",
            rawurlencode($region),
            rawurlencode($realm),
            rawurlencode($teamsize . 'v' . $teamsize),
            rawurlencode($name)
        );
    }

    static function realmListUrl($region)
    {
        return sprintf(
            "http://%s.battle.net/api/wow/realm/status",
            rawurlencode($region)
        );
    }

    static function battlegroupListUrl($region)
    {
        return sprintf(
            "http://%s.battle.net/api/wow/data/battlegroups/",
            rawurlencode($region)
        );
    }

    //not sure region matters here
    static function achievementsListUrl($region)
    {
        return sprintf(
            "http://%s.battle.net/api/wow/data/character/achievements",
            rawurlencode($region)
        );
    }

    static function auctionHouseDumpFileListUrl($region, $realm)
    {
        return sprintf(
            "http://%s.battle.net/api/wow/auction/data/%s",
            rawurlencode($region),
            rawurlencode($realm)
        );
    }
}