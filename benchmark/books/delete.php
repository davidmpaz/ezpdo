<?php

/**
 * $Id: delete.php 1001 2006-06-07 00:30:17Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1001 $ $Date: 2006-06-06 20:30:17 -0400 (Tue, 06 Jun 2006) $
 * @package ezpdo_bench
 * @subpackage ezpdo_bench.books
 */

/**
 * Need EZPDO runtime API
 */
include_once(dirname(__FILE__) . '/common.php');

// get the persistence manager
$m = getManager();

// delete all authors and books 
$authors = $m->deleteAll('Author');
$books = $m->deleteAll('Book');

echo "All authors and books are deleted. Use `php print.php` to check.\n";
showPerfInfo();

//dumpQueries();

?>
