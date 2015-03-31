<?php

namespace WowArmory\Parser\Json;


class RealmListParser extends WowApiJsonProcessor
{
    protected $structureValidationRules = [
        ['realms.*.type', 's']
        , ['realms.*.name', 's']
        , ['realms.*.battlegroup', 's']
    ];

    function realms()
    {
        $realms = [];
        foreach ($this->jsonObj->realms as $realm) {
            $realms[] = [
                'play_type'     => $realm->type
                , 'name'        => $realm->name
                , 'battlegroup' => $realm->battlegroup
            ];
        }

        return $realms;
    }

    function valid()
    {
        return (bool)$this->realms();
    }

    function __toString()
    {
        return sprintf(
            "Realms  names:%s"
            , join(',', array_map(function ($r) {
                return vsprintf('{%s,%s,%s} ', $r);
            }, $this->realms()))
        );
    }

}