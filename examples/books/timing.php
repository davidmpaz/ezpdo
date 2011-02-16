<?php

/**
 * $Id: timing.php 326 2005-07-15 13:34:38Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 326 $ $Date: 2005-07-15 09:34:38 -0400 (Fri, 15 Jul 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.bookstore
 */

// profiling
//apd_set_pprof_trace();

// record the start to measure how much it takes to run the script
$t = array();
$t[] = microtime(true);

/**
 * Need EZPDO runtime API
 */
include_once(dirname(__FILE__) . '/../../ezpdo_runtime.php');

// record time when initialization is done
$t[] = microtime(true);

/**
 * This script demonstrates how to use the EZPDO persistence manager
 * to create books, authors, bookstore, and their associations, and
 * how to persist them into the database. 
 */

// get the persistence manager
$m = epManager::instance();

// disable auto-flush so we can measure flush time
$m->setConfigOption('auto_flush', false);

// cleanup existing authors and books if any
$m->deleteAll('Author');
$m->deleteAll('Book');

// record time when cleanup is done
$t[] = microtime(true);

// create authors
$a1 = $m->create('Author');
$a1->name = 'Erich Gamma';

$a2 = $m->create('Author');
$a2->name = 'Richard Helm';

$a3 = $m->create('Author');
$a3->name = 'Ralph Johnson';

$a4 = $m->create('Author');
$a4->name = 'John Vlissides';

// create books 
$b1 = $m->create('Book');
$b1->title = 'Design Patterns';
$b1->pages = 395;
$b1->authors = array($a1, $a2, $a3, $a4);

$b2 = $m->create('Book');
$b2->title = 'Contributing to Eclipse: Principles, Patterns, and Plugins';
$b2->pages = 320;
$b2->authors = array($a1);

$b3 = $m->create('Book');
$b3->title = 'Contributing to Eclipse: Principles, Patterns, and Plugins';
$b3->pages = 320;
$b3->authors = array($a2);

$b4 = $m->create('Book');
$b4->title = 'Implementing Application Frameworks: Object-Oriented Frameworks at Work';
$b4->pages = 729;
$b4->authors = array($a3);

$b5 = $m->create('Book');
$b5->title = 'Pattern Hatching : Design Patterns Applied (Software Patterns Series)';
$b5->pages = 172;
$b5->authors = array($a4);

// add authors to books (two-way M:N associations between authors and books)
$a1->books = array($b1, $b2);
$a2->books = array($b1, $b3);
$a3->books = array($b1, $b4);
$a3->books = array($b1, $b5);

// record time when memeory operations are done
$t[] = microtime(true);

$m->flush();

// record time to persist objects
$t[] = microtime(true);

// compute time elapsed
$elapsed = $t[4] - $t[0]; 
echo "total   : $elapsed\n";

$init    = $t[1] - $t[0]; 
echo "init    : $init\n"; 

$cleanup = $t[2] - $t[1];  
echo "cleanup : $cleanup\n"; 

$create  = $t[3] - $t[2];
echo "create  : $create\n"; 

$persist = $t[4] - $t[3]; 
echo "persist : $persist\n"; 

?>
