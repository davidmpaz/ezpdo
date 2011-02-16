<?php

/**
 * $Id: delete.php 295 2005-06-30 16:29:13Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 295 $ $Date: 2005-06-30 12:29:13 -0400 (Thu, 30 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.cyclic.one
 */

/**
 * Need EZPDO runtime API
 */
include_once(dirname(__FILE__) . '/../../../ezpdo_runtime.php');

/**
 * This script deletes all authors and books stored in database
 */

// get the persistence manager
$m = epManager::instance();

// delete all A/B/C objects
$m->deleteAll('A');
$m->deleteAll('B');
$m->deleteAll('C');

echo "All objects of A/B/C are deleted. Use `php print.php` to check.\n";

?>
