<?php

/**
 * $Id: Author.php 1043 2007-03-06 12:58:53Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1043 $ $Date: 2007-03-06 07:58:53 -0500 (Tue, 06 Mar 2007) $
 * @package ezpdo_bench
 * @subpackage ezpdo_bench.books
 */

/**
 * Need Base
 */
include_once(realpath(dirname(__FILE__)).'/Base.php');

/**
 * Class of an author
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1043 $ $Date: 2007-03-06 07:58:53 -0500 (Tue, 06 Mar 2007) $
 * @package ezpdo_bench
 * @subpackage ezpdo_bench.books
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
        return count($this->books); 
    }

    /**
     * implement the magic function __toString()
     * @return string
     */
    public function toString() {
        $s = "author {\n";
        $s .= "  name: " . $this->name . "\n";
        $s .= "  books: \n";
        if ($this->books) {
            $i = 0;
            foreach($this->books as $book) {
                $s .= "    " . ++$i . ". " . $book->title . "\n";
            }
        } else {
            $s .= "    none\n";
        }
        $s .= "}";
        return $s;
    }
}

?>
