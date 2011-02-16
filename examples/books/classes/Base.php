<?php

/**
 * $Id: Base.php 989 2006-05-31 19:23:17Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 989 $ $Date: 2006-05-31 15:23:17 -0400 (Wed, 31 May 2006) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.bookstore
 */

/**
 * Base class of ezpdo example
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 989 $ $Date: 2006-05-31 15:23:17 -0400 (Wed, 31 May 2006) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.bookstore
 */
class Base {
    
    /**
     * Tracking ID that may be used by a bookstore for easy retrieval
     * @var string
     * @orm char(64)
     */
    public $trackId;
    
    /**
     * Constructor
     * @param string $name author name
     */
    public function __construct() { 
        $this->trackId = uniqid('track-'); 
    } 
}

?>
