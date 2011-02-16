<?php

/**
 * $Id: eptBookstore.php 118 2005-03-20 18:14:20Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 118 $ $Date: 2005-03-20 13:14:20 -0500 (Sun, 20 Mar 2005) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */

/**
 * Class of a bookstore 
 * 
 * This is a test class for ezpdo
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 118 $ $Date: 2005-03-20 13:14:20 -0500 (Sun, 20 Mar 2005) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */
class eptBookstore {
    
    /**
     * store name 
     * @var string 
     * @orm char(64)
     */
    public $name;
    
    /**
     * books
     * @orm has many eptBook
     */
    public $books;
    
    /**
     * authors
     * @orm has many eptAuthor
     */
    public $authors;
    
    /**
     * Constructor
     * @param string $name store name
     */
    public function __construct($name = '') { 
        $this->name = $name;
    }
    
}

?>
