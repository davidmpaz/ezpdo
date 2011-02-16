<?php

/**
 * $Id: all.php 1001 2006-06-07 00:30:17Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1001 $ $Date: 2006-06-06 20:30:17 -0400 (Tue, 06 Jun 2006) $
 * @package ezpdo_bench
 * @subpackage ezpdo_bench.books
 */

include_once(dirname(__FILE__) . '/common.php');

// get the persistence manager
$m = getManager();

// get all authors and books 
$authors = $m->get('Author');
$books = $m->get('Book');

echo "Authors (" . count($authors) . ") and books (" . count($books) . ") are retrieved.\n";
showPerfInfo();

//dumpQueries();

?>
