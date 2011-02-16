<?php

/**
 * $Id: eptItem.php 773 2006-01-25 11:52:21Z nauhygon $
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
abstract class eptItem extends eptBaseExtensive {

    const BOOK = 0;
    const RECORDING = 1;
    const VIDEO = 2;
    const MAGAZINE = 3;
    
    /**
     * Type of the item
     * @var int
     * @orm integer(2)
     */
    public $type;
    
    /**
     * Cost of the item
     * @var string
     * @orm decimal(5,2) 
     */
    public $cost;
    
    /**
     * Store of the item
     * @var eptStore
     * @orm has one eptStore inverse(items)
     */
    public $store;
    
    /**
     * Sale of the item
     * @var eptSale
     * @orm has one eptSale inverse(item)
     */
    public $sale;
    
    public function __construct($type) {
        parent::__construct();
        $this->type = $type;
    }
    
}

?>
