<?php

/*
This script fetches the realm list from blizzards api, then adds any missing realms to the db. 
If the game adds new servers, this script is neccesary to discover them. 

This is also neccesary to run periodically to to make sure a realm has the correct battlegroup, as they can change(rare).


This could be left as a running process that sleeps for a day or whatever interval you want updates
but right now it updates once and stops due to the break statement.

This wont delete realms. that needs to be done manually - which would be a pain 
because many character, guild, and arenateam entities in the db will reference a realm via foreign key, 
and they would need to be recrawled to get the new realms for them.
*/

require 'cli-config.php';


set_time_limit(30);
$sleepSeconds = 86400;
$throttler = new Throttler($sleepSeconds);

$regions = ['us', 'eu'];


while (true) {
    foreach ($regions as $region) {
        $url = "http://{$region}.battle.net/api/wow/realm/status";
        $parser = new RealmListParser(file_get_contents($url));
        $updater = new RealmListUpdater($parser, $dbh, $region);
        $updater->update();


    }
    $throttler->sleep();
    break;
}
