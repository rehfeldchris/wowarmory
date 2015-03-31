<?php


require 'config.php';

if ($_SERVER['argc'] !== 2) {
    echo "too many args. just give 1.\n";
    exit(1);
}

$realm = $_SERVER['argv'][1];
$region = 'us';


$sql = "
select 1 from realms where region = ? and name = ? 
";
$checkRealmStmt = $dbh->prepare($sql);

$checkRealmStmt->execute([$region, $realm]);
if ($checkRealmStmt->rowCount() !== 1) {
    echo "unknown realm name '$realm'\n";
    exit(1);
}


$sql = "
call add_url_to_crawl_queue(?, ?, 'auclist')
";
$url = WowArmory\UrlBuilder::auctionHouseDumpFileListUrl($region, $realm);

$stmt = $dbh->prepare($sql);
$stmt->execute([$url, json_encode(compact('region', 'realm'))]);

echo "success\n";