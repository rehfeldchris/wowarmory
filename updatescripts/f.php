<?php

require '../config.php';

$sql1 = "
            select type
                 , id
                 , url
                 , url_payload
                 , additional_data
              from urls_to_fetch 
             where http_response_code = 200
               and type in ('character', 'guild', 'arenateam', 'filelist')
               and processed = 0
             limit 100
        ";


$sql2 = "
            select type
                 , id
                 , url
                 , url_payload
                 , additional_data
from urls_to_fetch u
where id not in (select url_id from reprocessed_url_ids  where url_id between ? and ? + 1000)
              and type = 'character'
              and http_response_code = 200
              and processed = 1
              and id between ? and ? + 1000
        ";

$st1 = $dbh->prepare($sql1);
$st1->execute();
$r1 = $st1->fetchall();
print_r($r1);

$GLOBALS['c'] = 200;
$st2 = $dbh->prepare($sql2);
$st2->execute([$GLOBALS['c'] * 1000, $GLOBALS['c'] * 1000, $GLOBALS['c'] * 1000, $GLOBALS['c'] * 1000]);
$r2 = $st2->fetchall();
print_r($r2);