<?php

/**
 * $Id: delete.php 300 2005-07-01 19:28:12Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 300 $ $Date: 2005-07-01 15:28:12 -0400 (Fri, 01 Jul 2005) $
 * @package ezpdo_bench
 * @subpackage ezpdo_bench.index
 */

/**
 * Need EZPDO runtime API
 */
include_once(dirname(__FILE__) . '/common.php');

// get the persistence manager
$m = getManager();

// delete all users, groups, and thingies
$m->deleteAll('User');
$m->deleteAll('Group');
$m->deleteAll('Thingy');

echo "All users/groups/thingies are deleted.\n";
showPerfInfo();

?>
