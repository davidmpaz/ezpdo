<?php

/**
 * $Id: print.php 274 2005-06-27 12:38:39Z nauhygon $
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
include_once(dirname(__FILE__) . '/../../ezpdo_runtime.php');

// get the persistence manager
$m = epManager::instance();

// create the example object
$eg = $m->create('Group');
$eg->from = null; // null variable is ignored in searching
$eg->where = null; // null variable is ignored in searching 
$eg->like = null; // null variable is ignored in searching 
$eg->order = null; // null variable is ignored in searching 

// use the example object to find
if (!($groups = $m->find($eg))) {
    
    echo "Cannot find any group\n";

} else {

  // go through each group
  foreach($groups as $group) {
      echo $group;
      echo "\n";
  }

}

// create the example object
$eo = $m->create('Order');
$eo->update = null; // null variable is ignored in searching
$eo->insert = null; // null variable is ignored in searching 
$eo->delete = null; // null variable is ignored in searching 
$eo->group = null; // null variable is ignored in searching 

// use the example object to find
if (!($orders = $m->find($eo))) {
    
    echo "Cannot find any order\n";

} else {

  // go through each order
  foreach($orders as $order) {
      echo $order;
      echo "\n";
  }

}

?>
