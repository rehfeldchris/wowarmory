<?php

namespace WowArmory\Parser\Json;

class GuildParser extends WowApiJsonParser
{
    protected $structureValidationRules = [
        ['name', 's']
        , ['realm', 's']
        , ['side', 'i']
        , ['members.*.character.name', 's']
    ];

    function name()
    {
        return $this->jsonObj->name;
    }

    function realm()
    {
        return $this->jsonObj->realm;
    }


    function faction()
    {
        return $this->jsonObj->side;
    }

    function memberNames()
    {
        $members = [];
        foreach ($this->jsonObj->members as $member) {
            $members[] = $member->character->name;
        }

        return $members;
    }

    function valid()
    {
        return $this->memberNames()
        && strlen($this->name())
        && strlen($this->faction());
    }

    function  __toString()
    {
        return sprintf(
            "%s name:%s, faction:%s, members:(#%d){%s}",
            __CLASS__,
            $this->name(),
            $this->faction(),
            count($this->memberNames()),
            join(',', $this->memberNames())
        );
    }


}
