<?php

/**
 * $Id: eptBase.php 1019 2006-11-29 06:26:43Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1019 $ $Date: 2006-11-29 01:26:43 -0500 (Wed, 29 Nov 2006) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */

/**
 * Base class of ezpdo test
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1019 $ $Date: 2006-11-29 01:26:43 -0500 (Wed, 29 Nov 2006) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */
class eptBase {
    
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

	/**
	 * A no-op function to test that a call to this method through
	 * the wrapped object should not change its 'dirty' flag
	 * @return void
	 */
	public function doNothing() {
	}    
}

?>
