<?php

namespace WowArmory\Task;

use WowArmory\Task\CallbackPoweredUrlTaskProvider;

class UrlTaskProviderFactory
{
    function __construct(\PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    function create(callable $getNextUrlFunc)
    {
        return new CallbackPoweredUrlTaskProvider($this->dbh, $getNextUrlFunc);
    }


}