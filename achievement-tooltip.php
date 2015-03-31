<script type="text/javascript" src="http://static.wowhead.com/widgets/power.js"></script>
<script>var wowhead_tooltips = {"colorlinks": true, "iconizelinks": true, "renamelinks": true}</script>
<pre>

<?php


/*

show wowhead tooltip info links for achievements. debugging aid.

*/


$s = file_get_contents('ajson.txt');
$j = json_decode($s);

extract((array)$j->achievements);

//print_r($j);
$achievements = [];


foreach ($achievementsCompleted as $k => $id) {
    $achievements[] = [$achievementsCompleted[ $k ], $achievementsCompletedTimestamp[ $k ]];
    $dt = new DateTime();
    $dt->setTimestamp($achievementsCompletedTimestamp[ $k ] / 1000);
    printf('<a href="#" rel="achievement=%d&amp;who=FOO&amp;when=%s">%d %s</a>' . "\n",
        $achievementsCompleted[ $k ],
        $achievementsCompletedTimestamp[ $k ],
        $achievementsCompleted[ $k ],
        $dt->format('Y-m-d')
    );
}


$criteriaMap = [];
foreach ($criteria as $k => $id) {
    $criteriaMap[] = [$criteria[ $k ], $criteriaQuantity[ $k ], $criteriaTimestamp[ $k ], $criteriaCreated[ $k ]];
    printf("%s %s %s %s\n",
        $criteria[ $k ],
        $criteriaQuantity[ $k ],
        $criteriaTimestamp[ $k ],
        $criteriaCreated[ $k ],
        $dt->format('Y-m-d')
    );
    printf('<a href="#" rel="achievement=%s">%s %s %s </a>' . "\n",
        $criteria[ $k ],
        $criteria[ $k ],
        $criteriaQuantity[ $k ],

        $criteriaCreated[ $k ]
    );
}


//print_r($achievements);
print_r($criteriaMap);