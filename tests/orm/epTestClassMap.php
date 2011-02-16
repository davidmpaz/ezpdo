<?php

/**
 * $Id: epTestClassMap.php 185 2005-04-17 16:54:21Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 185 $ $Date: 2005-04-17 12:54:21 -0400 (Sun, 17 Apr 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.orm
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/../src/epTestCase.php');

/**
 * need epUtils
 */
include_once(EP_SRC_BASE.'/epUtils.php');

/**
 * Unit test class for {@link epClassMapFactory}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 185 $ $Date: 2005-04-17 12:54:21 -0400 (Sun, 17 Apr 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.orm
 */
class epTestClassMap extends epTestCase {
    
    /**
     * test basic functinos of class map factory
     */
    function testClassMapFactoryBasic() {
        
        // need epClassMapFactory
        include_once(EP_SRC_ORM.'/epClassMap.php');
        
        // get the singleton class map factory
        $cmf = & epClassMapFactory::instance();
        $this->assertTrue(isset($cmf)); 
        
        // need to remove all class map in factory
        $cmf->removeAll();
        
        // create a class map
        $cm1 = & $cmf->make('epTestBase');
        $this->assertTrue(isset($cm1)); 
        
        // create class map for the same class. should return the same
        $cm2 = &  $cmf->make('epTestBase');
        $this->assertTrue(isset($cm1)); 
        $this->assertTrue($cm1 === $cm2); 
        
        // create a class map
        $cm3 = & $cmf->make('epTest');
        $this->assertTrue(isset($cm3)); 
        $this->assertTrue($cm1 !== $cm3); 
        $this->assertTrue($cm2 !== $cm3); 
        
        // create class map for the same class. should return the same
        $cm4 = &  $cmf->make('epTest');
        $this->assertTrue(isset($cm4)); 
        $this->assertTrue($cm3 === $cm4); 
        $this->assertTrue($cm1 !== $cm4); 
        $this->assertTrue($cm2 !== $cm4);  
        
        // get all class maps made so far
        $cms = $cmf->allMade();
        $this->assertTrue(is_array($cms)); 
        $this->assertTrue(count($cms) == 2);  
        
        //epVarDump($cms);
    }
    
    /**
     * test container functions of classs map
     */
    function testClassMapContainer() {
        
        // get the singleton class map factory
        $cmf = & epClassMapFactory::instance();
        $this->assertTrue(isset($cmf)); 
        
        // create a class map
        $cm_tb = & $cmf->make('epTestBase');
        $this->assertTrue(isset($cm_tb)); 
        
        // create class map children 
        $num_children = 100;
        for($i = 0; $i < $num_children; $i ++) {
            
            // create class map 1
            $child_name = sprintf('epTest%03d',  $i);
            $cm_t = & $cmf->make($child_name);
            $this->assertTrue(isset($cm_t)); 
            $this->assertTrue($cm_tb !== $cm_t); 

            $cm_t->setParent($cm_tb);
            $this->assertTrue($cm_tb->addChild($cm_t));
        }
        
        // check parent-child relationship
        $children = $cm_tb->getChildren(false, true); // false: non-recursive; true: sort
        $this->assertTrue(count($children) == $num_children); 
        $i = 0;
        foreach ($children as $child) {
            $parent =& $child->getParent();
            $this->assertTrue(isset($parent)); 
            $this->assertTrue($parent->getName() == 'epTestBase');
            $child_name = sprintf('epTest%03d',  $i);
            $this->assertTrue($child->getName() == $child_name); 
            $i ++;
        }
    }
}

if (!defined('EP_GROUP_TEST')) {
    $t = new epTestClassMap;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
}

?>
