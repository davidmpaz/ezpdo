<?php

/**
 * $Id: epQueryBuilder.php 1048 2007-04-13 02:31:17Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author Trevan Richins <developer@ckiweb.com>
 * @version $Revision: 1048 $
 * @package ezpdo
 * @subpackage ezpdo.query
 */

/**
 * need epQueryNode (in epQueryParser.php)
 */
include_once(EP_SRC_QUERY.'/epQueryParser.php');

/**
 * Exception class for {@link epQueryBuilder}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1048 $
 * @package ezpdo
 * @subpackage ezpdo.query 
 */
class epExceptionQueryBuilder extends epException {
}

/**
 * The EZOQL SQL builder
 * 
 * The class builds standard SQL statement(s) from the syntax tree parsed 
 * by {@link epQueryParser}. 
 * 
 * Here is the basic idea behind query building. 
 * 
 * The essential part of the process is to translate the EZOQL variable 
 * paths (or path expressions) into the SQL expressions. Let us explain 
 * variable path first.
 * 
 * <b>Path expressions</b>
 * 
 * Here is an example of a variable path: 
 * <pre>
 *   book.publisher.contact
 * </pre>
 * It starts with an alias to the root class (book) and is followed by a 
 * sequence of ".var". In an EZOQL query, the root alias may not always
 * be explicit, for example, we may write 
 * <pre>
 *   from Book where publisher.contact.zipcode = '12345'
 * </pre>
 * However, we can always assign an alias to the root class to work on an 
 * equivalent query like this
 * <pre>
 *   from Book as book where book.publisher.contact.zipcode = '12345'
 * </pre>
 * We refer this as 'path normalization'.
 * 
 * In a path expression, each of item following the root alias relates 
 * to a field map ({@link epFieldMap}). So essentially a path is 
 * equivalent to a sequece of field maps following a root class.
 * 
 * The trailing part in a path expression is normally a primtive variable. 
 * In the above example, 
 * <pre>
 *   book.publisher.contact.zipcode, 
 * </pre>
 * 'zipcode' is a string value, a primitive type. It corresponds to a 
 * primtive field map ({@link epFieldMapPrimitive}). In some cases, we 
 * may have expressions that do not immedidately end with a primitive var, 
 * for instance,
 * <pre>
 *   book.publisher.contact = ? 
 *   book.authors.contains(a) AND a.name = 'James'
 *   book.authors.contains(?)
 * </pre>
 * But by parsing deep into the placeholders (or alias in the contains()
 * function), we can always make paths end with a primitive var.
 * 
 * Now if we leave the primitive variable out, the rest of the path is the 
 * sequence of relationship field maps ({@link epFieldMapRelationship}).
 * This sequence has great significance in defining how the tables should 
 * be joined. 
 * 
 * The joined table is the set of all potential candidates for the query 
 * result and will be filtered by a predicate expression which is composed 
 * of -only- primitive variables. 
 * 
 * From the above discussion, we know that a path expressions is composed of 
 * three parts, 1. the alias to the root class, 2. the sequence of relationship
 * field maps, and 3. the trailing primitive field map. 
 * 
 * <b>Helper classes</b>
 * 
 * To facilitate the implemenation of the builder, we venture to use two
 * helper classes to make the code more readable and maintainable. 
 * <ol>
 * <li>
 * Alias manager ({@link epQueryAliasManager}):  
 * The alias manager generates unique aliases for classes and relationship tables. 
 * </li>
 * <li>
 * Path expression manager ({@link epQueryPathManager})
 * The path expression manager uses path expression trees as the internal 
 * representation for EZOQL variable paths, from which it generates table join
 * statements. 
 * </li>
 * </ol>
 * 
 * The information contained in the path manager will later be used to create
 * the table joins that will be filtered by the predicate expression from the 
 * query builder. These parts work in concert to translate EZOQL to SQL.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author Trevan Richins <developer@ckiweb.com>
 * @version $Revision: 1048 $
 * @package ezpdo
 * @subpackage ezpdo.query
 */
class epQueryBuilder extends epBase {

    /**
     * The path expression manager
     * @var epQueryPath
     */
    protected $pm = null;

    /**
     * Wether to print out debugging info
     * @var boolean
     */
    protected $verbose = false;

    /**
     * The root of the EZOQL syntax tree
     * @var epQueryNode
     */
    protected $root = false;

    /**
     * The root class
     * @var string
     */
    protected $primary_class = '';

    /**
     * The alias for the root class
     * @var string
     */
    protected $primary_alias = '';

    /**
     * The original query string
     * @var epQueryNode
     */
    protected $query = '';

    /**
     * The argument array for the query
     * @var array
     */
    protected $args = false;

    /**
     * Aggreation function involved in the query
     * @var false|string 
     */
    protected $aggr_func = false;

    /**  
     * Order by in the query
     * @var false|string
     */
    protected $orderby  = false;
    
    /**
     * Limit in the query
     * @var false|string
     */
    protected $limit = false;

    /**
     * Aliases for secondary roots
     * @var false|array
     */
    private $aliases_secondary = false;

    /**
     * Aliases for contains roots
     * @var false|array
     */
    private $aliases_contained = false;

    /**
     * Constructor
     * @param epQueryNode $root the root node of the syntax tree
     * @param string &$query the query string
     * @param array $args the arguments for the query
     * @param boolean whether to print out debugging info
     */
    public function __construct(epQueryNode &$root, &$query = '', $args = array(), $verbose = false) {
		$this->initialize($root, $query, $args, $verbose);
    }

	/**
	 * Initialize query builder
     * @param epQueryNode $root the root node of the syntax tree
     * @param string &$query the query string
     * @param array $args the arguments for the query
     * @param boolean whether to print out debugging info
	 */
	public function initialize(epQueryNode &$root, &$query = '', $args = array(), $verbose = false) {

		// reset result vars
		$this->primary_class = '';
		$this->primary_alias = '';
		$this->aggr_func = false;
		$this->orderby = array();
		$this->limit = false;
		$this->aliases_secondary = false;
		
		// set input
		$this->root = & $root;
        $this->args = & $args;
        $this->query = & $query;
        $this->verbose = $verbose;

		// initialize query path manager
		if (!$this->pm) {
			include_once(EP_SRC_QUERY.'/epQueryPath.php');
			if (!($this->pm = new epQueryPathManager)) {
				throw new epExceptionQueryBuilder('cannot instantiate path manager');
				return false;
			}
		}
		$this->pm->initialize();
	}
    
    /**
     * Returns the root class
     * @return array epClassMap
     */
    public function getRootClassMaps() {
        return $this->pm->getPrimaryClassMaps();
    }

    /**
     * Returns whether the query has aggregate function
     * @return boolean|string
     */
    public function getAggregateFunction() {
        return $this->aggr_func;
    }

    /**
     * Returns whether the query has a limit
     * @return boolean|string
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * Returns the 'order by' items
     * @return array { 'path' => Array, 'dir' => 'asc'|'desc' }
     */
    public function getOrderBy() {
        return $this->orderby;
    }

    /**
     * Build the SQL query from syntax tree
     * @return false|string 
     */
    public function build() {

        // check if root is set
        if (!$this->root || !$this->query) {
            throw new epExceptionQueryBuilder('syntax tree or the query is not set');
            return false;
        }
        
        // preproc before writing sql
        if (!$this->preproc()) {
            return false;
        }

        // get the SQL statement for the query
        if (!($sql = $this->outputSql())) {
            return false;
        }

        // postproc after writing sql
        if (!$this->postproc()) {
            return false;
        }
        
        // finally return the SQL statement
        return $sql;
    }

    /**
     * The preprocess before writing SQL statement
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function preproc() {
        
        // get root alias
        $this->aliases_secondary = array();
        if (!($this->processFrom())) {
            return false;
        }
        
		// collect contained aliases
        $this->aliases_contained = array();
        if (!$this->walk($this->root, 'collectAliases')) {
            return false;
        }
        
        // normalize variables
        if (!$this->walk($this->root, 'normalizeVariable')) {
            return false;
        }
        
        // process 'contains' and 'variable' nodes 
        while (!$this->walk($this->root, 'processVariable')) {
            // loop until all aliases and vars are set up
        }
        
        // process array or object place holders
        if (!$this->walk($this->root, 'processPlaceholder')) {
            return false;
        }

        // call path manager to create SQL from clause
        return $this->pm->generateSql();
    }

    /**
     * Computes and returns the sql statement 
     * @return string
     */
    protected function outputSql() {
        return $this->buildSqlSelect($this->root); 
    }

    /**
     * Post process
     * Create tables for classes involved in query
     * @return boolean
     */
    protected function postproc() {
		$status = true;
		$status &= $this->walk($this->root, 'undoPlaceholder');
		$status &= $this->walk($this->root, 'undoVariable');
        $status &= $this->pm->prepareDbs();
		return $status;
    }

    /**
     * Preprocess on the from clause to add super and secondary roots
     * @return boolean 
     * @throws epExceptionQueryBuilder
     */
    private function processFrom() {
        
        // check if we have already done? (if done, root alias must have been set.)
        if ($this->primary_alias) {
            return true;
        }
        
        // get from node
        if (!($from = $this->root->getChild('from'))) {
            throw new epExceptionQueryBuilder($this->_e("cannot found 'from' clause"));
            return false;
        }
        
        // get from items
        if (!($items = $from->getChildren())) {
            throw new epExceptionQueryBuilder($this->_e("no 'from' items specified"));
            return false;
        }
        
        // get the super root (the 1st) from 'from' items
        $item = array_shift($items);

        // add the super root
        $this->primary_class = $item->getParam('class');
        $this->primary_alias = $item->getParam('alias');
        $this->pm->addPrimaryRoot($this->primary_class, $this->primary_alias);
        
        // go through the rest: secondary roots
        foreach($items as &$item) {
            
            // get class from item
            $class = $item->getParam('class');

            // a secondary root must have alias assigned
            if (!($alias = $item->getParam('alias'))) {
                // skip if not
                throw new epExceptionQueryBuilder($this->_e("no alias defined for class [$class]"));
                continue;
            }
            
            // keep track of aliases for secondary 
            $this->aliases_secondary[] = $alias;

            // add class as a secondary root
            $this->pm->addSecondaryRoot($class, $alias);
        }

        return true;
    }

    /**
     * Walks through the syntax tree in either depth-first (by default)
     * or breath-first mode. A process method is applied on each node
     * visited. 
     * @param epQueryNode &$node the starting node
     * @param string $proc the node process method
     * @param boolean $df whether it is depth-first or breath-firth
     * @return boolean 
     * @throws epExceptionQueryBuilder
     */
    protected function walk(epQueryNode &$node, $proc, $df = true) {
        
        // make sure we have proc
        if (!$proc) {
            throw new epExceptionQueryBuilder($this->_e('Empty node process method'));
            return false;
        }

        $status = true;

        // breath-first: process current node before walking the children
        if (!$df) {
            $status &= call_user_func_array(array($this, $proc), array(&$node));
        }

        // process all children (recursion)
        if ($children = $node->getChildren()) {
            foreach($children as &$child) {
                $status &= $this->walk($child, $proc, $df);
            }
        }

        // depth-first: process current node after walking the children
        if ($df) {
            $status &= call_user_func_array(array($this, $proc), array(&$node));
        }

        return $status;
    }

    /**
     * Collect aliases from contains() nodes
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function collectAliases(epQueryNode &$node) {

        // done if not a 'contains' node
        if ($node->getType() != EPQ_N_CONTAINS) {
            return true;
        }

        // collect alias name
        if ($arg = $this->_getContainsArg($node)) {
            if (is_string($arg)) {
                $this->aliases_contained[] = $arg;
            }
        }
        
        return true;
    }

    /**
     * Normalize variable so it always starts with an alias
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function normalizeVariable(epQueryNode &$node) {

        // is it a 'variable' node?
        if ($node->getType() != EPQ_N_VARIABLE) {
            return true;
        }

        // get children (var parts)
        if (!($children = $node->getChildren())) {
            throw new epExceptionQueryBuilder($this->_e('invalid variable', $node));
            return false;
        }

        // collect an array of var parts (string)
        $parts = array();
        foreach($children as &$child) {
            
            // if placeholder, get its acutally value
            if (($t = $child->getType()) == EPQ_N_PLACEHOLDER) {
                
                // get placeholder value
                if (!($part = $this->_getPlaceholderValue($child)) || !is_string($part)) {
                    throw new epExceptionQueryBuilder($this->_e('invalid placeholder (string expected)', $child));
                    return false;
                }
            
            } else {
                // get identifier val
                $part = $child->getParam('val');
            }

            $parts[] = $part;
        }

        // replace root class with root alias
        if ($parts[0] == $this->primary_class) {
            
            // remove the root class name
            array_shift($parts);
            
            // prepend the root alias
            array_unshift($parts, $this->primary_alias);
            
            // set path to variable
            $node->setParam('path', implode('.', $parts));
            
            return true;
        }

        // check if the first var is a known alias
        if (!$this->_isAlias($parts[0])) {
            // prepend super root alias 
            array_unshift($parts, $this->primary_alias);
        }
        
        // set path to variable
        $node->setParam('path', implode('.', $parts));
        
        return true;
    }

    /**
     * Check if a string is an alias
     * @param string $s
     * @return boolean
     */
    protected function _isAlias($s) {
        
        // is it the root alias
        if ($s == $this->primary_alias) {
            return true;
        }
        
        // is it an alias for a secondary root
        if (in_array($s, $this->aliases_secondary)) {
            return true;
        }
        
        // is it an alias for a contained root
        if (in_array($s, $this->aliases_contained)) {
            return true;
        }

        return false;
    }

    /**
     * Process variable/contains 
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function processVariable(epQueryNode &$node) {

        // is it a 'variable' node?
        if ($node->getType() != EPQ_N_VARIABLE) {
            // skip if not
            return true;
        }

        // check if this node has been processed
        if ($node->getParam('done')) {
            return true;
        }
        
        // make sure path has been set for the variable
        if (!($path = $node->getParam('path'))) {
            throw new epExceptionQueryBuilder($this->_e('no path for variable', $node));
            return false;
        }
        
        // insert path into path tree. note that this may not always
        // succeed if contained roots are not inserted already.
        if (!$this->pm->insertPath($path)) {
            return false;
        }

        // now see if this variable node is part of a 'contains' node
        if ($parent = & $node->getParent()) {
            if ($parent->getType() == EPQ_N_CONTAINS) {
                if ($arg = $this->_getContainsArg($parent)) {
                    if (is_string($arg)) {
                        $this->pm->addContainedRoot($path, $arg);
                    }
                }
            }
        }

        // set done flag to avoid repeated process
        $node->setParam('done', true);

        return true;
    }

	/**
	 * Undo the changes made to variables. Should be called in postproc()
	 * to make the parsed syntax tree reusable.
	 * @param epQueryNode &$node
	 */
	protected function undoVariable(epQueryNode &$node) {
        
		// is it a 'variable' node?
        if ($node->getType() != EPQ_N_VARIABLE) {
            // skip if not
            return true;
        }

        // check if this node has been processed
        if ($node->getParam('done')) {
			$node->setParam('done', false);
			$node->setParam('path', false);
        }

		return true;
	}

    /**
     * Process a placeholder node if it's part of 'contains()' 
     * or equals ('=' or '==') expression.
     * @param epQueryNode &$node
     * @return boolean
     */
    protected function processPlaceholder(epQueryNode &$node) {
        
        // done if not a placeholder node
        if ($node->getType() != EPQ_N_PLACEHOLDER) {
            return true;
        }

        // get placeholder value
        if (!($v = & $this->_getPlaceholderValue($node))) {
            return true;
        }

        // we only process array or epObject
        if (!is_array($v) && !($v instanceof epObject)) {
            return true;
        }

        // get parent node
        if (!($parent = & $node->getParent())) {
            return true;
        }

        // path prefix for the placeholder value
        $prefix = '';

        // is it a part of contains()?
        if (($parent_t = $parent->getType()) == EPQ_N_CONTAINS) {
            if ($var = & $parent->getChild('var')) {
                if ($path = $var->getParam('path')) {
                    $this->pm->addContainedRoot($path, $prefix);
                }
            }
        } 
        
        // or is it a part of equals expr?
        else if ($parent_t == EPQ_N_EXPR_COMPARISON) {
            if ('=' == $parent->getParam('op')) {
                if ($left = $parent->getChild('left')) {
                    if ($left->getType() == EPQ_N_VARIABLE) {
                        $prefix = $left->getParam('path');
                    }
                }
                if ($right = $parent->getChild('right')) {
                    if ($right->getType() == EPQ_N_VARIABLE) {
                        $prefix = $right->getParam('path');
                    }
                }
            }
        }

        // no more processing if prefix is still empty
        if (!$prefix) {
            return true;
        }

		// check if prefix points to an object and the placehodler 
		// is a *persisted* epObject. a non-persisted object is 
		// treated as an example object.
		if ($this->pm->isObject($prefix)) {
			if ($v instanceof epObject && $v->oid) {
				// if so, set 'specific' class on query path 
				$this->pm->specificClass($prefix, $v->epGetClass());
			}
		}

        // create a syntax tree for the array/object
        if (!($nodes = & $this->_createSyntaxNodes($v, $prefix))) {
            return true;
        }

        // make AND syntax nodes
        if (!($and_node = $this->_andNodes($nodes))) {
            return true;
        }
        
        // get parent's parent
        if (!($grand_parent = $parent->getParent())) {
            return true;
        }

        // call grand-parent to replace with parent this 'and' node 
        $grand_parent->replaceChild($parent, $and_node);

		// keep the replaced node so later we can reverse
		$and_node->setParam("replaced", $parent);

        return true;
    }

    /**
     * Undo replacement done in processPlaceholder(). This method
	 * is called in postproc() so the parsed syntax tree can be 
	 * reused next time for the same query with different args.
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function undoPlaceholder(epQueryNode &$node) {

		// any child? done if not
        if (!($children = $node->getChildren())) {
			return true;
        }

		// check if any child is a replacement
		$status = true;
		foreach($children as $key => $child) {

			// check if this node has been processed
			if (!($replaced = $child->getParam('replaced'))) {
				continue;
			}

			// undo replacement
			$status &= $node->replaceChild($key, $replaced);
		}

		return $status;
    }

    /**
     * Chain all nodes into an AND expression
     * @param array $nodes
     * @return epQueryNode
     */
    private function _andNodes($nodes) {

        // if nodes array empty 
        if (!$nodes) {
            // simply return it back
            return $nodes;
        }
        
        // if only one node 
        if (1 == count($nodes)) {
            // simply return it back
            return $nodes[0];
        }
        
        // get the first node
        $node_last = array_pop($nodes);

        // consume nodes in array one by one
        while ($node = array_pop($nodes)) {

            // create an AND node
            $and_node = new epQueryNode(EPQ_N_EXPR_LOGIC);
            $and_node->setParam('op', 'AND');

            // add last node and this node as children
            $and_node->addChildren(array($node_last, $node));

            $node_last = $and_node;
        }

        return $node_last;
    }

    /**
     * Creates a syntax tree for an arry or an epObject
     * @param array|epObject &$v
     * @param string $prefix
     * @return false|epQueryNode
     */
    protected function &_createSyntaxNodes(&$v, $prefix) {
        
        $nodes = array();

        // create expression for primitve vars from array or object
        if (!($exprs = $this->_getPrimitiveExprs($v, $prefix))) {
            return $nodes;
        }

        // create comparison '=' nodes
        foreach($exprs as $path => $value) {
            
            // insert path into tree
            if (!$this->pm->insertPath($path)) {
                // should not happen
                continue;
            }

            // create a comparison node
            if (!$node = new epQueryNode(EPQ_N_EXPR_COMPARISON)) {
                // should not happen
                continue;
            }
            
            // set operator '='
            $node->setParam('op', '=');

            // create left hand side variable
            if (!($left = new epQueryNode(EPQ_N_VARIABLE))) {
                // should not happen
                continue;
            }

            // set param path
            $left->setParam('path', $path);

            // add left hand side
            $node->addChild($left, 'left');

            // create right hand side value
            if (!($right = new epQueryNode(EPQ_N_STRING))) {
                // should not happen
                continue;
            }

            $right->setParam('val', $value);

            // add left hand side
            $node->addChild($right, 'right');

            // collect node
            $nodes[] = $node;
        }
        
        return $nodes;
    }

    /**
     * Collect all primitive expressions from an array or an object
     * @param array|epObject $v
     * @return boolean
     */
    private function _getPrimitiveExprs($v, $prefix = '') {

        // array to hold expressions
        $exprs = array();
        
        // always check if key 'oid' exists first
        if (isset($v['oid']) && $v['oid']) {
            // if so, no more deep matching
            $exprs[$prefix . '.oid'] = $v['oid'];
            return $exprs;
        }
        
        // go through each key-value pair
        foreach($v as $_k => $_v) {
            
            // skip oid or null value
            if ($_k == 'oid' || is_null($_v)) {
                continue;
            }

            // append key to prefix 
            $_prefix = $prefix . '.' . $_k;
            
            // if value is array or object. recursion.
            if (is_array($_v) || ($_v instanceof epObject)) {
                $exprs = array_merge($exprs, $this->_getPrimitiveExprs($_v, $_prefix));
            } 
            // otherwise only deal with scalar
            else if (is_scalar($_v)) {
                $exprs[$_prefix] = $_v; 
            }
        }

        return $exprs;
    }

    /**
     * Get argument value in 'contains' node
     * @param epQueryNode &$node
     * @return mixed
     * @throws epExceptionQueryBuilder
     */
    private function _getContainsArg(epQueryNode &$node) {

        // get arg param 
        if ($arg = $node->getParam('arg')) {
            return $arg;
        } 

        // get arg child (placeholder)
        if ($arg = $node->getChild('arg')) {
            if ($arg->getType() == EPQ_N_PLACEHOLDER) {
                return $this->_getPlaceholderValue($arg);
            }
        }
        
        // something wrong
        throw new epExceptionQueryBuilder($this->_e("Invalid 'contains' expression", $node));
        return false;
    }

    /**
     * Returns the value for a placeholder node 
     * @return mixed
     * @throws epExceptionQueryBuilder
     */
    private function &_getPlaceholderValue(epQueryNode &$node) {

        // get arg index: aindex
        if (is_null($aindex = $node->getParam('aindex'))) {
            throw new epExceptionQueryBuilder($this->_e('no argument for placeholder', $node));
            return self::$null;
        }

        // check if argument exists
        if (!isset($this->args[$aindex])) {
            throw new epExceptionQueryBuilder($this->_e('no argument for placeholder', $node));
            return self::$null;
        }
        
        // return the placeholder value 
        return $this->args[$aindex];
    }

    /**
     * Builds SQL statement from a node. 
     * 
     * The method uses node type to dispatche actual SQL generation to the 
     * node's proper handler - a method start with buildSql and appended 
     * with the node type. Examples: 
     * 
     * buildSqlAdd() handles all nodes with type EXP_N_EXPR_ADD, and 
     * buildSqlSelect() handles the EXP_N_EXPR_SELECT node.
     * 
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSql(epQueryNode &$node) {
        
        // get type without 
        $type = str_replace(array('EPQ_N_EXPR_', 'EPQ_N_FUNC_', 'EPQ_N_'), '', $node->getType());
        
        // the build sql method for this type
        $method = 'buildSql' . ucfirst(strtolower($type));
        
        // call the method
        $sql = $this->$method($node);
        
        // debug info if in verbose mode
        if ($this->verbose) {
            echo "\n";
            echo "method: $method\n";
            echo "node:\n"; echo $node ; echo "\n";;
            echo "result: " . print_r($sql, true) . "\n";
            echo "\n";
        }

        return $sql;
    }

    /**
     * Builds SQL statement from 'aggregate' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlAggregate(epQueryNode &$node) {
        
        // child arg?
        if ($c = & $node->getChild('arg')) {
            $argv = $this->buildSql($c);
            if ($argv && is_array($argv)) {
                $argv = $argv[0];
            }
        } 
        // param arg
        else {
            $argv = $node->getParam('arg');
        }

        // quote argv
        $argv = trim($argv);
        if ($argv != '*') {
            $this->_qq($dummy = '', $argv);
        }

        // return aggregate function with argv expr
        return $node->getParam('func') . '(' . $argv . ')';
    }

    /**
     * Builds SQL statement from 'add' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlAdd(epQueryNode &$node) {
        $left_exprs = $this->buildSql($left = & $node->getChild('left'));
        $right_exprs = $this->buildSql($right = & $node->getChild('right'));
        return $this->_buildSqlOpera($left_exprs, $left->getType(), $right_exprs, $right->getType(), $node->getParam('op'));
    }

    /**
     * Builds SQL statement from 'between' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlBetween(epQueryNode &$node) {
        
        // get expressions and node types (var)
        $vexprs = $this->buildSql($c = & $node->getChild('var'));
        if (!is_array($vexprs)) {
            $vexprs = array($vexprs);
        }
        $vtype = $c->getType();

        // get expressions and node types (expr1)
        $expr1s = $this->buildSql($c = & $node->getChild('expr1'));
        if (!is_array($expr1s)) {
            $expr1s = array($expr1s);
        }
        $type1 = $c->getType();
        
        // get expressions and node types (expr2)
        $expr2s = $this->buildSql($c = & $node->getChild('expr2'));
        if (!is_array($expr2s)) {
            $expr2s = array($expr2s);
        }
        $type2 = $c->getType();
        $separator = ' BETWEEN ';
        if ($not = & $node->getChild('not')) {
            $separator = ' NOT BETWEEN ';
        }

        // array to collect expressions
        $op_exprs = array();
        foreach($vexprs as $vpvar) {
            foreach($expr1s as $pvar1) {
                foreach($expr2s as $pvar2) {
                    
                    // collect exprs
                    $exprs = array();

                    // quote values (var, expr1)
                    $emsg = $this->qq($vpvar, $vtype, $pvar1, $type1);
                    if (is_string($emsg)) {
                        throw new epExceptionQueryBuilder($this->_e($emsg, $node));
                        continue;
                    }

                    // quote values (var, expr2)
                    $emsg = $this->qq($vpvar, $vtype, $pvar2, $type2);
                    if (is_string($emsg)) {
                        throw new epExceptionQueryBuilder($this->_e($emsg, $node));
                        continue;
                    }

                    // append to comparsion exprs
                    $op_exprs[] = $vpvar.$separator.$pvar1.' AND '.$pvar2;
                }
            }
        }

        return implode(' OR ', $op_exprs);
    }

    /**
     * Builds SQL statement from 'comparison' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlComparison(epQueryNode &$node) {
        $op = $node->getParam('op');
        $left_exprs = $this->buildSql($left = & $node->getChild('left'));
        $right_exprs = $this->buildSql($right = & $node->getChild('right'));
        return $this->_buildSqlOpera($left_exprs, $left->getType(), $right_exprs, $right->getType(), $op);
    }

    /**
     * Builds SQL statement from 'is' node
     * @return string
     * @throws epExceptionQueryBuilder
     * @todo incomplete
     */
    protected function buildSqlIs(epQueryNode &$node) {
        $var_exprs = $this->buildSql($var = & $node->getChild('var'));
        $is_what = $node->getParam('op'); 
        $is_what = strtoupper(str_replace('is ', '', $is_what));
        // use node type EPQ_N_EXPR_UNARY to force no quoting
        return $this->_buildSqlOpera($var_exprs, $var->getType(), $is_what, EPQ_N_EXPR_UNARY, ' IS ');
    }

    /**
     * Builds SQL statement from 'like' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlLike(epQueryNode &$node) {
        // process as a two-side operation. get left and right exprs
        $left_exprs = $this->buildSql($left = & $node->getChild('var'));
        $right_exprs = $this->buildSql($right = & $node->getChild('pattern'));
        $separator = ' LIKE ';
        if ($not = & $node->getChild('not')) {
            $separator = ' NOT LIKE ';
        }
        return $this->_buildSqlOpera($left_exprs, $left->getType(), $right_exprs, $right->getType(), $separator);
    }

    /**
     * Builds SQL statement from logic ('and', 'or') node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlLogic(epQueryNode &$node) {
        $op = strtoupper($node->getParam('op'));
        return $this->_buildSqlChildren($node, ' '.$op.' ', $op == 'AND');
    }

    /**
     * Builds SQL statement from 'mul' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlMul(epQueryNode &$node) {
        $left_exprs = $this->buildSql($left = & $node->getChild('left'));
        $right_exprs = $this->buildSql($right = & $node->getChild('right'));
        return $this->_buildSqlOpera($left_exprs, $left->getType(), $right_exprs, $right->getType(), $node->getParam('op'));
    }

    /**
     * Builds SQL statement from 'not' node
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlNot(epQueryNode &$node) {
        return ' NOT ' . $this->buildSql($node->getChild('method'));
    }

    /**
     * Builds SQL statement from 'paren' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqParen(epQueryNode &$node) {
        return '(' . $this->buildSql($node->getChild('expr')) . ')';
    }

    /**
     * Builds SQL statement from 'unary' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlUnary(epQueryNode &$node) {
        return $node->getParam('op') . $this->buildSql($node->getChild('expr'));
    }

    /**
     * Builds SQL statement from 'contains' node
     * @return false|string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlContains(epQueryNode &$node) {
        $var_exprs = $this->buildSql($var = & $node->getChild('var'));
        $arg = $this->_getContainsArg($node);
        // @@@todo@@@
        //return $this->_buildSqlRelationship($node, $fm, $var_exprs, $arg);
    }

    /**
     * Builds SQL statement from 'in' node
     * @return false|string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlIn(epQueryNode &$node) {
        $left_exprs = $this->buildSql($left = $node->getChild('var'));
        $right_exprs = $this->buildSql($right = $node->getChild('items'));
        $sql = array();
        $left_nt = $left->getType();
        foreach ($left_exprs as $left) {
            if ($left_nt == EPQ_N_VARIABLE) {
                $this->_qq($dummy = '', $left);
            }
            $sql[] = $left . ' in' . $right_exprs;
        }
        return join(' OR ', $sql);
    }

    /**
     * Builds SQL statement from 'in' node
     * @return false|string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlIn_Items(epQueryNode &$node) {
        $results = array();
        if ($children = $node->getChildren()) {
            foreach($children as &$child) {
                if ($result = $this->buildSql($child)) {
                    if (is_array($result)) {
                        foreach ($result as &$item) {
                            $item = $this->pm->quote($item);
                        }
                        $results = array_merge($results, $result);
                    } else {
                        $result = $this->pm->quote($result);
                        $results[] = $result;
                    }
                }
            }
        }
        
        return '(' . implode(', ', $results) . ')';
    }

    /**
     * Builds SQL statement from 'soundex' node
     * @return false|string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlSoundex(epQueryNode &$node) {
        $var_exprs = $this->buildSql($var = & $node->getChild('arg'));
        return array('soundex('.join(',', $var_exprs).')');
    }

    /**
     * Builds SQL statement from 'strcmp' node
     * @return false|string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlStrcmp(epQueryNode &$node) {
        $var_exprs = $this->buildSql($var = & $node->getChild('arg1'));
        $var_exprs = array_merge($var_exprs, $this->buildSql($var = & $node->getChild('arg2')));
        return array('strcmp('.join(',', $var_exprs).')');
    }

    /**
     * Builds SQL statement from 'identifier' node
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlIdentifier(epQueryNode &$node) {
        return $node->getParam('val');
    }

    /**
     * Builds SQL statement from 'limit' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlLimit(epQueryNode &$node) {
        
        $sql = 'LIMIT ';

        // grab the start
        $start = $this->buildSql($node->getChild('start'));

        // get length if exists
        if ($length_node = & $node->getChild('length')) {
            $sql .= $this->buildSql($length_node);

            $sql .= ' OFFSET ' . $start;

        } else {

            $sql .= $start;
        }
        
        return $sql;
    }

    /**
     * Builds SQL statement from 'number' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlNumber(epQueryNode &$node) {
        return $node->getParam('val');
    }

    /**
     * Builds SQL statement from 'boolean' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlBoolean(epQueryNode &$node) {
        return $node->getParam('val');
    }

    /**
     * Builds SQL statement from 'orderby' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlOrderby(epQueryNode &$node) {
        return 'ORDER BY ' . $this->_buildSqlChildren($node, ', ');
    }

    /**
     * Builds SQL statement from 'orderby_item' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlOrderby_item(epQueryNode &$node) {

        // get the direction
        $dir = $node->getParam('direction');

        // special treatment for 'order by random()'
        if ($dir == 'random') {
            // keep track of order by (note path is set to empty)
            $this->orderby[] = array('path' => '', 'dir' => $dir);
            return 'RANDOM()';
        }
        
        // get the variable node
        $var_node = $node->getChild('var');
        
        // keep track of orderby
        if ($path = $var_node->getParam('path')) {
            // remove the first piece
            $pieces = explode('.', $path);
            array_shift($pieces);
            $this->orderby[] = array('path' => implode('.', $pieces), 'dir' => $dir);
        }
        
        // make sql for this orderby item
        $vars = $this->buildSql($var_node);
        $var = $vars[0];
        $this->_qq($dummy_v = '', $var); // $var will be altered
        return  $var . ' ' . $dir;
    }

    /**
     * Builds SQL statement for a 'paren' node
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlParen(epQueryNode &$node) {
        if (!($sql = $this->_buildSqlChildren($node))) {
            return '';
        }
        return '(' . $sql . ')';
    }

    /**
     * Builds SQL statement from 'pattern'
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlPattern(epQueryNode &$node) {
        if ($pattern = $node->getParam('val')) {
            return $pattern;
        }
        return $this->_buildSqlChildren($node);
    }

    /**
     * Builds SQL statement from 'placeholder' node
     * @return mixed
     * @throws epExceptionQueryBuilder
     */
    protected function &buildSqlPlaceholder(epQueryNode &$node) {
        return $this->_getPlaceholderValue($node);
    }

    /**
     * Builds SQL statement from 'select' node
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlSelect(epQueryNode &$node) {

        // order matters!!
        
        // build aggregate
        $aggregate = $this->pm->quoteId($this->primary_alias.'.*');
        if ($n = $node->getChild('aggregate')) {
            $aggregate = $this->aggr_func = $this->buildSqlAggregate($n);
        }

        // build where 
        $where = 'WHERE 1=1';
        if ($n = $node->getChild('where')) {
            $where = $this->buildSqlWhere($n);
        } 

        // build limit
        $limit = '';
        if ($n = $node->getChild('limit')) {
            $limit = $this->limit = $this->buildSqlLimit($n);
        }

        // build orderby
        $orderby = '';
        if ($n = $node->getChild('orderby')) {
            $orderby = $this->buildSqlOrderby($n);
        }

        // get the first part select+distinct+aggregate
        $select = "SELECT DISTINCT $aggregate ";

        // get the second part order by + limit
        $orderby_limit = '';
        if ($orderby) {
            $orderby_limit .= ' '.$orderby;
        }
        if ($limit) {
            $orderby_limit .= ' '.$limit;
        }
        
        // get sql left-joins for primary/secondary roots
        $sql_parts = $this->pm->getRootSql();

        // pick out the primary root
        $p_sql_parts = $sql_parts[$this->primary_alias];

        // unset primary and get all secondary parts
        unset($sql_parts[$this->primary_alias]);
        $s_sql_parts = $sql_parts;

        // arrays to hold froms and joins
        $froms = array();
        $joins = array();

        // quote id (primary alias)
        $p_alias = $this->pm->quoteId($this->primary_alias);
        
        // loop through tables for primary root
        foreach($p_sql_parts as $p_table => $p_joins) {
            
            // quote id
            $p_table = $this->pm->quoteId($p_table);

            // collect table-as-alias for primary root
            $froms[] = array($p_table . ' AS ' . $p_alias);
            $joins[] = array($p_joins);
        }

        // loop through tables for secondary roots
        foreach($s_sql_parts as $s_alias => $s_sql_part) {

            // quote id
            $s_alias = $this->pm->quoteId($s_alias);

            // backup froms and joins
            $froms0 = $froms;
            $joins0 = $joins;

            // reset froms and joins
            $froms = array();
            $joins = array();

            // loop through joins for each secondary table
            foreach($s_sql_part as $s_table => $s_joins) {

                // quote id
                $s_table = $this->pm->quoteId($s_table);

                // start with froms/joins backup
                $froms_ = $froms0;
                $joins_ = $joins0;

                // collect table-as-alias for secondary root
                foreach($froms_ as $k => $from) {
                    $froms_[$k][] = $s_table . ' AS ' . $s_alias;
                }

                // collect joins
                foreach($joins_ as $k => $join) {
                    $joins_[$k][] = $s_joins;
                }

                $froms = array_merge($froms, $froms_);
                $joins = array_merge($joins, $joins_);
            }
        }

        // array to hold all sql statements
        $stmts = array();

        // assemble from 'froms' and 'joins'
        for($i = 0; $i < count($froms); $i ++) {
            
            // make from clause
            $from = implode(', ', $froms[$i]);
            
            // make left join clauses
            $join = '';
            if ($joins[$i]) {
                $join = implode('', $joins[$i]);
            }
            
            // assemble a sql statement
            $stmts[] = $select . 'FROM ' . $from . ' ' . $join . $where;
        }

        // if we have only one statement
        if (1 == count($stmts)) {
            // append orderby and limit clauses
            $stmts[0] .= $orderby_limit;
            // empty limit so no post-query limit operation
            $this->limit = false;
        }
 
        return $stmts;
    }

    /**
     * Builds SQL statement from 'string' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlString(epQueryNode &$node) {
        return $node->getParam('val');
    }

    /**
     * Builds SQL statement from 'variable' node
     * 
     * The returning array is an associative array keyed by the primitive
     * variable in the form of '<alias>.<var_name>' and the value is the
     * condition for the relationship fields. 
     * 
     * @return false|array
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlVariable(epQueryNode &$node) {
        
        // check if path is set on variable node
        if (!($path = trim($node->getParam('path')))) {
            throw new epExceptionQueryBuilder($this->_e("no path for varialbe", $node));
            return false;
        }

        // the varialbe name is the last item
        $pieces = explode('.', $path);
        $var = $pieces[count($pieces) - 1];

        // array to hold SQL expressions for primitive vars 
        $pvars = array();

        // if the path points to an object
        if ($this->pm->isObject($path)) {
            // force it to oid
            $var = 'oid';
        }

        // call path manager to get aliases
        if ($aliases = $this->pm->getAliases($path)) {
            foreach($aliases as $alias) {
                $pvars[] = $this->pm->quoteId($alias) . '.' . $this->pm->quoteId($var);
            }
        }

        return $pvars;
    }

    /**
     * Builds SQL statement from 'variable' node
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlWhere(epQueryNode &$node) {
        
        // array to collect where expressions
        $wheres = array();

        // get where expression from children nodes
        if ($where = trim($this->_buildSqlChildren($node))) {
            $wheres[] = $where;
        }
        
        // check if where is empty
        if (!($where = implode(' AND ', $wheres))) {
            $where = '1=1';
        }
        
        // prepend 'where '
        return 'WHERE ' . $where;
    }

    /**
     * Call children's SQL builders
     * @param epQueryNode &$node
     * @param string $seperator
     * @param boolean $use_parentheses Whether to use parentheses around child results
     * @return boolean
     */
    protected function _buildSqlChildren(epQueryNode &$node, $seperator = ' ', $use_parentheses = false) {
        $results = array();
        if ($children = $node->getChildren()) {
            foreach($children as &$child) {
                if ($result = $this->buildSql($child)) {
                    $results[] = $result;
                }
            }
        }
        // use parentheses only when we have more than one child results
        if ($use_parentheses && (count($results) > 1)) {
            foreach($results as $k => $result) {
                $results[$k] = '(' . $result . ')';
            }
        }
        
        return implode($seperator, $results);
    }

    /**
     * Build SQL for an operation that involves LHS and RHS operands for a primitive var
     * @param array|string $left_exprs the LHS expression
     * @param string $left_type the LHS node type
     * @param array|string $right_exprs the RHS expression
     * @param string $right_type the RHS node type
     * @param string $op the operation
     * @return string
     */
    protected function _buildSqlOpera($left_exprs, $left_type, $right_exprs, $right_type, $op) {

        // arrayize exprs
        if (!is_array($left_exprs)) {
            $left_exprs = array($left_exprs);
        }
        if (!is_array($right_exprs)) {
            $right_exprs = array($right_exprs);
        }

        // array to collect operation exprs
        $op_exprs = array();
        foreach($left_exprs as $left_pvar) {
            foreach($right_exprs as $right_pvar) {
                // quote left and right vars
                $emsg = $this->qq($left_pvar, $left_type, $right_pvar, $right_type);
                if (is_string($emsg)) {
                    throw new epExceptionQueryBuilder($this->_e($emsg));
                    continue;
                }
                // append to comparsion exprs
                $op_exprs[] = $left_pvar . $op . $right_pvar;
            }
        }

        return implode(' OR ', $op_exprs);
    }
    
    /**
     * Quotes left and right hand primitive values according to node type
     * @param mixed $left_v the lhs value (will be modified)
     * @param string $left_nt the lhs node type
     * @param mixed $right_v  the rhs value (will be modified)
     * @param string $right_nt the rhs node type
     * @return string|true
     */
    protected function qq(&$left_v, $left_nt, &$right_v, $right_nt) {
        // case one: left variable, right value
        if ($left_nt == EPQ_N_VARIABLE 
            && $right_nt != EPQ_N_VARIABLE 
            && false === strpos($right_nt, 'EPQ_N_EXPR_')) {
            if ($right_nt != EPQ_N_NUMBER) {
                return $this->_qq($right_v, $left_v);
            } else {
                return $this->_qq($dummy='', $left_v);
            }
        } 
        // case two: left value, right variable
        else if ($left_nt != EPQ_N_VARIABLE 
                 && false === strpos($left_nt, 'EPQ_N_EXPR_')
                 && $right_nt == EPQ_N_VARIABLE) {
            if ($left_nt != EPQ_N_NUMBER) {
                return $this->_qq($left_v, $right_v);
            } else {
                return $this->_qq($dummy='', $right_v);
            }
        }
        // case three: left func, right value
        if (false !== strpos($left_nt, 'EPQ_N_FUNC_')
            && $right_nt != EPQ_N_VARIABLE 
            && false === strpos($right_nt, 'EPQ_N_EXPR_')) {
            if ($right_nt != EPQ_N_NUMBER) {
                $right_v = $this->pm->quote($right_v);
            }
            return true;
        } 
        // case four: left variable, right null (for oid)
        else if ($right_nt == EPQ_N_EXPR_UNARY
                 && $left_nt == EPQ_N_VARIABLE
                 && ($right_v == 'NULL' || $right_v == 'NOT NULL')) {
            return $this->_qq($dummy='', $left_v);
        }
        // case five: left variable, right variable
        else if ($right_nt == EPQ_N_VARIABLE
                 && $left_nt == EPQ_N_VARIABLE) {
            $this->_qq($dummy='', $left_v);
            $this->_qq($dummy='', $right_v);
        }
        // done for all else
        return true;
    }

    /**
     * Quotes primitive value with its primitve variable (alias.var)
     * @param mixed $v
     * @param string $pvar
     * @return true|string (error message if string)
     */
    private function _qq(&$v, &$pvar) {
        return $this->pm->quoteVar($v, $pvar);
    }

    /**
     * Returns error message with pointer to the original query
     * @param string $msg
     * @param epQueryNode $node
     * @return epExceptionQueryBuilder
     */
    private function _e($msg, $node = false) {
        
        if (!$node) {
            $node = $this->root;
        }

        $l = $node->getParam('line') - 1;
        $c = $node->getParam('char');
        
        // find the right line
        $pos = 0;
        while ($l && false !== ($pos_ = strpos($this->query, "\n"))) {
            $pos = $pos_;
            $l --;
        }
        $pos += $c;

        // find word start and end
        $start = $pos;
        while ($start && $this->query[$start] != ' ') {
            $start --;
        }
        
        $len = strlen($this->query);

        $end = $pos;
        while ($end < $len && $this->query[$end] != ' ') {
            $end ++;
        }
        
        $s = substr($this->query, $start = max(0, $start - 10), $pos-1-$start);
        $s .= '###' . substr($this->query, $pos-1, min($end + 10, $len));
        
        // append pointer
        $msg .= ' (near "... ' . $s . ' ...")';

        return $msg;
    }

}

?>
