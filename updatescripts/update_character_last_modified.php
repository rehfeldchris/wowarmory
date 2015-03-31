<?php

require '../config.php';


$sql = "
select url_payload
from urls_to_fetch
where url = ?

";

$getTsStmt = $dbh->prepare($sql);

$getTs = function ($url) use ($getTsStmt) {
    $getTsStmt->execute([$url]);
    $row = $getTsStmt->fetch();
    if (!$row) {
        return;
    }
    extract($row);
    $data = json_decode($url_payload);
    if (empty($data->lastModified)) {
        die(444);
    }

    return $data->lastModified;
};


$sql = "
select name, realm_name, region, id
from denormalized_characters
where last_modified_api_ts is null
limit 1000
";

$stmt = $dbh->prepare($sql);


$sql = "
update characters
set last_modified_api_ts = ?
where id = ?
";

$updateStmt = $dbh->prepare($sql);


while (1) {
    $stmt->execute();
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        extract($row);
        $url = WowArmory\UrlBuilder::characterUrl($region, $realm_name, $name);
        echo "$url\n";
        $ts = $getTs($url);
        echo "  $ts\n\n";
        if ($ts) {
            $bool = $updateStmt->execute([$ts, $id]);
        }
    }
    break;
}
//print_r($rows);