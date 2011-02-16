<?php

/**
 * $Id: Point.php 256 2005-06-17 18:36:53Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 256 $ $Date: 2005-06-17 14:36:53 -0400 (Fri, 17 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.composite
 */

/**
 * Need Base class
 */
include_once(dirname(__FILE__) . '/Graphic.php');

/**
 * A point is a Graphic object that has (x,y) coordinates
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 256 $ $Date: 2005-06-17 14:36:53 -0400 (Fri, 17 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.composite
 */
class Point extends Graphic {
    
    /**
     * Coordinate x
     * @var integer 
     * @orm integer 
     */
    public $x = 0;

    
    /**
     * Coordinate x
     * @var integer 
     * @orm integer 
     */
    public $y = 0;

    /**
     * Constructor
     * @param Node $parent
     */
    public function __construct($x = 0, $y = 0) { 
        parent::__construct();
        $this->x = $x;
        $this->y = $y;
    } 

}

?>
