<?php

/**
 * $Id: add.php 274 2005-06-27 12:38:39Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 274 $ $Date: 2005-06-27 08:38:39 -0400 (Mon, 27 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.bookstore
 */

/**
 * Need EZPDO runtime API
 */
include_once(realpath(dirname(__FILE__)) . '/../../ezpdo_runtime.php');

// get the persistence manager
$m = epManager::instance();

// create groups
$gs = array();
for($i = 0; $i < 3; $i ++) {
    $g = $m->create('Group');
    $gs[] = $g;
}

// create orders
$os = array();
for($i = 0; $i < 3; $i ++) {
    $o = $m->create('Order');
    $o->group = $gs;
    $os[] = $o;
}

// add orders into groups
foreach($gs as $g) {
    $g->order = $os;
}

// flush explicitly
$m->flush();

echo "Groups are persisted. Use `php print.php` to check.\n";

?>
