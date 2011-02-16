<?php

/**
 * $Id: epTestCache.php 873 2006-03-22 14:06:48Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 873 $ $Date: 2006-03-22 09:06:48 -0500 (Wed, 22 Mar 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.cache
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/../src/epTestCase.php');

/**
 * need ezpdo utils
 */
include_once(EP_SRC_BASE.'/epUtils.php');

/**
 * Unit test class for {@link epClassMapFactory}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 873 $ $Date: 2006-03-22 09:06:48 -0500 (Wed, 22 Mar 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.cache
 */
class epTestCache extends epTestCase {

    /**
     * Common testing for epCache
     */
    function _testCache(epCache &$cache) {
        
        echo "  testing " . get_class($cache) . "... ";
        
        $status = true;

        // 
        // - Round 1 -
        // 

        // prepare data
        $kv = array();
        for($i = 0; $i < 50; $i ++) {
            $k = uniqid();
            $v = str_repeat((string)rand(0, 9), 1000);
            $kv[$k] = $v;
        }

        // set
        foreach($kv as $k => $v) {
            $cache->set($k, $v);
        }

        // get
        $kv2 = array();
        foreach(array_keys($kv) as $k) {
            $kv2[$k] = $cache->get($k);
        }

        // check
        foreach(array_keys($kv) as $k) {
            $this->assertTrue($r = ($kv[$k] == $kv2[$k]));
            if (!$r) {
                $status = false;
            }
        }

        // delete
        foreach($kv as $k => $v) {
            $this->assertTrue($r = $cache->delete($k));
            if (!$r) {
                $status = false;
            }
        }

        // get again
        foreach(array_keys($kv) as $k) {
            $this->assertFalse($r = $cache->get($k));
            if ($r) {
                $status = false;
            }
        }

        // 
        // - Round 2 -
        // 

        // refill again (set)
        foreach($kv as $k => $v) {
            $cache->set($k, $v);
        }

        // get
        $kv2 = array();
        foreach(array_keys($kv) as $k) {
            $kv2[$k] = $cache->get($k);
        }

        // check
        foreach(array_keys($kv) as $k) {
            $this->assertTrue($r = ($kv[$k] == $kv2[$k]));
            if (!$r) {
                $status = false;
            }
        }

        // instead of delete(), now use clear()
        $cache->clear();

        // get again
        foreach(array_keys($kv) as $k) {
            $this->assertFalse($r = $cache->get($k));
            if ($r) {
                $status = false;
            }
        }

        echo "done\n";

        return $status;
    }

    /**
     * Test APC wrapper {@link epCacheApc}
     * 
     * Prepare APC:
     * 
     * 1. Download and install APC from http://pecl.php.net/package/APC
     * 2. Enable APC in php.ini by adding: 
     *    [apc]
     *    extension = apc.so
     *    apc.enabled = 1
     *    apc.enable_cli = 1 ; !!!important for CLI testing!!!
     */
    function testApc() {
        
        // apc params
        $ttl = 360;
        
        // instantiate cache
        include_once(EP_SRC_CACHE . '/epCacheApc.php');
        try {
            $this->assertTrue($cache = new epCacheApc($ttl));
            $this->assertTrue($this->_testCache($cache));
        }
        catch(Exception $e) {
        }
    }
    
    /**
     * Test Cache_Lite wrapper {@link epCacheCachelite}
     * 
     * Prepare Cache_Lite:
     *   Upgrade or install Cache_Lite through PEAR
     *   $ sudo pear install Cache_Lite
     *   $ sudo pear upgrade Cache_Lite
     */
    function testCachelite() {
        
        // cachelite params
        $cache_dir = dirname(__FILE__) . '/tmp/';
        $ttl = 360;
        
        epMkDir($cache_dir);

        // instantiate cache
        include_once(EP_SRC_CACHE . '/epCacheCachelite.php');
        try {
            $this->assertTrue($cache = new epCacheCachelite($cache_dir, $ttl));
            $this->assertTrue($this->_testCache($cache));
        }
        catch(Exception $e) {
        }
        
        epRmDir($cache_dir);
    }
    
    /**
     * Test memcache wrapper {@link epCacheMemcache}
     * 
     * Prepare Memcache: 
     * 
     * 1. download 
     * 
     *   1.a libevent (http://www.monkey.org/~provos/libevent/)
     *   1.b memcached (http://www.danga.com/memcached/)
     *   1.c memcache (php extenstion) (http://pecl.php.net/package/memcache)
     * 
     *   (note libevent is required by memcached.)
     * 
     * 2. install 
     * 
     *   2.a. type commands under both libevent & memcached package dirs
     *        ./configure ; make ; sudo make install
     * 
     *   2.b. install memcached php extension
     *        $ phpsize ; ./configure ; make ; sudo make install
     * 
     *   2.c. enable memcache in php.ini by adding a line
     *        extension = memcache.so      
     *  
     * 3. run memcache
     * 
     *   $ sudo memcached -u nobody -d -p 12345 -l 127.0.0.1 -m 128 -vv
     *   
     *   (note: -u assume user when run by root; 
     *          -d run as a daemon
     *          -p port number
     *          -l which address to listen to
     *          -m memory size in megabytes
     *          -v verbose
     *          -vv very verbose)
     */
    function testMemcache() {
        
        // memcache parameters
        //$server = '127.0.0.1';
        $server = 'localhost';
        $port = 12345;
        $compress = true;

        include_once(EP_SRC_CACHE . '/epCacheMemcache.php');

        // instantiate cache
        try {
            $this->assertTrue($cache = new epCacheMemcache($server, $port, $compress));
            $this->assertTrue($this->_testCache($cache));
        }
        catch(Exception $e) {
        }
    }
    
}

if (!defined('EP_GROUP_TEST')) {
    $t = new epTestCache;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
}

?>
