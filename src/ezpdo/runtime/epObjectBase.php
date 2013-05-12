<?php

/**
 * $Id: epObjectBase.php 1050 2007-06-19 10:54:09Z nauhygon $
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
use ezpdo\base\epOverload;
use ezpdo\base\exception\epException;

use ezpdo\orm\epFieldMapRelationship;

use ezpdo\runtime\exception\epExceptionObject;

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
class epObjectBase extends epOverload implements \IteratorAggregate, \ArrayAccess, \Countable {

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
    public function &epGetClassMap() {
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
        return new \ArrayIterator($vars);
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
