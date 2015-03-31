<?php
namespace WowArmory\Service;

use WowArmory\Parser\Json\ArenaTeamParser;
use WowArmory\Parser\Json\AuctionHouseDumpFileListParser;
use WowArmory\Parser\Json\AuctionHouseDumpFileParser;
use WowArmory\Parser\Json\CharacterParser;
use WowArmory\Parser\Json\GuildParser;
use WowArmory\Processor\ArenaTeamProcessor;
use WowArmory\Processor\AuctionHouseDumpFileListProcessor;
use WowArmory\Processor\AuctionHouseDumpFileProcessor;
use WowArmory\Processor\CharacterProcessor;
use WowArmory\Processor\GuildProcessor;
use WowArmory\Throttler\Throttler;

class JsonProcessingService implements Service
{
    protected $dbh;
    protected $getUrlsStmt, $markUrlProcessedStmt;
    protected $characterProcessor, $guildProcessor, $arenaTeamProcessor, $auctionHouseDumpFileListProcessor, $auctionHouseDumpFileProcessor;


    function __construct(\PDO $dbh, Throttler $throttler)
    {
        $this->dbh = $dbh;
        $this->throttler = $throttler;
        $this->characterProcessor = new CharacterProcessor($this->dbh);
        $this->guildProcessor = new GuildProcessor($this->dbh);
        $this->arenaTeamProcessor = new ArenaTeamProcessor($this->dbh);
        $this->auctionHouseDumpFileListProcessor = new AuctionHouseDumpFileListProcessor($this->dbh);
        $this->auctionHouseDumpFileProcessor = new AuctionHouseDumpFileProcessor($this->dbh);


        $stmts = [];

        $sql = "
            -- we use a union to ensure that we get some of the 4 types below. theyre higher priority than character type
            (select type
                 , id
                 , url
                 , url_payload
                 , additional_data
              from urls_to_fetch 
             where http_response_code = 200
               and type in ('guild', 'arenateam', 'auclist', 'aucfile')
               and processed = 0
             limit 100)
             
             union

            (select type
                 , id
                 , url
                 , url_payload
                 , additional_data
              from urls_to_fetch 
             where http_response_code = 200
               and type in ('character')
               and processed = 0
             limit 100)
        ";
        $this->getUrlsStmt = $dbh->prepare($sql);

        $sql = "update urls_to_fetch set processed = 1 where id = :id";
        $this->markUrlProcessedStmt = $this->dbh->prepare($sql);

        $sql = "update urls_to_fetch set processed = -1 where id = :id";
        $this->markUrlFailedStmt = $this->dbh->prepare($sql);
    }

    public function run()
    {
        while (true) {
            foreach ($this->getUrlRows() as $row) {
                try {
                    $data = json_decode($row['additional_data'], true);
                    $this->process($row, $data['region']);
                    $this->markUrlProcessedStmt->execute(['id' => $row['id']]);
                } catch (\Exception $e) {
                    $this->markUrlFailedStmt->execute(['id' => $row['id']]);
                    printf("failure, row id=%d", $row['id']);
                    error_log(sprintf("failure, row id=%d", $row['id']));
                    error_log($e->getMessage());
                    printf("failure, row id=%d", $row['id']);
                    echo $e->getMessage();
                }
                echo $this->rowProcessed($row) . "\n";
            }

            $this->throttler->sleep();
        }
    }


    protected function process($row, $region)
    {
        $data = json_decode($row['additional_data'], true);
        $region = $data['region'];
        switch ($row['type']) {
            case 'character':
                $cparser = new CharacterParser($row['url_payload'], $region);
                $this->characterProcessor->process($cparser);
                break;
            case 'guild':
                $gparser = new GuildParser($row['url_payload'], $region);
                $this->guildProcessor->process($gparser);
                break;
            case 'arenateam':
                $aparser = new ArenaTeamParser($row['url_payload'], $region);
                $this->arenaTeamProcessor->process($aparser);
                break;
            case 'auclist':
                $adflparser = new AuctionHouseDumpFileListParser($row['url_payload'], $region, $data['realm']);
                $this->auctionHouseDumpFileListProcessor->process($adflparser);
                break;
            case 'aucfile':
                $adfparser = new AuctionHouseDumpFileParser($row['url_payload'], $region);
                $this->auctionHouseDumpFileProcessor->process($adfparser);
                break;
            default:
                throw new Exception("unexpected type '$row[type]'");
        }

    }


    protected function rowProcessed($row)
    {
        return "$row[type] $row[url]";
    }

    protected function getUrlRows()
    {
        $this->getUrlsStmt->execute();

        return $this->getUrlsStmt->fetchAll();
    }

    protected function markUrlProcessed($rowId)
    {
        $this->getUrlsStmt->execute(['id' => $rowId]);
    }
}