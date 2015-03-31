<?php



require 'config.php';

$service = new WowArmory\Service\UrlFetchingService($dbh);
$service->run(); 