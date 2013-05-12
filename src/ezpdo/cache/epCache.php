<?php

/**
 * $Id: epCache.php 872 2006-03-22 14:05:54Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author Trevan Richins <developer@ckiweb.com>
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision: 872 $ $Date: 2006-03-22 09:05:54 -0500 (Wed, 22 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.cache
 */
namespace ezpdo\cache;

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
