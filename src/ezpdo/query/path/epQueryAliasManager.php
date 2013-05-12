<?php

/**
 * $Id: epQueryAliasManager.php 1036 2007-01-31 12:20:00Z nauhygon $
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

use ezpdo\base\epBase;
use ezpdo\query\exception\epExceptionQueryPath;

use ezpdo\orm\epFieldMap;
use ezpdo\orm\epFieldMapPrimitive;

use ezpdo\runtime\epManager;

/**
 * The alias manager
 *
 * This class is a helper class for for {@link epQueryPath}. It generates
 * unique aliases for both data object classes as well as relationship
 * tables.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1036 $
 * @package ezpdo
 * @subpackage ezpdo.query.path
 */
class epQueryAliasManager extends epBase {

    /**
     * The alias counter
     * @var integer
     */
    protected $num_aliases = 0;

    /**
     * Array for alias and class lookup
     * @var array (keyed by alias, value is class name)
     */
    protected $alias2class = false;

    /**
     * Array for alias and relationship table lookup
     * @var array (keyed by alias, value is name of relationship table)
     */
    protected $alias2table = false;

    /**
     * Constructor (reset)
     */
    public function __construct() {
        $this->reset();
    }

    /**
     * Resets alias counter and empties associative arrays
     * @return void
     */
    public function reset() {
        $this->num_aliases = 0;
        $this->alias2class = array();
        $this->alias2table = array();
    }

    /**
     * Returns all (unique) classes
     * @return array
     */
    public function getAllClasses() {
        return array_unique(array_values($this->alias2class));
    }

    /**
     * Returns the class for an alias
     * @param string $alias
     * @return false|string
     */
    public function getClass($alias) {
        if (isset($this->alias2class[$alias])) {
            return $this->alias2class[$alias];
        }
        return false;
    }

    /**
     * Returns all aliases for a class
     * @param string $class
     * @return array
     */
    public function getClassAliases($class) {
        return array_keys($this->alias2class, $class);
    }

    /**
     * Generates a unique alias for class if not exists. Otherwise returns
     * the existing aliass if not forced to create a new one.
     * @param string $class
     * @param boolean $create (default to true) whether force to generate a new alias
     * @return string
     */
    public function getClassAlias($class, $create = false) {

        // if alias exists and create not forced
        if (!$create && ($aliases = $this->getClassAliases($class))) {
            return $aliases[0];
        }

        // auto genarete a unique alias ('_%d')
        $alias = '_' . (++ $this->num_aliases);

        // put alias into alias_class lookup with class name unquoted
        $this->alias2class[$alias] = $class;

        return $alias;
    }

    /**
     * Set an alias to class
     * @param string $class
     * $param string $alias
     * @return boolean
     */
    public function setClassAlias($class, $alias, $replace = false) {
        // if no forced replace and alias exists
        if (!$replace && array_key_exists($alias, $this->alias2class)) {
            return false;
        }
        $this->alias2class[$alias] = $class;
        return true;
    }

    /**
     * Returns all (unique) relationship tables
     * @return array
     */
    public function getAllTables() {
        return array_unique(array_values($this->alias2table));
    }

    /**
     * Returns the (relationship) table for an alias
     * @param string $alias
     * @return false|string
     */
    public function getTable($alias) {
        if (isset($this->alias2table[$alias])) {
            return $this->alias2table[$alias];
        }
        return false;
    }

    /**
     * Returns all aliases for a relationship table
     * @param string $table
     * @return array
     */
    public function getTableAliases($table) {
        return array_keys($this->alias2table, $table);
    }

    /**
     * Generates a unique alias for relationship table if not exists.
     * Otherwise returns the existing aliass if not forced to create
     * a new one.
     * @param string $table
     * @param boolean $create (default to true) whether force to generate a new alias
     * @return string
     */
    public function getTableAlias($table, $create = false) {

        // if alias exists and create not forced
        if (!$create && ($aliases = $this->getTableAliases($table))) {
            return $aliases[0];
        }

        // auto genarete a unique alias ('_%d')
        $alias = '_' . (++ $this->num_aliases);

        // put alias into alias-to-table lookup
        $this->alias2table[$alias] = $table;

        return $alias;
    }

}
