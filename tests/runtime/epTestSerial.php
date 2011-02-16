<?php

/**
 * $Id: epTestSerial.php 482 2005-08-30 20:56:22Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 482 $ $Date: 2005-08-30 16:56:22 -0400 (Tue, 30 Aug 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/epTestRuntime.php');

/**
 * The unit test class for object serialization
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 482 $ $Date: 2005-08-30 16:56:22 -0400 (Tue, 30 Aug 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 */
class epTestSerial extends epTestRuntime {
    
    /**
     * The cached manager
     * @var epManager
     */
    protected $m = false;

    /**
     * Test serialization on an array of objects 
     */
    function testSerialization() {
        
        $this->_setUp('adodb', 'sqlite');

        // make sure we have setup manager
        $this->assertTrue($this->m);

        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneManyA.php');
        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneManyB.php');

        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a = $this->m->create('eptInvOneManyA'));
        $this->assertTrue($b = $this->m->create('eptInvOneManyB'));

        // set $a to $b->a
        $b->a = $a;
        $this->assertTrue($b->a === $a);
        $this->assertTrue($a->bs->inArray($b));
        
        // keep the dump string of the objects
        $this->assertTrue($a_str = $a->__toString());
        $this->assertTrue($b_str = $b->__toString());
    
        // put $a1 and $b1 into an array
        $array = array($a, $b);
        $this->assertTrue(count($array) == 2);
        
        // --- serailize array ---
        $this->assertTrue($serialized = serialize($array));
        
        // --- unserailize array ---
        $this->assertTrue($array_unserialized = unserialize($serialized));
        
        // make sure two objects only
        $this->assertTrue(count($array_unserialized) == 2);
        
        // get a and b
        $this->assertTrue($a_u = $array_unserialized[0]);
        $this->assertTrue($b_u = $array_unserialized[1]);
        
        // check if association is kept
        $this->assertTrue($b_u->a === $a_u);
        $this->assertTrue($a_u->bs->inArray($b_u));
        
        // check if string dump matches
        $this->assertTrue($a_u->__toString() == $a_str);
        $this->assertTrue($b_u->__toString() == $b_str);
    }
}

if (!defined('EP_GROUP_TEST')) {
    
    $tm = microtime(true);
    
    $t = new epTestSerial;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
    
    $elapsed = microtime(true) - $tm;
    
    echo epNewLine() . 'Time elapsed: ' . $elapsed . ' seconds' . "\n";
}

?>
