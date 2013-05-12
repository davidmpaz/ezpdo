<?php

/**
 * $Id: epQueryPathNode.php 1036 2007-01-31 12:20:00Z nauhygon $
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
use ezpdo\base\epContainer;

use ezpdo\orm\epFieldMap;
use ezpdo\orm\epFieldMapPrimitive;

use ezpdo\runtime\epManager;
use ezpdo\query\exception\epExceptionQueryPath;

/**
 * Class of a node in the EZOQL path expression tree
 *
 * See more description of path expressions in {@link epQueryBuilder}.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1036 $
 * @package ezpdo
 * @subpackage ezpdo.query.path
 */
abstract class epQueryPathNode extends epContainer {

    /**
     * The cached EZPDO runtime manager
     * @var epManager
     * @static
     */
    static protected $em = false;

    /**
     * The underlying database connection for the primary root node
     * @var epDbObject
     */
    static protected $db = false;

    /**
     * The alias manager shared by all nodes in tree
     * @var epQueryAliasManager
     * @static
     */
    static protected $am = false;

    /**
     * Aliases for classes and their subclasses
     * @var array
     */
    protected $class2alias = false;

	/**
	 * Specific class for the node. If set, no ramification of subclassing
	 * when building SQL. Used when a path points to an object argument.
	 * @var string
	 */
	protected $specificClass = false;

    /**
     * Constructor
     * @param $name
     * @param epQueryAliasManager &$am
     */
    public function __construct($name) {

        // call parent to set name and child key ('Name')
        parent::__construct($name, 'Name');

        // set up the EZPDO runtime manager if not already
        if (!self::$em) {
            self::$em = & epManager::instance();
        }

        // initialize class2alias lookup array
        $this->class2alias = array();
    }

    /**
     * Is this node a root? Same to effect of
     * 'instanceof epQueryPathRoot', but faster.
     * @return boolean
     */
    public function isRoot() {
        return false;
    }

    /**
     * Returns the alias manager
     * @return epQueryAliasManager&
     */
    public function &getAliasManager() {
        return self::$am;
    }

    /**
     * Returns the alias manager.
     * If param is not null, set alias manager.
     * @return epQueryAliasManager
     */
    protected function &_am(&$am = null) {
        if ($am) {
            self::$am = & $am;
        }
        return self::$am;
    }

    /**
     * Returns the cached runtime manager (reference)
     * @return epManager
     */
    protected function &_em() {
        return self::$em;
    }

    /**
     * Returns the cached database (reference).
     * If param is not null, set database.
     * @return epManager
     */
    protected function &_db(&$db = null) {
        if ($db) {
            self::$db = & $db;
        }
        return self::$db;
    }

    /**
     * Returns either field map or class map
     * @return epFieldMap|epClassMap
     */
    abstract public function &getMap();

    /**
     * Recursively generates SQL statement for this node (calls
     * {@link _generateSql()}) and its children
     * @param boolean $recursive
     * @return false|string
     */
    public function generateSql($recusive = true) {
        $sql = $this->_generateSql();
        if ($recusive && $children = $this->getChildren()) {
            foreach($children as $child) {
                $sql .= $child->generateSql(true);
            }
        }
        return $sql;
    }

    /**
     * Generates SQL statement for -this- node and set up
     * aliases for all classes (root class and subclasses)
     * for children to get ({@link getAliases()}) in their
     * own _generateSql().
     * @return string
     * @abstract
     */
    abstract protected function _generateSql();

    /**
     * Returns the class aliases. This is mostly called by its
     * children nodes during the generation of SQL statemenet.
     * @return array (keyed by class name)
     */
    public function getAliases() {
        return $this->class2alias;
    }

    /**
     * Consults EZPDO runtime manager to get class map
     * @return epClassMap
     */
    public function &getClassMap($class) {
        return self::$em->getClassMap($class);
    }

	/**
	 * Sets a specific class to suppress subclassing in build SQL for
	 * the node. Also returns the specific class.
	 * @param string $class
	 * @return false|string
	 */
	public function specificClass($class = false) {
		if ($class) {
			$this->specificClass = $class;
		}
		return $this->specificClass;
	}

    /**
     * Prepare database if not exists
     * @param string $class
     * @param string $rtable (relationship table, only used if class is epObjectRelation)
     * @return boolean
     */
    public function prepareDb($class, $rtable = false) {

        // if relation table is given, set it to runtime manager
        if ($rtable) {
            self::$em->setRelationTable($rtable);
        }

        // get class map for class
        if (!($cm = self::$em->getClassMap($class))) {
            return false;
        }

        // no table for abstract class
        if ($cm->isAbstract()) {
            return true;
        }

        return self::$db->create($cm);
    }

    /**
     * Calls the underlying database to quote value
     * @param string $v
     * @param epFieldMap $fm
     * @return string
     */
    public function quote($v, $fm = false) {
        return self::$db->quote($v, $fm);
    }

    /**
     * Calls the underlying database to quote id
     * @param string $id
     * @return string
     */
    public function quoteId($id) {
        return self::$db->quoteId($id);
    }

    /**
     * Insert nodes for a path and return the last node.
     * Path is given in either an array or a dot-connected
     * string (for example, 'a.b.c').
     * @param string|array $path
     * @return false|epQueryPathNode
     */
    public function &insertPath($path) {
        // make path an array if a string
        if (is_string($path)) {
            $path = explode('.', $path);
        }
        return $this->_obtainNodeByPath($path, true);
    }

    /**
     * Returns the last node on a path
     * Path is given in either an array or a dot-connected
     * string (for example, 'a.b.c').
     * @param string|array $path
     * @return false|epQueryPathNode
     */
    public function &findNode($path) {
        // make path an array if a string
        if (is_string($path)) {
            $path = explode('.', $path);
        }
        return $this->_obtainNodeByPath($path, false);
    }

    /**
     * Returns the last node on a path. If the path does not exist
     * in the the tree, creates the nodes if $create is set to true.
     * Path is given in an array.
     * @param array $path
     * @return false|epQueryPathNode
     * @throws epExceptionQueryPath
     */
    protected function &_obtainNodeByPath(&$path, $create = true) {

        // get the first non-empty piece in path
        while (!($piece = array_shift($path)) && ($path)) {
        }

        // this is the end if piece is empty
        if (!$piece) {
            return $this;
        }

        // check if piece is a child
        if ($child = & $this->getChild($piece)) {
            // if so, recursion on child
            return $child->_obtainNodeByPath($path, $create);
        }

        // check if we need to create path if it does not exist
        if (!$create) {
            return self::$false;
        }

        // get the class and field map for this node
        $fm = null;
        if ($this->isRoot()) {
            $cm = $this->getMap();
        } else {
            $fm = $this->getMap();
            $cm = $this->_em()->getClassMap($fm->getClass());
        }

        //
        // 1. if the node is a many-valued relationship, then create an contained root node
        //
        if ($fm && !$fm->isPrimitive() && $fm->isMany()) {

        // create a child - an alias root
	    // New code because assigning the return valure of new by reference is deprecated
        //    if (!($child = & new epQueryPathRoot($cm, $piece, epQueryPathRoot::CONTAINED))) {
	    $childObj = new epQueryPathRoot($cm, $piece, epQueryPathRoot::CONTAINED);
	    $child =& $childObj;
	    if (!($child)) {
                throw new epExceptionQueryPath("cannot create a node for '$piece'");
                return false;
            }

            // add child into this node
            $this->addChild($child);

            // recursion on child
            return $child->_obtainNodeByPath($path, $create);
        }

        //
        // 2. otherwise, create a field node
        //

        // get field map for the piece
        if ($piece == 'oid') {
            $fm = new epFieldMapPrimitive('oid', epFieldMap::DT_INTEGER, array(), $cm);
            $fm->setColumnName($cm->getOidColumn());
        }
        else {
            // get field map
            if (!($fm = & $cm->getField($piece))) {
                throw new epExceptionQueryPath("no field map for '$piece'");
                return self::$false;
            }
        }

        // create a child
	    //New code because assigning the return valure of new by reference is deprecated
        //if (!($child = & new epQueryPathField($fm))) {
        $childObj = new epQueryPathField($fm);
        $child =& $childObj;
        if (!($child)) {
            throw new epExceptionQueryPath("cannot create a node for '$piece'");
            return self::$false;
        }

        // add it into parent
        $this->addChild($child);

        // recursion on child
        return $child->_obtainNodeByPath($path, $create);
    }

}
