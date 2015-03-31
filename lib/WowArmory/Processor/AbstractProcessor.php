<?php

/**
 * Base class for the various types of json processors. These processors take
 * a parser, and query the parser for data. Then, they update the db with the data.
 *
 * They mostly add new db records, but may update some tables,
 * in addition to marking the task as completed if its successful.
 *
 */

namespace WowArmory\Processor;

use WowArmory\Parser\Parser;

abstract class AbstractProcessor
{
    //pdo db handle
    protected $dbh;

    //list of prepared statements
    protected $pdoStmts = [];

    /**
     * Initializes the object. This will use the db handle to prepare some sql statements.
     *
     * @param \PDO $dbh the database handle to be used to perform updates
     */
    public function __construct(\PDO $dbh)
    {
        $this->dbh = $dbh;
        $this->init();
    }


    /**
     * Prepares some sql statements which are likely to be used by many specific subclasses.
     */
    protected function init()
    {
        $sql = "CALL add_url_to_crawl_queue(:url, :additional_data, :type)";
        $this->pdoStmts['addUrlToCrawlQueue'] = $this->dbh->prepare($sql);

        $sql = "CALL add_or_update_url_to_crawl_queue(:url, :additional_data, :type)";
        $this->pdoStmts['addOrUpdateUrlToCrawlQueue'] = $this->dbh->prepare($sql);

        $sql = "CALL add_or_update_character(:name, :realm, :region, :guild, :level, :gender, :class, :race)";
        $this->pdoStmts['addCharacter'] = $this->dbh->prepare($sql);

        $sql = "CALL add_or_update_arenateam(:name, :realm, :match_size, :region)";
        $this->pdoStmts['addArenaTeam'] = $this->dbh->prepare($sql);

        $sql = "CALL add_or_update_guild(:name, :realm, :region)";
        $this->pdoStmts['addGuild'] = $this->dbh->prepare($sql);
    }

    /**
     * Adds a url to crawl queue table, so that the crawler will crawl it sometime in the future.
     * Does nothing if the url already exists in the db table.
     *
     * @param type $url             A full url to fetch.
     * @param type $type            The type of payload the url is expected to provide.
     *                              This will be used later to decide what type of parser and
     *                              processor needs to be used to handle the response.
     * @param type $additional_data Any additional data that needs to be
     *                              bundled with the url. Parsers and processors may need info not
     *                              available in the payload, and this is a way to provide it to them.
     *
     * Must be in json format.
     */
    protected function addUrlToCrawlQueue($url, $type, $additional_data)
    {
        $this->pdoStmts['addUrlToCrawlQueue']->execute(compact('url', 'additional_data', 'type'));
    }

    /**
     * Same as addUrlToCrawlQueue() but in the event the url already exists
     * in the table, the data will be updated to the new data provided.
     *
     */
    protected function addOrUpdateUrlToCrawlQueue($url, $type, $additional_data)
    {
        $this->pdoStmts['addOrUpdateUrlToCrawlQueue']->execute(compact('url', 'additional_data', 'type'));
    }

    /**
     * Deletes a record from the crawl_queue.
     *
     * @param type $id the id field for this record, in the url_crawl_queue table
     */
    protected function deleteUrlFromCrawlQueue($id)
    {
        $this->pdoStmts['deleteUrlFromCrawlQueue']->execute(compact('id'));
    }

    /**
     * Extracts data from the supplied parser, and updates the db as neccesary.
     *
     * @param \WowArmory\Parser\Parser $parser the parser which will supply the needed info
     * @return type boolean
     * @throws \RuntimeException if the parser is invalid
     * @throws \WowArmory\Processor\Exception
     */
    public function process(Parser $parser)
    {
        if (!$parser->valid()) {
            throw new \RuntimeException("invalid parser '$parser'");
        }

        $this->dbh->beginTransaction();
        try {
            $ret = $this->addToDb($parser);
            $this->dbh->commit();
        } catch (\PDOException $e) {
            $this->dbh->rollback();
            throw $e;
        }

        return $ret;
    }

    /**
     * Child classes must implement this method.
     * A call to this method should fully execute all processing work.
     */
    abstract protected function addToDb(Parser $parser);
}