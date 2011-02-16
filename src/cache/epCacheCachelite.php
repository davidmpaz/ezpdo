<?php

/**
 * $Id: epCacheCachelite.php 872 2006-03-22 14:05:54Z nauhygon $
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
 * Exception class for {@link epCacheCacheLite}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 872 $ $Date: 2006-03-22 09:05:54 -0500 (Wed, 22 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.cache
 */
class epExceptionCacheCacheLite extends epException {
}

/**
 * Class of CacheLite client 
 * 
 * Implementation of the {@link epCache} interface so CacheLite
 * can be easily plugged into EZPDO
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 872 $ $Date: 2006-03-22 09:05:54 -0500 (Wed, 22 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.cache
 * @see http://pear.php.net/package/Cache_Lite
 */
class epCacheCacheLite implements epCache {

    /**
     * The cache group used by EZPDO
     * @var string  
     */
    static protected $group = 'ezpdo';

    /**
     * The Cache_Lite instance
     * @var Cache_Lite
     */
    protected $cache = false;

    /**
     * Constructor
     * @param string $cacheDir The cache diectory
     * @param integer $ttl Time-to-live in seconds
     */
    public function __construct($cache_dir = '/tmp/', $ttl = 360) {
        
        // include CacheLite (assuming in include paths)
        include_once('Cache/Lite.php');
        
        // options
        $options = array(
            "automaticSerialization" => true,
            "cacheDir" => $cache_dir . '/',
            "lifeTime" => $ttl, 
            );
        
        // instantiate Cache_Lite
        if (!($this->cache = new Cache_Lite($options))) {
            throw new epExceptionCacheCacheLite(
                "Cannot intantiate Cache_Lite. Make sure Cache_Lite is installed properly"
                );
        }
    }

    /**
     * Retrieves a value stored in cache by the given key
     * @param string $key The key used to retreive value
     * @return null|mixed (null if not key not found)
     */
    public function get($key) {
        return $this->cache->get($key, self::$group);
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
        return $this->cache->save($value, $key, self::$group);
    }

    /**
     * Explicitly deletes (empties) values stored for the given key
     * @param string $key The key used to delete value
     * @return boolean
     */
    public function delete($key) {
        return $this->cache->remove($key, self::$group);
    }

    /**
     * Clears all cached data
     * @return boolean
     */
    public function clear() {
        return $this->cache->clean(self::$group);
    }
}   

?>
