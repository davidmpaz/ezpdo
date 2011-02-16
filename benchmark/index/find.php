<?php

/**
 * $Id: find.php 300 2005-07-01 19:28:12Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 300 $ $Date: 2005-07-01 15:28:12 -0400 (Fri, 01 Jul 2005) $
 * @package ezpdo_bench
 * @subpackage ezpdo_bench.index
 */

include_once(dirname(__FILE__) . '/common.php');

// get the persistence manager
$m = getManager();

// name of the author to be found
$username = 'user-' . rand(0, NUM_USERS - 1);

// find thingies for user
$thingies = $m->find(
    'from Thingy ' . 
    'where groups.contains(g) and g.users.contains(u) and u.name = ?', 
    $username);

// show results
echo count($thingies) . " thingies are found for user " . $username . ".\n";

// show performance info
showPerfInfo();

?>
