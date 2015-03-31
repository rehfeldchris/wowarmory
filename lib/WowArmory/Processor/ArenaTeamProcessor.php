<?php

namespace WowArmory\Processor;

use WowArmory\Parser\Json\ArenaTeamParser;
use WowArmory\Parser\Parser;
use WowArmory\UrlBuilder;

class ArenaTeamProcessor extends AbstractProcessor
{

    protected function init()
    {
        parent::init();
        $sql = "CALL add_or_update_arenateam(:name, :realm, :match_size, :region)";
        $this->pdoStmts['addArenaTeam'] = $this->dbh->prepare($sql);

        $sql = "CALL unteam_current_arenateam_members(:name, :realm, :region)";
        $this->pdoStmts['unteamCurrentArenaTeamMembers'] = $this->dbh->prepare($sql);

        $sql = "CALL add_or_update_character_arenateam(:character_name, :realm, :region, :team_name, :match_size)";
        $this->pdoStmts['setCharacterArenaTeam'] = $this->dbh->prepare($sql);
    }


    protected function addToDb(Parser $parser)
    {
        $this->addArenaTeam($parser);
        $this->unteamCurrentArenaTeamMembers($parser);
        $this->recordArenaTeamMembers($parser);

        return true;
    }

    protected function addArenaTeam(ArenaTeamParser $p)
    {
        $this->pdoStmts['addArenaTeam']->execute([
            'name'         => $p->name()
            , 'realm'      => $p->realm()
            , 'match_size' => $p->teamSize()
            , 'region'     => $p->region()
        ])
        ;
    }

    protected function unteamCurrentArenaTeamMembers(Parser $p)
    {
        $this->pdoStmts['unteamCurrentArenaTeamMembers']->execute([
            'name'     => $p->name()
            , 'realm'  => $p->realm()
            , 'region' => $p->region()
        ])
        ;
    }

    protected function setCharacterArenaTeam(ArenaTeamParser $p, $character_name)
    {
        $this->pdoStmts['setCharacterArenaTeam']->execute([
            'team_name'        => $p->name()
            , 'realm'          => $p->realm()
            , 'region'         => $p->region()
            , 'character_name' => $character_name
            , 'match_size'     => $p->teamSize()
        ])
        ;
    }


    protected function recordArenaTeamMembers(ArenaTeamParser $p)
    {
        foreach ($p->memberNames() as $character_name) {
            $this->setCharacterArenaTeam($p, $character_name);
            $additional_data = json_encode([
                'region'  => $p->region()
                , 'realm' => $p->realm()
                , 'name'  => $character_name
            ]);
            $this->addUrlToCrawlQueue(
                UrlBuilder::characterUrl($p->region(), $p->realm(), $character_name),
                'character',
                $additional_data
            )
            ;
        }
    }

}