<?php

/**
 * $Id: epCacheMemcache.php 872 2006-03-22 14:05:54Z nauhygon $
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
 * Exception class for {@link epCacheMemcache}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 872 $ $Date: 2006-03-22 09:05:54 -0500 (Wed, 22 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.cache
 */
class epExceptionCacheMemcache extends epException {
}

/**
 * Class of memcache client 
 * 
 * Implementation of the {@link epCache} interface so memcache
 * can be easily plugged into EZPDO
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 872 $ $Date: 2006-03-22 09:05:54 -0500 (Wed, 22 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.cache
 * @see http://www.php.net/manual/en/ref.memcache.php
 */
class epCacheMemcache implements epCache {

    /**
     * The memcache connection
     * @var Memcache
     */
    protected $con = false;
    
    /**
     * Wether to compress value or not (uses zlib)
     * @var boolean
     */
    protected $compress = true;

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
    public function __construct($server, $port, $compress = true, $ttl = 360) {
        
        // check if Memcache exists
        if (!class_exists('Memcache')) {
            throw new epExceptionCacheMemcache(
                "Class Memcache dose not exist. Install memcache extension."
                );
        }

        // instantiate Memcache
        $this->conx = new Memcache;

        // connect to cache server
        if (!($this->conx->connect($server, $port))) {
            throw new epExceptionCacheMemcache(
                "Cannot connect to memcache server/port. Check config."
                );
        }

        // set compression
        $this->compress = $compress;

        // set time-to-live
        $this->ttl = $ttl;
    }

    /**
     * Destructor (closes connection)
     */
    public function __destruct() {
        if ($this->conx) {
            $this->conx->close();
        }
    }

    /**
     * Retrieves a value stored in cache by the given key
     * @param string $key The key used to retreive value
     * @return null|mixed (null if not key not found)
     */
    public function get($key) {
        return $this->conx->get($key);
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
        return $this->conx->set($key, $value, $ttl === false ? $this->ttl : $ttl);
    }

    /**
     * Explicitly deletes (empties) values stored for the given key
     * @param string $key The key used to delete value
     * @return boolean
     */
    public function delete($key) {
        return $this->conx->delete($key);
    }

    /**
     * Clears all cached data
     * @return boolean
     */
    public function clear() {
        return $this->conx->flush();
    }
}

?>
