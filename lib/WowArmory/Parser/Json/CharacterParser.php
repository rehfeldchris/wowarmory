<?php

namespace WowArmory\Parser\Json;

class CharacterParser extends WowApiJsonParser
{
    protected $structureValidationRules = [
        ['name', 's']
        //, ['guild', 'a']//propertety doesnt exist if no guild
        , ['level', 'i']
        , ['race', 'i']
        , ['class', 'i']
        , ['realm', 's']
        , ['lastModified', 'f']
        , ['gender', 'i']
        //, ['pvp.arenaTeams.*.name', 's'] // sometimes pvp is missing even though we requested it in url...wtf blizz...
        //, ['pvp.arenaTeams.*.size', 's']
        , ['titles.*.name', 's']
        , ['titles.*.id', 'i']
        , ['achievements', 'a']
    ];


    function name()
    {
        return $this->jsonObj->name;
    }

    function guild()
    {
        return isset($this->jsonObj->guild->name) ? $this->jsonObj->guild->name : null;
    }

    function hasGuild()
    {
        return strlen($this->guild()) > 0;
    }


    function level()
    {
        return $this->jsonObj->level;
    }


    function race()
    {
        return $this->jsonObj->race;
    }


    function characterClassId()
    {
        return $this->jsonObj->class;
    }


    function realm()
    {
        return $this->jsonObj->realm;
    }


    function battleGroup()
    {
        return $this->jsonObj->battlegroup;
    }


    function gender()
    {
        return $this->jsonObj->gender ? 'Female' : 'Male';
    }

    function lastModifiedApiTimestamp()
    {
        return $this->jsonObj->lastModified;
    }


    function arenaTeams()
    {
        if (empty($this->jsonObj->pvp->arenaTeams) || !is_array($this->jsonObj->pvp->arenaTeams)) {
            return [];
        }
        $sizeMap = ['2v2' => 2, '3v3' => 3, '5v5' => 5];
        $teams = [];
        foreach ($this->jsonObj->pvp->arenaTeams as $t) {
            if (!isset($t->size)) {
                throw new RuntimeException("arena team size missing");
            }
            if (!isset($t->name)) {
                throw new RuntimeException("arena team name missing");
            }
            if (!isset($sizeMap[ $t->size ])) {
                throw new RuntimeException("unknown arena team size '{$t->size}'");
            }
            $teams[] = ['name' => $t->name, 'match_size' => $sizeMap[ $t->size ]];
        }

        return $teams;
    }

    function timestampedAchievements()
    {
        $o = $this->jsonObj;
        if (!isset($this->jsonObj->achievements->achievementsCompleted) || !isset($this->jsonObj->achievements->achievementsCompletedTimestamp)) {
            return [];
        }
        $ac = $this->jsonObj->achievements->achievementsCompleted;
        $act = $this->jsonObj->achievements->achievementsCompletedTimestamp;
        if (!is_array($ac) || !is_array($act)) {
            throw new RuntimeException("achievements have unknown form");
        }
        if (count($ac) !== count($act)) {
            throw new RuntimeException("achievements have unequal length");
        }

        $res = [];
        foreach ($ac as $k => $v) {
            $res[] = ['achievement_id' => $v, 'achievement_completed_timestamp' => $act[ $k ]];
        }

        return $res;
    }

    function titles()
    {
        $titles = [];
        foreach ($this->jsonObj->titles as $t) {
            $titles[] = ['name' => $t['name'], 'id' => $t['id']];
        }

        return $titles;
    }


    function valid()
    {
        return strlen($this->name())
        && strlen($this->realm())
        && strlen($this->battleGroup())
        && strlen($this->characterClassId())
        && strlen($this->race())
        && strlen($this->region())
        && strlen($this->level());
    }

    function __toString()
    {
        return sprintf(
            "Character: name:%s, realm:%s, region:%s, battlegroup:%s, characterClass:%s, race:%s, level:%s, guild: %s"
            , $this->name()
            , $this->realm()
            , $this->region()
            , $this->battleGroup()
            , $this->characterClassId()
            , $this->race()
            , $this->level()
            , $this->guild()

        );
    }


}