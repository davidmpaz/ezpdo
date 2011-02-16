<?php

/**
 * $Id: Author.php 989 2006-05-31 19:23:17Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 989 $ $Date: 2006-05-31 15:23:17 -0400 (Wed, 31 May 2006) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.bookstore
 */

/**
 * Need Base
 */
include_once(dirname(__FILE__).'/Base.php');

/**
 * Class of an author
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 989 $ $Date: 2006-05-31 15:23:17 -0400 (Wed, 31 May 2006) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.bookstore
 */
class Author extends Base {
    
    /**
     * Name of the author
     * @var string
     * @orm char(64)
     */
    public $name;
    
    /**
     * Books written by the author
     * @var array of Book
     * @orm has many Book
     */
    public $books = array();

    /**
     * Constructor
     * @param string $name author name
     */
    public function __construct($name = '') { 
        parent::__construct();
        $this->name = $name;
    }
    
    /**
     * Get the number of books the author has written
     * @return integer
     */
    public function getBookNum() {
        return $this->books->count(); 
    }

}

?>
