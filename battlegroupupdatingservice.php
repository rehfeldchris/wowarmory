<?php

/*
 * grabs the latest list of battlegroups from the blizzard api, and updates our local db with the info.
 * 
This could be left as a running process that sleeps for a day or whatever interval you want updates
but right now it updates once and stops due to the break statement
*/

require 'cli-config.php';


set_time_limit(30);
$sleepSeconds = 1;
$throttler = new Throttler($sleepSeconds);

$regions = ['us', 'eu'];


while (true) {
    foreach ($regions as $region) {
        $url = "http://{$region}.battle.net/api/wow/data/battlegroups/";
        $parser = new BattlegroupParser(file_get_contents($url));
        $updater = new BattlegroupUpdater($parser, $dbh, $region);
        $updater->update();
    }
    $throttler->sleep();
    break;
}
