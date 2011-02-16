<?php

/**
 * $Id: eptBaseExtensive.php 773 2006-01-25 11:52:21Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 773 $ $Date: 2006-01-25 06:52:21 -0500 (Wed, 25 Jan 2006) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */

/**
 * Base class of ezpdo test
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 773 $ $Date: 2006-01-25 06:52:21 -0500 (Wed, 25 Jan 2006) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */
abstract class eptBaseExtensive {
    
    /**
     * uuid
     * @var string
     * @orm char(64)
     */
    public $uuid;
    
    /**
     * Constructor
     * @param string $name author name
     */
    public function __construct() { 
        $this->uuid = md5(uniqid());
    } 

}

?>
