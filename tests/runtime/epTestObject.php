<?php

/**
 * $Id: epTestObject.php 890 2006-03-29 13:19:38Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 890 $ $Date: 2006-03-29 08:19:38 -0500 (Wed, 29 Mar 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/../src/epTestCase.php');

/**
 * need ezpdo utils
 */
include_once(EP_SRC_BASE.'/epUtils.php');

/**#@+
 * need epObject to test
 */
include_once(EP_SRC_RUNTIME.'/epObject.php');
include_once(EP_SRC_RUNTIME.'/epManager.php');
/**#@-*/

/**
 * A test class 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 890 $ $Date: 2006-03-29 08:19:38 -0500 (Wed, 29 Mar 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 */
class eptTest {
    
    /**
     * @var string
     */
    public $x = 'X';
    
    /**
     * @var string
     */
    protected $y = 'Y';
    
    /**
     * @var string
     */
    private $z = 'Z';
    
    /**
     * @var array
     */
    public $a = false;
    
    /**
     * @var object
     */
    public $o = false;
    
    /**
     * Constructor
     * @param string author name
     */
    public function __construct($x, $y, $z) { 
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }
    
    /**
     * Implement magic method __toString()
     */
    function __toString() {
        return "x: " . $this->x . "; y: " . $this->y . "; z: " . $this->z . ";";
    }
    
}

/**
 * The unit test class for {@link epObject}  
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 890 $ $Date: 2006-03-29 08:19:38 -0500 (Wed, 29 Mar 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 */
class epTestObject extends epTestCase {
    
    /**
     * test primitive variables
     */
    function testPrimitiveVars() {
        
        // make an eptTest object
        $this->assertTrue($o = new eptTest("XXX", "YYY", "ZZZ"));
        
        // wrap it with epObject
        $this->assertTrue($w = new epObject($o));
        
        // test new object is not dirty  
        $this->assertFalse($w->epIsDirty());
        
        // check vars
        $this->assertTrue($vars = $w->epGetVars());
        
        // x, a, o are in vars
        $this->assertTrue(array_key_exists("x", $vars));
        $this->assertTrue(array_key_exists("a", $vars));
        $this->assertTrue(array_key_exists("o", $vars));
        
        // y protected is not in vars
        $this->assertFalse(array_key_exists("y", $vars));
        $this->assertFalse(array_key_exists("z", $vars));
        
        /**
         * test primitive vars
         */
        
        // test epGet('x') against direct object property access ($o->x)
        $this->assertTrue($w->epGet('x') === $o->x);
        
        // test epSet('x'), epGet('x'), and $w->x (overloading __get())
        $value = md5($w->epGet('x')); 
        $this->assertTrue($w->epSet('x', $value)); 
        $this->assertTrue($o->x === $value); 
        $this->assertTrue($w->epGet('x') === $o->x); 
        $this->assertTrue($w->x === $o->x); 
        $this->assertTrue($w->epIsDirty()); 
        
        // check if 'x' is in modified list
        $this->assertTrue($modified = $w->epGetModifiedVars());
        $this->assertTrue(array_key_exists('x', $modified));
        $this->assertTrue($modified['x'] === $value);
        
        // test getter/setter (overloading __call())  
        $value = md5($w->epGet('x'));
        $this->assertTrue($w->setX($value));
        $this->assertTrue($w->getX() === $o->x);
        $this->assertTrue($w->getX() === $value);
        
        // check which 'x' is in modified list
        $this->assertTrue($modified = $w->epGetModifiedVars());
        $this->assertTrue(array_key_exists('x', $modified));
        $this->assertTrue($modified['x'] === $value);
        
        // check magic method __get() and __set()
        $this->assertTrue($w->x === $value);
        $value = md5($w->x);
        $this->assertTrue($w->x = $value);
        $this->assertTrue($w->x === $value);
        
        // getting protected 'y' gets exception
        try {
            $this->assertTrue($w->epGet('y') === false);
        } catch (Exception $e) {
            // !!!simpletest seem to ignore assert in catch block
            $this->assertTrue($e instanceof epExceptionObject);
        }
    
        // setting protected 'y' gets exception
        try {
            $this->assertTrue($w->epSet('y', 'YYY|YYY') === false);
        } catch (Exception $e) {
            // !!!simpletest seem to ignore assert in catch block
            $this->assertTrue($e instanceof epExceptionObject);
        }
    
        // getting protected 'z' gets exception
        try {
            $this->assertTrue($w->epGet('z') === false);
        } catch (Exception $e) {
            // !!!simpletest seem to ignore assert in catch block
            $this->assertTrue($e instanceof epExceptionObject);
        }
        
        // setting protected 'z' gets exception
        try {
            $this->assertTrue($w->epSet('z', 'ZZZ|ZZZ') === false);
        } catch (Exception $e) {
            // !!!simpletest seem to ignore assert in catch block
            $this->assertTrue($e instanceof epExceptionObject);
        }
        
    }
    
    /**
     * test variables, dirty flag, setter and getters, and overloading
     */
    function testObjectVars() {
        
        // make an eptTest object
        $this->assertTrue($o = new eptTest("XXX", "YYY", "ZZZ"));
        
        // wrap it with epObject
        $this->assertTrue($w = new epObject($o));
        
        /**
         * test object var 
         */
        $m = new eptTest("mXX", "mYY", "mZZ");

        // assign new object m to $w->o
        $this->assertTrue($w->o = $m);

        // notice that object reference is assigned 
        $this->assertTrue($w->o === $m);
        $this->assertTrue($w->o->x == "mXX");
        // assign new value to var x
        $new_x = $m->x . $m->x;
        $this->assertTrue($m->x = $new_x);
        // proof of object reference being passed
        $this->assertTrue($w->o->x == $new_x);
        // again - proof of references being passed
        $this->assertTrue($oo = $w->o); // $oo is ref to $w->o
        $this->assertTrue($oo === $w->o); // $oo is ref to $w->o
        $new_x = $new_x . $new_x;
        $this->assertTrue($oo->x = $new_x);
        $this->assertTrue($oo->x == $new_x);
        $this->assertTrue($w->o->x == $new_x);
    }

    /**
     * test array var
     */
    function testArrayVars() {

        // make an eptTest object
        $this->assertTrue($o = new eptTest("XXX", "YYY", "ZZZ"));
        
        // wrap it with epObject
        $this->assertTrue($w = new epObject($o));
        
        $w->epSetDirty(false);
        $this->assertTrue($w->a = array("1" => "111", "2" => "222", "3" => "333"));
        $this->assertTrue($w->epIsDirty());
        // check values
        $this->assertTrue(count($w->a) == 3);
        $this->assertTrue($w->a['1'] == "111");
        $this->assertTrue($w->a['2'] == "222");
        $this->assertTrue($w->a['3'] == "333");
        
        // !!!note that the following does not work!!!
        // $w->a['4'] == "444";
        // a way of inserting new items into an array var
        $this->assertTrue($a = $w->a);
        $this->assertTrue(count($a) == 3);
        $this->assertTrue($a['1'] == "111");
        $this->assertTrue($a['2'] == "222");
        $this->assertTrue($a['3'] == "333");
        
        // now insert a new item
        $this->assertTrue($a['4'] = "444");
        
        // reassign the whole array back
        $w->epSetDirty(false);
        $this->assertTrue($w->a = $a);
        $this->assertTrue($w->epIsDirty());
        
        // check items
        $this->assertTrue($w->a['1'] == "111");
        $this->assertTrue($w->a['2'] == "222");
        $this->assertTrue($w->a['3'] == "333");
        // check new item ['4']
        $this->assertTrue($w->a['4'] == "444");

    }
    
    /**
     * test epObject::epMatches()
     */
    public function testObjectMatch() {
        
        // make an eptTest object
        $this->assertTrue($o = new eptTest("XXX", "YYY", "ZZZ"));
        
        // wrap it with epObject
        $this->assertTrue($w = new epObject($o));
        
        // make an eptTest object
        $this->assertTrue($p = new eptTest("XXX", "YYY", "ZZZ"));
        
        // wrap it 
        $this->assertTrue($m = new epObject($p));
        
        // copy vars over from $w
        $m->epCopyVars($w);
        
        // should match with the copy
        $this->assertTrue($w->epMatches($m));
        
        // set vars in m to null, should still match (because null vars are ignored)
        $this->assertTrue($vars = $m->epGetVars());
        foreach($vars as $var => $value) { 
            // skip oid
            if ($var == 'oid') {
                continue;
            }
            $m->epSet($var, null);
            $this->assertTrue($w->epMatches($m));
        }
        
        // set vars to diff values, assert no match 
        foreach($vars as $var => $value) { 
            if (is_string($value)) {
                $m->epSet($var, md5($w->epGet($var)));
                $this->assertFalse($w->epMatches($m));
            }
        }
    }

    /**
     * test array access
     */
    function testArrayAccess() {
        
        // make an eptTest object
        $this->assertTrue($o = new eptTest("XXX", "YYY", "ZZZ"));
        
        // wrap it with epObject
        $this->assertTrue($w = new epObject($o));
        
        // test new object is not dirty  
        $this->assertFalse($w->epIsDirty());
        
        // test epGet('x') against direct object property access ($o->x)
        $this->assertTrue($w['x'] === $o->x);
        
        // test epSet('x'), epGet('x'), and $w->x (overloading __get())
        $value = md5($w['x']);
        $this->assertTrue($w['x'] = $value); 
        $this->assertTrue($o->x === $value); 
        $this->assertTrue($w['x'] === $o->x); 
        $this->assertTrue($w->epIsDirty()); 
        
        // check if 'x' is in modified list
        $this->assertTrue($modified = $w->epGetModifiedVars());
        $this->assertTrue(array_key_exists('x', $modified));
        $this->assertTrue($modified['x'] === $value);
        
        // getting protected 'y' gets exception
        try {
            $this->assertTrue($w['y'] === false);
        } catch (Exception $e) {
            // !!!simpletest seem to ignore assert in catch block
            $this->assertTrue($e instanceof epExceptionObject);
        }
    
        // setting protected 'y' gets exception
        try {
            $w['y'] = 'YYY|YYY';
        } catch (Exception $e) {
            // !!!simpletest seem to ignore assert in catch block
            $this->assertTrue($e instanceof epExceptionObject);
        }
    
        // getting protected 'z' gets exception
        try {
            $this->assertTrue($w['z'] === false);
        } catch (Exception $e) {
            // !!!simpletest seem to ignore assert in catch block
            $this->assertTrue($e instanceof epExceptionObject);
        }
        
        // setting protected 'z' gets exception
        try {
            $w['z'] = 'ZZZ|ZZZ';
        } catch (Exception $e) {
            // !!!simpletest seem to ignore assert in catch block
            $this->assertTrue($e instanceof epExceptionObject);
        }
        
        // test foreach
        $vars = array();
        foreach($w as $var => $value) {
            $vars[$var] = $value;
        }
        
        // check var number 
        $this->assertTrue(count($vars) == $w->count());
    }
    
}

if (!defined('EP_GROUP_TEST')) {
    $t = new epTestObject;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
}

?>
