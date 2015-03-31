<?php

/**
 * Updates a db table to those provided in the AchievementListParser.
 * This should be the latest definitions of known achievements, as fetched from the blizzard api.
 * It may add new achievements, and it may also update some old ones.
 * It will not delete any existing achievements.
 */


use WowArmory\Parser\Json\AchievementListParser;


class AchievementListUpdater
{
    protected $achievementListParser;

    protected $dbh;

    protected $region;

    /**
     * Initializes the class. No work performed.
     *
     * @param \WowArmory\Parser\Json\AchievementListParser $rp
     * @param \PDO                                         $pdo    a database handle
     * @param type                                         $region The blizzard game server region. Eg "us" or "eu"
     *                                                             etc...
     * @throws \RuntimeException if an invalid AchievementListParser was supplied
     */
    function __construct(AchievementListParser $rp, \PDO $pdo, $region)
    {
        $this->achievementListParser = $rp;
        $this->dbh = $pdo;
        $this->region = $region;
        $rp->data();
        if (!$rp->valid()) {
            throw new \RuntimeException("invalid Acheivement list parser");
        }
    }

    /**
     * Updates The database table "achievements" to contain the data in the AchievementListParser.
     *
     * @throws \PDOException if a db error occurs.
     * @return void
     */
    function update()
    {
        try {
            $this->dbh->beginTransaction();
            $sql = "insert ignore into achievements (id, title, account_wide, faction_id, last_update) values (?, ?, ?, ?, now())
            on duplicate key update achievements.title = ?, achievements.account_wide = ?, achievements.faction_id = ?, achievements.last_update = now()";
            $stmt = $this->dbh->prepare($sql);
            foreach ($this->achievementListParser->data() as $achievement) {
                extract($achievement);
                $stmt->execute([$id, $title, $account_wide, $faction_id, $title, $account_wide, $faction_id]);
            }
            $this->dbh->commit();
        } catch (\PDOException $e) {
            $this->dbh->rollback();
            throw $e;
        }
    }
}

