<?php

namespace WowArmory\Processor;

use WowArmory\Parser\Json\AuctionHouseDumpFileParser;
use WowArmory\Parser\Parser;
use WowArmory\UrlBuilder;

class AuctionHouseDumpFileProcessor extends AbstractProcessor
{

    protected function init()
    {
        parent::init();
    }


    protected function addToDb(Parser $parser)
    {
        $this->recordCharacterNames($parser);

        return true;
    }

    protected function recordCharacterNames(AuctionHouseDumpFileParser $p)
    {
        foreach ($p->auctionOwnerNames() as $name) {
            echo $name, "\n";
            $additional_data = json_encode([
                'region'  => $p->region()
                , 'realm' => $p->realm()
                , 'name'  => $name
            ]);
            $this->addUrlToCrawlQueue(
                UrlBuilder::characterUrl($p->region(), $p->realm(), $name),
                'character',
                $additional_data
            )
            ;
        }
    }

}