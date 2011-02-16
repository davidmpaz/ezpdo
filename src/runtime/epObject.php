<?php

/**
 * $Id: epObject.php 1050 2007-06-19 10:54:09Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1050 $ $Date: 2007-06-19 06:54:09 -0400 (Tue, 19 Jun 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */

/**#@+
 * Needs {@link epBase} and {@link epOverload} 
 */
include_once(EP_SRC_BASE.'/epBase.php');
include_once(EP_SRC_BASE.'/epOverload.php');
/**#@-*/

/**
 * The Countable interface is introduced in PHP 5.1.0. For PHP versions 
 * earlier than 5.1.0, we need to declare the interface. 
 */
if (!interface_exists('Countable', false)) {
    /**
     * Interface Countable 
     */
    interface Countable {
        /**
         * Returns the number of items
         * @return integer
         */
        public function count();
    }
}

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
 * Exception class for {@link epArray}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1050 $ $Date: 2007-06-19 06:54:09 -0400 (Tue, 19 Jun 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epExceptionArray extends epException {
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
class epArray implements IteratorAggregate, ArrayAccess, Countable {
    
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
        return new ArrayIterator($this->array);
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
            $va = epArrayGet($a, $path);
            $vb = epArrayGet($b, $path);
            
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

/**
 * Exception class for {@link epObjectBase} and {@link epObject}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1050 $ $Date: 2007-06-19 06:54:09 -0400 (Tue, 19 Jun 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epExceptionObject extends epException {
}

/**
 * The base class of EZPDO object wrapper
 * 
 * For an ordinary PHP object to be persistable in EZPDO, it needs
 * to be 'wrapped'. This class provides a wrapper over the original
 * object so it can be managed by the persistence manager 
 * ({@link epManager}).
 * 
 * <b>Object IDs</b>
 * 
 * Each persistent object has an Object Id (OID), used to identify 
 * an object within a class. The OID is persisted in datastore. One 
 * can think of OID as an auto-incremental field in a relational 
 * table (which is in fact used by the current implementation of EZPDO 
 * to generate OIDs). Keep in mind, however, the significance of OID
 * is only within one class. In other words, the same OID may be used 
 * in two different classes. 
 * 
 * Within the extent of all domain classes, the combination of class 
 * name and the OID is unique, which is also referred as the UOID 
 * (Universal Object ID). The persistence manager ({@link epManager})
 * is responsible to code and decode the UOIDs. Please refer to 
 * {@link epManager::encodeUoid()} and {@link epManager::decodeUoid()}. 
 * 
 * The object also has a transient id, UID (Unique Id). The id is 
 * unique for all in-memeory objects. The reason of having this id is
 * that a new object will not have its own Object ID (OID) until it is 
 * persisted to datastore. The UID provides a way for us to differentiate 
 * new objects before they have valid OIDs. 
 * 
 * <b>Interfaces Implemented</b>
 * 
 * This wrapper class also implements the following SPL interfaces.
 * <ol>
 * <li>
 * {@link 
 * http://www.php.net/~helly/php/ext/spl/interfaceIteratorAggregate.html 
 * IteratorAggregate}
 * </li>
 * <li>
 * {@link http://www.php.net/~helly/php/ext/spl/interfaceArrayAccess.html 
 * ArrayAccess}
 * </li>
 * <li>
 * {@link http://www.php.net/~helly/php/ext/spl/interfaceCountable.html 
 * Countable}
 * </li>
 * </ol>
 * With the above interfaces impelemented, the data members in the object 
 * can be accessed as in an array. 
 * 
 * <b>Magic methods implemented</b>
 * 
 * The wrapper also implements the {@link 
 * http://www.php.net/manual/en/language.oop5.overloading.php overloading 
 * magic methods},  
 * {@link __get()} and {@link __set()} so public vars in the original object
 * can be directly accessed.
 * 
 * The class is derived from {@link epOverload}, which implements the 
 * {@link http://www.php.net/manual/en/language.oop5.overloading.php 
 * magic method}, {@link __call()}. All methods in the original class 
 * can be called directly. 
 * 
 * Also implemented in this class is the magic method, {@link 
 * __toString()}, which dumps the internals of the object and the wrapper. 
 * This can be really useful for debugging. You can simply 'echo' the 
 * object to get this output.
 * 
 * <b>Base wrapper only</b>
 * 
 * This is the base class for object wrapping and <b>does not</b> deal with 
 * object relationships, which are dealt with in its subclass {@link epObject}.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1050 $ $Date: 2007-06-19 06:54:09 -0400 (Tue, 19 Jun 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epObjectBase extends epOverload implements IteratorAggregate, ArrayAccess, Countable {

    /**#@+
     * Primitive or relationship vars (binary exclusive)
     */
    const VAR_PRIMITIVE = 1;
    const VAR_RELATIONSHIP = 2;
    /**#@-*/
    
    /**#@+
     * Actions for updating inverse (binary exclusive)
     */
    const INVERSE_ADD = 1;
    const INVERSE_REMOVE = 2;
    const INVERSE_BOTH = 3;
    /**#@-*/

    /**
     * Constant: not a getter or setter
     */
    const NOT_AN_ACCESSOR = "NOT_AN_ACCESSOR";
    
    /**#@+
     * Used for return value to avoid reference notice in 5.0.x and up
     * @var bool
     */
    static public $false = false;
    static public $true = true;
    static public $null = null;
    /**#@-*/

    /**
     * Counter for ep_uid
     * @var int
     */
    static $uniqid_counter = 0;

    /**
     * The object wrapped (cached)
     * @var object
     */
    protected $ep_object = false;
    
    /**
     * The object's uid (used to id objects in memory)
     * @var string
     */
    protected $ep_uid = false;
    
    /**
     * The object id
     * @var false|integer
     */
    protected $ep_object_id = false;
    
    /**
     * The deleted flag
     * @var bool
     */
    protected $ep_is_deleted = false; 

    /**
     * The dirty flag
     * @var bool
     */
    protected $ep_is_dirty = false; 
    
    /**
     * The committable flag (true by default)
     * @var bool
     */
    protected $ep_is_committable = true; 
    
    /**
     * The inverse updating add flag
     * @var bool
     */
    protected $ep_updating_add = false;

    /**
     * The inverse updating remove flag
     * @var bool
     */
    protected $ep_updating_remove = false;
    
    /**
     * The searching flag
     * @var bool
     */
    protected $ep_is_searching = false;
    
    /**
     * The matching flag
     * @var bool
     */
    protected $ep_is_matching = false;

    /**
     * The flag of object being committing (false by default)
     * @var bool
     */
    protected $ep_is_committing = false; 

    /**
     * Array to keep vars before __call()
     * @var array (keyed by var names)
     */
    protected $ep_vars_before = false; 
    
    /**
     * Cached persistence manager
     * @var epManager
     */
    protected $ep_m = false;
    
    /**
     * Cached class map for this object
     * @var epClassMap
     */
    protected $ep_cm = false;
    
    /**
     * Cached field maps for the variables
     * @var array (of epFieldMap) 
     */
    protected $ep_fms = array();

    /**
     * The cached var names
     * @var array
     */
    protected $ep_cached_vars = array();
    
    /**
     * The cached method names
     * @var array
     */
    protected $ep_cached_methods = array();

    /**
     * The modified primitive vars
     * @var array
     */
    protected $ep_modified_pvars = array();

    /**
     * The modified relationship vars
     * @var array
     */
    protected $ep_modified_rvars = array();

    /**
     * Constructor
     * @param object $o The object to be wrapped 
     * @param epClassMap $cm The class map of the object 
     * @see epOverload
     */
    public function __construct($o, $cm = null) {
        
        if (!is_object($o)) {
            throw new epExceptionObject("Cannot wrap a non-object");
        }
        
		// set up manager and class map (back reference)
        $this->ep_m = epManager::instance();
        $this->ep_cm = & $cm;

		// wrap the object 
        $this->_wrapObject($o);
        
		// collect methods from object. must be called before 
		// _collectVars(), which depends on the cached methods
		$this->_collectMethods();
        
		// collect vars (after _collectMethods)
		$this->_collectVars();
    }
    
    /**
     * Gets the persistence manager
     * @return epManager
     * @access public
     */
    public function epGetManager() {
        return $this->ep_m;
    }
    
    /**
     * Gets the class (name) of the object 
     * @return string
     */
    public function epGetClass() {
        return $this->ep_cm ? $this->ep_cm->getName() : get_class($this->ep_object);
    }
    
    /**
     * Gets class map for this object
     * @return epClassMap
     * @throws epExceptionManager, epExceptionObject
     */
    public function epGetClassMap() {
        return $this->ep_cm;
    }
    
    /**
     * Gets object uid 
     * @return integer
     */
    public function epGetUId() {
        return $this->ep_uid;
    }
    
    /**
     * Gets object id
     * @return integer
     */
    public function epGetObjectId() {
        return $this->ep_object_id;
    }
    
    /**
     * Sets object id. Should only be called by {@link epManager}
     * Note that object id can be set only once (when it is persisted for the first time).
     * @return void
     * @throws epExceptionObject
     */
    public function epSetObjectId($oid) {
        if ($this->ep_object_id === false) {
            $this->ep_object_id = $oid;
        } else {
            throw new epExceptionObject("Cannot alter object id");
        }
    }
    
    /**
     * Collects and caches all methods for the object
     * @return void
     * @access protected
     */
    protected function _collectMethods() {
    	$this->ep_cached_methods = get_class_methods(get_class($this->ep_object));
    }

    /**
     * Returns a variable from the wrapped object. This should be used instead of 
	 * $this->ep_object->$var_name for the support of private/protected vars. 
	 * 
	 * Please note that no long do we call $this->ep_object->$var_name directly. 
	 * Instead we use this method to alter an var in the object. 
	 * 
     * @return mixed Variable from the wrapped object, accessed directly, or via __get, or via get<Var_name>.
     * @access protected
     */
    protected function &epObjectGetVar($var_name) {

    	// public variable
    	$public_vars = array_keys(get_object_vars($this->ep_object));
    	if (in_array($var_name, $public_vars)) {
    		return $this->ep_object->$var_name;
    	}

    	// specific getter getVar_name()
    	$getter = 'get' . ucfirst($var_name);
    	if (in_array($getter, $this->ep_cached_methods)) {
    		$res =& $this->ep_object->$getter();
    		return $res;
    	}

    	// generic getter __get()
    	if (in_array('__get', $this->ep_cached_methods)) {
    		$res =& $this->ep_object->__get($var_name);
    		return $res;
    	}

    	// otherwise error
    	throw new epExceptionObject('Variable [' . $this->_varName($var_name) . '] is not public nor does it have public getter');
		
		return self::$false;
    }

    /**
     * Sets a variable of the wrapped object. Paired with {@link epObjectGetVar).
	 * This should be used instead of $this->ep_object->$var_name = $value for the
	 * support for private/protected vars.
     * 
     * @return mixed $true or $false
     * @access protected
     */
    protected function &epObjectSetVar($var_name, &$value) {

    	// public variable
    	$public_vars = array_keys(get_object_vars($this->ep_object));
    	if (in_array($var_name, $public_vars)) {
    		$this->ep_object->$var_name = $value;
    		return self::$true;
    	}

    	// specific setter setVar_name()
    	$setter = 'set' . ucfirst($var_name);
    	if (in_array($setter,$this->ep_cached_methods)) {
    		$this->ep_object->$setter($value);
    		return self::$true;
    	}

    	// generic getter __set()
    	if (in_array('__set',$this->ep_cached_methods)) {
    		$this->ep_object->__set($var_name, $value);
    		return self::$true;
    	}

    	// otherwise error
    	throw new epExceptionObject('Variable [' . $this->_varName($var_name) . '] is not public nor does it have public setter');
    	
		// other cases : error
		return self::$false;
    }

    /**
     * Returns all variable names and values
     * @return array (var_name => var_value)
     * @access public
     */
    public function epGetVars() {
        
        // get vars from the wrapped object
        $vars = get_object_vars($this->ep_object);
        
        // append oid to array
        $vars['oid'] = $this->ep_object_id;
        
        // also collect protected/private members (accessible via public g/setters)
		if ($this->ep_cm) {
			foreach ($this->ep_cm->getAllFields() as $name => $field) {
				// if field is not in the get_object_vars result
				if (!isset($vars[$name])) {
					// then either private or protected
					$vars[$name] = $this->epObjectGetVar($name);
				}
			}
		}
		
		return $vars;
    }
    
    /**
     * Checks if the object has the variable 
     * @param string $var_name variable name
     * @return bool
     * @access public
     */
    public function epIsVar($var_name) {
        // check if we can find a best-matched var name
        return in_array($var_name, $this->ep_cached_vars);
    }

    /**
     * Checks if a variable is a primitive field
     * @param string $var_name variable name
     * @return bool
     * @access public
     */
    public function epIsPrimitive($var_name) {
        
        // get field map of the variable 
        if (!($fm = & $this->_getFieldMap($var_name))) {
            // always a primitve if a var doesn't have a field map
            return true; 
        }

        // return whether var is a single valued field
        return $fm->isPrimitive();
    }

    /**
     * Checks if a variable is single-valued relationship field
     * @param string $var_name variable name
     * @return bool
     * @access public
     */
    public function epIsSingle($var_name) {
        
        // get field map of the variable 
        if (!($fm = & $this->_getFieldMap($var_name))) {
            return false;
        }

        // cannot be single if pritimive
        if ($fm->isPrimitive()) {
            return false;
        }

        // return whether var is a single valued field
        return $fm->isSingle();
    }
    
    /**
     * Checks if a variable is many-valued relationship field
     * @param string $var_name variable name
     * @return bool
     * @access public
     */
    public function epIsMany($var_name) {
        
        // get field map of the variable 
        if (!($fm = & $this->_getFieldMap($var_name))) {
            return false;
        }
        
        // cannot be single if pritimive
        if ($fm->isPrimitive()) {
            return false;
        }

        // return whether var is a many valued field
        return $fm->isMany();
    }

    /**
     * Returns the class of an relational field
     * @param string $var_name variable name
     * @return bool
     * @access public
     */
    public function epGetClassOfVar($var_name) {
        
        // get field map of the variable 
        if (!($fm = & $this->_getFieldMap($var_name))) {
            return false;
        }
        
        // return the related class
        return $fm->getClass();
    }

    /**
     * Returns the value of a variable 
     * @param string $var_name variable name
     * @return mixed
     * @access public
     */
    public function &epGet($var_name) {
        
        // is var oid? 
        if ($var_name == 'oid') {
            return $this->ep_object_id;
        }
        
        // get the best-matched var name
        if (!in_array($var_name, $this->ep_cached_vars)) {
            throw new epExceptionObject('Variable [' . $this->_varName($var_name) . '] does not exist or is not accessible');
            return self::$false;
        }
        
        // intermediate variable to prevent notice about reference returns
        $var =& $this->epObjectGetVar($var_name);
        return $var;
    }
    
    /**
     * Sets the value to a variable
     * @param string $var_name variable name
     * @param mixed value
     * @param bool clean (if true no dirty flag change)
     * @return bool
     */
    public function epSet($var_name, $value, $clean = false) {
        
        // find the best-matched var name
        if (!in_array($var_name, $this->ep_cached_vars)) {
            throw new epExceptionObject('Variable [' . $var_name . '] does not exist or is not accessible');
            return false;
        }
        
        // only when old and new value differ
        if ($this->epObjectGetVar($var_name) !== $value) {
            
            // notify change
            if (!$clean) {
                $this->epNotifyChange('onPreChange', array($var_name => ''));
            }

            // change var value
            $this->epObjectSetVar($var_name, $value);
            
            // flag object dirty if it's not a clean 'set' 
            if (!$clean) {
                $this->epNotifyChange('onPostChange', array($var_name => $value));
            }
        }
        
        return true;
    }
    
    /**
     * Checks if object has been deleted 
     * @return bool
     * @access public
     */
    public function epIsDeleted() {
        return $this->ep_is_deleted;
    }

    /**
     * Set object being deleted 
     * @return bool
     * @access public
     */
    public function epSetDeleted($is_deleted = true) {
        $this->ep_is_deleted = $is_deleted;
    }

    /**
     * Checks if object is 'dirty' (ie whether it's different than what's in datastore)
     * @return bool
     * @access public
     */
    public function epIsDirty() {
        return $this->ep_is_dirty;
    }
    
    /**
     * Explicitly sets dirty flag  
     * @param false|true $is_dirty
     * @return void
     * @access public
     */
    public function epSetDirty($is_dirty = true) {
        
        // set the dirty flag
        $this->ep_is_dirty = $is_dirty;
        
        // clear modified vars if not dirty 
        if (!$this->ep_is_dirty) {
            $this->ep_modified_pvars = array();
            $this->ep_modified_rvars = array();
        }
    }

    /**
     * Notify the manager before and after object change  
     * @param string $event Either 'onPreChange' or 'onPostChange'
     * @param array $vars The variables involved in var => value pairs
     * @return bool
     * @access public
     */
    public function epNotifyChange($event, $vars) {

        // onPostChange
        if ($event == "onPostChange") {
            $this->epSetModifiedVars($vars);
        }

        // call manager to notify changes
        $status = true;
        if ($this->ep_m) {
            $status = $this->ep_m->notifyChange($this, $event, array_keys($vars));
        }

        return $status;
    }

    /**
     * Checks if object is a committable object 
     * Only a commmitable object can be commited.
     * @return bool
     * @access public
     */
    public function epIsCommittable() {
        return $this->ep_is_committable;
    }

    /**
     * Explicitly sets whether object is a committable object or not.
     * @param bool $is_committable whether to set object and its children committable
     * @param bool $children whether to set children objects committable
     * @access public
     */
    public function epSetCommittable($is_committable = true, $children = false) {

        // set this object to be committable
        $this->ep_is_committable = $is_committable;

        // check if we need set children objects committable
        if (!$children) {
            // done if not 
            return;
        }

        // otherwise, set children committable (recursion)
        
        // to avoid loop, use 'searching' flag
        $this->epSetSearching(true);

        // go through each var-value pair
        foreach($this->epGetVars() as $var => $val) {

            // exclude empty values and get field map
            if (!$val || !($fm = & $this->_getFieldMap($var))) {
                continue;
            }

            // skip primitive fields
            if ($fm->isPrimitive()) {
                continue;
            }

            // many-valued relationship field
            if ($val instanceof epArray) {

                foreach ($val as $obj) {

                    // skip empty object and object under search
                    if (!$obj || $obj->epIsSearching()) {
                        continue;
                    }

                    // set committable flag recursively
                    $obj->epSetCommittable($is_committable, true);
                }
            } 

            // one-valued relationship field 
            else if ($val instanceof epObject && !$val->epIsSearching()) {
                // set committable flag recursively
                $val->epSetCommittable($is_committable, true);
            }

        }

        $this->epSetSearching(false);
    }
    
    /**
     * Checks if object is all inverse update actions specified are taking place
     * @param integer $actions Inverse update actions INVERSE_ADD/REMOVE/BOTH
     * @return bool
     * @access public
     */
    public function epIsUpdating($actions = self::INVERSE_BOTH) {
        if ($actions & self::INVERSE_ADD) {
            if (!$this->ep_updating_add) {
                return false;
            }
        }
        if ($actions & self::INVERSE_REMOVE) {
            if (!$this->ep_updating_remove) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set flag of inverse updating
     * @param bool $updating Whether object is updating inverse
     * @param integer $action Which update action is taking place
     * @return void
     * @access public
     */
    public function epSetUpdating($updating = true, $action = self::INVERSE_BOTH) {
        if ($action & self::INVERSE_ADD) {
            $this->ep_updating_add = $updating;
        }
        if ($action & self::INVERSE_REMOVE) {
            $this->ep_updating_remove = $updating;
        }
    }

    /**
     * Checks if object is being used in a select query
     * @return bool
     * @access public
     */
    public function epIsSearching() {
        return $this->ep_is_searching;
    }

    /**
     * Set flag of searching status
     * @param bool $updating
     * @return void
     * @access public
     */
    public function epSetSearching($status = true) {
        $this->ep_is_searching = $status;
    }

    /**
     * Checks if object is being matched against
     * 
     * Deep matches can cause infinite recursion
     * 
     * @return bool
     * @access public
     */
    public function epIsMatching() {
        return $this->ep_is_matching;
    }

    /**
     * Set flag of matching status
     * 
     * @param bool $var the new status
     * @return bool
     * @access public
     */
    public function epSetMatching($status = true) {
        $this->ep_is_matching = $status;
    }

    /**
     * Checks if object is being committed
     * @return bool
     * @access public
     */
    public function epIsCommitting() {
        return $this->ep_is_committing;
    }
    
    /**
     * Set the object is being committed
     * 
     * Setting this flag is important for persisting object relationships. 
     * A committable object is committed after all its relational fields
     * (vars) are committed. And this flag prevents the same object from 
     * being commited more than once.
     * 
     * @param bool $is_committing
     * @access public
     */
    public function epSetCommitting($is_committing = true) {
        $this->ep_is_committing = $is_committing;
    }

    /**
     * Returns the vars that have been modified
     * @param integer $type The type of vars: VAR_PRIMITIVE, VAR_RELATIONSHIP or both (|)
     */
    public function epGetModifiedVars($type = null) {
        
        // if type is not specified, get both types of vars
        if (!$type) {
            $type = self::VAR_PRIMITIVE | self::VAR_RELATIONSHIP;
        }

        // array to hold all modified vars
        $modified_vars = array();

        // modified primtive vars
        if ($type & self::VAR_PRIMITIVE) {
            $modified_vars = array_merge($modified_vars, $this->ep_modified_pvars);
        }

        // modified relationship vars
        if ($type & self::VAR_RELATIONSHIP) {
            $modified_vars = array_merge($modified_vars, $this->ep_modified_rvars);
        }

        return $modified_vars;
    }
    
    /**
     * Sets the modified vars (only used by EZPDO internally)
     * @param array $modified_vars
     * @access public
     */
    public function epSetModifiedVars($vars) {
        
        // flag to indicate if any var modified
        $dirty = false;
        
        // categorize vars into pvars and rvars
        foreach($vars as $var => $value) {

            // is it a var?
            if (!$this->epIsVar($var)) {
                continue;
            }

            // primitive var
            if ($this->epIsPrimitive($var)) {
                $this->ep_modified_pvars[$var] = $value;
                $dirty = true;
            }

            // relationship var
            else {
                $this->ep_modified_rvars[$var] = true;
                $dirty = true;
            }
        }

        // set dirty flag
        if ($dirty) {
            $this->epSetDirty(true);
        }

        return $dirty;
    }
    
    /**
     * Copy the values of the vars from another object. Note this is
     * a low-level copy that alters the vars of the wrapped object
     * directly. Also object id is not copied. 
     * @param epObject|array $from
     * @return bool 
     */
    public function epCopyVars($from) {
        
        // the argument must be either an array or an epObject
        if (!is_array($from) && !($from instanceof epObject)) {
            return false;
        }
        
        // array-ize if epObject
        $var_values = $from;
        if ($from instanceof epObject) {
            $var_values = $from->epGetVars();
        }
        
        // copy over values from input
        foreach($var_values as $var => $value) {
            //  to vars that exist in this object
            if (in_array($var, $this->ep_cached_vars)) {
                $this->epObjectSetVar($var, $value);
            }
        }
        
        return true;
    }
    
    /**
     * Checks if the variables of this object matches all the 
     * non-null variables in another object
     * 
     * <b>
     * Note: for now we only match primitive fields. For relational 
     * fields, things can get complicated as we may be dealing with 
     * very "deep" comparisons and recursions.
     * </b>
     * 
     * @param epObject $o the example object
     * @param bool $deep should this be a deep search
     * @return bool 
     */
    public function epMatches($o, $deep = false) {
        
        // make sure param is epObject
        if (!($o instanceof epObject)) {
            return false;
        }
        
        // same object if same object uid
        if ($this->ep_uid == $o->epGetUId()) {
            return true;
        }
        
        // matches if the example object does not have any non-null variable
        if (!($vars_o = $o->epGetVars())) {
            return true;
        }

        // get vars of this object
        $vars = $this->epGetVars();
        
        // set 'matching' flag to avoid endless loop
        $this->epSetMatching(true);
        $o->epSetMatching(true);
        
        // go through each var from the example object
        foreach($vars_o as $var => $value) {

            // skip 'oid'
            if ($var == 'oid') {
                continue;
            }
            
            // ignore null values (including empty string)
            if (is_null($value) || (is_string($value) && !$value)) {
                continue;
            }
            
            // for primitive var, mismatch if var is not in object or two vars unequal 
            if ($this->epIsPrimitive($var)) {
                if (!isset($vars[$var]) || $vars[$var] != $value) {
                    $this->epSetMatching(false);
                    $o->epSetMatching(false);
                    return false;
                }
                continue;
            }

            // done if no deep matching (see method comments)
            if (!$deep) {
                continue;
            }

            // deep matching for relationship fields

            // many-valued relationship var
            if ($value instanceof epArray) {
                
                // the epArray can have different order between the
                // two objects, so we have to do an n^2 loop to see
                // if they are the same (ugly)
                // oak: shall we match array count? strict mode? //
                foreach ($value as $obj) {
                    
                    if (!$obj) {
                        continue;
                    }
                    
                    // skip object already under matching
                    if ($obj->epIsMatching()) {
                        continue;
                    }
                    
                    // flag indicates object being matched
                    $matched = false;
                    
                    // go through each var in array
                    foreach ($vars[$var] as $tmp) {
                        if (!$tmp->epIsMatching() && !$obj->epIsMatching()) {
                            if ($tmp->epMatches($obj, true)) {
                                $matched = true;
                                break;
                            }
                        }
                    }
                    
                    // no match to $obj. done.
                    if (!$matched) {
                        $this->epSetMatching(false);
                        $o->epSetMatching(false);
                        return false;
                    }
                }
            }

            // one-valued relationship field
            else if (($value instanceof epObject) && !$value->epIsMatching()) {
                
                // we need to check this
                if (!isset($vars[$var])) {
                    $vars[$var] = $this->epGet($var);
                }
                
                // match only when vars are not flagged 'matching'
                if (!$value->epIsMatching() && !$vars[$var]->epIsMatching()) {
                    if (!$vars[$var]->epMatches($value, true)) {
                        $this->epSetMatching(false);
                        $o->epSetMatching(false);
                        return false;
                    }
                } 

                // o.w. one is matching and the other is not, they are not equal
                else {
                    $this->epSetMatching(false);
                    $o->epSetMatching(false);
                    return false;
                } 
            }
            
        }

        // reset matching flags
        $this->epSetMatching(false);
        $o->epSetMatching(false);
        
        return true;
    }

    /**
     * Checks if a method exists in the object
     * @param string 
     * @return bool
     */
    public function epMethodExists($method) {
        
        // sanity check: method name should not be empty 
        if (!$method || !$this->ep_object) {
            return false;
        }
        
        // call the cached object to check if method exists
        return method_exists($this->ep_object, $method);
    }

    /**
     * Check if the object needs to be commited
     * 
     * An object needs to be committed when it is either dirty or new (object 
     * id is not set), and it is committable, not being committed, nor being 
     * deleted.
     * 
     * @return bool
     */
    public function epNeedsCommit() {
        return (
            ($this->ep_is_dirty 
             || !$this->ep_object_id) 
            && $this->ep_is_committable 
            && !$this->ep_is_committing 
            && !$this->ep_is_deleted
            );
    }

    /**
     * Persists the object into datastore. 
     * 
     * Actual persisting is delegated to {@link epManager}
     * 
     * @return bool
     */
    public function commit() { 
        if (!$this->_checkManager()) {
            return false;
        }
        return $this->ep_m->commit($this);
    }

    /**
     * Delete the object from the datastore and memory 
     * Calls epManager to delete the object.
     * @return bool
     */
    public function delete() { 
        if (!$this->_checkManager()) {
            return false;
        }
        return $this->ep_m->delete($this);
    }
    
    /**
     * Implements magic method __sleep()
     * Sets cached class map, field maps, and manager to null or empty
     * to prevent them from being serialized
     */
    public function __sleep() {
        
        // set cached class map to null so it's not serialized
        $this->ep_cm = NULL;

        // empty cached field maps so they are not serialized
        $this->ep_fms = array();

        // set manager to null so it's not serialized
        $this->ep_m = NULL;

        // return vars to be serialized
        return array_keys(get_object_vars($this));
    }

    /**
     * Implements magic method __wakeup()
     * Recovers cached manager and class map
     */
    public function __wakeup() {
        
        // recover manager when waking up
        $this->ep_m = epManager::instance();
        
        // recover class map
        $this->ep_cm = $this->ep_m->getMap(get_class($this->ep_object));

        // cache this object in manager (important for flush)
        $this->ep_m->cache($this, true); // true: force replace
    }

    /**
     * Implements magic method __isset()
     * @return bool
     */
    final public function __isset($var_name) {
        $value = $this->epGet($var_name);
        return isset($value);
    }

    /**
     * Implements magic method __unset()
     */
    final public function __unset($var_name) {
        return $this->epSet($var_name, null);
    }

    /**
     * Implements magic method __get(). 
     * This method calls {@link epGet()}.
     * @param $var_name
     */
    final public function &__get($var_name) {
        return $this->epGet($var_name);
    }
    
    /**
     * Implements magic method __set(). 
     * This method calls {@link epSet()}.
     * @param $var_name
     */
    final public function __set($var_name, $value) {
        $this->epSet($var_name, $value);
    }
    
    /**
     * Intercepts getter/setters to manage persience state. 
     * @param string $method method name 
     * @param array arguments
     * @return mixed
     * @see epObject::__call()
     */
    final public function __call($method, $args) {
        
        try {
            // try getters/setters first 
            $ret = $this->_intercept($method, $args);
            if ($ret !== self::NOT_AN_ACCESSOR) {
                return $ret;
            }
        } catch (epExceptionObject $e) {
            // exception: var cannot found in setter and getter
        }
        
        // 
        // after getters and setters, call epOverload::__call()
        // 
        
        // preprocess before __call()
        if ($method != 'onPreChange') {
            $this->_pre_call();
        }
        
        // get old argument conversion flag
        $ca = $this->getConvertArguments();
        
        // force -no- argument conversion (fix bug 69)
        $this->setConvertArguments(false);
        
        // actually call method in original class
        $ret =  parent::__call($method, $args);
        
        // recover old argument conversion flag
        $this->setConvertArguments($ca);
        
        // post process after __call() 
        $this->_post_call();
        
        return $ret;
    }
    
    /**
     * Preprocess before __call()
     * @return void
     */
    protected function _pre_call() {
        // notify changes that will be made
        // !!! since we don't know which vars exactly will be changed, report none !!!
        $this->epNotifyChange('onPreChange', array());
    }
    
    /**
     * Post process after __call()
     * @return void
     */
    protected function _post_call() {
        
        // get vars after _call()
        $vars_after = get_object_vars($this->ep_object);
        
        // collect modified vars
        $changed_vars = array();
        foreach($vars_after as $var => $value) {
            
            // skip relationship field
            if ($this->epIsMany($var)) {
                continue;
            }
            
            // skip unspotted var before method call
			if (!isset($this->ep_vars_before[$var])) {
				continue;
            }

            // has var been changed?
			if ($this->ep_vars_before[$var] !== $value) {
                $changed_vars[$var] = $value;
			}
        }

        // notify changes made
        if ($changed_vars) {
            $this->epNotifyChange('onPostChange', $changed_vars);
        }
        
        // release old values
        $this->ep_vars_before = false;
    }
    
    /**
     * Intercept getters/setters
     * @param string $method method name 
     * @param array $args arguments
     * @param bool $okay
     * @return mixed
     */
    protected function _intercept($method, $args) {
        
        // intercept getter
        if (substr($method, 0, 4) == 'get_') {
            $vn = substr($method, 4);
            return $this->epGet($vn);
        } 
        else if (substr($method, 0, 3) == 'get') {
            $vn = strtolower(substr($method, 3, 1)) . substr($method, 4);
            return $this->epGet($vn);
        } 
        
        // intercept setter
        if (substr($method, 0, 4) == 'set_') {
            $vn = substr($method, 4);
            return $this->epSet($vn, isset($args[0]) ? $args[0] : null);
        } 
        else if (substr($method, 0, 3) == 'set') {
            $vn = strtolower(substr($method, 3, 1)) . substr($method, 4);
            return $this->epSet($vn, isset($args[0]) ? $args[0] : null);
        } 
        
        return self::NOT_AN_ACCESSOR;
    }

    /**
     * Wraps an object. 
     * The method checks if the arg is an object. It also checks whether it has 
     * interface {@link epObject} already. If so, it throws an exception. 
     * @param $o object
     * @return bool
     * @throws epExceptionObject
     */
    protected function _wrapObject($o) {
        
        // no need to wrap an epObject instance 
        if ($o instanceof epObject) {
            throw new epExceptionObject('No wrapper for epObject instance');
            return false;
        }
        
        // set the wrapped object (the foreign object)
        $this->setForeignObject($o);
        
        // cache the object for quick access
        $this->ep_object = $o;
        
        // create the uid for this object
        $this->ep_uid = self::$uniqid_counter++;
        
        return true;
    }
    
    /**
     * Collect and cache all vars for the object
     * @return void
     * @access protected
     */
    protected function _collectVars() {
        
		// collect vars in objects
		$this->ep_cached_vars = array_keys(get_object_vars($this->ep_object));
		
        // add private/protected vars in case missed out
		if ($this->ep_cm) {
			
			// has __get() or __set() in class?
			$has__get = in_array('__get', $this->ep_cached_methods);
			$has__set = in_array('__set', $this->ep_cached_methods);
			
			// get all vars from class map
			$vars = array_keys($this->ep_cm->getAllFields());
			
			// go through all vars and collect accessible non-public vars 
			// 'accessible' means a var has both public getter and setter  
			foreach ($vars as $var) {
				
				// skip if already collected
				if (in_array($var, $this->ep_cached_vars)) {
					continue;
                }
				
				// does it have public g/setter?
				$hasGetter = $has__get || 
					in_array('get' . ucfirst($var), $this->ep_cached_methods);
				$hasSetter = $has__set || 
					in_array('set' . ucfirst($var), $this->ep_cached_methods);
				
				// collect it if publically accessible
				if ($hasGetter && $hasSetter) {
					$this->ep_cached_vars[] = $var;
				}
            }
        } 
    }
    
    /**
     * Checks persistence manager 
     * @return bool
     * @throws epExceptionObject
     */
    public function _checkManager() { 
        if (!$this->ep_m) {
            throw new epExceptionObject('Persistence manager missing');
            return false;
        }
        return true;
    }

    /**
     * Returns the field map for the variable 
     * Note that param $var_name will be replaced with the best matched var name
     * @param string $var_name
     * @return false|epFieldMap
     */
    protected function &_getFieldMap($var_name) {
        
        // check if we have class map
        if (!$this->ep_cm || !$var_name) {
            return self::$false;
        }
        
        // check if field map is cached
        if (isset($this->ep_fms[$var_name])) {
            return $this->ep_fms[$var_name];
        }

        // get field map
        $fm = $this->ep_cm->getField($var_name);
        
        // cache it 
        $this->ep_fms[$var_name] = $fm;
        
        // return the field map for the var
        return $fm;
    }
    
    /**
     * Implements Countable::count(). 
     * Returns the number of vars in the object
     * @return integer
     */
    public function count() {
        return count($this->epGetVars()); // oid included
    }
     
     /**
     * Implements IteratorAggregate::getIterator()
     * Returns the iterator which is an ArrayIterator object connected to the vars
     * @return ArrayIterator
     */
    public function getIterator () {
        
        // get all vars of the object
        $vars = array();
        foreach($this->epGetVars() as $k => $v) { 
            $vars[$k] = $this->epGet($k);
        }
        
        // return the array iterator
        return new ArrayIterator($vars);
    }
     
    /**
     * Implements ArrayAccess::offsetExists()
     * @return bool
     */
    public function offsetExists($index) {
        
        // is the index oid?
        if ($index == 'oid') {
            return true;
        }

        return in_array($index, $this->ep_cached_vars);
    }
    
    /**
     * Implements ArrayAccess::offsetGet()
     * @return mixed
     */
    public function offsetGet($index) {
        return $this->epGet($index);
    }
     
    /**
     * Implements ArrayAccess::offsetSet()
     * @return mixed
     */
    public function offsetSet($index, $newval) {
        if ($index == 'oid') {
            throw new epExceptionObject('Object ID is read-only');
        }
        $this->epSet($index, $newval);
    }

    /**
     * Implements ArrayAccess::offsetUnset()
     * @return mixed
     * @throws epExceptionObject
     */
     public function offsetUnset($index) {
        if ($index == 'oid') {
            throw new epExceptionObject('Object ID is read-only');
        }
        $this->o->epSet($index, null);
     }
     
     /**
      * Implement magic method __toString()
      * 
      * This method can be handily used for debugging purpose. 
      * Simply use "echo" to dump the object's info. 
      * 
      * @return string
      */
     public function __toString() {
         
         // indentation
         $indent = '  ';

         // the output string
         $s = '';

         // class for the object
         $s .= 'object (' . $this->epGetClassMap()->getName() . ')' . "\n";
         
         // object id
         $s .= $indent . 'oid : ' . $this->epGetObjectId() . "\n";

         // object uid
         $s .= $indent . 'uid : ' . $this->epGetUId() . "\n";

         // dirty flag
         $s .= $indent . 'is dirty?  : ';
         if ($this->epIsDirty()) {
             $s .= 'yes';
         } else {
             $s .= 'no';
         }
         $s .= "\n";

         // dirty flag
         $s .= $indent . 'is committable?  : ';
         if ($this->epIsCommittable()) {
             $s .= 'yes';
         } else {
             $s .= 'no';
         }
         $s .= "\n";

         // delete flag
         $s .= $indent . 'is deleted?  : ';
         if ($this->epIsDeleted()) {
             $s .= 'yes';
         } else {
             $s .= 'no';
         }
         $s .= "\n";

         // vars
         $vars = $this->epGetVars();

         // go through each var from the example object
         $s .= $indent . 'vars' . "\n";
         $indent .= $indent;
         foreach($vars as $var => $value) {
             
             // skip oid
             if ($var == 'oid') {
                 continue;
             }

             // output var name
             $s .= $indent . '[' . $var . ']: ';
             
             // re-get value so objects are loaded
             $value = $this->epGet($var);
             
             if ($value instanceof epObject) {
                 $s .= $this->ep_m->encodeUoid($value); 
             } 
             else if ($value instanceof epArray) {
                 $s .= $value->getClass() . '(' . $value->count() . ')';
             } 
             else {
                 $s .= print_r($value, true);
             }
             
             $s .= "\n";
         }

         // return the string
         return $s;
     }

     /**
      * Returns the class::var name for a var
      * @params string $var_name
      * @return string
      */
     protected function _varName($var) {
         $class = '';
         if ($this->ep_cm) {
             $class = $this->ep_cm->getName() . '::';
         }
         return $class.$var;
     }

}

/**
 * Class of the EZPDO object Wrapper
 * 
 * This class is derived from {@link epObjectBase}. The base class 
 * only deals with primitive variables in an object being wrapped, 
 * and does not deal with object relationships. This class addresses
 * object relationships.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1050 $ $Date: 2007-06-19 06:54:09 -0400 (Tue, 19 Jun 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epObject extends epObjectBase {
    
    /**
     * Array to hold the names of the fetched relational variables
     * @var array
     */
    protected $ep_rvars_fetched = array();
    
    /**
     * The array to keep the original state of the object when a 
     * transaction starts. It is also used as the indicator of 
     * whether the object is currently in transaction. 
     * @var array
     * @see epStartTransaction
     */
    protected $ep_trans_backup = false;

    /**
     * Constructor
     * @param object $o The object to be wrapped 
     * @param epClassMap $cm The class map for the object
     */
    public function __construct($o, $cm = null) {
        parent::__construct($o, $cm);
    }

    /**
     * Overrides {@link epObjectBase::epGet()}
     * 
     * @param epObject|array $from
     * @return bool 
     */
    public function epCopyVars($from) {
        if (!parent::epCopyVars($from)) {
            return false;
        }
        $this->ep_rvars_fetched = array();
        return true;
    }
    
    /**
     * Overrides {@link epObjectBase::epGet()}
     * 
     * If the variable is a relational field (non-primitive), check if
     * it's set to UOID or an array of UOIDs. If so, convert the UOID(s) 
     * into related objects. This is doing the lazy loading, or the "proxy" 
     * pattern: fetching the object by its oid only when it's needed.
     * 
     * @param string $var_name variable name
     * @return mixed
     * @access public
     * @throws epExceptionManager, epExceptionObject
     */
    public function &epGet($var_name) {
        
        // do the usual stuff 
        $val = & parent::epGet($var_name);
        
        // done if either manger, class map or field map not found
        if (!$this->ep_m || !$this->ep_cm || !($fm = $this->_getFieldMap($var_name))) {
            return $val;
        }
        
        // return now if primitive field
        if ($fm->isPrimitive()) {
            return $val;
        }

        // one-valued
        if (!$fm->isMany()) {
            return $this->_installOneValued($var_name, $fm, $val);
        }

        // many-valued
        return $this->_installManyValued($var_name, $fm, $val);
    }

    /**
     * Overrides {@link epObjectBase::epSet()}
     * Sets the value to a variable
     * Special treatment for "many" fields: replace with epArray
     * @param string $var_name The name of the variable 
     * @param mixed $value The value to be set to the variable
     * @param bool $clean Whether or not to set dirty flag  
     * @return bool
     * @throws epExceptionManager, epExceptionObject
     */
    public function epSet($var_name, $value, $clean = false) {
        
        // do the usual stuff if manger or class map or field map not found
        if (!$this->ep_m || !$this->ep_cm || !($fm = & $this->_getFieldMap($var_name))) {
            return parent::epSet($var_name, $value, $clean);
        }
        
        // return now if not a relatiionship field
        if ($fm->isPrimitive()) {
            // do the usual stuff
            return parent::epSet($var_name, $value, $clean);
        }

        // check type for non-primitive
        if (!$this->_typeMatches($value, $fm)) {
            throw new epExceptionObject('Invalid value for var [' . $this->_varName($var_name) . ']');
            return false;
        }

        // one-valued
        if (!$fm->isMany()) {
            return $this->_setOneValued($var_name, $fm, $value, $clean);
        }

        return $this->_setManyValued($var_name, $fm, $value, $clean);
    }

    /**
     * Returns whether the object in transition
     * @return bool
     */
    public function epInTransaction() {
        return $this->ep_trans_backup !== false;
    }
    
    /**
     * Signals the object to prepare for a transaction
     * 
     * In this method, the object needs to make a backup of its current state,
     * which later can be used to rollback this state should the transaction
     * fail at the end of the transaction (@link epEndTransaction())
     * 
     * @return bool
     */
    public function epStartTransaction() {
        
        // array to hold wrapper vars and object vars
        $this->ep_trans_backup = array();

        // keep serialized wrapper vars
        $this->ep_trans_backup['wrapper_vars'] = $this->_backupWrapperVars();

        // keep serialized object vars
        $this->ep_trans_backup['object_vars'] = $this->_backupObjectVars();

        return true;
    }

    /**
     * Signals the object to end the current transaction
     * 
     * In this method, the object marks the end of the current transaction and
     * if rollback is required, it should fall back to the state before the current
     * transaction was started (@link epStartTransaction). 
     * 
     * @return bool
     */
    public function epEndTransaction($rollback = false) {
        
        // need to roll back?
        if ($rollback) {
            
            // restore (unserialize) wrapper vars
            $this->_restoreWrapperVars($this->ep_trans_backup['wrapper_vars']);
            
            // restore (unserialize) object vars
            $this->_restoreObjectVars($this->ep_trans_backup['object_vars']);
        }
        
        // reset backup to false (not in transaction)
        $this->ep_trans_backup = false;
        
        return true;
    }
    
    /**
     * Updates all the object's inverses 
     * @param $action INVERSE_ADD or INVERSE_REMOVE
     * @return bool
     * @access public
     */
    public function epUpdateInverse($action) {

        $status = true;

        foreach($this->epGetVars() as $var => $val) {

            // exclude empty values and get field map
            if (!$val || !($fm = & $this->_getFieldMap($var))) {
                continue;
            }

            // skip primitive fields
            if ($fm->isPrimitive()) {
                continue;
            }

            // skip also composed_of vars (since they will be deleted 
            // anyway) if action is INVERSE_REMOVE
            if ($action == self::INVERSE_REMOVE) {
                if ($fm->isComposedOf()) {
                    continue;
                }
            }

            // one-valued
            if (!$fm->isMany()) {
                // false: not one_way_only (allow two-way update)
                $status &= $this->_updateInverse($fm, $val, $action, false);
                continue;
            }

            $convert_oid_0 = $val->getConvertOid();
            $val->setConvertOid(false);
            foreach($val as $_val) {
                // false: not one_way_only (allow two-way update)
                $status &= $this->_updateInverse($fm, $_val, $action, false);
            }
            $val->setConvertOid($convert_oid_0);
        }

        return $status;
    }
    
    /**
     * Backup the wrapper vars
     * @return array 
     */
    protected function _backupWrapperVars() {
        
        // vars in this wrapper to be backed up 
        static $wrapper_vars = array(
            'ep_uid',
            'ep_object_id',
            'ep_is_dirty',
            'ep_modified_pvars',
            'ep_modified_rvars',
            );
        
        // put wrapper vars into an array
        $backup = array();
        foreach($wrapper_vars as $var) {
            $backup[$var] = $this->$var;
        }

        return $backup;
    }

    /**
     * Restore the wrapper vars
     * @param array $backup
     * @return void
     */
    protected function _restoreWrapperVars($backup) {
        
        // set vars back to wrapper
        foreach($backup as $var => $value) {
            $this->$var = $value;
        }
    }

    /**
     * Backup the object vars
     * @return array 
     */
    protected function _backupObjectVars() {
        $backup = array();
        foreach($this->ep_cached_vars as $var) {
            // is var primitve?
            if ($this->epIsPrimitive($var)) {
                // primitive var: simply keep value
                $backup[$var] = $this->epObjectGetVar($var);
            }
            else {
                // relationship var: reduce value to uoid
                $backup[$var] = $this->_reduceRelationshipVar($var);
            }
        }
        return $backup;
    }

    /**
     * Restore (unserialize) the wrapper vars
     * @param string $backup
     * @return void
     */
    protected function _restoreObjectVars($backup) {
        // set vars back to wrapped object
        foreach($backup as $var => $value) {
            $this->epObjectSetVar($var, $value);
        }
    }

    /**
     * Reduce relationship vars to UOIDs ({@see epManager::encodeUoid})
     * Alias to protected method {@link _reduceRelationshipVar()}
     * @param string $var_name
     * @return string|array (of string)
     */
    public function reduceRelationshipVar($var_name) {
        $reduced = $this->_reduceRelationshipVar($var_name);
        return is_array($reduced) ? array_values(array_unique($reduced)) : $reduced;
    }

    /**
     * Reduce relationship vars to UOIDs ({@see epManager::encodeUoid})
     * @param string $var_name
     * @return string|array (of string)
     */
    protected function _reduceRelationshipVar($var_name) {
        
        // get the var
        $var = $this->epObjectGetVar($var_name);
        
        // many-valued vars
        if (is_array($var) || $var instanceof epArray) {
            
            // (optimization) turn off oid conversion
            if ($var instanceof epArray) {
                $convert_oid_0 = $var->getConvertOid();
                $var->setConvertOid(false);
            }

            // go through items in array
            $rvar = array();
            foreach($var as $v) {
                if ($v instanceof epObject) {
                    $rvar[] = $this->ep_m->encodeUoid($v);
                }
                else {
                    $rvar[] = $v;
                }
            }

            // (optimization) restoreoid conversion
            if ($var instanceof epArray) {
                $var->setConvertOid($convert_oid_0);
            }

        }
        
        // one-valued var
        else {
            if ($var instanceof epObject) {
                $rvar = $this->ep_m->encodeUoid($var);
            }
            else {
                $rvar = $var;
            }
        }
        
        return $rvar;
    }

    /**
     * Overrides {@link epObjectBase::_pre_call()}
     * Convert all relation oids to objects
     * Preprocess before __call()
     * @return void
     */
    protected function _pre_call() {
        
        // call parent::_pre_call() first
        parent::_pre_call();
        
        // done if manager and class map cannot be found 
        if (!$this->ep_m || !$this->ep_cm) {
            return;
        }
        
		// get vars so relation oids are converted to objects
        if ($npfs = $this->ep_cm->getNonPrimitive()) {
			foreach($npfs as $var_name => $fm) {
				$this->epGet($var_name);
			}
        }

        // keep track of old values
        $this->ep_vars_before = get_object_vars($this->ep_object);
    }
    
    /**
     * Installs the value for a one-valued relatinship var
     * @param string $var_name The name of the variable
     * @param epFieldMap $fm The field map for the variable
     * @param mixed $value The current value for the var
     * @return epObject The installed value
     */
    protected function &_installOneValued($var_name, $fm, $val) {

        // already a full-blown object?
        if ($val instanceof epObject) {
            return $val;
        }

        // fetch uoid
        $uoid = & $this->_getOneValuedUOid($var_name, $fm, $val);
        if (!is_string($uoid)) {
            return $val;
        }

        // get object by uoid (string)
        $val = & $this->ep_m->getByUoid($uoid);
        
        // (clean) set var 
        parent::epSet($var_name, $val, true); // true: clean

        // reget var value
        return parent::epGet($var_name);
    }

    /**
     * Fetches the UOID for a one-valued relationship var
     * @param string $var_name The name of the variable
     * @param epFieldMap $fm The field map for the variable
     * @param mixed $val The current value for the var
     * @return mixed
     */
    protected function &_getOneValuedUOid($var_name, $fm, $val) {
        
        // done if non-empty value
        if ($val) {
            return $val;
        }
        
        // has this object been persisted?
        if (!$this->epGetObjectId()) {
            return $val;
        }

        // has it been modified? if so, no fetch.
        if (isset($this->ep_modified_rvars[$var_name])) {
            return $val;
        }

        // has it been fetched already?
        if (in_array($var_name, $this->ep_rvars_fetched)) {
            return $val;
        }

        // call manager to get relational oids (single string if only one)
        $uoid = $this->ep_m->getRelationIds($this, $fm, $this->ep_cm);

        // mark the relational var fetched
        $this->ep_rvars_fetched[] = $var_name;

        return $uoid;
    }

    /**
     * Installs the value for a many-valued relatinship var
     * @param string $var_name The name of the variable
     * @param epFieldMap $fm The field map for the variable
     * @param mixed $value The current value for the var
     * @return epObject The installed value
     */
    protected function &_installManyValued($var_name, $fm, $val) {

        // already an epArray?
        if ($val instanceof epArray) {
            return $val;
        }

        // if var is not epArray, make one
        $var = new epArray($this, $fm);

        // set it (the new epArray) back
        parent::epSet($var_name, $var, true); // true: no change in dirty flag

        return parent::epGet($var_name); 
    }

    /**
     * Sets the value to a one-valued variable
     * @param string $var_name The name of the variable 
     * @param epFieldMap $fm The field map for the variable 
     * @param mixed $value The value to be set to the variable
     * @param bool $clean Whether or not to set dirty flag  
     * @return bool
     */
    protected function _setOneValued($var_name, $fm, $value, $clean) {

        $status = true;

        // The var might not have been fetched from the database
        // so call $this->epGet but only if we are doing a non clean
        // change as $this->epGet() calls $this->epSet()
        $value0 = $clean ? parent::epGet($var_name) : $this->epGet($var_name);
        if ($value0 && $value0 instanceof epObject) {
            $status &= $this->_updateInverse($fm, $value0, epObject::INVERSE_REMOVE);
        }

        // do the usual stuff
        $status &= parent::epSet($var_name, $value, $clean);

        // udpate inverse
        if ($value && $value instanceof epObject) {
            $status &= $this->_updateInverse($fm, $value, epObject::INVERSE_ADD);
        }

        return $status;
    }

    /**
     * Sets the value to a many-valued variable
     * @param string $var_name The name of the variable 
     * @param epFieldMap $fm The field map for the variable 
     * @param mixed $value The value to be set to the variable
     * @param bool $clean Whether or not to set dirty flag  
     * @return bool
     */
    protected function _setManyValued($var_name, $fm, $value, $clean) {

        // if value is false, null, or neither an array nor an epObject
        if ($value === false || is_null($value) 
            || !is_array($value) && !($value instanceof epObject)) {
            
            $status = true;

            // udpate inverse remove
            if ($value0 = parent::epGet($var_name)) {
                $convert_oid_0 = $value0->getConvertOid();
                $value0->setConvertOid(false);
                foreach($value0 as $_val) {
                    $status &= $this->_updateInverse($fm, $_val, epObject::INVERSE_REMOVE);
                }
                $value0->setConvertOid($convert_oid_0);
            }
            
            // do the usual stuff
            $status &= parent::epSet($var_name, $value, $clean);

            return $status;
        }
        
        // make sure epArray allocated
        $var = & $this->_installManyValued($var_name, $fm, parent::epGet($var_name));

        // arrayize value
        if (!is_array($value) && !($value instanceof epArray)) {
            $value = array($value);
        }

        // copy array value into epArray
        $var->copy($value, $clean);

        return true;

    }

    /**
     * Checks if the value to be set matches the type set in the field map
     * @param epObject|string $value The value to be set
     * @param epFieldMap $fm The field map for the value
     * @return bool
     */
    protected function _typeMatches($value, $fm) {
        
        // no check if no manager
        if (!$this->ep_m) {
            return true;
        }
        
        // no checking on primitve, ignore false|null|empty, and non-epObject
        if ($fm->isPrimitive() || !$value) {
            // always true
            return true;
        }
        
        // epObject
        if ($value instanceof epObject) {
            return $this->_isClassOf($this->ep_m->getClass($value), $fm->getClass());
        }
        
        // array
        else if (is_array($value) || $value instanceof epArray) {
            
            foreach($value as $k => $v) {
                
                if (!($v instanceof epObject)) {
                    continue;
                }
                
                if (!$this->_isClassOf($this->ep_m->getClass($v), $fm->getClass())) {
                    return false;
                }
            }
            return true;
        }
        
        return true;
    }
    
    /**
     * Checks whether a class is or is rooted from a base class
     * @param string $class
     * @param string $base
     * @return bool
     */
    protected function _isClassOf($class, $base) {
        if ($class == $base) {
            return true;
        }
        return is_subclass_of($class, $base);
    }

    /**
     * Updates the value of the inverse var
     * @param epFieldMap $fm The field map toward the inverse var
     * @param epObject &$o The opposite object
     * @param string $actoin The update action to take: INVERSE_ADD|REMOVE
     * @param bool $one_way Whether inverse update is one way only
     * @return bool
     */
    protected function _updateInverse($fm, &$o, $action = epObject::INVERSE_ADD, $one_way = true) {
        
        // check if object is epObject
        if (!$o || !($o instanceof epObject)) {
            return false;
        }

        // no action if an object is updating (to prevent endless loop)
        if ($o->epIsUpdating($action)) {
            return true;
        }

        // get inverse var
        if (!($ivar = $fm->getInverse())) {
            return true;
        }

        // set inverse updating flag
        if ($one_way) {
            $this->epSetUpdating(true, $action);
        }
        $o->epSetUpdating(true, $action);

        // a single-valued field 
        if (!$o->epIsMany($ivar)) {
            switch ($action) {

                case epObject::INVERSE_ADD:
                    $o[$ivar] = $this;
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
                    $o[$ivar][] = $this;
                    break;

                case epObject::INVERSE_REMOVE:
                    $o[$ivar]->remove($this);
                    break;
            }
        }
        
        // reset inverse updating flag
        $o->epSetUpdating(false, $action);
        if ($one_way) {
            $this->epSetUpdating(false, $action);
        }

        return true;
    }

}

?>
