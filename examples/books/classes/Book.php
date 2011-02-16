<?php

/**
 * $Id: Book.php 994 2006-06-01 13:05:20Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 994 $ $Date: 2006-06-01 09:05:20 -0400 (Thu, 01 Jun 2006) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.bookstore
 */

/**
 * Need Base
 */
include_once(dirname(__FILE__).'/Base.php');

/**
 * Class of a book
 * 
 * This is a test class for ezpdo
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 994 $ $Date: 2006-06-01 09:05:20 -0400 (Thu, 01 Jun 2006) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.bookstore
 */
class Book extends Base {
    
    /**
     * Book title
     * @var string
     * @orm title char(80)
     */
    public $title;
    
    /**
     * Number of pages
     * @var integer
     * @orm integer
     */
    public $pages = -1;
    
    /**
     * Price of the book (in dollar)
     * @var float
     * @orm decimal(5,2)
     */
    public $price = 0.0;

    /**
     * Is this bool verbose?
     * @var boolean
     * @orm boolean
     */
    public $verbose = false;
    
    /**
     * The authors who write the book
     * @var Author
     * @orm has many Author
     */
    public $authors = array();

    /**
     * Constructor
     * @param string
     */
    public function __construct($title = '') { 
        parent::__construct();
        $this->title = $title;
    }
    
    /**
     * Get author names
     * @return array
     */
    public function getAuthorNames() {
        $names = array();
        if ($this->authors) {
            foreach($this->authors as $author) {
                $names[] = $author->name;
            }
        }
        return $names;
    }
    
}

?>
