<?php

/**
 * $Id: epQueryPathRoot.php 1036 2007-01-31 12:20:00Z nauhygon $
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
 * @subpackage ezpdo.query.path
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
    public function &getMap() {
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
