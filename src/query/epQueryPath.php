<?php

/**
 * $Id: epQueryPath.php 1036 2007-01-31 12:20:00Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1036 $
 * @package ezpdo
 * @subpackage ezpdo.query
 */

/**
 * need epContainer 
 */
include_once(EP_SRC_BASE.'/epContainer.php');

/**
 * Exception class for {@link epQueryPath}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1036 $
 * @package ezpdo
 * @subpackage ezpdo.query 
 */
class epExceptionQueryPath extends epException {
}

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
 * @subpackage ezpdo.query 
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

/**
 * Class of a node in the EZOQL path expression tree
 * 
 * See more description of path expressions in {@link epQueryBuilder}.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1036 $
 * @package ezpdo
 * @subpackage ezpdo.query 
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
    abstract public function getMap(); 

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
    public function insertPath($path) {
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
    public function findNode($path) {
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
            if (!($child = & new epQueryPathRoot($cm, $piece, epQueryPathRoot::CONTAINED))) {
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
        if (!($child = & new epQueryPathField($fm))) {
            throw new epExceptionQueryPath("cannot create a node for '$piece'"); 
            return self::$false;
        }
        
        // add it into parent
        $this->addChild($child);

        // recursion on child
        return $child->_obtainNodeByPath($path, $create);
    }

}

/**
 * Class of a root node in the EZOQL path expression tree
 * 
 * A root node of the path expression tree is associated to a class 
 * map {@link epClassMap} and is identified by an alias. The same 
 * class map may be used for multiple path expression trees, but 
 * they should have different aliases. 
 * 
 * We have three types of root ndoes: 
 * <ol>
 * <li> A node for the root class in the whole EZOQL query, i.e. the first 
 * in the FROM clause. We call such a node the primary root and set its type 
 * to {@link epQueryPathRoot::PRIMARY}.</li>
 * <li> A node for an alias other than the root class in the from clause.
 * The node should not have any parent. The type for such a node is 
 * {@link epQueryPathRoot::SECONDARY}.</li>
 * <li> A node for an alias referred in .contains() function. Such a node 
 * can only be a child of a field node ({@link epQueryPathField}) 
 * associated with many-valued relationship 
 * ({@link epFieldMapRelationship}). The type of the node is 
 * {@link epQueryPathRoot::CONTAINED}.</li>
 * </ol>
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1036 $
 * @package ezpdo
 * @subpackage ezpdo.query 
 */
class epQueryPathRoot extends epQueryPathNode {

    /**#@+
     * Constants for root types
     */
    const PRIMARY = 0;
    const SECONDARY = 1;
    const CONTAINED = 2;
    /**#@-*/

    /**
     * The class map that the root node is associated to
     * @var epClassMap|epFieldMap
     */
    protected $cm = false;

    /**
     * The alias for the root class
     * @var string
     */
    protected $alias = false;

    /**
     * The type of the node
     * @var integer
     */
    protected $type = false;

    /**
     * Constructor
     * @param string|epClassMap $class
     * @param string $alias
     * @param integer $type
     * @param epQueryAliasManager &$am
     */
    public function __construct($class, $alias = false, $type = self::PRIMARY, &$am = false) {
        
        // call parent to set up alias manager
        parent::__construct($alias, $am);

        // set the class map to node
        if (is_string($class)) {
            // string (class name). call manage to get class map.
            $this->cm = & $this->_em()->getClassMap($class);
        } 
        else {
            // class map otherwise 
            $this->cm = & $class;
        }

        // set alias manager and database when constructing primary root
        if ($type == self::PRIMARY) {
            $this->_db($this->_em()->getDb($this->cm));
            $this->_am($am);
        }

        // is alias is given?
        if ($alias) {
            // if yes, set class alias
            $this->_am()->setClassAlias($this->cm->getName(), $alias);
        } else {
            // otherwise, create one 
            $alias = $this->_am()->getClassAlias($this->cm->getName());
        }

        // set alias
        $this->alias = $alias;

        // set alias as the name of the node
        $this->setName($this->alias);

        // set root type
        $this->type = $type;
    }

    /**
     * Is this node a super node?
     * @return boolean
     */
    public function isPrimary() {
        return ($this->type == self::PRIMARY);
    }

    /**
     * Is this node a secondary node?
     * @return boolean
     */
    public function isSecondary() {
        return ($this->type == self::SECONDARY);
    }

    /**
     * Is this node a contained node?
     * @return boolean
     */
    public function isContained() {
        return ($this->type == self::CONTAINED);
    }

    /**
     * Is this node a root? 
     */
    public function isRoot() {
        return true;
    }

    /**
     * Returns field map
     * Implements {@link epQueryPathNode::getMap()}
     * @return epClassMap
     */
    public function getMap() {
        return $this->cm;
    }

    /**
     * Returns alias of the root class
     * @return string
     */
    public function getAlias() {
        return $this->alias;
    }

    /**
     * Return the '<table> as <alias>' part for the SQL FROM clause
     * @return string 
     */
    public function getTableAlias() {
        
        // quote ids
        $table = $this->quoteId($this->cm->getTable());
        $alias = $this->quoteId($this->alias);

        // return sql
        return "$table AS $alias";
    }

	/**
	 * Overrides {@link epQueryPathNode::specificClass()}
	 * @param string $class
	 * @return false|string
	 */
	public function specificClass($class = false) {

		// call parent class method if not contained link
		if (!$this->isContained()) {
			return parent::specificClass($class);
		}

		// otherwise, set specific class to parent
		return $this->getParent()->specificClass($class);
	}

    /**
     * Returns an array of class maps of the root class and its subclasses
     * @param $non_abstract_only whether to gether non-abstrct classes only
     * @return array
     */
    public function getClassMaps($non_abstract_only = true) {
        
        // return array
		$cms = array();

        // get class map for the primary root node
        if (!($cm = & $this->getMap())) {
            return $cms;
        }

		// check if we have specific class
		if ($sc = $this->specificClass()) {
			if ($cm = $this->getClassMap($sc)) {
				$cms[] = $cm;
			}
			return $cms;
		}

        // get all class maps for the subclasses
        if (!$non_abstract_only || !$cm->isAbstract()) {
            $cms[] = $cm;
        }

        // collect all concrete subclasses
        foreach($cm->getChildren(true) as $cm_) {
            if (!$non_abstract_only || !$cm_->isAbstract()) {
                $cms[] = $cm_;
            }
        }

        return $cms;
    }

    /**
     * Override epQueryPathNode::generateSql() for primary node
     * @param boolean $recursive 
     * @return false|string|array
     */
    public function generateSql($recusive = true) {
        
        // the usual stuff for contained root
        if ($this->isContained()) {
            return parent::generateSql($recusive);
        }
        
        // array to hold sql parts
        $sql_parts = array();

        // for each non-abstract class map
        foreach($this->getClassMaps() as $cm) {

            // pass only alias of this class
            $this->class2alias = array($cm->getName() => $this->alias);
            
            // generate sql recursively
            $sql = $this->_generateSql();
            if ($recusive && $children = $this->getChildren()) {
                foreach($children as $child) {
                    $sql .= $child->generateSql(true);
                }
            }

            // collect sql statement for this class
            $sql_parts[$cm->getTable()] = $sql;
        }

        return $sql_parts;
    }

    /**
     * Generates SQL statement for the node and set up 
     * aliases for all classes (root class and subclasses).
     * Implements {@link epQueryPathNode::_generateSql()} 
     * @return string
     */
    protected function _generateSql() {

        // for contained root only
        if (self::CONTAINED == $this->type) {
            
            // pass aliases from the parent to children
            $class2alias = $this->getParent()->getAliases();
            
            // @@@ $class may be in the form of 'ClassName.alias'
            // @@@ for multiple aliases for the same class
            foreach ($class2alias as $class => $alias) {
                if ($parts = explode('.', $class)) {
                    if (count($parts) == 1 || $this->alias == $parts[1]) {
                        $this->class2alias[$parts[0]] = $alias;
                    }
                }
            }
        }

        return '';
    }
}

/**
 * Class of a non-root node in the path expression tree 
 * 
 * A node in the path expression tree other than the root node is 
 * associated to a field map ({@link epFieldMap}). 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1036 $
 * @package ezpdo
 * @subpackage ezpdo.query 
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
    public function getMap() {
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

/**
 * The helper class for {@link epQueryBuilder}: the path manager
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1036 $
 * @package ezpdo
 * @subpackage ezpdo.query 
 */
class epQueryPathManager extends epBase {

    /**
     * The alias manager used by the manager
     * @var epQueryAliasManager
     */
    protected $am = false;

    /**
     * Cached the primary root
     * @var epQueryPathRoot
     */
    protected $proot = false;

    /**
     * An associative array for root alisas and path tree roots
     * @var array
     */
    protected $alias2root = false;

    /**
     * Array to hold SQL parts for primary and secondary. Structure: 
     * <pre>
     * array(
     *    # root 1
     *   'alias1' => array(
     *     'table11' => 'left_joins' (string)
     *     'table12' => 'left_joins' (string)
     *   ),
     *    # root 2
     *   'alias2' => array(
     *     'table21' => 'left_joins' (string)
     *     'table22' => 'left_joins' (string)
     *   )
     *   ......
     * )
     * </pre>
     * 
     * @var array (keyed by alias)
     */
    protected $sql_parts = array();

    /**
     * Table-as-alias expression for primary root and subclasses 
     * (format: 'table AS alias')
     * @var array (string)
     */
    protected $aliases_primary = array();

    /**
     * Table-as-alias expression for secondary roots
     * (format: 'table AS alias')
     * @var array (string)
     */
    protected $aliases_secondary = array();

    /**
     * The counter for contained aliases. The query function
     * contains() can have a placeholder as its argument. The 
     * placeholder may not be a string (an alias) and can be an
     * array or an object, in which case an alias is implilied
     * and should be auto generated.
     * 
     * @var integer
     */
    protected $num_contained_aliases = 0;

    /**
     * Constructor
     */
    public function __construct() {
		$this->initialize();
    }

	/**
	 * Initialize path manager
	 * @throws epExceptionQueryPath
	 */
	public function initialize() {

        // instantiate alias manager
        if (!$this->am) {
			if (!($this->am = new epQueryAliasManager)) {
				throw new epExceptionQueryPath('Cannot instantiate alias manager');
			}
		}
		$this->am->reset();

		// reset primary root
		$this->proot = false;
        
        // array to hold alias to root lookup
        $this->alias2root = array();

		// reset sql parts
		$this->sql_parts = array();

		// reset aliases_primary table
		$this->aliases_primary = array();

		// reset alias_secondary table
		$this->aliases_secondary = array();

        // reset counter for contained aliases
        $this->num_contained_aliases = 0;
	}

    /**
     * Add the primary root
     * @param string $class
     * @param false|string $alias
     * @return boolean
     */
    public function addPrimaryRoot($class, &$alias = false) {

        // create primary root node 
        if (!($node = new epQueryPathRoot($class, $alias, epQueryPathRoot::PRIMARY, $this->am))) {
            return false;
        }
        
        // put it into alias-root lookup
        $this->proot = $this->alias2root[$alias = $node->getAlias()] = & $node;
        
        return true;
    }

    /**
     * Add the secondary root
     * @param string $class
     * @param string $alias
     * @return boolean
     */
    public function addSecondaryRoot($class, $alias) {

        // create primary root node 
        if (!($node = new epQueryPathRoot($class, $alias, epQueryPathRoot::SECONDARY))) {
            return false;
        }
        
        // put it into alias-root lookup
        $this->alias2root[$node->getAlias()] = & $node;
        return true;
    }

    /**
     * Add the contained root
     * @param string $path
     * @param string $alias
     * @return boolean
     */
    public function addContainedRoot($path, &$alias = false) {
        
        // split path
        list($root_alias, $pieces) = $this->_splitPath($path); 
        if (!$root_alias) {
            return false;
        }
        
        // get root
        if (!($root = & $this->_getRoot($root_alias))) {
            return false;
        }

        // in case alias is not given
        if (!$alias) {
            $alias = 'c' . (++ $this->num_contained_aliases);
        }

        // add contained alias into pieces
        $pieces[] = $alias;

        // call root to insert path and get contained root
        if (!($node = & $root->insertPath($pieces))) {
            return false;
        }

        // put contained root into alias-root lookup
        $this->alias2root[$alias] = & $node;
        return true;
    }

    /**
     * Returns the SQL parts
     * @return array
     */
    public function getRootSql($force = false) {
        return $this->sql_parts;
    }

    /**
     * Generates SQL from path expressions without the nodes of primitive 
     * field maps (which are dealt with in {@link epQueryBuilder}).
     * @return false|array 
     * @throws epExceptionQueryPath
     */
    public function generateSql() {
        
        // reset sql parts
        $this->sql_parts = array();

        // go through each root
        foreach($this->alias2root as $alias => &$root) {
            
            // skip contained root (as they are taken care in recursion)
            if ($root->isContained()) {
                continue;
            }

            // collect sql statement
            if ($sql_part = $root->generateSql()) {
                $this->sql_parts[$alias] = $sql_part;
            }
        }
        
        return true;
    }

    /**
     * Inserts path into a path tree. The first part in the path is 
     * the root alias, followed by a sequence of field (var) names.
     * @param string $path
     * @return false|epQueryPathNode
     */
    public function insertPath($path) {
        
        // split path
        list($alias, $pieces) = $this->_splitPath($path); 
        if (!$alias) {
            return false;
        }
        
        // get root
        if (!($root = & $this->_getRoot($alias))) {
            return false;
        }

        // call root to insert path
        return $root->insertPath($pieces);
    }

	/**
	 * Marks that a path is asscoated to an object of a specified class
	 * so that no subclass ramification when building SQL
	 * @return boolean
	 */
	public function specificClass($path, $class) {

		// sanity check
		if (!$class) {
			return false;
		}

        // split path
        list($alias, $pieces) = $this->_splitPath($path); 
        if (!$alias) {
            return false;
        }
        
        // get root
        if (!($root = & $this->_getRoot($alias))) {
            return false;
        }
        
        // find node by path
        if (!($node = & $root->findNode($pieces))) {
            return false;
        }

		// set specific class
		return $node->specificClass($class) ? true : false;
	}
    
    /**
     * Returns class aliases for the last node on path
     * @param string $path
     * @return array
     */
    public function getAliases($path) {
        
        // split path
        list($alias, $pieces) = $this->_splitPath($path); 
        if (!$alias) {
            return false;
        }
        
        // get root
        if (!($root = & $this->_getRoot($alias))) {
            return false;
        }

        // find node by path
        if (!($node = & $root->findNode($pieces))) {
            return false;
        }

        // return class aliaes from the node
        return $node->getAliases();
    }

    /**
     * Tests to see if the last item in the path is an object
     * @param string $path
     * @return array
     */
    public function isObject($path) {
        
        // split path
        list($alias, $pieces) = $this->_splitPath($path); 
        if (!$alias) {
            return false;
        }
        
        // get root
        if (!($root = & $this->_getRoot($alias))) {
            return false;
        }

		// root if no rest of the path
		if (!$pieces) {
			return true;
		}
        
        // find node by path
        if (!($node = & $root->findNode($pieces))) {
            return false;
        }
        
        if ($node->isRoot()) {
            return false;
        }
        
        // get the fm from the node
        if (!($fm = & $node->getMap())) {
            return false;
        }
        
        // return true if not primitive
        return !$fm->isPrimitive();
    }

    /**
     * Creates tables for classes and relationship involved 
     * in the query path tree if they don't exist. 
     * @return boolean
     */
    public function prepareDbs() {

        $status = true;

        // make sure tables for the classes involved in the query are created
        if ($classes = $this->am->getAllClasses()) {
            foreach($classes as $class) {
                if (!$this->proot->prepareDb($class)) {
                    $status = false;
                }
            }
        }

        // make sure all the child classes of the roots are created as well
        if ($classes = $this->getPrimaryClassMaps()) {
            foreach($classes as $class) {
                if (!$this->proot->prepareDb($class->getName())) {
                    $status = false;
                }
            }
        }
        
        // make sure relationship tables are created as well
        if ($tables = $this->am->getAllTables()) {
            foreach($tables as $table) {
                if (!$this->proot->prepareDb('epObjectRelation', $table)) {
                    $status = false;
                }
            }
        }

        return $status;
    }

    /**
     * Returns the primary root class map
     * @return epClassMap
     */
    public function getPrimaryClassMaps() {
        return $this->proot->getClassMaps(); // implicit: non-abstract only
    }

    /**
     * Calls the underlying database through primary root to quote value
     * @param string $v
     * @param epFieldMap $fm
     * @return string
     */
    public function quote($v, $fm = false) {
        return $this->proot->quote($v, $fm);
    }

    /**
     * Calls the underlying database through primary root to quote identifier
     * @param string $id 
     * @return string
     */
    public function quoteId($id) {
        return $this->proot->quoteId($id);
    }

    /**
     * Quotes primitive value with its primitve variable (alias.var)
     * @param mixed $v
     * @param string $pvar
     * @return true|string (error message if string)
     */
    public function quoteVar(&$v, &$pvar) {

        // unquote pvar
        $pvar_ = $this->_unquote($pvar);

        // split primitive var to alias and var
        list($alias, $var) = @explode('.', $pvar_);
        if (!$alias || !$var) {
            return "invalid primitive var '$pvar'";
        }

        // get the class for alias
        if (!($class = $this->am->getClass($alias))) {
            return "no class found for alias '$alias'";
        }

        // get class map 
        if (!($cm = $this->proot->getClassMap($class))) {
            return "no class map for '$class'";
        }

        // is var 'oid'?
        if ($var == 'oid') {
            // replace var name with column name
            $pvar = $this->quoteId($alias).'.'.$this->quoteId($cm->getOidColumn());
            return true;
        }

        // get field map (for non-oid field)
        if (!($fm = $cm->getField($var))) {
            return "no field map for '$class::$var'";
        }
        
        // replace var name with column name
        $pvar = $this->quoteId($alias).'.'.$this->quoteId($fm->getColumnName());
        
        // quote value
        $v = $this->quote($v, $fm);
        
        return true;
    }

    /**
     * Splits a full path into an alias (the first item) and 
     * an array of following pieces
     * @param string $path
     * @return array ($alias, $pieces)
     */
    private function _splitPath($path) {
        
        // explode path into pieces
        if (!($pieces = explode('.', $path))) {
            return array(false, false);
        }
        
        // get the first piece - alias
        $alias = array_shift($pieces);
        
        // return an array
        return array($alias, $pieces);
    }

    /**
     * Find the root for a given alias
     * @param string $alias
     * @return false|epQueryPathRoot
     */
    private function &_getRoot($alias) {
        
        // check if root exists 
        if (!isset($this->alias2root[$alias])) {
            return self::$false;
        }
        
        // get the root
        return $this->alias2root[$alias];
    }

    /**
     * Unquote a sting
     * @param string $s
     * @return string
     */
    private function _unquote($s) {
        return str_replace(array("'",'"','`'), '', $s);
    }
}

?>
