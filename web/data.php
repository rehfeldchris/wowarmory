<?php

header('content-type: text/plain');
require '../config.php';

$sql = "
select
	c.guild_id,
	c.guild_name,
	ca.arenateam_id,
	a.name arenateam_name,
	count(*) num_connections
from denormalized_characters c
join character_arenateams ca
	on c.id = ca.character_id
join arenateams a
	on a.id = ca.arenateam_id
where guild_id is not null
group by guild_id, arenateam_id
limit 400
";
$stmt = $dbh->query($sql);
$connections = $stmt->fetchAll();


$nodes = [];
$links = [];
$ids = [];
function buildLookupFunc($connections)
{
    $arenateams = [];
    $guilds = [];
    foreach ($connections as $row) {
        extract($row);
        $arenateams[] = $arenateam_name;
        $guilds[] = $guild_name;
    }
    $lookup = [
        'arenateam' => array_flip(array_values(array_unique($arenateams)))
        , 'guild'   => array_flip(array_values(array_unique($guilds)))
    ];

    return function ($type, $name) use ($lookup) {
        $pos = $lookup[ $type ][ $name ];
        if ($type === 'guild') {
            $pos += count($lookup['arenateam']);
        }

        return $pos;
    };
}

function buildNodes($connections)
{
    $arenateams = [];
    $guilds = [];
    $nodes = [];
    foreach ($connections as $row) {
        extract($row);
        $arenateams[] = $arenateam_name;
        $guilds[] = $guild_name;
    }
    foreach (array_unique($arenateams) as $name) {
        $nodes[] = ['name' => $name, 'group' => 1, 'size' => 2];
    }
    foreach (array_unique($guilds) as $name) {
        $nodes[] = ['name' => $name, 'group' => 6, 'size' => 8];
    }

    return $nodes;
}


$lookup = buildLookupFunc($connections);
$nodes = buildNodes($connections);


foreach ($connections as $row) {
    $links[] = [
        'source'   => $lookup('guild', $row['guild_name'])
        , 'target' => $lookup('arenateam', $row['arenateam_name'])
        , 'value'  => pow($row['num_connections'], 4)
    ];
}


echo json_encode(compact('nodes', 'links'));