<?php

/**
 * $Id: add.php 252 2005-06-17 17:42:43Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 252 $ $Date: 2005-06-17 13:42:43 -0400 (Fri, 17 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.composite
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

// Create a root node (Graphic) 
$root = $m->create('Graphic');

// The root contains the following nodes: 

// 
// 1. a line 
// 

// start point
$start_p = $m->create('Point', 1, 1);
// end point
$end_p = $m->create('Point', 111, 111);
// create the line
$line = $m->create('Line', $start_p, $end_p);

// 
// 2. a text
// 

// text position
$pos = $m->create('Point', 222, 222);

// text
$text = $m->create('Text', $pos, "hello, world!");

//
// 3. put line, text into root
// 

// add line and text into root
$root->children = array($line, $text);
// set root as parent of line and text
$line->parent = $root;
$text->parent = $root;

/**
 * Note that if we enable auto_flush in config.xml, 
 * we don't even need to flush. Upon quitting the 
 * script, all objects are commited to database.
 */
//$m->flush();

echo "All graphics are persisted. Use `php print.php` to check.\n";

?>
