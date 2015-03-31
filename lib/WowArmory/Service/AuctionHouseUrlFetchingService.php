<?php
namespace WowArmory\Service;

use WowArmory\Task\UrlTaskProviderFactory;
use WowArmory\ThrottledUrlFetcher;
use WowArmory\Throttler\TimeIntervalThrottler;

class AuctionHouseUrlFetchingService implements Service
{
    protected $dbh;

    function __construct(\PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    function run()
    {
        $secondsBetweenRequests = 86400 / 3000;
        $secondsBetweenRequests = 2;

        $sql = "
		select url
		    , last_attempt
		 from urls_to_fetch
		where (http_response_code is null or http_response_code = 200)
          and type='auclist'
		  and (now() - interval 1 day > last_attempt)
		limit 1
		";
        $pdoStmt = $this->dbh->prepare($sql);

        $callback = function () use ($pdoStmt) {
            $pdoStmt->execute();

            return $pdoStmt->fetch();
        };

        $factory = new UrlTaskProviderFactory($this->dbh);
        $taskProvider = $factory->create($callback);

        $fetcher = new ThrottledUrlFetcher(
            $taskProvider,
            new TimeIntervalThrottler($secondsBetweenRequests)
        );

        $fetcher->run();
    }
}