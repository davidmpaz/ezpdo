<?php

/**
 * $Id: epTestListeners.php 485 2005-08-30 21:26:36Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 485 $ $Date: 2005-08-30 17:26:36 -0400 (Tue, 30 Aug 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/epTestRuntime.php');

/**
 * An empty (thus invalid) event listener class for test 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 485 $ $Date: 2005-08-30 17:26:36 -0400 (Tue, 30 Aug 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 */
class eptListenerEmpty {
}

/**
 * A event listener class for test 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 485 $ $Date: 2005-08-30 17:26:36 -0400 (Tue, 30 Aug 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 */
class eptListener {
    
    /**
     * Counter of events
     * @var array (keyed by event) 
     */
    static public $counts = array();

    // event handler: onPreCreate
    public function onPreCreate($c, $params = null) {
        $this->_inc(__METHOD__, $c, $params);
    }

    // event handler: onLoad
    public function onCreate($o, $params = null) {
        $this->_inc(__METHOD__, $o, $params);
    }

    // event handler: onPreLoad
    public function onPreLoad($c, $params = null) {
        $this->_inc(__METHOD__, $c, $params);
    }

    // event handler: onLoad
    public function onLoad($o, $params = null) {
        $this->_inc(__METHOD__, $o, $params);
    }

    // event handler: onPreInsert
    public function onPreInsert($o, $params = null) {
        $this->_inc(__METHOD__, $o, $params);
    }

    // event handler: onInsert
    public function onInsert($o, $params = null) {
        $this->_inc(__METHOD__, $o, $params);
    }

    // event handler: onPreUpdate
    public function onPreUpdate($o, $params = null) {
        $this->_inc(__METHOD__, $o, $params);
    }

    // event handler: onUpdate
    public function onUpdate($o, $params = null) {
        $this->_inc(__METHOD__, $o, $params);
    }

    // event handler: onPreEvent
    public function onPreEvict($o, $params = null) {
        $this->_inc(__METHOD__, $o, $params);
    }

    // event handler: onEvict
    public function onEvict($o, $params = null) {
        $this->_inc(__METHOD__, $o, $params);
    }

    // event handler: onPreDelete
    public function onPreDelete($o, $params = null) {
        $this->_inc(__METHOD__, $o, $params);
    }

    // event handler: onDeleteAll
    public function onDelete($o, $params = null) {
        $this->_inc(__METHOD__, $o, $params);
    }

    // event handler: onPreDeleteAll
    public function onPreDeleteAll($o, $params = null) {
        $this->_inc(__METHOD__, $o, $params);
    }

    // event handler: onDelete
    public function onDeleteAll($o, $params = null) {
        $this->_inc(__METHOD__, $o, $params);
    }

    /**
     * Increment event counts
     * @param string $method (value of __METHOD__ from the caller method)
     * @param epObject|string $oc either the involved object or class
     * @param mixed $params 
     * @return void
     * @access protected
     */
    protected function _inc($method, $oc, $params = null) {
        
        // rip off 'eptListener::' in method name
        $method = str_replace(__CLASS__ . '::', '', $method);

        // check if counter for method (event) exists
        if (!isset(self::$counts[$method])) {
            self::$counts[$method] = 0;
        }

        // increase counter by 1
        self::$counts[$method] ++;

        // return the nubmer of calls to the event handler
        return self::$counts[$method];
    }

}

/**
 * The maximum number of objects to tests
 */
define('MAX_OBJECTS', 10);

/**
 * The unit test class for event listener in {@link epManager}  
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 485 $ $Date: 2005-08-30 17:26:36 -0400 (Tue, 30 Aug 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 */
class epTestListeners extends epTestRuntime {
    
    /**
     * Override epTestRuntime::_setUp() to set validate_after_compile
     */
    function _setUp($dbal, $db) {
        
        // call parent to setup manager
        parent::_setUp($dbal, $db);
        
        // set validate_after_compile to true
        $this->assertTrue($this->m);
        $this->m->setConfigOption('validate_after_compile', true);
    }

    /**
     * Test {@link epManager::register()} and {@link epManager::unregister()}
     * on global listeners
     */
    function testRegisterListeners_g() {
        
        // setup manager
        $this->_setUp('adodb', 'sqlite');
        $this->assertTrue($this->m);
        
        // register a null listener (exception is thrown)
        try {
            $this->assertFalse($this->m->register(null));
        }
        catch(epException $e) {
            $this->assertTrue($e);
        }

        // register an empty listener (class) (exception is thrown)
        try {
            $this->assertFalse($this->m->register('eptListenerEmpty'));
        }
        catch(epException $e) {
            $this->assertTrue($e);
        }
        
        // register an empty (thus invalid) listener instance (exception is thrown)
        $this->assertTrue($l = new eptListenerEmpty);
        try {
            $this->assertFalse($this->m->register($l));
        }
        catch(epException $e) {
            $this->assertTrue($e);
        }

        // register a valid listener class
        $this->assertTrue($this->m->register('eptListener'));

        // register a valid listener instance
        $this->assertTrue($l = new eptListener);
        $this->assertTrue($this->m->register($l));

        // unregister a listner class (returns 2 instances unregistered)
        $this->assertTrue($this->m->unregister('eptListener') == 2);
    }

    /**
     * Test {@link epManager::register()} and {@link epManager::unregister()}
     * on local listeners
     */
    function testRegisterListeners_l() {
        
        // setup manager
        $this->_setup('adodb', 'sqlite');
        $this->assertTrue($this->m);

        // register a null listener (exception is thrown)
        try {
            $this->assertFalse($this->m->register(null));
        }
        catch(epException $e) {
            $this->assertTrue($e);
        }

        // register a valid listener class
        $this->assertTrue($this->m->register('eptBook'));
        
        // register a valid listener class again. (no addition)
        $this->assertTrue($this->m->register('eptBook'));

        // unregister a listner class (returns 2 instances unregistered)
        $this->assertTrue($this->m->unregister('eptBook') == 1);
    }

    /**
     * Test event dispatch in {@link epManager} to global listeners
     */
    function testEventDispatch_g() {
        
        // setup manager
        $this->_setUp('adodb', 'sqlite');
        $this->assertTrue($this->m);

        // use manager to create one object
        include_once(EP_TESTS.'/classes/bookstore/src/eptBook.php');
        
        // register a valid listener class
        $this->assertTrue($this->m->register('eptListener'));

        // remove all books
        $this->m->deleteAll('eptBook');

        // events (onPreDeleteAll, onDelete) should have fired
        $this->assertTrue(eptListener::$counts['onPreDeleteAll'] == 1);
        $this->assertTrue(eptListener::$counts['onDeleteAll'] == 1);

        // test object creation
        $this->assertTrue($o = $this->m->create('eptBook', $title = md5('eptBook')));

        // events (onPreEvict, onEvict) should have fired
        $this->assertTrue(eptListener::$counts['onPreCreate'] == 1);
        $this->assertTrue(eptListener::$counts['onCreate'] == 1);

        // insert book into db
        $this->assertTrue($o->commit());
        $this->assertTrue($oid = $o->epGetObjectId());

        // events (onPreInsert, onInsert) should have fired
        $this->assertTrue(eptListener::$counts['onPreInsert'] == 1);
        $this->assertTrue(eptListener::$counts['onInsert'] == 1);

        // evict book into db
        $this->assertTrue($this->m->evict($o));

        // events (onPreEvict, onEvict) should have fired
        $this->assertTrue(eptListener::$counts['onPreEvict'] == 1);
        $this->assertTrue(eptListener::$counts['onEvict'] == 1);

        // reload book into db
        $this->assertTrue($o = $this->m->get('eptBook', $oid));

        // events (onPreLoad, onLoad) should have fired
        $this->assertTrue(eptListener::$counts['onPreLoad'] == 1);
        $this->assertTrue(eptListener::$counts['onLoad'] == 1);

        // reload the same object won't increase onLoad
        $this->assertTrue($o = $this->m->get('eptBook', $oid));
        
        // events (onPreLoad) should have fired, but no onLoad
        $this->assertTrue(eptListener::$counts['onPreLoad'] == 2);
        $this->assertTrue(eptListener::$counts['onLoad'] == 1);

        // update object
        $o->title = md5($o->title);
        $this->assertTrue($o->commit());

        // events (onPreUpdate, onUpdate) should have fired
        $this->assertTrue(eptListener::$counts['onPreUpdate'] == 1);
        $this->assertTrue(eptListener::$counts['onUpdate'] == 1);

        // delete object
        $this->assertTrue($o->delete());

        // events (onPreDelete, onDelete) should have fired
        $this->assertTrue(eptListener::$counts['onPreDelete'] == 1);
        $this->assertTrue(eptListener::$counts['onDelete'] == 1);

        // unregister a listner class (returns 1 instances unregistered)
        $this->assertTrue($this->m->unregister('eptListener') == 1);
    }

    /**
     * Test event dispatch in {@link epManager} to local listeners
     */
    function testEventDispatch_l() {
        
        // setup manager
        $this->_setUp('adodb', 'sqlite');
        $this->assertTrue($this->m);

        // register a valid listener class
        $this->assertTrue($this->m->register('eptBook'));

        // remove all books
        $this->m->deleteAll('eptBook');

        // events (onPreDeleteAll, onDelete) should have fired
        $this->assertTrue(eptBook::$counts['onPreDeleteAll'] == 1);
        $this->assertTrue(eptBook::$counts['onDeleteAll'] == 1);

        // test object creation, persisting, and eviction
        $oids = array();
        for($i = 0; $i < MAX_OBJECTS; $i ++) {
            
            // create
            $this->assertTrue($o = $this->m->create('eptBook', $title = md5('eptBook' . $i)));

            // events (onPreEvict, onEvict) should have fired
            $this->assertTrue(eptBook::$counts['onPreCreate'] == $i + 1);
            $this->assertTrue(eptBook::$counts['onCreate'] == $i + 1);
            
            // insert object into db
            $this->assertTrue($o->commit());
            $this->assertTrue($oid = $o->epGetObjectId());
            
            // put object into array
            $oids[] = $oid;

            // events (onPreInsert, onInsert) should have fired
            $this->assertTrue(eptBook::$counts['onPreInsert'] == $i + 1);
            $this->assertTrue(eptBook::$counts['onInsert'] == $i + 1);

            // evict book into db
            $this->assertTrue($this->m->evict($o));

            // events (onPreEvict, onEvict) should have fired
            $this->assertTrue(eptBook::$counts['onPreEvict'] == $i + 1);
            $this->assertTrue(eptBook::$counts['onEvict'] == $i + 1);
        }
        
        // test object loading, updating, and deletion
        for($i = 0; $i < MAX_OBJECTS; $i ++) {
            
            // get oid
            $oid = $oids[$i];

            // reload book into db
            $this->assertTrue($o = $this->m->get('eptBook', $oid));

            // events (onPreLoad, onLoad) should have fired
            $this->assertTrue(eptBook::$counts['onPreLoad'] == $i * 2 + 1);
            $this->assertTrue(eptBook::$counts['onLoad'] == $i + 1);

            // reload the same object won't increase onLoad
            $this->assertTrue($o = $this->m->get('eptBook', $oid));

            // events (onPreLoad) should have fired, but no onLoad
            $this->assertTrue(eptBook::$counts['onPreLoad'] == $i * 2 + 2);
            $this->assertTrue(eptBook::$counts['onLoad'] == $i + 1);

            // update object
            $o->title = md5($o->title);
            $this->assertTrue($o->commit());

            // events (onPreUpdate, onUpdate) should have fired
            $this->assertTrue(eptBook::$counts['onPreUpdate'] == $i + 1);
            $this->assertTrue(eptBook::$counts['onUpdate'] == $i + 1);

            // delete object
            $this->assertTrue($o->delete());

            // events (onPreDelete, onDelete) should have fired
            $this->assertTrue(eptBook::$counts['onPreDelete'] == $i + 1);
            $this->assertTrue(eptBook::$counts['onDelete'] == $i + 1);
        }

        // unregister a listner class (returns 1 instances unregistered)
        $this->assertTrue($this->m->unregister('eptBook') == 1);
    }

}

if (!defined('EP_GROUP_TEST')) {
    
    $tm = microtime(true);
    
    $t = new epTestListeners;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
    
    $elapsed = microtime(true) - $tm;
    
    echo epNewLine() . 'Time elapsed: ' . $elapsed . ' seconds' . "\n";
}

?>
