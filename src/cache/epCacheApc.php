<?php

/**
 * $Id: epCacheApc.php 872 2006-03-22 14:05:54Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author Trevan Richins <developer@ckiweb.com>
 * @version $Revision: 872 $ $Date: 2006-03-22 09:05:54 -0500 (Wed, 22 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.cache
 */

// Needs epCache interface
include_once(EP_SRC_CACHE . '/epCache.php');

/**
 * Exception class for {@link epCacheApc}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 872 $ $Date: 2006-03-22 09:05:54 -0500 (Wed, 22 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.cache
 */
class epExceptionCacheApc extends epException {
}

/**
 * Class of APC client 
 * 
 * Implementation of the {@link epCache} interface so APC
 * can be easily plugged into EZPDO
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 872 $ $Date: 2006-03-22 09:05:54 -0500 (Wed, 22 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.cache
 * @see http://www.php.net/manual/en/ref.apc.php
 */
class epCacheApc implements epCache {

    /**
     * Time to live (in seconds)
     * @var integer
     */
    protected $ttl = 360;

    /**
     * Constructor
     * @param string $server The memcache server
     * @param integer $port The port number
     * @param boolean $compress Whether to compress value or not
     * @param integer $ttl Time-to-live in seconds
     */
    public function __construct($ttl = 360) {
        
        // check if apc is installed
        if (!function_exists("apc_fetch") 
            || !function_exists("apc_store") 
            || !function_exists("apc_delete")) {
            throw new epExceptionCacheApc("APC extension is not installed.");
        }

        $this->ttl = $ttl;
    }

    /**
     * Retrieves a value stored in cache by the given key
     * @param string $key The key used to retreive value
     * @return null|mixed (null if not key not found)
     */
    public function get($key) {
        return apc_fetch($key);
    }

    /**
     * Stores a variable into the cache with a key. 
     * 
     * A time-to-live (TTL) parameter (in seconds) can also be passed 
     * so that the stored value will be removed from the cache if TTL 
     * has expired. This is normally done on the next request but the 
     * actual behavior may depend on the cache implementation. 
     * 
     * @param string $key The key used to store the value
     * @param mixed $value The value (variable) to be cached
     * @param integer $ttl The time-to-live in seconds
     */
    public function set($key, $value, $ttl = false) {
        return apc_store($key, $value, $ttl === false ? $this->ttl : $ttl);
    }

    /**
     * Explicitly deletes (empties) values stored for the given key
     * @param string $key The key used to delete value
     * @return boolean
     */
    public function delete($key) {
        return apc_delete($key);
    }

    /**
     * Clears all cached data
     * @return boolean
     */
    public function clear() {
        // For some reason if no parameters passed in, it won't clean 
        // up the cache that apc_fetch(), apc_store(), and apc_delete() 
        // interact with. This is a workaround suggested by a user note
        // on the manual page.
        return apc_clear_cache('user');
    }
}

?>
