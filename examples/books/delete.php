<?php

/**
 * $Id: delete.php 855 2006-03-13 13:12:05Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 855 $ $Date: 2006-03-13 08:12:05 -0500 (Mon, 13 Mar 2006) $
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

// delete all authors and books 
$m->deleteAll('Author');
$m->deleteAll('Book');

echo "All authors and books are deleted. Use `php print.php` to check.\n";

?>
