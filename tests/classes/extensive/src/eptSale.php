<?php

/**
 * $Id: eptSale.php 773 2006-01-25 11:52:21Z nauhygon $
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
include_once(realpath(dirname(__FILE__)).'/eptBaseExtensive.php');
include_once(realpath(dirname(__FILE__)).'/eptPerson.php');
include_once(realpath(dirname(__FILE__)).'/Person/eptEmployee.php');
include_once(realpath(dirname(__FILE__)).'/Person/eptCustomer.php');
include_once(realpath(dirname(__FILE__)).'/eptStore.php');
include_once(realpath(dirname(__FILE__)).'/eptItem.php');

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
class eptSale extends eptBaseExtensive {

    const BOOK = 0;
    const MULTIMEDIA = 1;
    const OTHER = 2;
    
    /**
     * Type of the sale
     * @var int
     * @orm integer(2)
     */
    public $type;
    
    /**
     * Time of the sale
     * @var datetime
     * @orm datetime 
     */
    public $datetime;
    
    /**
     * Amount of the sale
     * @var string
     * @orm decimal(5,2)
     */
    public $amount;
    
    /**
     * Store of the sale
     * @var eptStore
     * @orm has one eptStore inverse(sales)
     */
    public $store;
    
    /**
     * Employee of the sale
     * @var eptEmployee
     * @orm has one eptEmployee inverse(sales)
     */
    public $employee;
    
    /**
     * Customer of the sale
     * @var eptCustomer
     * @orm has one eptCustomer inverse(sales)
     */
    public $customer;
    
    /**
     * Item of the sale
     * @var eptItem
     * @orm has one eptItem inverse(sale)
     */
    public $item;
    
    public function __construct($type = '') {
        parent::__construct();
        if ($type == '') {
            $this->type = eptSale::OTHER;
        } else {
            $this->type = $type;
        }
    }
    
}

?>
