<?php

/**
 * $Id: Text.php 256 2005-06-17 18:36:53Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 256 $ $Date: 2005-06-17 14:36:53 -0400 (Fri, 17 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.composite
 */

/**
 * Need Grpahic class
 */
include_once(dirname(__FILE__) . '/Graphic.php');

/**
 * A Text node is a Graphic object that is simply some text 
 * displayed at a certain position.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 256 $ $Date: 2005-06-17 14:36:53 -0400 (Fri, 17 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.composite
 */
class Text extends Graphic {
    
    /**
     * Position
     * @var Point
     * @orm composed_of one Point
     */
    public $position;

    /**
     * End point
     * @var Point
     * @orm char(24)
     */
    public $text;

    /**
     * Constructor
     * @param Node $parent
     */
    public function __construct($position = false, $text = false) { 
        
        parent::__construct();
        
        if (!$position) {
            $position = new Point(0,0);
        }
        $this->position = $position;
        
        if (!$text) {
            $text = "Some text here";
        }
        $this->text = $text;
    } 

}

?>
