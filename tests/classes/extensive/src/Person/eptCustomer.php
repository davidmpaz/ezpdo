<?php

/**
 * $Id: eptCustomer.php 773 2006-01-25 11:52:21Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 773 $ $Date: 2006-01-25 06:52:21 -0500 (Wed, 25 Jan 2006) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */

/**
 * Need eptPerson
 */
include_once(realpath(dirname(__FILE__)).'/../eptPerson.php');

/**
 * Class of a book
 * 
 * This is a test class for ezpdo
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 773 $ $Date: 2006-01-25 06:52:21 -0500 (Wed, 25 Jan 2006) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 * @orm otherCustomer
 */
class eptCustomer extends eptPerson {
    
    /**
     * Sales of the customer 
     * @var eptSale
     * @orm has many eptSale inverse(customer)
     */
    public $sales;

    /**
     * Constructor
     * @param string
     */
    public function __construct() { 
        parent::__construct();
    }
}

?>
