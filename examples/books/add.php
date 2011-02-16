<?php

/**
 * $Id: add.php 990 2006-05-31 19:23:49Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 990 $ $Date: 2006-05-31 15:23:49 -0400 (Wed, 31 May 2006) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.bookstore
 */

/**
 * Need EZPDO runtime API
 */
include_once(dirname(__FILE__) . '/../../ezpdo_runtime.php');

/**
 * This script demonstrates how to use the EZPDO persistence manager
 * to create books, authors, bookstore, and their associations, and
 * how to persist them into the database. 
 */

// get the persistence manager
$m = epManager::instance();

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
$b1->title = 'Design Patterns: Elements of reusable object orientated software';
$b1->pages = 395;
$b1->price = 100.01;
$b1->authors[] = $a1;
$b1->authors[] = $a2;
$b1->authors[] = $a3;
$b1->authors[] = $a4;

// or you can simply write 
//$b1->authors = array($a1, $a2, $a3, $a4);

$b2 = $m->create('Book');
$b2->title = 'Contributing to Eclipse: Principles, Patterns, and Plugins';
$b2->pages = 320;
$b2->price = 200.02;
$b2->authors[] = $a1;

$b3 = $m->create('Book');
$b3->title = 'Detecting and eliminating redundant derivations in deductive database systems';
$b3->pages = 17;
$b3->price = 300.03;
$b3->authors[] = $a2;

$b4 = $m->create('Book');
$b4->title = 'Implementing Application Frameworks: Object-Oriented Frameworks at Work';
$b4->pages = 729;
$b4->price = 400.04;
$b4->authors[] = $a3;

$b5 = $m->create('Book');
$b5->title = 'Pattern Hatching : Design Patterns Applied (Software Patterns Series)';
$b5->pages = 172;
$b5->price = 500.05;
$b5->authors[] = $a4;

// add authors to books (two-way M:N associations between authors and books)
$a1->books = array($b1, $b2);
$a2->books = array($b1, $b3);
$a3->books = array($b1, $b4);
$a4->books = array($b1, $b5);

/**
 * Note that if we enable auto_flush in config.ini, we don't need to flush. 
 * Upon quitting the script, all objects are commited to database.
 */
//$m->flush();

echo "Authors and books are persisted. Use `php print.php` to check.\n";

?>
