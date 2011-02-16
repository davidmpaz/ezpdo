<?php

/**
 * $Id: eptPublisher.php 773 2006-01-25 11:52:21Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 773 $ $Date: 2006-01-25 06:52:21 -0500 (Wed, 25 Jan 2006) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */

/**
 * Need eptContact
 */
include_once(realpath(dirname(__FILE__)).'/eptBaseExtensive.php');

/**
 * Class of an author
 * 
 * This is a test class for ezpdo
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 773 $ $Date: 2006-01-25 06:52:21 -0500 (Wed, 25 Jan 2006) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */
class eptPublisher extends eptBaseExtensive {
    
    /**
     * Books published by this publisher
     * @var eptItemBook
     * @orm has many eptItemBook inverse(publisher)
     */
    public $books;
    
    /**
     * Constructor
     */
    public function __construct() { 
        parent::__construct();
    }
    
}

?>
