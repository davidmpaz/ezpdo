<?php

/**
 * $Id: eptStore.php 773 2006-01-25 11:52:21Z nauhygon $
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
class eptStore extends eptBaseExtensive {
    
    /**
     * Store sales
     * @var eptSale
     * @orm has many eptSale inverse(store)
     */
    public $sales;
    
    /**
     * Store items
     * @var eptItem
     * @orm has many eptItem inverse(store)
     */
    public $items;
    
    /**
     * Store employees
     * @var eptEmployee
     * @orm has many eptEmployee inverse(store)
     */
    public $employees;
    
    public function __construct() {
        parent::__construct();
    }
    
}

?>
