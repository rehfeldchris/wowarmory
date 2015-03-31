<?php

/**
 * Parses the json text for an achievement list,
 * providing the list of achievements that characters may obtain
 */

namespace WowArmory\Parser\Json;

class AchievementListParser extends WowApiJsonParser
{

    /**
     * Provides an array of arrays. Each subarray represents the info for a single achievement.
     *
     * @return array of arrays
     */
    function data()
    {
        $arr = json_decode($this->jsonText, true);
        $ritit = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($arr), \RecursiveIteratorIterator::SELF_FIRST);
        $r = [];
        foreach ($ritit as $k => $v) {
            if (isset($v['points'])) {
                extract($v);
                $r[] = [
                    'id'             => $id
                    , 'title'        => $title
                    , 'account_wide' => $accountWide
                    , 'faction_id'   => $factionId
                ];
            }
        }

        return $r;
    }

    /**
     * Checks if data extraction seems succesful
     *
     * @return boolean
     */
    function valid()
    {
        try {
            return (bool)$this->data();
        } catch (\Exception $e) {
            return false;
        }
    }

    function __toString()
    {
        return sprintf(
            "Achievements:  ids:%s"
            , join(',', array_map(function ($r) {
                return $r['id'];
            }, $this->data()))
        );
    }

}