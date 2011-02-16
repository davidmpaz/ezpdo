<?php

/**
 * $Id: delete.php 274 2005-06-27 12:38:39Z nauhygon $
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

/**
 * This script deletes all authors and books stored in database
 */

// get the persistence manager
$m = epManager::instance();

// delete all groups
$m->deleteAll('Group');

// delete all orders 
$m->deleteAll('Order');

echo "All groups and orders are deleted. Use `php print.php` to check.\n";

?>
