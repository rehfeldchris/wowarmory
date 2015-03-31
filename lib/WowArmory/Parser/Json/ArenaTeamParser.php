<?php

/**
 * Parses the json text for an arenateam,
 * providing the list of team members.
 */

namespace WowArmory\Parser\Json;

class ArenaTeamParser extends WowApiJsonParser
{
    /**
     * @see SimpleArrayStructureValidator for rule format
     * @var array
     */
    protected $structureValidationRules = [
        ['name', 's']
        , ['realm', 's']
        , ['teamsize', 'i']
        , ['members', 'a']
        , //['members.*.character.name'] // sometimes blizzard api would would  return json text with a numeric member array entry, ...but no character...skip strict validation
    ];

    /**
     * The arenateam name.
     *
     * @return string
     */
    function name()
    {
        return $this->jsonObj->name;
    }

    function realm()
    {
        return $this->jsonObj->realm;
    }

    function teamSize()
    {
        return $this->jsonObj->teamsize;
    }

    function teamSizeString()
    {
        return $this->teamSize() . 'v' . $this->teamSize();
    }


    function memberNames()
    {
        $names = [];
        foreach ($this->jsonObj->members as $member) {
            // sometimes the character entry is just missing, test using isset
            if (isset($member->character->name)) {
                $names[] = $member->character->name;
            }
        }

        return $names;
    }

    function valid()
    {
        return strlen($this->name())
        && strlen($this->realm())
        && in_array($this->teamsize(), [2, 3, 5]);
    }

    function isValidTeamSizeString($s)
    {
        return $this->teamSize() . 'v' . $this->teamSize();
    }

    function __toString()
    {
        return sprintf(
            "%s: name:%s, realm:%s, teamsize:%s, members:%s"
            , __CLASS__
            , $this->name()
            , $this->realm()
            , $this->teamsize()
            , join(',', $this->memberNames())
        );
    }


}