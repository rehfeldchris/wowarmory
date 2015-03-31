<?php

namespace WowArmory\Parser\Json;


class BattleGroupParser extends WowApiJsonProcessor
{
    protected $structureValidationRules = [
        ['battlegroups.*.name', 's']
    ];

    function battlegroups()
    {
        $bgs = [];
        foreach ($this->jsonObj->battlegroups as $bg) {
            $bgs[] = ['name' => $bg->name];
        }

        return $bgs;
    }


    function valid()
    {
        return (bool)$this->battlegroups();
    }

    function __toString()
    {
        return sprintf(
            "%s:  names:%s"
            , __CLASS__
            , join(',', $this->battlegroups())
        );
    }

}