<?php

/**
 * $Id: find.php 1010 2006-07-17 21:55:39Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1010 $ $Date: 2006-07-17 17:55:39 -0400 (Mon, 17 Jul 2006) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.bookstore
 */

/**
 * Need EZPDO runtime API
 */
include_once(dirname(__FILE__) . '/../../ezpdo_runtime.php');

// get the persistence manager
$m = epManager::instance();

// 1. find by example object
echo "\n\nFind by example: \n\n";

// create the example object
$ea = $m->create('Author');
$ea->name = 'Erich Gamma'; // set name used in search
$ea->trackId = null; // null variable is ignored in searching
$ea->books = null; // null variable is ignored in searching

// use the example object to find
if (!($authors = $m->find($ea))) {
    echo "Cannot find author: ". $ea->name . "\n";
} else {
    // go through each author and print
    foreach($authors as $author) {
        echo $author;
        echo "\n";
    }
}

// 2. find by EZOQL - 1
echo "\n\nEZOQL query - 1: \n\n";

// use EZOQL to find books with 'Design' title 
$books = $m->find("from Book where title like '%Design%'");
if (!$books) {
    echo "Cannot find any book with 'Design' in title\n";
} else {    
    echo "Books with word 'Design' in title\n\n";
    // go through each book and print
    foreach($books as $book) {
        echo $book;
        echo "\n";
    }
}   

// 3. find by EZOQL - 2
echo "\n\nEZOQL query - 2: \n\n";

// use EZOQL to find books with 'Design' in any author's name
$books = $m->find("from Book where authors.contains(a) and a.name like '%Richard%'");
if (!$books) {
    echo "Cannot find any book with 'Richard' in any author's name\n";
} else {
    echo "Books with 'Richard' in any author's name\n";
    // go through each book and print
    foreach($books as $book) {
        echo $book;
        echo "\n";
    }
}

?>
