<?php

/**
 * $Id: eptPerson.php 773 2006-01-25 11:52:21Z nauhygon $
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
include_once(realpath(dirname(__FILE__)).'/eptContactInfo.php');

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
abstract class eptPerson extends eptBaseExtensive {
    
    /**
     * Id of the person
     * Tests column name that is smaller than the privous one
     * @var integer
     * @orm cde integer(64)
     */
    public $id;
    
    /**
     * Name of the person 
     * Tests column name that is smaller than the privous one
     * @var string
     * @orm abcde char(64)
     */
    public $name;
    
    /**
     * Age of the person
     * Tests column name that is smaller than the privous one
     * @var integer
     * @orm cdefg integer(3)
     */
    public $age;
    
    /**
     * Contact info of the person
     * @var eptContactInfo
     * @orm composed_of one eptContactInfo
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
