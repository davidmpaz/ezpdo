<?php

/**
 * $Id: eptAuthor.php 118 2005-03-20 18:14:20Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 118 $ $Date: 2005-03-20 13:14:20 -0500 (Sun, 20 Mar 2005) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */

/**
 * Need eptBook
 */
include_once(realpath(dirname(__FILE__)).'/eptBook.php');

/**
 * Need eptContact
 */
include_once(realpath(dirname(__FILE__)).'/eptContact.php');

/**
 * Class of an author
 * 
 * This is a test class for ezpdo
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 118 $ $Date: 2005-03-20 13:14:20 -0500 (Sun, 20 Mar 2005) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */
class eptAuthor extends eptBase {
    
    /**
     * Id of the author
     * @var integer
     * @orm integer(64)
     */
    public $id;
    
    /**
     * Name of the author
     * @var string
     * @orm char(64)
     */
    public $name;
    
    /**
     * Age of the author
     * @var integer
     * @orm integer(3)
     */
    public $age;
    
    /**
     * Is the author an elite writer?
     * @var boolean
     * @orm boolean
     */
    public $is_elite;
    
    /**
     * Books written by the author
     * @var array of eptBook
     * @orm has many eptBook
     */
    public $books;
    
    /**
     * Contact info of the author
     * @var eptContact
     * @orm composed_of one eptContact
     */
    public $contact;
    
    /**
     * Constructor
     * @param string $name author name
     */
    public function __construct($name = '') { 
        parent::__construct();
        $this->name = $name;
    }
    
}

?>
