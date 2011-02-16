<?php

/**
 * $Id: print.php 991 2006-05-31 19:24:19Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 991 $ $Date: 2006-05-31 15:24:19 -0400 (Wed, 31 May 2006) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.bookstore
 */

/**
 * Need EZPDO runtime API
 */
include_once(dirname(__FILE__) . '/../../ezpdo_runtime.php');

/**
 * This script prints out all authors and books stored in database
 * and can be called by other scripts to print all records.
 */

// get the persistence manager
$m = epManager::instance();

// get all authors
$authors = $m->get('Author');

// print all authors
if ($authors) {
    foreach($authors as $a) {
        //echo $a->toString() . "\n";
        echo $a; echo "\n";
    }
} else {
    echo "No author is found.\n";
}

// get all books
$books = $m->get('Book');

// print all books
if ($books) {
    foreach($books as $b) {
        //echo $b->toString() . "\n";
        echo $b; echo "\n";
    }
} else {
    echo "No book is found.\n";
}

?>
