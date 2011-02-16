<?php

/**
 * $Id: Line.php 256 2005-06-17 18:36:53Z nauhygon $
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
 * A line is a graphic object that has a starting 
 * point and an ending point.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 256 $ $Date: 2005-06-17 14:36:53 -0400 (Fri, 17 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.composite
 */
class Line extends Graphic {
    
    /**
     * Start point
     * @var Point
     * @orm composed_of one Point
     */
    public $start;

    /**
     * End point
     * @var Point
     * @orm composed_of one Point
     */
    public $end;

    /**
     * Constructor
     * @param Node $parent
     */
    public function __construct($start = null, $end = null) { 
        
        parent::__construct();
        
        if (!$start) {
            $start = new Point(0,0);
        }
        $this->start = $start;
        
        if (!$end) {
            $end = new Point(100,100);
        }
        $this->end = $end;
    } 
}

?>
