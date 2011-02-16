<?php

/**
 * $Id: epCache.php 872 2006-03-22 14:05:54Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author Trevan Richins <developer@ckiweb.com>
 * @version $Revision: 872 $ $Date: 2006-03-22 09:05:54 -0500 (Wed, 22 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.cache
 */

/**
 * The interface of EZPDO cache
 * 
 * To plug in a third-party external cache, all you need to do is
 * to write a wrapper around that cache that implements this interface 
 * and register it to the EZPDO runtime manager ({@link epManager}):
 * <code>
 *   // get manager
 *   $m = epManager::instance();
 *   // plug in cache
 *   $m->setCache($cache);
 * </code>
 * 
 * So far we have three built-in cache wrappers for
 * + APC: {@link epCacheApc}
 * + Cache_Lite: {@link epCachelite}
 * + Memcache: {@link epCacheMemcache}
 * 
 * You can use the above classes as examples if you'd want to write
 * your own wrapper.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 872 $ $Date: 2006-03-22 09:05:54 -0500 (Wed, 22 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.cache
 */
interface epCache {

    /**
     * Retrieves a value stored in cache by the given key
     * @param string $key The key used to retreive value
     * @return null|mixed (null if not key not found)
     */
    public function get($key);

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
     * @param false|integer $ttl The time-to-live in seconds
     */
    public function set($key, $value, $ttl = false);

    /**
     * Explicitly deletes (empties) values stored for the given key
     * @param string $key The key used to delete value
     * @return boolean
     */
    public function delete($key);

    /**
     * Clears all cached data
     * @return boolean
     */
    public function clear();
}

/**
 * Need epConfigurableWithLog as the superclass for epCacheObject
 */
if (!class_exists('epConfigurableWithLog')) {
    include_once(EP_SRC_BASE . '/epConfigurableWithLog.php');
}

/**
 * Exception class for {@link epCacheObject}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 872 $ $Date: 2006-03-22 09:05:54 -0500 (Wed, 22 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.cache
 */
class epExceptionCacheObject extends epException {
}

/**
 * Class of EZPDO object cache
 * 
 * This class delegates EZPDO object caching tasks to the underlying 
 * caching mechanism, which implements the {@link epCache} interface.
 * 
 * One note on the locking mechanism. Since caching mechnisms such as
 * memcache strives to implement non-blocking read, read lock is not
 * used in {@link epCacheObject} so far as it may offset the benefit
 * of caching, although read lock is also implemented in the class.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author Trevan Richins <developer@ckiweb.com>
 * @version $Revision: 872 $ $Date: 2006-03-22 09:05:54 -0500 (Wed, 22 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.cache
 */
class epCacheObject extends epConfigurableWithLog {

    /**#@+
     * Constants for locking 
     * Should be binary exclusive. So one can have (Lock_READ | LOCK_WRITE).
     */
    const LOCK_READ  = 1;
    const LOCK_WRITE = 2;
    /**#@-*/

    /**
     * List of builtin caches (all lowercases) and their parameters
     * in configuration options
     * @var array
     */
    static protected $builtin_caches = array(
        
        "apc" => array(
            "cache_ttl"
            ), 
        
        "cachelite" => array(
            "cache_dir", 
            "cache_ttl"
            ), 
        
        "memcache" => array(
            "cache_server", 
            "cache_port", 
            "cache_compress", 
            "cache_ttl"
            ), 
        );

    /**
     * The cached EZPDO runtime manager
     * @var epManager
     */
    static protected $em = false;

    /**
     * The cache plugged in
     * @var epCache
     */
    protected $cache = false;

    /**
     * Constructor
     * @param string|epCache & $cache The cache to be plugged in
     * @param array|epConfig $config The configuration 
     */
    public function __construct($cache, $config = null) {

        // call parent to set configuration
        parent::__construct($config);
        
        // is input a string?
        if (is_string($cache)) {
            $this->cache = & $this->_cache();
        }
        
        // or is it an epCache?
        else if ($cache instanceof epCache) {
            $this->cache = & $cache;
        }
        
        // unrecognized parameter
        else {
            throw new epExceptionCacheObject("Unrecognized parameter");
        }

        // get the runtime manager
        self::$em = epManager::instance();
    }

    /**
     * Implement abstract method {@link epConfigurable::defConfig()}
     * @access public
     */
    public function defConfig() {
        return array_merge(
            parent::defConfig(), 
            array(
                "cache_server" => "localhost", // Default cache server 
                "cache_port" => 11211, // Default port number 
                "cache_compress" => true, // Whether to turn on compression (default to true)
                "cache_ttl" => 360, // Default time-to-live: 360 seconds
                "cache_dir" => '/tmp', // Default cache directory
                )
            );
    }

    /**
     * Cache an object
     * @param epObject $o The object to be added into cache
     * @param boolean $force Whether to force replace object in cache
     * @return boolean
     */
    public function add(epObject &$o, $force = false) {
        
        // don't cache objects that haven't been committed
        if (!($oid = $o->epGetObjectId())) {
            return false;
        }

        // get class
        if (!($class = $o->epGetClass())) {
            return false;
        }
        
        // generate key
        $key = $this->_key($class);

        // get all objects for the class
        if (!($os = $this->cache->get($key))) {
            $os = array();
        }
        
        // done if not forced -and- oid already in cache
        if (!$force && in_array($o->oid, array_keys($os))) {
            return true;
        } 
        
        // put object into array
        $os[$o->oid] = $o;

        // is class write locked?
        if ($this->isLocked($class, self::LOCK_WRITE)) {
            return false;
        }

        // lock write 
        $this->_lock($class, self::LOCK_WRITE);

        // cache array 
        $status = $this->cache->set($key, $os);
        
        // unlock write 
        $this->_unlock($class, self::LOCK_WRITE);

        return $status;
    }
    
    /**
     * Retrieves an object from cache by oid or all objects of a class
     * if oid is not specified.
     * 
     * @param string $class The class name 
     * @param integer $oid The object id for the object to be retrieved
     * @return false|epObject|array
     */
    public function get($class, $oid = false) {
        
        // sanity check
        if (!$class) {
            return false;
        }
    
        // generate key
        $key = $this->_key($class);

        // retreive objects from cache
        if (!($os = $this->cache->get($key))) {
            return false;
        }

        // double checking: must be array
        if (!is_array($os)) {
            return false;
        }

        // if no oid specified
        if (!$oid) {
            // return all objects
            return $os;
        }

        // o.w. return object with oid
        return $os[$oid];
    }
    
    /**
     * Removes an object by class and oid. If oid is not given, remove
     * all objects of a class.
     * 
     * @param string $class The class name 
     * @param integer $oid The object id for the object to be retrieved
     * @return boolean 
     */
    public function delete($class, $oid = false) {
        
        // sanity check
        if (!$class) {
            return false;
        }
    
        // generate key
        $key = $this->_key($class);

        // is class read locked?
        if ($this->isLocked($class, self::LOCK_WRITE)) {
            return false;
        }

        // is oid specified?
        if (!$oid) {

            // lock write 
            $this->_lock($class, self::LOCK_WRITE);

            // remove all objects in class
            $status = $this->cached->remove($key);

            // unlock write 
            $this->_unlock($class, self::LOCK_WRITE);

            return $status;
        }

        // retrieve objects from cache
        if (!($os = $this->cache->get($key))) {
            return true;
        }

        // unset oid in array
        unset($os[$oid]);

        // lock write 
        $this->_lock($class, self::LOCK_WRITE);

        // cache the array again
        $status = $this->cache->set($key, $os);

        // unlock write 
        $this->_unlock($class, self::LOCK_WRITE);

        return $status;
    }

    /**
     * Checks if a class is locked
     * @param string $class The class name
     * @param integer $type The lock type 
     * @return boolean
     */
    protected function _isLocked($class, $type) {
    
        $locked = true;

        // lock read?
        if ($type & self::LOCK_READ) {
            $key = $this->_key($class, 'lock' . self::LOCK_READ);
            if ($key != $this->cache->get($key)) {
                $locked = false;
            }
        }

        // lock write?
        if ($type & self::LOCK_WRITE) {
            $key = $this->_key($class, 'lock' . self::LOCK_WRITE);
            if ($key != $this->cache->get($key)) {
                $locked = false;
            }
        }

        return $locked;
    }

    /**
     * Locks the cache for a class
     * @param string $class The class name
     * @param integer $type The lock type (LOCK_READ, LOCK_WRITE)
     * @return boolean
     */
    protected function _lock($class, $type) {
        
        // return status
        $status = true;
        
        // lock read?
        if ($type & self::LOCK_READ) {
            $key = $this->_key($class, 'lock' . self::LOCK_READ);
            $status &= $this->cache->set($key, $key); // key as value
        }

        // lock write?
        if ($type & self::LOCK_WRITE) {
            $key = $this->_key($class, 'lock' . self::LOCK_WRITE);
            $status &= $this->cache->set($key, $key); // key as value
        }

        return $status;
    }

    /**
     * Unlocks the cache for a class
     * @param string $class The class name
     * @return boolean
     */
    protected function _unlock($class, $type) {
        
        // return status
        $status = true;
        
        // lock read?
        if ($type & self::LOCK_READ) {
            $key = $this->_key($class, 'lock' . self::LOCK_READ);
            $status &= $this->cache->remove($key); // key as value
        }

        // lock write?
        if ($type & self::LOCK_WRITE) {
            $key = $this->_key($class, 'lock' . self::LOCK_WRITE);
            $status &= $this->cache->remove($key); // key as value
        }

        return $status;
    }

    /**
     * Makes a cache key for a class 
     * 
     * Since EZPDO allows a user to change database (DSN) at runtime (see 
     * {@link epManager::setDsn()}), it is important to use the current 
     * DSN of the class in generating the cache key.
     * 
     * @param string $class The class name
     * @param string $extra The extra string (mostly for locking type)
     * @return false|string
     */
    protected function _key($class, $extra = '') {
        
        if (!($cm = & self::$em->getClassMap($class))) {
            return false;
        }

        if (!($dsn = $cm->getDsn())) {
            return false;
        }

        return md5($dsn . $class . $extra);
    }

    /**
     * Instantiates built-in cache by name
     * @param string $cache 
     * @return false|epCache
     * @throws epExceptionCacheObject
     */
    protected function & _getCache($name = 'apc') {
        
        // normalize cache name
        $name = strtolower($name);
        
        // check if it is one of the built-in caches
        if (!in_array($name, array_keys(self::$builtin_caches))) {
            throw new epExceptionCacheObject("Unrecognized built-in cache: $cache");
            return false;
        }
        
        // collect all arguments for cache constructor
        $args = array();
        foreach(self::$builtin_caches[$name] as $option) {
            $args[] = $this->getConfigOption($option);
        }

        // class name for the built-in cache
        $class = epCache . ucfirst($name);

        // include the class source file
        include_once(EP_SRC_CACHE . '/' . $class . '.php');

        // instantiate it
        return epNewObject($class, $args);
    }

}

?>
