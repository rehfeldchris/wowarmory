<?php

namespace WowArmory\Parser\Json;

class AuctionHouseDumpFileParser extends WowApiJsonParser
{
    protected $structureValidationRules = [
        ['realm.name', 's']
        , ['alliance.auctions.*.owner', 's']
        , ['horde.auctions.*.owner', 's']
        , ['neutral.auctions.*.owner', 's']
    ];

    function realm()
    {
        return $this->jsonObj->realm->name;
    }

    function auctionOwnerNames()
    {
        $o = $this->jsonObj;
        $names = [];
        foreach ($o->alliance->auctions as $auc) {
            $names[ $auc->owner ] = 1;
        }
        foreach ($o->horde->auctions as $auc) {
            $names[ $auc->owner ] = 1;
        }
        foreach ($o->neutral->auctions as $auc) {
            $names[ $auc->owner ] = 1;
        }

        return array_keys($names);
    }

    function valid()
    {
        return strlen($this->realm())
        && strlen($this->region())
        && $this->auctionOwnerNames();
    }

    function __toString()
    {
        return sprintf(
            "Auctions: realm:%s, region:%s, num names:%d"
            , $this->realm()
            , $this->region()
            , count($this->auctionOwnerNames())
        );
    }


}