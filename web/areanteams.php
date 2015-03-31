<?php


require '../config.php';
$sql = "
select id
, name
from arenateams
";
$stmt = $dbh->prepare();