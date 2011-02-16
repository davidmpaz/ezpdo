<?php

/**
 * $Id: eptContactInfo.php 993 2006-06-01 11:15:40Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 993 $ $Date: 2006-06-01 07:15:40 -0400 (Thu, 01 Jun 2006) $
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
 * @version $Revision: 993 $ $Date: 2006-06-01 07:15:40 -0400 (Thu, 01 Jun 2006) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 * @orm wierdTableName
 */
class eptContactInfo extends eptBaseExtensive {
    
    /**
     * store name 
     * tests keyword
     * @var string 
     * @orm desc char(64)
     */
    public $phone;
    
    /**
     * zipcode
     * tests keyword
     * @var string 
     * @orm asc char(12)
     */
    public $zipcode;
    
    /**
     * contains
     * tests ezoql keyword
     * @var string 
     * @orm contains char(12)
     */
    public $contains;

    public function __construct() {
        parent::__construct();
    }
    
}

?>
