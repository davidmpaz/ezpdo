<?php

/**
 * $Id: delete1.php 1043 2007-03-06 12:58:53Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1043 $ $Date: 2007-03-06 07:58:53 -0500 (Tue, 06 Mar 2007) $
 * @package ezpdo_bench
 * @subpackage ezpdo_bench.books
 */

include_once(dirname(__FILE__) . '/common.php');

// get the persistence manager
$m = getManager();

if (!($authors = $m->find("from Author where name = ?", $author_name))) {
	echo "Cannot find author [" . $author_name . "]\n";
	exit();
} 

// go through each author 
foreach($authors as $author) {
	// delete this author
	$author->delete();
}

echo "Author [$author_name] is deleted. Use `php find.php` to check.\n";
showPerfInfo();

//dumpQueries();

?>
