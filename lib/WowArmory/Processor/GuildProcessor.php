<?php

namespace WowArmory\Processor;

use WowArmory\Parser\Json\GuildParser;
use WowArmory\Parser\Parser;
use WowArmory\UrlBuilder;

class GuildProcessor extends AbstractProcessor
{

    protected function init()
    {
        parent::init();

        $sql = "CALL add_or_update_guild(:name, :realm, :region)";
        $this->pdoStmts['addGuild'] = $this->dbh->prepare($sql);

        $sql = "CALL set_character_guild(:character_name, :realm, :region, :guild_name)";
        $this->pdoStmts['setCharacterGuild'] = $this->dbh->prepare($sql);

        $sql = "CALL unguild_current_guild_members(:name, :realm, :region)";
        $this->pdoStmts['unguildCurrentGuildMembers'] = $this->dbh->prepare($sql);
    }


    protected function addToDb(Parser $parser)
    {
        $this->addGuild($parser);
        $this->unGuildCurrentGuildMembers($parser);
        $this->recordGuildMembers($parser);

        return true;
    }

    protected function addGuild(GuildParser $parser)
    {
        $this->pdoStmts['addGuild']->execute([
            'name'   => $parser->name(),
            'realm'  => $parser->realm(),
            'region' => $parser->region(),
        ])
        ;
    }

    protected function setCharacterGuild(GuildParser $parser, $character_name)
    {
        $this->pdoStmts['setCharacterGuild']->execute([
            'guild_name'     => $parser->name(),
            'realm'          => $parser->realm(),
            'region'         => $parser->region(),
            'character_name' => $character_name
        ])
        ;
    }

    protected function unGuildCurrentGuildMembers(GuildParser $parser)
    {
        $this->pdoStmts['unguildCurrentGuildMembers']->execute([
            'name'   => $parser->name(),
            'realm'  => $parser->realm(),
            'region' => $parser->region(),
        ])
        ;
    }

    protected function recordGuildMembers(GuildParser $parser)
    {
        foreach ($parser->memberNames() as $characterName) {
            //we dont explicitly create the new character here, this just updates their guild if they already exist.
            //after their url gets added to queue, and crawled, they will exist
            $this->setCharacterGuild($parser, $characterName);
            $additional_data = json_encode([
                'region'  => $parser->region()
                , 'realm' => $parser->realm()
                , 'name'  => $characterName
            ]);
            $this->addUrlToCrawlQueue(
                UrlBuilder::characterUrl($parser->region(), $parser->realm(), $characterName)
                , 'character'
                , $additional_data
            )
            ;
        }
    }

}