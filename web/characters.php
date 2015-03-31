<?php


require '../config.php';
$sql = "
select character_id
, arenateam_id
from character_arenateams;
";
$stmt = $dbh->prepare();