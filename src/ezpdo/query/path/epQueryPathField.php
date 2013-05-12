<?php

/**
 * $Id: epQueryPathField.php 1036 2007-01-31 12:20:00Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Moises Paz <davidmpaz@gmail.com>
 * @version $Revision: 1036 $
 * @package ezpdo
 * @subpackage ezpdo.query.path
 */
namespace ezpdo\query\path;

use ezpdo\query\exception\epExceptionQueryPath;

use ezpdo\orm\epFieldMap;
use ezpdo\orm\epFieldMapPrimitive;

use ezpdo\runtime\epManager;

/**
 * Class of a non-root node in the path expression tree
 *
 * A node in the path expression tree other than the root node is
 * associated to a field map ({@link epFieldMap}).
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1036 $
 * @package ezpdo
 * @subpackage ezpdo.query.path
 */
class epQueryPathField extends epQueryPathNode {

    /**
     * The field map this tree node is associated to
     * @var epFieldMap
     */
    protected $fm;

    /**
     * Aliases for relationship tables and their subclasses
     * @var array
     */
    protected $table2alias = false;

    /**
     * Constructor
     * @param epFieldMap & $fm
     * @param epQueryAliasManager &am
     */
    public function __construct(epFieldMap $fm) {

        // call parent to set up alias manager
        parent::__construct($fm->getName());

        // set field map
        $this->fm = $fm;

        // reset table2alias for relation table
        $this->table2alias = array();
    }

    /**
     * Returns field class map
     * Implements {@link epQueryPathNode::getMap()}
     * @return epFieldMap
     */
    public function &getMap() {
        return $this->fm;
    }

    /**
     * Generates SQL statement for -this- node and set up
     * aliases for all classes (root class and subclasses)
     * @return false|string
     * @throws epExceptionQueryPath
     */
    protected function _generateSql() {

        // -no- sql for primitive field
        if ($this->fm->isPrimitive()) {
            // only pass aliases from the parent to children
            $this->class2alias = $this->getParent()->getAliases();
            return '';
        }

        // get base a
        $base_a = $this->fm->getBase_a();

        // get base_b
        $base_b = $this->fm->getBase_b();

        // get class alias from parent
        if (!($class2alias_a = $this->getParent()->getAliases())) {
            throw new epExceptionQueryPath("no aliases found from parent node");
            return false;
        }

		// check if spcific class is set. use it only if so.
		if ($sc = $this->specificClass()) {
			// get class map of specific class
			if (!($cm_b = $this->_em()->getClassMap($sc))) {
				throw new epExceptionQueryPath("no class map for classes and '$sc'");
				return false;
			}
		} else {
			// get class map of base b
			if (!($cm_b = $this->_em()->getClassMap($base_b))) {
				throw new epExceptionQueryPath("no class map for classes and '$base_b'");
				return false;
			}
		}

        // get relationship table with base_a and base_b
        if (!($rt = $this->_em()->getRelationTable($base_a, $base_b))) {
            throw new epExceptionQueryPath("no relationship table for classes '$base_a' and '$base_b'");
            return false;
        }

        $aliases = array();
        $aliases[] = '';

        // check if we have any children contained
        $children_contained = false;
        if ($children = $this->getChildren()) {
            foreach($children as $child) {
                if ($child->isRoot() && $child->isContained()) {
                    $aliases[] = $child->getAlias();
                    $children_contained = true;
                }
            }
        }

        $sql = '';

        // go through each contained child
        foreach ($aliases as $alias) {

            // get alias for relationship table
            if (!isset($this->table2alias[$rt.'.'.$alias])) {
                $rt_alias = $this->_am()->getTableAlias($rt, true);
                $this->table2alias[$rt.'.'.$alias] = $rt_alias;
            } else {
                $rt_alias = $this->table2alias[$rt.'.'.$alias];
            }

            // generate sql for class_a (including its subclasses)
            if ($alias || !$children_contained) {
                $sql .= $this->_generateSqlClassA($rt, $rt_alias, $this->fm->getName(), $class2alias_a);
            }

            // assemble sql for class b and subclasses
            $cms_b = array();
			// collect all children if no specific class set
			if (!$this->specificClass()) {
				$cms_b = $cm_b->getChildren(true);
			}
            array_unshift($cms_b, $cm_b);

            foreach($cms_b as $cm_b_) {

                // skip abstract
                if ($cm_b_->isAbstract()) {
                    continue;
                }

                // get class b name and make alias
                $class_b = $cm_b_->getName();

                if ($children_contained) {
                    // @@@ alias key may be in the form of 'ClassName.alias'
                    // @@@ to differentiate multiple aliases of the same class
                    if (!isset($this->class2alias[$class_b.'.'.$alias])) {
                        if (!$alias) {
                            $alias_b = $this->_am()->getClassAlias($class_b, true);
                        } else {
                            $alias_b = $this->class2alias[$class_b.'.'];
                            $this->_am()->setClassAlias($class_b, $alias_b.$alias);
                        }
                        $this->class2alias[$class_b.'.'.$alias] = $alias_b.$alias;
                    }
                    $alias_b = $this->class2alias[$class_b.'.'.$alias];
                } else {
                    if (!isset($this->class2alias[$class_b])) {
                        $alias_b = $this->_am()->getClassAlias($class_b, true);
                        $this->class2alias[$class_b] = $alias_b;
                    } else {
                        $alias_b = $this->class2alias[$class_b];
                    }
                }

                // generate sql for class b
                if ($alias || !$children_contained) {
                    $sql .= $this->_generateSqlClassB(
                        $rt_alias, $cm_b_->getTable(), $base_b, $class_b, $alias_b, $cm_b_->getOidColumn()
                        );
                }
            }
        }

        return $sql;
    }

    /**
     * Generates the SQL statement for class a and its subclasses
     * @param string $rt Name of the relationship table
     * @param string $rt_alias Alias of the relationship table
     * @param string $var_a Variable of class a
     * @param array  $class2alias_a Class a and subclasses and their aliases
     * @return string
     */
    private function _generateSqlClassA($rt, $rt_alias, $var_a, $class2alias_a) {

        // quote ids and values
        $rt = $this->quoteId($rt);
        $rt_alias = $this->quoteId($rt_alias);
        $var_a = $this->quote($var_a);

        // assemble sql for class_a (including its subclasses)
        $sql = "LEFT JOIN $rt AS $rt_alias ON "
             . "$rt_alias.var_a = $var_a ";

        // collect the OR items
        $or_items = array();
        foreach($class2alias_a as $class_a => $alias_a) {

            // get oid column
            $oid_col = $this->_em()->getClassMap($class_a)->getOidColumn();

            // quote value and id
            $alias_a = $this->quoteId($alias_a);
            $oid_col = $this->quoteId($oid_col);
            $class_a = $this->quote($class_a);

            // assemble this OR item
            $or_items[] = "$rt_alias.class_a = $class_a AND $rt_alias.oid_a = $alias_a.$oid_col";
        }

        $sql .= 'AND (' . implode(' OR ', $or_items) . ') ';

        return $sql;
    }

    /**
     * Generates the SQL statement for class b (or one of its subclasses)
     * @param string $rt_alias Alias to relationship table
     * @param string $table_b Table for class b
     * @param string $base_b Base class of class b
     * @param string $class_b Name of class b
     * @param string $alias_b Alias to table b
     * @param string $oid_col Oid column name for class b
     * @return string
     */
    private function _generateSqlClassB($rt_alias, $table_b, $base_b, $class_b, $alias_b, $oid_col) {

        // quote value/ids
        $rt_alias = $this->quoteId($rt_alias);
        $table_b = $this->quoteId($table_b);
        $base_b = $this->quote($base_b);
        $class_b = $this->quote($class_b);
        $alias_b =  $this->quoteId($alias_b);
        $oid_col = $this->quoteId($oid_col);

        // assemble sql
        $sql = "LEFT JOIN $table_b AS $alias_b ON "
             . "$rt_alias.base_b = $base_b "
             . "AND $rt_alias.class_b = $class_b "
             . "AND $rt_alias.oid_b = $alias_b.$oid_col ";

        return $sql;
    }

}
