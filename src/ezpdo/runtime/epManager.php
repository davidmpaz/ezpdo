<?php

/**
 * $Id: epManager.php 1044 2007-03-08 02:25:07Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Moises Paz <davidmpaz@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
namespace ezpdo\runtime;

use ezpdo\base\epUtils;
use ezpdo\base\epSingleton;
use ezpdo\base\epConfigurableWithLog;
use ezpdo\base\exception\epExceptionConfigurableWithLog;

use ezpdo\db\epDb;
use ezpdo\db\epDbFactory;
use ezpdo\db\exception\epExceptionDb;
use ezpdo\db\exception\epExceptionDbAdodb;
use ezpdo\db\exception\epExceptionDbAdodbPdo;
use ezpdo\db\exception\epExceptionDbPeardb;

use ezpdo\orm\epClassMap;
use ezpdo\orm\epClassMapFactory;
use ezpdo\compiler\epClassCompiler;

use ezpdo\runtime\exception\epExceptionManager;

/**
 * Class of ezpdo persistence manager
 *
 * This is a subclass of {@link epManagerBase}. {@link epManagerBase}
 * has dealt with the persistence of primitive data types. This class
 * addresses issues related to object relationships.
 *
 * This class also implements the {@link epSingleton} interface.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epManager extends epManagerBase implements epSingleton {

    /**
     * The cached example object for epObjectRelation
     * @var epObjectRelation
     */
    protected $eo_obj_rel = false;

    /**
     * The cached relationship class map
     * @var epClassMap
     */
    protected $cm_obj_rel = false;

    /**
     * The cached prefix of relation table name
     * @var string
     */
    protected $rel_tbl_prefix = '';

    /**
     * Whether to split relation table
     * @var boolean (default to false)
     */
    protected $rel_tbl_split = false;

    /**
     * Constructor
     * @param epConfig|array
     * @access public
     */
    public function __construct($config = null) {
        parent::__construct($config);
    }

    /**
     * Overrides {@link epManagerBase::initialize()}
     * @param bool $force whether to force initialization
     * @return bool
     * @throws epExceptionManager
     */
    protected function initialize($force = false) {

        // call parent epManagerBase to initialize
        $status = parent::initialize($force);

        // check if init'ed before
        if (!$force && $this->cm_obj_rel) {
            return true;
        }

        // check if epObjectRelation has been compiled
        if (!($this->cm_obj_rel = & $this->_getMap('ezpdo\\orm\\epObjectRelation'))) {
            if (!($this->cm_obj_rel = & $this->_compileClass('ezpdo\\orm\\epObjectRelation'))) {
                throw new epExceptionManager('Failed in compiling class ezpdo\\orm\\epObjectRelation');
                return false;
            }
        }

        // append overall table prefix
        $this->rel_tbl_prefix = $this->getConfigOption('relation_table');
        if ($prefix = $this->getConfigOption('table_prefix')) {
            $this->rel_tbl_prefix = epUtils::epUnquote($prefix) . $this->rel_tbl_prefix;
        }

        // set relation table name
        $this->cm_obj_rel->setTable($this->rel_tbl_prefix);

        // set default dsn to relation table
        $this->cm_obj_rel->setDsn($this->getConfigOption('default_dsn'));

        // cache relation table splitting flag
        $this->rel_tbl_split = $this->getConfigOption('split_relation_table');

        // auto update schema?
        if ($this->getConfigOption('auto_update')){
            if ($this->su && ($strat = $this->su->getStrategy())) {
                if ($strat == 'drop'){
                    // drop all tables
                    $this->dropTables();
                }else{
                    // if it is set strategy and is not drop, it is alter|sim
                    $this->alterTables();
                }
            }
        }

        return $status;
    }

    /**
     * Commits an object
     * @param object $o
     * @return bool $force (ignored - treated as false!)
     * @access public
     */
    public function commit(&$o, $force = false) {

        // make sure we are dealing with an epObject
        if (!($o instanceof epObject)) {
            return false;
        }

        // check if the object should be commited
        if (!$force && !$o->epNeedsCommit()) {
            return true;
        }

        // get class map for class
        if (!($cm = & $o->epGetClassMap())) {
            return false;
        }

        // get class name
        if (!($class = $cm->getName())) {
            return false;
        }

        // check if class has any non-primitive fields
        if (!($npfs = $cm->getNonPrimitive())) {
            return $this->_commit_o($o);
        }

        // set object is being commited
        $o->epSetCommitting(true);

        // get all modified relationship vars
        $modified_rvars = $o->epGetModifiedVars(epObject::VAR_RELATIONSHIP);

        // array to keep track of 1-to-many relations
        $relations = array();

        // go through each non-primitive field
        $status = true;
        foreach($npfs as $name => $fm) {

            // initialize arrays to hold relation oids
            if (isset($modified_rvars[$name])) {
                $relations[$name] = array();
                $relations[$name][$fm->getBase_b()] = array('new' => array(), 'old' => null);
            }

            // var field value
            if (!($v = & $o->epGet($name))) {
                continue;
            }

            // check if value is array
            if (is_array($v) || $v instanceof epArray) {

                // check if it is a "many" field
                if (!$fm->isMany()) {
                    throw new epExceptionManager('Value (array) of variable [' . $cm->getName() . "::" . $fm->getName() . '] and its field map mismatch');
                    continue;
                }

                // no convertion from oid to object
                if ($v instanceof epArray) {
                    $convert_oid_0 = $v->getConvertOid();
                    $v->setConvertOid(false);
                }

                // go through each value in $v
                $oids = array();
                foreach($v as &$w) {

                    if (is_string($w)) {

                        $oids[] = $w;

                    } else if (is_object($w) && ($w instanceof epObject)) {

                        // ignore deleted object
                        if ($w->epIsDeleted()) {
                            continue;
                        }

                        // commit the value (object)
                        if ($w->epIsCommitting()) {

                            if (!$w->epGetObjectId()) {
                                // if the object is to be commited, do a simple commit to get the oid
                                $status &= $this->_commit_o($w, true); // true: simple commit
                            }

                        } else {

                            // for not in to-be-committed queue
                            $status &= $this->commit($w);
                        }

                        if ($w->epIsCommittable()) {
                            // collect oid
                            $oids[] = $this->encodeUoid($w);
                        }
                    }

                }

                // set convert id flag back
                if ($v instanceof epArray) {
                    $v->setConvertOid($convert_oid_0);
                }

                // put oids into the relation array
                if (isset($modified_rvars[$name])) {
                    $new = $oids;
                    $old = null;
                    if ($v instanceof epArray) {
                        $old = array();
                        $new = array();

                        $origArray = $v->getOrigArray();

                        // figure out how many of each object is in the relationships
                        $oidscount = array();
                        $oidsduplicate = array();
                        foreach ($oids as $oid) {
                            if (!isset($oidscount[$oid])) {
                                $oidscount[$oid] = 0;
                            }
                            $oidscount[$oid]++;
                            if ($oidscount[$oid] > 1) {
                                // keep track of the duplicate ones
                                $oidsduplicate[$oid] = $oidscount[$oid];
                            }
                        }
                        $origcount = array();
                        $origduplicate = array();
                        foreach ($origArray as $oid) {
                            if (!isset($origcount[$oid])) {
                                $origcount[$oid] = 0;
                            }
                            $origcount[$oid]++;
                            if ($origcount[$oid] > 1) {
                                // keep track of the duplicate ones
                                $origduplicate[$oid] = $origcount[$oid];
                            }
                        }

                        // grab the differences between the original and the new one
                        $new = array_diff($oids, $origArray);
                        $old = array_diff($origArray, $oids);

                        // check for duplicates because array_diff doesn't care about them
                        if (count($origduplicate) > 0 || count($oidsduplicate) > 0) {
                            // find out if there are any duplicate counts
                            // This is done by going through all the original duplicates
                            // and checking:
                            // 1. Are they even in the new oids
                            //   a. If not, the array_diff already took care of it
                            //   b. If so, check the count to see if there is a difference
                            //     i. If not, leave it alone
                            //     ii. If so, check if greater or lesser
                            //        I. orig is greater: delete them all and add them back to new
                            //        II. new is greater: add the difference to new
                            //
                            // Also, delete the oidsduplicates that we see so we can check if we have
                            // any new duplicates.
                            // Just add one less than the count to the new for the new duplicates. This
                            // is because if they are brand new, $new will already have on entry, and if
                            // there are not new, the relationship already contains one of them.
                            foreach ($origduplicate as $oid => $count) {
                                if (isset($oidscount[$oid])) {
                                    if ($oidscount[$oid] > $count) {
                                        $new = array_merge($new, array_fill(0, $oidscount[$oid] - $count, $oid));
                                    } elseif ($oidscount[$oid] < $count) {
                                        $new = array_merge($new, array_fill(0, $oidscount[$oid], $oid));
                                        $old[] = $oid;
                                    }
                                    unset($oidsduplicate[$oid]);
                                }
                            }

                            foreach ($oidsduplicate as $oid => $count) {
                                $new = array_merge($new, array_fill(0, $count - 1, $oid));
                            }
                        }

                        // set the new original Array
                        $v->setOrigArray($oids);
                    }
                    $relations[$name][$fm->getBase_b()]['new'] = $new;
                    $relations[$name][$fm->getBase_b()]['old'] = $old;
                }

            } else {

                $oid = false;

                if (is_string($v)) {

                    $oid = $v;

                } else if (is_object($v) && ($v instanceof epObject)) {

                    // check if it is a "One" field map
                    if (!$fm->isSingle()) {
                        throw new epExceptionManager('Variable value (array) and field map (One) mismatch');
                        continue;
                    }

                    // ignore deleted object
                    if ($v->epIsDeleted()) {
                        continue;
                    }

                    // commit the value (object)
                    if ($v->epIsCommitting()) {

                        if (!$v->epGetObjectId()) {
                            // if the object is to be commited, do simple commit to get the oid
                            $status &= $this->_commit_o($v, true); // true: simple commit
                        }

                    } else {

                        // object not in to-be-committed queue. force commit
                        $status &= $this->commit($v);

                    }

                    if ($v->epIsCommittable()) {
                        // collect oid
                        $oid = $this->encodeUoid($v);
                    }
                }

                // put oid into the relation array
                if ($oid !== false) {
                    if (isset($modified_rvars[$name])) {
                        $relations[$name][$fm->getBase_b()]['new'] = array($oid);
                        $relations[$name][$fm->getBase_b()]['old'] = null;
                    }
                }
            }
        }

        // make object committable
        $o->epSetCommitting(false);
        if ($o->epNeedsCommit()) {
            $status &= $this->_commit_o($o);
        }

        // update object relation for has_many or composed_of_many fields
        foreach($relations as $var_a => $relation) {
            $base_a = $cm->getField($var_a)->getBase_a();
            foreach($relation as $base_b => $b_oids) {
                $status &= $this->_updateRelations($base_a, $class, $o->epGetObjectId(), $var_a, $base_b, $b_oids['new'], $b_oids['old']);
            }
        }

        return $status;
    }

    /**
     * Overrides {@link epManagerBase::delete()} to deal with object relationships
     * Delete all objects in a class and all its subclasses
     * @param string class
     * @return bool
     * @access public
     */
    public function deleteAll($class) {

        // get class map for class
        if (!($cm = & $this->_getMap($class))) {
            return false;
        }

        // get all subclasses (true: recursive)
        $cms = $cm->getChildren(true);

        // add this class
        $cms[] = $cm;

        // go through each class
        $status = true;
        foreach($cms as $cm) {
            $status &= $this->_deleteAll($cm);
        }

        return $status;
    }

    /**
     * Delete all objects in one class BUT NOT in its subclasses
     * object relationships
     * @param epClassMap $cm
     * @return bool
     * @access protected
     */
    protected function _deleteAll(epClassMap $cm) {

        // get class name
        $class = $cm->getName();

        // check if class has any non-primitive fields
        if (!$cm->hasNonPrimitive()) {
            // if not, simply call parent to delete all
            return parent::deleteAll($class);
        }

        // check if class has any composed_of fields
        if (!($cofs = $cm->getComposedOf())) {

            // call parent to delete all
            $status = parent::deleteAll($class);

            // remove relations from and to the class
            $status &= $this->_deleteRelations($class);
            return $status;
        }

        // otherwise, we need to go through each object and delete the composed_of fields
        // this is an expensive operation. should be optimized.

        // get all objects of the class
        if (!($os = $this->getAll($class))) {
            return true;
        }

        // delete every object
        $status = true;
        foreach($os as $o) {
            $status &= $this->delete($o);
        }

        return $status;
    }

    /**
     * Overrides {@link epManagerBase::delete()} to deal with object relationships
     * @param object
     * @return bool
     * @access public
     */
    public function delete(&$o = null) {

        // get class name
        if (!($class = $this->_getClass($o))) {
            return false;
        }

        // get class map for class
        if (!($cm = & $this->_getMap($class))) {
            return false;
        }

        // get oid before it's deleted
        $oid = $o->epGetObjectId();

        // remove inverse relationships from memory
        $o->epUpdateInverse(epObject::INVERSE_REMOVE);

        // check if class has any composed_of fields
        if (!($cofs = $cm->getComposedOf())) {

            // if not, delete all relations from and to the object
            $status = parent::delete($o);

            // remove relations from and to the class
            $status &= $this->_deleteRelations($class, $oid);

            return $status;
        }

        // otherwise, go through composed_of fields and delete each object composed of
        $status  = true;
        foreach($cofs as $cof) {

            // get the value for the composed_of field
            if (!($val = $o->epGet($cof->getName()))) {
                continue;
            }

            // is the field value an array? if so, delete recursively
            if (!is_array($val) && !($val instanceof epArray) ) {
                $status &= $this->delete($val);
            } else {
                foreach($val as $val_) {
                    $status &= $this->delete($val_);
                }
            }
        }

        $status &= parent::delete($o);
        $status &= $this->_deleteRelations($class, $oid);

        return $status;
    }

    /**
     * Returns the relation ids for the variable specified of the object
     * @param epObject $o the object
     * @param epFieldMap $fm the field map of the variable
     * @param epClassMap $cm the class map of the object
     * @return false|string|array
     */
    public function getRelationIds(&$o, $fm, $cm) {

        // make sure we are dealing with valid object and non-primitive field
        if (!$o || !$fm  || !$cm) {
            return false;
        }

        // object needs to have a valid id and has to be non-primitive
        if (!($oid_a = $o->epGetObjectId()) /* || $fm->isPrimitive() */) {
            return false;
        }

        // get class_a, var_a, and the related class
        $base_a = $fm->getBase_a();
        $class_a = $cm->getName();
        $var_a = $fm->getName();
        $base_b = $fm->getBase_b();
        if (!$base_a ||!$class_a || !$var_a || !$base_b) {
            throw new epExceptionManager('Cannot find related class for var [' . $class_a . '::' . $var_a . ']');
            return false;
        }

        // switch relations table
        $this->_setRelationTable($base_a, $base_b);

        // make an example relation objects
        if (!($eo = & $this->_relationExample($class_a, $oid_a, $var_a, $base_b))) {
            return false;
        }

        // find all relation objects using the example object
        // (find from db only, false: no cache, false: don't convert to objects)
        $rs = & parent::find($eo, EP_GET_FROM_DB, false, false);

        // convert result into oids
        $oids_b = null;
        if ($fm->isSingle()) {

            if (is_array($rs) && count($rs) > 1) {
                throw new epExceptionManager('Field ' . $fm->getName() . ' mapped as composed_of_/has_one but is associated with > 1 objects');
                return false;
            }

            if ($rs) {
                $oids_b = $rs[0];
            }

        } else if ($fm->isMany()) {

            $oids_b = array();
            if ($rs) {
                $oids_b = $rs;
            }

        }

        // always return unique oids
		return is_array($oids_b) ? array_unique($oids_b) : $oids_b;
    }

    /**
     * Delete one-to-many relations between class_a and class_b.
     * @param string $class name of class
     * @param integer|null $oid object id
     * @return bool
     */
    protected function _deleteRelations($class, $oid = null) {

        // split relation table?
        if (!$this->rel_tbl_split) {
            // delete with no split
            return $this->_deleteRelationsNoSplit($class, $oid);
        }

        // delete relation with split
        return $this->_deleteRelationsSplit($class, $oid);
    }

    /**
     * Delete one-to-many relations between class_a and class_b
     * under split_relation_table mode
     * @param string $class name of class
     * @param integer|null $oid object id
     * @return bool
     */
    protected function _deleteRelationsSplit($class, $oid = null) {

        $status = true;

        foreach($this->_getRelationPairs($class) as $base_a_b) {

            // split base a and b
            list($base_a, $base_b) = explode(' ', $base_a_b);

            // switch relation table
            $this->_setRelationTable($base_a, $base_b);

            // delete relation in the current table
            $status &= $this->_deleteRelationsNoSplit($class, $oid, null, null);
        }

        return $status;
    }

    /**
     * Delete one-to-many relations between class_a and class_b
     * under no split_reltion_table mode
     * @param string $class name of class
     * @param integer|null $oid object id
     * @return bool
     */
    protected function _deleteRelationsNoSplit($class, $oid = null) {

        // get db for object relationship
        if (!($db = & $this->_getDb($this->cm_obj_rel))) {
            return false;
        }

        // call db to update relationship
        return $db->deleteRelationship($this->cm_obj_rel, $class, $oid);
    }

    /**
     * Updates one-to-many relation between two classes
     *
     * Relations from $class_a stored that are not in array $oids_b are deleted and
     * new relations to $class_b objects in $oids_b are added.
     *
     * @param string $base_a name of base class a
     * @param string $class_a name of class a
     * @param integer $oid_a object id of the class a object
     * @param integer $var_a the relational field of object a
     * @param string $base_b name of base b
     * @param array $oids_b_new oids of the class b object related to the class a object that are new
     * @param array $oids_b_old oids of the class b object related to the class a object that are old (null if all old ones should be deleted)
     * @return bool
     */
    protected function _updateRelations($base_a, $class_a, $oid_a, $var_a, $base_b, $oids_b_new = array(), $oids_b_old = null) {

        // need to have a non-empty name
        if (!$base_a || !$class_a || !$oid_a || !$var_a|| !$base_b) {
            throw new epExceptionManager('Incorrect parameters to update relations');
            return false;
        }

        // switch relations table
        $this->_setRelationTable($base_a, $base_b);

        // get db for object relationship
        if (!($db = & $this->_getDb($this->cm_obj_rel))) {
            return false;
        }

        // decode oids_b
        $oids_b_new_decoded = array();
        foreach($oids_b_new as $oid_b) {
            $this->decodeUoid($oid_b, $class, $oid);
            $oids_b_new_decoded[] = array('class' => $class, 'oid' => $oid);
        }

        if (is_array($oids_b_old)) {
            $oids_b_old_decoded = array();
            foreach($oids_b_old as $oid_b) {
                $this->decodeUoid($oid_b, $class, $oid);
                $oids_b_old_decoded[] = $oid;
            }
        } else {
            $oids_b_old_decoded = $oids_b_old;
        }

        // call db to update relationship
        return $db->updateRelationship($this->cm_obj_rel, $class_a, $var_a, $oid_a, $base_b, $oids_b_new_decoded, $oids_b_old_decoded);
    }

    /**
     * Returns the example object of {@link epObjectRelation}
     *
     * The example object will only be created once and used later by changing the
     * values of its vars. This saves memory and a little execution time.
     *
     * @param string $class_a name of class a
     * @param integer $oid_a object id of the class a object
     * @param integer $var_a the relational field of object a
     * @param string $base_b name of base b
     * @param string $class_b name of class b
     * @param integer $oid_b oid of the class b object
     * @return false|epObjectRelation
     */
    protected function &_relationExample($class_a = null, $oid_a = null, $var_a = null, $base_b = null, $class_b = null, $oid_b = null) {

        // check if the example object has been created
        if ($this->eo_obj_rel) {

            // set values to vars
            $this->eo_obj_rel->class_a = $class_a;
            $this->eo_obj_rel->oid_a   = $oid_a;
            $this->eo_obj_rel->var_a   = $var_a;
            $this->eo_obj_rel->base_b  = $base_b;
            $this->eo_obj_rel->class_b = $class_b;
            $this->eo_obj_rel->oid_b   = $oid_b;

            return $this->eo_obj_rel;
        }

        // need to create example object (false: no caching, false: no event dispatching)
        $this->eo_obj_rel = & parent::_create('ezpdo\\orm\\epObjectRelation', false, false, array($class_a, $oid_a, $var_a, $base_b, $class_b, $oid_b));
        if (!$this->eo_obj_rel) {
            throw new epExceptionManager('Cannot create relation object');
            return false;
        }

        return $this->eo_obj_rel;
    }

    /**
     * Get relation pairs for a given class
     *
     * Calls class map factory to get all relation field maps for the class
     * and sorts out redundant pairs
     *
     * @return array
     */
    protected function _getRelationPairs($class) {

        // return value (array)
        $pairs = array();

        // call class map factory to get all relation fields that involves the given class
        if (!($fms = $this->cmf->getRelationFields($class))) {
            return $pairs;
        }

        // go through one by one
        $pairs = array();
        foreach($fms as $fm) {

            // get base_a and base_b
            $base_a = $fm->getBase_a();
            $base_b = $fm->getBase_b();

            // swap base_a and base_b if a > b
            if ($base_a > $base_b) {
                $t = $base_a;
                $base_a = $base_b;
                $base_b = $t;
            }
            $base_a_b = $base_a . ' ' . $base_b;

            // check if pair has been seen
            if (in_array($base_a_b, $pairs)) {
                continue;
            }

            $pairs[] = $base_a_b;
        }

        // return the pairs
        return $pairs;
    }

    /**
     * Returns the relation table accroding to current settting
     * @param string $base_a
     * @param string $base_b
     * @return string
     */
    public function getRelationTable($base_a, $base_b) {

        // are we in split mode?
        if (!$this->rel_tbl_split) {
            // return the single relation table name
            return $this->rel_tbl_prefix;
        }

        // make relation table postfix
        if ($base_a < $base_b) {
            $postfix = strtolower($base_a . '_' .  $base_b);
        } else {
            $postfix = strtolower($base_b . '_' .  $base_a);
        }

        // append postfix
        $table = $this->rel_tbl_prefix;
        if ($table[strlen($table) - 1] != '_') {
            $table .= '_';
        }
        $table .= $postfix;

        return $table;
    }

    /**
     * Switches table name for the object relations class
     * ({@link epObjectRelation}). The public method of
     * {@link _setRelationTable()}.
     *
     * If the second parameter is default to false, the first is the
     * table name. Otherwise the two parameters are a pair of base
     * classes for a relationship.
     *
     * @param string $table_or_base_a
     * @param string $base_b
     * @return void
     */
    public function setRelationTable($table_or_base_a, $base_b = false) {
        $this->_setRelationTable($table_or_base_a, $base_b);
    }

    /**
     * Changes table name for object relations class
     *
     * If the second parameter is default to false, the first is the
     * table name. Otherwise the two parameters are a pair of base
     * classes for a relationship.
     *
     * @param string $table_or_base_a
     * @param string $base_b
     * @return void
     */
    protected function _setRelationTable($table_or_base_a, $base_b = false) {

        // get relation table name (w/o prefix)
        $table = $table_or_base_a;
        if ($base_b) {
            $table = $this->getRelationTable($table_or_base_a, $base_b);
        }

        // fix bug 175: make DSN always the same as class_a or class_b
        if ($base_b) {

            // set dsn to relation table
            $this->cm_obj_rel->setDsn($this->_getRelationDsn($table_or_base_a, $base_b));

            // remove cached db for relation table
            if (isset($this->dbs[$this->cm_obj_rel->getName()])) {
                unset($this->dbs[$this->cm_obj_rel->getName()]);
            }
        }

        // set table name for relationship
        $this->cm_obj_rel->setTable($table);
    }

    /**
     * Returns the DSN for relationship table of two classes. Also checks
     * if the two classes are in the same DSN.
     * @param string $class_a
     * @return false|string
     * @throws epExceptionManager
     */
    protected function _getRelationDsn($class_a, $class_b) {

        // get class map a
        if (!($cm_a = $this->_getMap($class_a))) {
            throw new epExceptionManager('Cannot get class map for class [' . $class_a . ']');
            return false;
        }

        // get dsn for class a
        if (!($dsn_a = $cm_a->getDsn())) {
            throw new epExceptionManager('Cannot get DSN for class [' . $class_a . ']');
            return false;
        }

        // get class map b
        if (!($cm_b = $this->_getMap($class_b))) {
            throw new epExceptionManager('Cannot get class map for class [' . $class_b . ']');
            return false;
        }

        // get dsn for class b
        if (!($dsn_b = $cm_b->getDsn())) {
            throw new epExceptionManager('Cannot get DSN for class [' . $base_b . ']');
            return false;
        }

        if ($dsn_a != $dsn_b) {
            throw new epExceptionManager('DSNs of classes ['. $dsn_a .', ' . $dsn_b . '] mismatch');
            return false;
        }

        return $dsn_a;
    }

    /**
     * Get object by universal oid
     * @param string $uoid The universal object id
     * @return false|epObject
     */
    public function &getByUoid($uoid) {

        // string: encoded objected id
        if (!is_string($uoid)) {
            return false;
        }

        // ask manager to decode uoid
        if (!$this->decodeUoid($uoid, $class, $oid)) {
            return false;
        }

        // let manager find object with class and oid
        return $this->get($class, $oid);
    }

    /**
     * Get object by universal oids
     * @param array $uoids The universal object oids
     * @return false|array of epObject (with same index as $uoids)
     */
    public function getByUoids($uoids) {

        // string: encoded objected id
        if (!is_array($uoids)) {
            return false;
        }

        $oids_class = array();
        foreach ($uoids as $uoid) {
            // ask manager to decode universal oid
            if (!$this->decodeUoid($uoid, $class, $oid)) {
                return false;
            }
            $oids_class[$class][] = $oid;
        }

        $objects = array();

        // get all the objects for the different classes
        foreach ($oids_class as $class => $oids) {
            $objects = array_merge($objects, $this->getAll($class, EP_GET_FROM_BOTH, $oids));
        }

        return $objects;
    }

    /**
     * Generate a string that can be used to uniquely identify an object.
     * False is returned if invalid object or object does not have oid yet.
     * @param epObject $o
     * @return false|string
     */
    public function encodeUoid(epObject $o) {
        if (!$o || !($oid = $o->epGetObjectId())) {
            return false;
        }
        // put class name and id into simple format: "<class>:<id>"
        return $this->_encodeUoid($o->epGetClass(), $oid);
    }

    /**
     * Lower level method called by epManager::encodeUoid()
     * @param string $class
     * @param string $oid
     * @return false|string
     */
    protected function _encodeUoid($class, $oid) {
        return $class . ':' . $oid;
    }

    /**
     * The reverse of epManager::encodeUoid()
     * @param string $s (the encoded object id)
     * @param string $class (to hold class name)
     * @param string $oid (to hold object id)
     * @return boolean
     */
    public function decodeUoid($s, &$class, &$oid) {

        if (!$s || strpos($s,':') <= 0) {
            return false;
        }

        // split by ':'
        list($class, $oid) = explode(':', $s);
        $oid = (integer)$oid;

        // class and oid should not be null
        return ($class && $oid);
    }

    /**
     * Changes DSN for classes at runtime.
     *
     * Working assumption: the class hierarchy of the input classes
     * of which you want to change DSN, all their related classes
     * and their relationship tables should be confined in one
     * database (i.e. one DSN) to insure data integrity and queries
     * to work.
     *
     * If no class name is specified, all compiled classes will
     * change their DSN to the new one.
     *
     * @param string $dsn The targeted DSN
     * @param string ... Names of classes to change to the target DSN
     * @return boolean
     * @todo Needs to check if class hiearchies and relations is in one db.
     */
    public function setDsn($dsn) {

        // DSN should not be empty
        if (!$dsn) {
            return false;
        }

        // need to initialize if not yet done (no forcing)
        $this->initialize();

        // get all classes
        $classes = func_get_args();
        array_shift($classes);

        // call class map factory to switch dsn
        if (!($cms = $this->cmf->setDsn($dsn, $classes))) {
            return false;
        }

        // reset cached dbs
        $this->dbs = array();

        return true;
    }

    /**
     * Create all tables for classes mapped so far
     *
     * This method may be useful if you want to create tables all at once.
     *
     * @return false|array (of classes for which tables are created)
     * @access public
     */
    public function createTables() {

        // if class map factory does not exist yet
        if (!$this->cmf || !$this->cm_obj_rel) {
            // initialize
            $this->initialize(true);
        }

        // done if no class at all
        if (!($cms = $this->cmf->allMade())) {
            return array();
        }

        // array to hold classes/relation tables done
        $classes_done = array();

        // reset dbs cache so they will be created
        $this->dbs = array();

        // go through all classes mapped
        foreach($cms as $cm) {

            // get class name
            $class = $cm->getName();

            // relation table dealt with for each class
            if ($class == $this->cm_obj_rel->getName()) {
                continue;
            }

            // create table by calling db
            if ($db = $this->_getDb($cm)) {
                $classes_done[] = $class;
            }

            // create relation tables
            foreach($this->_getRelationPairs($class) as $base_a_b) {

                // don't try to create indexes twice
                if (in_array($base_a_b, $classes_done)) {
                    continue;
                }

                // split base a and b
                list($base_a, $base_b) = explode(' ', $base_a_b);

                // switch relation table
                $this->_setRelationTable($base_a, $base_b);

                // call _getDb to create table
                if ($this->_getDb($this->cm_obj_rel)) {
                    $classes_done[] = $base_a_b;
                }
            }
        }

        return $classes_done;
    }

    /**
     * Alter all tables for classes mapped so far
     *
     * This method may be useful if you want to update your schema
     * with class changes.
     *
     * @return false|array (of classes for which tables were altered)
     * @access public
     */
    public function alterTables() {

        // if class map factory does not exist yet
        if (! ($this->cmf && $this->cm_obj_rel)) {
            // initialize
            $this->initialize(true);
        }

        // not initialized the updater
        if (!$this->su){
            $this->su = epDbUpdate::instance();
        }

        // done if no class at all
        if (!($cms = $this->cmf->allMade())) {
            return array();
        }

        // array to hold queries per classes tables done
        $classes_done = array();

        // reset dbs cache so they will be created
        $this->dbs = array();

        // do we force updates ?
        $force = $this->getConfigOption("force_update");

        // do we execute queries or log them ?
        $update = $this->getConfigOption('update_strategy') == 'alter';

        try{
            // go through all classes
            foreach($cms as $cm) {

                // done if abstract class
                if($cm->isAbstract()){
                    continue;
                }

                // get class name
                $class = $cm->getName();

                // relation table dealt with for each class
                if ($class == $this->cm_obj_rel->getName()) {
                    continue;
                }

                // call updater to alter
                if($ret = $this->su->updateSchema($cm, $update, $force)){
                    $classes_done[] = $ret;
                }

            }

            // at this point, if there is any class map outdated not processed
            // it is not used, could be said the class was deleted
            if($force && $update){
                // only clean up automatically if requested
                $this->su->cleanupSchema();
            }
        }
        catch (epExceptionDb $edb){

            // if we are not using "update from file" feature
            if (!$this->getConfigOption('update_from')){
                // unsucessfull update, need to delete last backup
                epRmBackup($this->getConfigOption('compiled_dir'));
            }

            // continue with the exception
            throw $edb;
        }

        return $classes_done;
    }

    /**
     * Drops all tables for classes mapped so far
     *
     * This method may be useful if you want to drop tables all at once.
     *
     * @return false|array (of classes for which tables are drops)
     * @access public
     */
    public function dropTables() {

        // if class map factory does not exist yet
        if (!$this->cmf || !$this->cm_obj_rel) {
            // initialize
            $this->initialize(true);
        }

        // done if no class at all
        if (!($cms = $this->cmf->allMade())) {
            return array();
        }

        // array to hold classes/relation tables done
        $classes_done = array();

        // go through all classes mapped
        foreach($cms as $cm) {

            // get class name
            $class = $cm->getName();

            // relation table dealt with for each class
            if ($class == $this->cm_obj_rel->getName()) {
                continue;
            }

            if ($db = $this->_getDb($cm)) {
                $db->drop($cm);
                $classes_done[] = $class;
            }

            // create relation tables
            foreach($this->_getRelationPairs($class) as $base_a_b) {

                // don't try to create indexes twice
                if (in_array($base_a_b, $classes_done)) {
                    continue;
                }

                // split base a and b
                list($base_a, $base_b) = explode(' ', $base_a_b);

                // switch relation table
                $this->_setRelationTable($base_a, $base_b);

                if ($this->_getDb($this->cm_obj_rel)) {
                    $db->drop($this->cm_obj_rel);
                    $classes_done[] = $base_a_b;
                }
            }
        }

        return $classes_done;
    }

    /**
     * Create indexes all at once
     *
     * This method may be useful if you want to create indexes all at once
     * or create indexes for already created tables.
     *
     * @return array (The array of classes and relationship tables with indexes created)
     * @access public
     */
    public function createIndexes() {

        // initialize tables
        if (!$this->cmf || !$this->cm_obj_rel) {
            $this->initialize();
        }

        // done if no classes at all
        if (!($cms = $this->cmf->allMade())) {
            return array();
        }

        // array to hold classes/tables with indexes created
        $classes_done = array();

        // go through all classes mapped
        foreach($cms as $cm) {

            // get class name
            $class = $cm->getName();

            // relation table dealt with for each class
            if ($class == $this->cm_obj_rel->getName()) {
                continue;
            }

            // don't try to create indexes twice
            if (!in_array($class, $classes_done)) {
                // create indexes on the class
                if ($db =& $this->_getDb($cm)) {
                    $db->index($cm);
                    $classes_done[] = $class;
                }
            }

            // create indexes on the object relations for the class
            foreach($this->_getRelationPairs($class) as $base_a_b) {

                // don't try to create indexes twice
                if (in_array($base_a_b, $classes_done)) {
                    continue;
                }

                // split base a and b
                list($base_a, $base_b) = explode(' ', $base_a_b);

                // switch relation table
                $this->_setRelationTable($base_a, $base_b);

                // call db to create index
                if ($db =& $this->_getDb($this->cm_obj_rel)) {
                    $db->index($this->cm_obj_rel);
                    $classes_done[] = $base_a_b;
                }
            }
        }

        return $classes_done;
    }

    /**
     * Implements {@link epSingleton} interface
     * @return epBase (instance)
     * @access public
     * @static
     */
    static public function &instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Implement {@link epSingleton} interface
     * Forcefully destroy old instance (only used for tests).
     * After reset(), {@link instance()} returns a new instance.
     */
    static public function destroy() {
        self::$instance = null;
    }

    /**
     * self instance
     * @var epManagerBase
     * @static
     */
    static protected $instance;
}
