<?php

/**
 * $Id: delete.php 570 2005-10-17 23:33:15Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 570 $ $Date: 2005-10-17 19:33:15 -0400 (Mon, 17 Oct 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.composite
 */

/**
 * Need EZPDO runtime API
 */
include_once(dirname(__FILE__) . '/../../ezpdo_runtime.php');

/**
 * This script deletes all graphic objects
 */

// get the persistence manager
$m = epManager::instance();

// delete all grahpic objects (Graphic and all its subclasses)
$authors = $m->deleteAll('Graphic');

echo "All graphics are deleted. Use `php print.php` to check.\n";

?>
