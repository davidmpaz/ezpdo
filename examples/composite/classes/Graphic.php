<?php

/**
 * $Id: Graphic.php 258 2005-06-18 03:51:23Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 258 $ $Date: 2005-06-17 23:51:23 -0400 (Fri, 17 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.composite
 */

/**
 * Base class for all Graphic objects
 * 
 * Graphic is a container that can have a parent (a Graphic object) 
 * and child Graphic objects or its subclass objects, for example, 
 * Line, Text, etc. 
 * 
 * Each Graphic object also has a unique id (myid) so it can be 
 * uniquely id'ed among all Graphic objects. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 258 $ $Date: 2005-06-17 23:51:23 -0400 (Fri, 17 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.composite
 */
class Graphic {
    
    /**
     * The unique id every object has
     * @var string
     * @orm char(64)
     */
    public $myid;
    
    /**
     * Parent
     * @var string
     * @orm has one Graphic
     */
    public $parent = false;
    
    /**
     * Children
     * @var string
     * @orm has many Graphic
     */
    public $children = false;

    /**
     * Constructor
     * @param Node $parent
     */
    public function __construct() { 
        $this->myid = uniqid(); 
    } 
}

?>
