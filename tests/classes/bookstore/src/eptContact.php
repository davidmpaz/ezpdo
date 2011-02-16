<?php

/**
 * $Id: eptContact.php 118 2005-03-20 18:14:20Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 118 $ $Date: 2005-03-20 13:14:20 -0500 (Sun, 20 Mar 2005) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */

/**
 * Need eptBase
 */
include_once(realpath(dirname(__FILE__)).'/eptBase.php');

/**
 * Class of contact infomation
 * 
 * This is a test class for ezpdo
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 118 $ $Date: 2005-03-20 13:14:20 -0500 (Sun, 20 Mar 2005) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */
class eptContact extends eptBase {
    
    /**
     * store name 
     * @var string 
     * @orm char(64)
     */
    public $phone;
    
    /**
     * zipcode
     * @var string 
     * @orm char(12)
     */
    public $zipcode;
    
}

?>
