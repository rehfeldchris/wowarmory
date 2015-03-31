<?php

namespace WowArmory\Processor;

use WowArmory\Parser\Json\CharacterParser;
use WowArmory\Parser\Parser;
use WowArmory\UrlBuilder;

class CharacterProcessor extends AbstractProcessor
{

    protected function init()
    {
        parent::init();
        $sql = "CALL add_or_update_character(:name, :realm, :region, :guild, :level, :gender, :class, :race, :last_modified_api_timestamp)";
        $this->pdoStmts['addCharacter'] = $this->dbh->prepare($sql);

        $sql = "CALL add_or_update_arenateam(:name, :realm, :match_size, :region)";
        $this->pdoStmts['addArenaTeam'] = $this->dbh->prepare($sql);

        $sql = "CALL add_or_update_guild(:name, :realm, :region)";
        $this->pdoStmts['addGuild'] = $this->dbh->prepare($sql);

        $sql = "CALL add_character_achievement(:name, :realm, :region, :achievement_id, :achievement_completed_timestamp)";
        $this->pdoStmts['addAchievement'] = $this->dbh->prepare($sql);
    }


    protected function addToDb(Parser $parser)
    {
        $this->addCharacter($parser);
        if ($parser->hasGuild()) {
            $this->addGuild($parser);
            $additional_data = json_encode([
                'region'  => $parser->region()
                , 'realm' => $parser->realm()
                , 'name'  => $parser->guild()
            ]);
            $this->addUrlToCrawlQueue(
                UrlBuilder::guildUrl($parser->region(), $parser->realm(), $parser->guild())
                , 'guild'
                , $additional_data
            )
            ;
        }
        $this->recordArenaTeams($parser);
        $this->recordAchievements($parser);

        return true;
    }

    protected function addCharacter(CharacterParser $p)
    {
        $this->pdoStmts['addCharacter']->execute([
            'name'                          => $p->name()
            , 'realm'                       => $p->realm()
            , 'region'                      => $p->region()
            , 'guild'                       => $p->guild()
            , 'level'                       => $p->level()
            , 'gender'                      => $p->gender()
            , 'class'                       => $p->characterClassId()
            , 'race'                        => $p->race()
            , 'last_modified_api_timestamp' => $p->lastModifiedApiTimestamp()
        ])
        ;
    }

    protected function addGuild(CharacterParser $p)
    {
        $this->pdoStmts['addGuild']->execute([
            'name'     => $p->guild()
            , 'realm'  => $p->realm()
            , 'region' => $p->region()
        ])
        ;
    }

    protected function addArenaTeam($name, $realm, $region, $match_size)
    {
        $this->pdoStmts['addArenaTeam']->execute(compact('name', 'realm', 'region', 'match_size'));
    }

    protected function recordArenaTeams(CharacterParser $parser)
    {
        foreach ($parser->arenaTeams() as $team) {
            $this->addArenaTeam($team['name'], $parser->realm(), $parser->region(), $team['match_size']);
            $additional_data = json_encode([
                'region'       => $parser->region()
                , 'realm'      => $parser->realm()
                , 'name'       => $team['name']
                , 'match_size' => $team['match_size']
            ]);
            $this->addUrlToCrawlQueue(
                UrlBuilder::arenateamUrl($parser->region(), $parser->realm(), $team['name'], $team['match_size']),
                'arenateam',
                $additional_data
            )
            ;
        }
    }

    protected function recordAchievements(CharacterParser $p)
    {
        $s = microtime(true);

        foreach ($p->timestampedAchievements() as $k => $row) {
            $this->pdoStmts['addAchievement']->execute([
                'name'                              => $p->name()
                , 'realm'                           => $p->realm()
                , 'region'                          => $p->region()
                , 'achievement_id'                  => $row['achievement_id']
                , 'achievement_completed_timestamp' => $row['achievement_completed_timestamp']
            ])
            ;
        }

        printf("%.4f ", microtime(true) - $s);
    }

}