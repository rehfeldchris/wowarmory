<?php

namespace WowArmory\Task;


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