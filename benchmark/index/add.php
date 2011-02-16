<?php

/**
 * $Id: add.php 377 2005-08-06 21:43:44Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 377 $ $Date: 2005-08-06 17:43:44 -0400 (Sat, 06 Aug 2005) $
 * @package ezpdo_bench
 * @subpackage ezpdo_bench.index
 */

include_once(dirname(__FILE__) . '/common.php');

// get the persistence manager
$m = getManager();
$m->createIndexes();

// create users
$users = array();
for($i = 0; $i < NUM_USERS; $i ++) {
    $user = $m->create('User');
    $user->name = "user-$i";
    $users[] = $user;
}

// create groups
$groups = array();
for($i = 0; $i < NUM_GROUPS; $i ++) {
    
    $group = $m->create('Group');
    $group->name = "group-$i";
    
    // randomly pick 20 users to join the group
    $uids = range(0, NUM_USERS - 1);
    shuffle($uids);
    for($j = 0; $j < min(20, count($uids)); $j ++) {
        $group->users[] = $users[$uids[$j]];
    }

    $groups[] = $group;
}

// create thingies
$thingies = array();
for($i = 0; $i < NUM_THINGIES; $i ++) {
    
    $thingy = $m->create('Thingy');
    $thingy->name = "thingy-$i";
    
    // randomly pick 5 groups to view the thingy
    $gids = range(0, NUM_GROUPS - 1);
    shuffle($gids);
    for($j = 0; $j < min(count($gids), 5); $j ++) {
        $thingy->groups[] = $groups[$gids[$j]];
    }

    $thingies[] = $thingy;
}

// save all 
$m->flush();

echo "Users (" . count($users) . "), groups (" . count($groups) . ") and thingies (" . count($thingies) . ") are persisted.\n";
showPerfInfo();

//dumpQueries();

?>
