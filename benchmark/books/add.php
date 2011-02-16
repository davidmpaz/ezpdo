<?php

/**
 * $Id: add.php 1001 2006-06-07 00:30:17Z nauhygon $
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

// create authors
$as = array();
for($i = 0; $i < NUM_AUTHORS; $i ++) {
    $a = $m->create('Author');
    $a->name = "author-" . $i;
    $as[] = $a;
}

// create books
$bs = array();
for($i = 0; $i < NUM_BOOKS; $i ++) {
    $b = $m->create('Book');
    $b->title = "book-title-$i";
    $b->pages = $i * 100;
    $b->authors = $as;
    $bs[] = $b;
}

// set books to authors
foreach($as as $a) {
    $a->books = $bs;
}

// save all 
$m->flush();

echo "Authors (" . count($as) . ") and books (" . count($bs) . ") are persisted. Use `php print.php` to check.\n";
showPerfInfo();

//dumpQueries();

?>
