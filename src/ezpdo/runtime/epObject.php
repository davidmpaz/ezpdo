<?php

/**
 * $Id: epObject.php 1050 2007-06-19 10:54:09Z nauhygon $
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

        // nothing to restore?
        if(!$backup){
            return;
        }

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
        // nothing to restore ?
        if(!$backup){
            return;
        }

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
