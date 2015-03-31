<?php

require 'config.php';
/*
JsonProcessingService grabs 100 records at a time. it normally takes much longer than 10 seconds to process them all,
so 10s between each query will usually only happen if theres little or nothing to process. normally it will take much longer.
*/
$service = new WowArmory\Service\JsonProcessingService(
    $dbh
    , new WowArmory\Throttler\TimeIntervalThrottler(10)
);
$service->run();