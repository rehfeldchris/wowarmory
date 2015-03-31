<?php

namespace WowArmory\Task;

use WowArmory\Task\CallbackPoweredUrlTask;
use WowArmory\Task\UrlTaskProvider;

class CallbackPoweredUrlTaskProvider implements UrlTaskProvider
{
    protected $updateUrlStmt, $checkoutUrlStmt, $getNextUrlFunc, $dbh;

    function __construct(\PDO $dbh, callable $getNextUrlFunc)
    {
        $this->getNextUrlFunc = $getNextUrlFunc;
        $this->dbh = $dbh;

        $sql = "
		update urls_to_fetch
		 set last_attempt = now()
		where url = :url
		 and last_attempt = :last_attempt
		";
        $this->checkoutUrlStmt = $dbh->prepare($sql);

        $sql = "
		update urls_to_fetch
		 set url_payload = :url_payload
		   , http_response_code = :http_response_code
		   , last_attempt = now()
		where url = :url
		";
        $this->updateUrlStmt = $dbh->prepare($sql);
    }


    public function update($url, $url_payload, $http_response_code)
    {
        $this->updateUrlStmt->execute(compact('url', 'url_payload', 'http_response_code'));
    }

    protected function checkoutUrl()
    {
        $row = $this->getUrlRow();
        if ($row) {
            extract($row);
            $this->checkoutUrlStmt->execute(compact('url', 'last_attempt'));
            if ($this->checkoutUrlStmt->rowCount() > 0) {
                return $url;
            }
        }

        return null;
    }

    protected function getUrlRow()
    {
        return call_user_func($this->getNextUrlFunc);
    }

    public function getNextUrlTask()
    {
        $url = $this->checkoutUrl();
        if ($url) {
            $callback = [$this, 'update'];

            return new CallbackPoweredUrlTask($url, $callback);
        }

        return null;
    }
}