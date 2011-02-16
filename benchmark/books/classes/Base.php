<?php

/**
 * $Id: Base.php 1001 2006-06-07 00:30:17Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1001 $ $Date: 2006-06-06 20:30:17 -0400 (Tue, 06 Jun 2006) $
 * @package ezpdo_bench
 * @subpackage ezpdo_bench.books
 */

/**
 * Base class of ezpdo example
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1001 $ $Date: 2006-06-06 20:30:17 -0400 (Tue, 06 Jun 2006) $
 * @package ezpdo_bench
 * @subpackage ezpdo_bench.books
 */
class Base {
    
    /**
     * The id that can be used by bookstores for tracking 
     * 
     * Please note that this id is simply a regular variable and should not be 
     * confused with the object ids internally used and maintained by EZPDO.
     * 
     * @var string
     * @orm char(64)
     */
    public $trackId;
    
    /**
     * Constructor
     * @param string $name author name
     */
    public function __construct() { 
        // generate a tracking id 
        $this->trackId = uniqid('track-'); 
    } 
}

?>
