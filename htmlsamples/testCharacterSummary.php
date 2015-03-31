<?php
header('content-type: text/plain;charset=uitf-8');
$html = file_get_contents('CharacterSummary.html');
require '../BattleNetDomParser.php';
require '../CharacterSummaryParser.php';
$csp = new CharacterSummaryParser($html);
print_r($csp->parse());