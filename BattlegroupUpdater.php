<?php

/**
 * Updates the "battlegroups" db table to contain the list of battlegroups in the supplied parser.
 *
 */
class BattlegroupUpdater
{
    protected $battlegroupParser, $dbh, $region;

    /**
     *
     * @param BattlegroupParser $rp     the battlegroup parser which will supply the list of battlegroups.
     * @param PDO               $pdo    the db handle
     * @param type              $region The blizzard game server region. Eg "us" or "eu" etc...
     * @throws RuntimeException if the parser is invalid
     */
    function __construct(BattlegroupParser $rp, PDO $pdo, $region)
    {
        $this->battlegroupParser = $rp;
        $this->dbh = $pdo;
        $this->region = $region;
        $rp->battlegroups();
        if (!$rp->valid()) {
            throw new RuntimeException("invalid battlegrooup parser");
        }
    }


    /**
     * Updates The database table "battlegroups" to contain the data in the BattlegroupParser.
     *
     * @throws \PDOException if a db error occurs.
     * @return void
     */
    function update()
    {
        try {
            $this->dbh->beginTransaction();
            $sql = "call add_or_update_battlegroup(:name, :region)";
            $stmt = $this->dbh->prepare($sql);
            foreach ($this->battlegroupParser->battlegroups() as $bg) {
                $stmt->execute($bg + ['region' => $this->region]);
            }
            $this->dbh->commit();
        } catch (PDOException $e) {
            $this->dbh->rollback();
            throw $e;
        }
    }


}

