<?php

/**
 * $Id: epArray.php 1050 2007-06-19 10:54:09Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Moises Paz <davidmpaz@gmail.com>
 * @version $Revision: 1050 $ $Date: 2007-06-19 06:54:09 -0400 (Tue, 19 Jun 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
namespace ezpdo\runtime;

use ezpdo\base\epUtils;
use ezpdo\orm\epFieldMapRelationship;

/**
 * Array sort order flag: asending
 */
if (!defined(SORT_ASC)) {
    define(SORT_ASC, 0);
}

/**
 * Array sort order flag: descending
 */
if (!defined(SORT_DESC)) {
    define(SORT_DESC, 1);
}

/**
 * Class of an EZPDO array
 *
 * This class is to model the many-valued relationship fields
 * ({@link epFieldMapRelationship}) in an epObject.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1050 $ $Date: 2007-06-19 06:54:09 -0400 (Tue, 19 Jun 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epArray implements \IteratorAggregate, \ArrayAccess, \Countable {

    /**
     * The internal array
     * @var false|array (false if array is not loaded)
     */
    protected $array = false;

    /**
     * Copy of internal array at first load
     * Used to decide what relations should get saved or deleted
     * @var array
     */
    protected $origArray = array();

    /**
     * The epObject this array is associated to
     * @var epObject
     */
    protected $o = false;

    /**
     * The field map for this array
     * @var epFieldMap
     */
    protected $fm = false;

    /**
     * Whether to mark the associated object dirty when array is changed
     * @var bool
     */
    protected $clean = false;

    /**
     * Whether to convert oid strings to actual objects
     * @var bool
     */
    protected $convert_oid = true;

    /**
     * Have UOIDs been loaded to the array yet?
     * @var bool
     */
    protected $loaded = false;

    /**
     * Constructor
     * @param epObject &$o The epObject this array is associated to
     * @param epFieldMapRelationship $fm The field map of the relationship
     */
    public function __construct(epObject &$o, epFieldMapRelationship &$fm) {
        $this->o = &$o;
        $this->fm = &$fm;
    }

    /**
     * Implements magic method __sleep()
     * Collapes field map to an array
     */
    public function __sleep() {
        // collapse field map if still an object
        if (is_object($this->fm)) {
            $this->fm = array(
                $this->fm->getClassMap()->getName(),
                $this->fm->getName()
                );
        }
        return array_keys(get_object_vars($this));
    }

    /**
     * Implements magic method __wakeup()
     * Inflates the collaped field map
     */
    public function __wakeup() {
        // inflate field map
        $this->fm = epManager::instance()->
            getClassMap($this->fm[0])->getField($this->fm[1]);
    }

    /**
     * Loads object ids (UOID) to array
     * @param bool $force Whether to force loading or not
     * @return bool
     */
    protected function _load($force = false) {

        // if loaded and no forcing
        if (!$force && $this->loaded) {
            // done
            return true;
        }

        // only need to initialize if array is not set
        if (is_array($this->array)) {
            $this->loaded = true;
            return true;
        }

        // initialize array with an empty array
        $this->array = array();

        // get manager through object
        if (!($em = $this->o->epGetManager())) {
            $this->loaded = true;
            return true;
        }

        // get all relationship ids
        if ($array = $em->getRelationIds($this->o, $this->fm, $this->o->epGetClassMap())) {
            $this->array = $array;
            $this->origArray = $array;
        }

        return ($this->loaded = true);
    }

    /**
     * Returns the original array
     * @return array
     * @access public
     */
    public function getOrigArray() {
        return $this->origArray;
    }

    /**
     * Sets the original array
     * @return array
     * @access public
     */
    public function setOrigArray($origArray) {
        $this->origArray = $origArray;
    }

    /**
     * Returns the associated object
     * @return epObject
     * @access public
     */
    public function &getObject() {
        return $this->o;
    }

    /**
     * Returns the base class name of the object contained in the array
     * @return string
     * @access public
     */
    public function getClass() {
        return $this->fm->getClass();
    }

    /**
     * Returns the field map
     * @return epFieldMap
     * @access public
     */
    public function getFieldMap() {
        return $this->fm;
    }

    /**
     * Returns whehter to flag object dirty
     * @return bool
     * @access public
     */
    public function getClean() {
        return $this->clean;
    }

    /**
     * Sets whether to flag the associated object dirty
     * @param bool $clean
     * @return void
     * @access public
     */
    public function setClean($clean = true) {
        $this->clean = $clean;
    }

    /**
     * Returns whether it is converting oids or not
     * @return bool
     * @access public
     */
    public function getConvertOid() {
        return $this->convert_oid;
    }

    /**
     * Set whether to convert oids
     * @param bool $convert_oid
     * @return void
     * @access public
     */
    public function setConvertOid($convert_oid = true) {
        $this->convert_oid = $convert_oid;
    }

    /**
     * Copies values from an array
     *
     * Note that this method is mostly used by {@link epObject} and does
     * <b>not</b> check whether UOIDs are loaded or not.
     *
     * @param array $from The source array
     * @param bool @clean Whether to notify changes or not
     * @return void
     * @access public
     */
    public function copy($from, $clean = true) {

        // backup clean flag
        $clean0 = $this->getClean();

        // set clean flag
        $this->setClean($clean);

        // remove all items before copying array
        $this->removeAll(false);

        // if so, add items one by one
        foreach($from as $v) {
            $this->offsetSet(null, $v);
        }

        // restore clean flag
        $this->setClean($clean0);
    }

    /**
     * Implements Countable::count().
     * Returns the number of vars in the object
     * @return integer
     */
    public function count() {

        // load UOIDs to array if not already
        $this->_load();
        if (!$this->array) {
            return 0;
        }

        // clean up deleted objects
        $this->_cleanUp();
        if (!$this->array) {
            return 0;
        }

        return count($this->array);
    }

    /**
     * Implements IteratorAggregate::getIterator()
     * Returns the iterator which is an ArrayIterator object connected to the array
     * @return ArrayIterator
     */
    public function getIterator() {

        // load UOIDs to array if not already
        $this->_load();

        // clean up deleted objects
        $this->_cleanUp();

        // convert uoid into objects before "foreach" is called
        if ($this->convert_oid) {
            // change all the UOIDs to objects
            $this->_uoids2Objs();
        }

        // return the array iterator
        return new \ArrayIterator($this->array);
    }

    /**
     * Implements ArrayAccess::offsetExists()
     * @return bool
     */
    public function offsetExists($index) {

        // load UOIDs to array if not already
        $this->_load();

        // clean up deleted objects
        $this->_cleanUp();

        return isset($this->array[$index]);
    }

    /**
     * Implements ArrayAccess::offsetGet()
     * @return mixed
     */
    public function offsetGet($index) {

        // load UOIDs to array if not already
        $this->_load();

        // clean up deleted objects
        $this->_cleanUp();

        // check if value of index is set
        if (!isset($this->array[$index])) {
            return null;
        }

        // convert uoid (if string) into object
        if ($this->convert_oid && is_string($this->array[$index])) {
            $this->array[$index] = $this->_uoid2Obj($this->array[$index]);
        }

        // return the object
        return $this->array[$index];
    }

    /**
     * Implements ArrayAccess::offsetSet()
     * @return void
     */
    public function offsetSet($index, $newval) {

        // load UOIDs to array if not already
        $this->_load();

        // clean up deleted objects
        $this->_cleanUp();

        // notify on pre change
        if ($this->o && !$this->clean) {
            $this->_notifyChange('onPreChange');
        }

        // set new value to index
        if (is_null($index)) {
            $this->array[] = & $newval;
            $this->_updateInverse($newval, epObject::INVERSE_ADD);
        }
        else {

            // existing index
            if (count($this->array) > $index) {
                // remove from inverse
                $oldval = $this->array[$index];
                $this->_updateInverse($oldval, epObject::INVERSE_REMOVE);
            }

            if (!$newval) {
                // unset item if empty new value
                unset($this->array[$index]);
            } else {
                $this->array[$index] = & $newval;
                $this->_updateInverse($newval, epObject::INVERSE_ADD);
            }
        }

        // notify the associated object that array has changed
        if ($this->o && !$this->clean) {
            $this->_notifyChange('onPostChange');
        }
    }

    /**
     * Implements ArrayAccess::offsetUnset()
     * @return mixed
     */
    public function offsetUnset($index) {

        // load UOIDs to array if not already
        $this->_load();

        // clean up deleted objects
        $this->_cleanUp();

        if (isset($this->array[$index])) {

            // notify on pre change
            if ($this->o && !$this->clean) {
                $this->_notifyChange('onPreChange');
            }

            // update inverse
            $this->_updateInverse($this->array[$index], epObject::INVERSE_REMOVE);

            // unset the index
            unset($this->array[$index]);

            // notify the associated object that array has changed
            if ($this->o && !$this->clean) {
                $this->_notifyChange('onPostChange');
            }
        }
    }

    /**
     * Removes an object (using UId as identifier)
     * @return void
     * @access protected
     */
    public function remove($o) {

        // load UOIDs to array if not already
        $this->_load();

        // done if no items
        if (!$this->array) {
            return false;
        }

        // get all objects before looping through
        $this->_uoids2Objs();

        // go through each object
        foreach($this as $k => $v) {

            // check if value is an epObject
            if (!($v instanceof epObject)) {
                continue;
            }

            // check if this is the object to be deleted
            if (/*$o->epGetUId() == $v->epGetUId()*/ $o->epGetObjectId() == $v->epGetObjectId()) {

                // notify on pre change
                if ($this->o && !$this->clean) {
                    $this->_notifyChange('onPreChange');
                }

                // update inverse
                $this->_updateInverse($v, epObject::INVERSE_REMOVE);
                unset($this->array[$k]);

                // notify the associated object that array has changed
                if ($this->o && !$this->clean) {
                    $this->_notifyChange('onPostChange');
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Removes all items
     * @param bool $load Whether to load UOIDs to the array
     * @return void
     * @access public
     */
    public function removeAll($load = true) {

        // do we need to initialize?
        if ($load) {
            $this->_load();
        }

        if ($this->array) {

            // notify on pre change
            if ($this->o && !$this->clean) {
                $this->_notifyChange('onPreChange');
            }

            // go through each object
            foreach($this as $k => $v) {
                // update inverse
                $this->_updateInverse($v, epObject::INVERSE_REMOVE);
            }

            // remove everything in array
            $this->array = array();

            // notify the associated object that array has changed
            if ($this->o && !$this->clean) {
                $this->_notifyChange('onPostChange');
            }
        }
    }

    /**
     * Checks if a value is in array
     * @param mixed $v
     * @param bool $strict Whether to do strict match (uid only checking)
     * @return bool
     * @access public
     */
    public function inArray($val, $strict = true) {

        // load uoids to array
        $this->_load();

        // clean up deleted objects
        $this->_cleanUp();

        // for non-object, do the usual matching
        if (!is_object($val)) {
            return in_array($val, $this->array);
        }

        // if value is an epObject
        if ($val instanceof epObject) {

            foreach($this->array as $k => $v) {

                // if $v is a string (uoid)
                if (is_string($v)) {
                    if ($v == $val->epGetManager()->encodeUoid($val)) {
                        return true;
                    }
                }

                // $v is an object
                else if ($v instanceof epObject) {

                    // check if UID matches
                    if ($v->epGetUId() == $val->epGetUId()) {
                        return true;
                    }

                    // if non-strict, then OID matching also counts
                    if (!$strict) {
                        if ($v->epGetObjectId() == $val->epGetObjectId()) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Converts the uoid
     * @param string $uoid (encoded object id)
     * @return epObject
     */
    protected function &_uoid2Obj($uoid) {

        // is this array associated with an object
        if (!$this->o) {
            return $uoid;
        }

        // get manager
        if (!($m = $this->o->epGetManager())) {
            return $uoid;
        }

        // call manager to get object by encoded id
        if ($o = $m->getByUoid($uoid)) {
            return $o;
        }

        // return encode oid if can't get object
        return $uoid;
    }

    /**
     * Converts all UOIDs in $this->array to objects.
     * @return bool
     */
    protected function _uoids2Objs() {

        // is this array associated with an object
        if (!$this->o) {
            return false;
        }

        // get manager
        if (!($m = $this->o->epGetManager())) {
            return false;
        }

        // save all the encode oids
        $uoids = array();
        foreach($this->array as $k => $v) {
            if (is_string($v)) {
                $uoids[$k] = $v;
            }
        }

        // call manager to get object by encoded id
        if (!($os = $m->getByUoids(array_values($uoids)))) {
            return false;
        }

        // match up the objects with the right index in the array
        // not going to work as duplicates can exist and array_flip
        // will destroy the duplicates
        // $uoid_keys = array_flip($uoids);

        foreach ($os as &$o) {
            $uoid = $m->encodeUoid($o);
            while (($index = array_search($uoid, $uoids)) !== FALSE) {
                //$this->array[$uoid_keys[$uoid]] = $o;
                $this->array[$index] = $o;
                // take it out so that any duplicates will be found
                unset($uoids[$index]);
            }
        }

        return true;
    }

    /**
     * Cleans up all deleted objects
     * @return void
     * @access protected
     */
    protected function _cleanUp() {

        // done if no items
        if (!$this->array) {
            return;
        }

        // go through each object
        foreach($this->array as $k => $v) {
            if (($v instanceof epObject) && $v->epIsDeleted()) {
                unset($this->array[$k]);
            }
        }
    }

    /**
     * Updates the value of the inverse var (always a one-way action)
     * @param epObject $o The opposite object
     * @param string $actoin The action to be taken: INVERSE_ADD or INVERSE_REMOVE
     * @return bool
     */
    protected function _updateInverse(&$o, $action = epObject::INVERSE_ADD) {

        // check if object is epObject
        if (!$o || !$this->fm || !($o instanceof epObject)) {
            return false;
        }

        // get inverse var
        if (!($ivar = $this->fm->getInverse())) {
            return true;
        }

        // no action if an object is updating (to prevent endless loop)
        if ($o->epIsUpdating($action)) {
            return true;
        }

        // set inverse updating flag
        $this->o->epSetUpdating(true, $action);
        $o->epSetUpdating(true, $action);

        // a single-valued field
        if (!$o->epIsMany($ivar)) {
            switch ($action) {
                case epObject::INVERSE_ADD:
                    $o[$ivar] = $this->o;
                    break;
                case epObject::INVERSE_REMOVE:
                    $o[$ivar] = null;
                    break;
            }
        }

        // a many-valued field
        else {
            switch ($action) {
                case epObject::INVERSE_ADD:
                    $o[$ivar][] = $this->o;
                    break;
                case epObject::INVERSE_REMOVE:
                    $o[$ivar]->remove($this->o);
                    break;
            }
        }

        // reset inverse updating flag
        $o->epSetUpdating(false, $action);
        $this->o->epSetUpdating(false, $action);

        return true;
    }

    /**
     * Notify the object change has been made to the var this array is associtated to
     * @param string $event (either 'onPreChange' or 'onPostChange')
     * @return bool
     */
    protected function _notifyChange($event) {

        // get var name
        $var = '';
        if ($this->fm) {
            $var = $this->fm->getName();
        }

        // call object to notify the manager this change
        return $this->o->epNotifyChange($event, array($var => ''));
    }

    /**
     * Sort array by keys and orders.
     *
     * The arguments should be given in (key, order) pairs,
     * in which key is a path expression and order is either
     * SORT_ASC or SORT_DESC.
     *
     * The key can be a primitive var, for example, "name", or a
     * path for a relationship var, for example, "contact.zipcode".
     * The last item in the path must be a primtive var though.
     *
     * The order is either SORT_ASC or SORT_DESC, same as in PHP native
     * function array_multisort(). See
     * {@link http://www.php.net/manual/en/function.array-multisort.php}.
     *
     * @param ... variable params should be ($key, $order) pairs
     * @return bool
     */
    public function sortBy() {

        $this->_load();

        // get arguments
        if (!($args = func_get_args())) {
            throw new epExceptionArray('No sortBy() arguments');
            return false;
        }

        // convert UOIDs to objects
        $this->_uoids2Objs();

        // collect orderbys
        $this->orderbys = array();
        $orderby = array();
        $is_path = true;

        foreach($args as $arg) {

            if ($is_path) {

                // make sure arg is string
                if (!is_string($arg)) {
                    throw new epExceptionArray('Only string key is allowed');
                    return false;
                }

                $orderby['path'] = $arg;
                $orderby['dir'] = SORT_ASC;

                $is_path = false;

            } else {

                // it must be either SORT_ASC or SORT_DESC
                if ($arg != SORT_ASC && $arg != SORT_DESC) {
                    throw new epExceptionArray('Unrecognized sorting flag (valid: SORT_ASC, SORT_DESC)');
                    return false;
                }

                $orderby['dir'] = $arg;
                $this->orderbys[] = $orderby;

                $is_path = true;
            }
        }

        return usort($this->array, array($this, '__sort'));
    }

    /**
     * Sorts two objects
     * @param epObject $a
     * @param epObject $b
     * @throws epExceptionDbObject
     */
    private function __sort($a, $b) {

        // tie if no orderbys
        if (!$this->orderbys) {
            return 0;
        }

        // go through each orderby
        foreach($this->orderbys as $orderby) {

            // sign by direction
            $sign = $orderby['dir'] == SORT_DESC ? -1 : + 1;

            // get values from a and b
            $path = $orderby['path'];
            $va = epUtils::epArrayGet($a, $path);
            $vb = epUtils::epArrayGet($b, $path);

            // numeric
            if (is_numeric($va)) {
                // a < b
                if ($va < $vb) {
                    return -1 * $sign;
                }
                else if ($va > $vb) {
                    return +1 * $sign;
                }
                continue;
            }

            // string
            if (is_string($va)) {
                if (($r = strcmp($va, $vb)) < 0) {
                    return -1 * $sign;
                }
                else if ($r > 0) {
                    return +1 * $sign;
                }
                continue;
            }

            // invalid orderby value
            throw new epExceptionArray('Invalid sorting parameters');
        }

        // tie
        return 0;
    }

}
