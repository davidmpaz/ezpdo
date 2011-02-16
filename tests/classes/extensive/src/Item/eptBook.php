<?php

/**
 * $Id: eptBook.php 773 2006-01-25 11:52:21Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 773 $ $Date: 2006-01-25 06:52:21 -0500 (Wed, 25 Jan 2006) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */

/**
 * Need eptBase
 */
include_once(realpath(dirname(__FILE__)).'/../eptItem.php');

/**
 * Class of contact infomation
 * 
 * This is a test class for ezpdo
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 773 $ $Date: 2006-01-25 06:52:21 -0500 (Wed, 25 Jan 2006) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */
abstract class eptItemBook extends eptItem {

    const FICTION = 0;
    const NONFICTION = 1;
    
    /**
     * Author of the book
     * @var string
     * @orm char(64)
     */
    public $author;
    
    /**
     * Category of the Book
     * @var int
     * @orm integer(2)
     */
    public $category;

    /**
     * Publisher of this book
     * @var eptPublisher
     * @orm has one eptPublisher inverse(books)
     */
    public $publisher;

    /**
     * Editors of this book
     * @var eptEditor
     * @orm has many eptEditor inverse(books)
     */
    public $editors;
    
    public function __construct($category) {
        parent::__construct(eptItem::BOOK);
        $this->category = $category;
    }
    
}

?>
