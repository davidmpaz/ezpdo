<?php

/**
 * $Id: epQueryParser.php 1048 2007-04-13 02:31:17Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1048 $
 * @package ezpdo
 * @subpackage ezpdo.query
 */

/**#@+
 * need epQueryLexer
 */
include_once(EP_SRC_BASE_PARSER.'/epParser.php');
include_once(EP_SRC_QUERY.'/epQueryLexer.php');
/**#@-*/

/**#@+
 * Types of EZOQL syntax nodes
 */
epDefine('EPQ_N_AGGREGATE');
epDefine('EPQ_N_BOOLEAN');
epDefine('EPQ_N_EXPR_ADD');
epDefine('EPQ_N_EXPR_BETWEEN');
epDefine('EPQ_N_EXPR_COMPARISON');
epDefine('EPQ_N_EXPR_IS');
epDefine('EPQ_N_EXPR_LIKE');
epDefine('EPQ_N_EXPR_LOGIC');
epDefine('EPQ_N_EXPR_MUL');
epDefine('EPQ_N_EXPR_NOT');
epDefine('EPQ_N_EXPR_PAREN');
epDefine('EPQ_N_EXPR_UNARY');
epDefine('EPQ_N_FUNC_SOUNDEX');
epDefine('EPQ_N_FUNC_STRCMP');
epDefine('EPQ_N_CONTAINS');
epDefine('EPQ_N_FROM');
epDefine('EPQ_N_FROM_ITEM');
epDefine('EPQ_N_IDENTIFIER');
epDefine('EPQ_N_IN');
epDefine('EPQ_N_IN_ITEMS');
epDefine('EPQ_N_LIMIT');
epDefine('EPQ_N_NUMBER');
epDefine('EPQ_N_ORDERBY');
epDefine('EPQ_N_ORDERBY_ITEM');
epDefine('EPQ_N_PATTERN');
epDefine('EPQ_N_PLACEHOLDER');
epDefine('EPQ_N_SELECT');
epDefine('EPQ_N_STRING');
epDefine('EPQ_N_UNKNOWN');
epDefine('EPQ_N_VARIABLE');
epDefine('EPQ_N_WHERE');
/**#@-*/

/**
 * The class of an EZOQL syntax node 
 * 
 * A syntax node is part of the syntax tree and can have its 
 * children nodes and a parent. A node without parent is the 
 * root of the syntax tree. A node can also have named 
 * parameters. 
 * 
 * There are two parameters 'line' and 'char' that the parser
 * always installs for a node, which indicate the location 
 * in the source code from which this node is parsed from. 
 * 
 * A node can also keep records of parsing errors, but so far
 * not used (as the parser {@epQueryParser} also collects errors
 * during parsing).
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1048 $
 * @package ezpdo
 * @subpackage ezpdo.query
 */
class epQueryNode extends epBase {
    
    /**
     * Parent of the node (It's a root node if empty.)
     * @var false|epQueryNode 
     */
    protected $parent = false;
    
    /**
     * Array of children
     * @var array
     */
    protected $children = array();
    
    /**
     * Type of the node
     * @var string
     */
    protected $type = EPQ_N_UNKNOWN;
    
    /**
     * Parameters of the node (an associative array)
     * @var array 
     */
    protected $params = array();
    
    /**
     * Erros when parsing the node
     * @var array 
     */
    protected $errors = array();
    
    /**
     * Constructor 
     * @param false|epQueryNode $parent
     */
    public function __construct($type = EPQ_N_UNKNOW, $parent = false) {
        $this->setType($type);
        $this->setParent($parent);
    }
    
    /**
     * Returns the parent of the node
     * @return false|epQueryNode
     */
    public function &getParent() {
        return $this->parent;
    }

    /**
     * Returns the parent of the node
     * @return false|epQueryNode
     */
    public function setParent($p) {
        $this->parent = & $p;
    }

    /**
     * Returns the type of the node
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Returns the parent of the node
     * @return false|epQueryNode
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * Returns all children
     * @return array
     */
    public function &getChildren() {
        return $this->children;
    }
    
    /**
     * Get a child by key
     * @return false|epQueryNode
     */
    public function &getChild($key) {
        $false = false;
        if (!isset($this->children[$key])) {
            return $false;
        } 
        return $this->children[$key];
    }
    
    /**
     * Add a child to the node
     * @param array|epQueryNode &$child
     * @return boolean
     */
    public function addChild(&$child, $key = false) {
        
        if ($child) {
            
            if ($key) {
                $this->children[$key] = $child;
            } else {
                $this->children[] = $child;
            }
            
            if (!is_array($child)) {
                if ($parent = $child->getParent()) {
                    $parent->removeChild($child);
                }
                $child->setParent($this);
            } else {
                foreach($child as $child_) {
                    if ($parent = $child_->getParent()) {
                        $parent->removeChild($child_);
                    }
                    $child_->setParent($this);
                }
            }
        }

        return true;
    }

    /**
     * Add a list of children to the node
     * @return boolean
     */
    public function addChildren($children) {
        
        if (!is_array($children)) {
            return false;
        }
        
        $status = true;
        foreach($children as $k => $n) {
            if (is_integer($k)) {
                $status &= $this->addChild($n);
            } 
            else if (is_string($k)) {
                $status &= $this->addChild($n, $k);
            }
        }
        
        return $status;
    }

    /**
     * Remove a child by key or node
     * @param string|epQueryNode $key_or_node
     * @return boolean
     */
    public function removeChild(&$key_or_node) {
        
        // a key (string)
        if (is_string($key_or_node)) {
            
            if (!isset($this->children[$key_or_node])) {
                return false;
            } 
            
            $this->children[$key_or_node]->setParent(false);
            unset($this->children[$key_or_node]);
            return true;
        }

        // a node (epQueryNode)
        else if ($key_or_node instanceof epQueryNode) {
            
            // need to go through all children
            foreach($this->children as $key => &$child) {

                // same as this child (notice ===)
                if ($child === $key_or_node) {
                    $this->children[$key]->setParent(false);
                    unset($this->children[$key]);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Replace child with another node
     * @param string|epQueryNode &$key_or_node
     * @param epQueryNode &$node
     * @return boolean
     */
    public function replaceChild(&$key_or_node, epQueryNode &$node) {
        
        // a key (int or string)
        if (is_int($key_or_node) || is_string($key_or_node)) {
            
            if (!isset($this->children[$key_or_node])) {
                return false;
            } 
            
            // remove parent from child
            $this->children[$key_or_node]->setParent(false);

            // replace child with new node
            $this->children[$key_or_node] = $node;

            // set parent to child node
            $node->setParent($this);
            
            return true;
        }

        // a node (epQueryNode)
        else if ($key_or_node instanceof epQueryNode) {
            
            // need to go through all children
            foreach($this->children as $key => &$child) {

                // same as this child (notice ===)
                if ($child === $key_or_node) {

                    // remove parent from child
                    $this->children[$key]->setParent(false);
                    
                    // replace child with new node 
                    $this->children[$key] = $node;

                    // set parent to child node
                    $node->setParent($this);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get params of the node
     * @return array
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * Get the value of a parameter 
     * @param string $name
     * @return null|else (null if not found)
     */
    public function getParam($name) {
        if (!isset($this->params[$name])) {
            return null;
        } 
        return $this->params[$name];
    }
    
    /**
     * Set a parameter
     * @param string $name
     * @param any $value
     * @param boolean $replace (if exists)
     * @return boolean
     */
    public function setParam($name, $value, $replace = true) {
        if (isset($this->params[$name]) && !$replace) {
            return false;
        }
        $this->params[$name] = $value;
        return true;
    }
    
    /**
     * Get all errors when parsing the node 
     * @return array
     */
    public function getErrors() {
        $this->errors;
    }
    
    /**
     * Add an error to the node
     * @return array
     */
    public function addError($error) {
        $this->errors[] = $error;
    }
    
    /**
     * Implement magic method __toString()
     * 
     * Note that all the Parser testing depend on the output of this method. 
     * Changing this method may break all the tests. 
     */
    public function __toString($indent = '') {
        
        // indent
        $s = $indent;
        
        // type
        $s .= $this->getType();
        
        // params
        $s .= ' [';
        ksort($this->params);
        foreach($this->params as $name => $value) {
            $s .= $name . ': ' . $this->_pv2str($value) . ', ';
        }
        // remove the last ', '
        if ($this->params) {
            $s = substr($s, 0, strlen($s) - 2);
        }
        $s .= "]";
        
        // go through children (recursion)
        $indent .= '  ';
        if ($this->children) {
            $s .= "\n";
            foreach($this->children as $key => $child) {
                
                if (!is_integer($key)) {
                    $s .= $indent . "::" . $key . "::\n";
                }

                // child may be an array
                if (is_array($child)) {
                    foreach($child as $child_) { 
                        $s .= $child_->__toString($indent);
                    }
                } else {
                    $s .= $child->__toString($indent);
                }
            }
        }
        
        // make sure child string ends with only one "\n"
        $s = rtrim($s) . "\n";

        return $s;
    }
    
    /**
     * Convert a parameter value into string. Called by __toString()
     * @param any $v
     * @return string
     */
    private function _pv2str($v) {
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        } 
        else if (is_array($v)) {
            
            // stringify array $v
            $vv = array();
            foreach($v as $v_) {
                $vv[] = is_object($v_) ? $v_->__toString() : $v_;
            }

            return "(" . implode(', ', $vv) . ")";
        }
        return $v;
    }
}

/**
 * The class of EZOQL Parser
 * 
 * Put it simply, the parser converts the EZOQL query string into a 
 * syntax tree that contains syntax nodes. 
 * 
 * This is a recursive-descent parser (see 
 * {@link http://en.wikipedia.org/wiki/Recursive_descent_parser}) 
 * for EZOQL and uses lexical and semantic feedback to disambiguate 
 * non-LL(1) structures. The '1' in LL(1) means that the parser uses 
 * only 1-token lookahead ({@link epQueryParser::peek()}). 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1048 $
 * @package ezpdo
 * @subpackage ezpdo.query
 */
class epQueryParser extends epParser {
    
    /**
     * The current node 
     * @var false|epQueryNode
     */
    protected $n = false;
    
    /**
     * The index of the current argument
     * @var integer
     */
    protected $aindex = 0;

    /**
     * Constructor
     * @param string $s
     * @param boolean $v(verbose)
     */
    public function __construct($s = '', $verbose = false) {
        $this->initialize($s, $verbose);
    }

    /**
     * Override {@link epParser::initialize()}
     * Initializes the parser to use {@link epQueryLexer} instead of {@link epParser}
     * @param string $s
     */
    public function initialize($s, $verbose = false) {

        // set verbosity
		$this->verbose($verbose);
        
        // set string to lexer
        if (!$this->_lexer) {
            $this->_lexer = new epQueryLexer($s);
        } else {
            $this->_lexer->initialize($s);
        }

        // clear errors
        $this->errors = array();

        // reset argument index
        $this->aindex = 0;
    }

    /**
     * Parses the stream and return the root node
     * @return false|epQueryNode
     */
    public function parse($s = '', $verbose = false) {
        
        // set the string to parse. o.w. parse the one already set
        if ($s) {
            $this->initialize($s, $verbose);
        }
        
        // start parsing
        $this->message("start parsing [".$this->getInput()."]");

        // is query empty?
        if ($this->peek() === false) {
            
            // raise error
            $this->error('Empty query');
            
            // done parsing
            $this->message('parsing done');

            return false;
        }

        // actual parsing
        $root = $this->statement();

        // done parsing
        $this->message('parsing done');

        return $root;
    }

    /**
     * Parses the entire query statement
     * @return false|
     */
    protected function statement() {
        $this->message(__METHOD__);

        // peek the next token
        if ($this->peek() === false) {
            // eof reached
            return false;
        }

        // parse select statement
        return $this->select();
    }

    /**
     * Parses the select statement
     * @return false|epQueryNode
     */
    protected function select() {
        $this->message(__METHOD__);

        // consume 'select' if exists (ok if not)
        if ($this->peek() == EPQ_T_SELECT) {
            $this->next();
        }
        
        // make a select node
        if (!($node = $this->node(EPQ_N_SELECT))) {
            return false;
        }

        // aggregate function?
        if ($this->_isAggregateFunc($t = $this->peek())) {
            if ($aggregate = $this->aggregate()) {
                $node->addChild($aggregate, 'aggregate');
            }
        }

        // expect 'from'
        if ($this->peek() != EPQ_T_FROM) {
            $this->syntax_error("'from' expected");
            return false;
        } 

        // parse 'from'
        if ($from = $this->from()) {
            $node->addChild($from, 'from');
        }
        
        // expect 'where'
        if ($this->peek() == EPQ_T_WHERE) {
            // parse 'where' if exists
            if ($where = $this->where()) {
                $node->addChild($where, 'where');
            }
        } 

        // check if 'order by' exists
        if ($this->peek() == EPQ_T_ORDER) {
            if ($orderby = $this->orderby()) {
                $node->addChild($orderby, 'orderby');
            }
        }
        
        // check if 'limit' exists
        if ($this->peek() == EPQ_T_LIMIT) {
            if ($limit = $this->limit()) {
                $node->addChild($limit, 'limit');
            }
        }
        
        return $node;
    }
    
    /**
     * Parses the aggregate functions
     * @return false|epQueryNode
     */
    protected function aggregate() {
        $this->message(__METHOD__);
        
        // expect aggreate function name
        if (!($this->_isAggregateFunc($t = $this->peek()))) {
            return false;
        }
        $this->next();
        $func = $this->t->value;

        // expect '('
        if ($this->peek() != '(') {
            return false;
        }
        $this->next();
        
        // parse variable
        if (!($arg = $this->argument())) {
            $this->syntax_error("Invalid variable for '$func'");
            return false;
        }
        
        // expect ')'
        if ($this->peek() != ')') {
            $this->syntax_error("')' is expected");
        } else {
            $this->next();
        }

        // create aggregate node
        if (!($node = $this->node(EPQ_N_AGGREGATE))) {
            return false;
        }
        $node->setParam('func', $func);
        if (is_string($arg)) {
            $node->setParam('arg', $arg);
        } else {
            $node->addChild($arg, 'arg');
        }

        return $node;
    }

    /**
     * Parses the 'from' clause
     * @return false|epQueryNode
     */
    protected function from() {
        $this->message(__METHOD__);

        // expect 'from'
        if (($t = $this->peek()) != EPQ_T_FROM) {
            return false;
        }
        $this->next();

        // array to keep all from items
        $items = array();
        do {
            // eat ','
            if ($t == ',') {
                $this->next();
            }

            // expect an identifier
            if (($t = $this->peek()) != EPQ_T_IDENTIFIER) {
                $this->syntax_error("A class name is expected");
                return false;
            }
            $this->next();

            // create a from node
            if (!($item = $this->node(EPQ_N_FROM_ITEM))) {
                return false;
            }

            // get the class name
            $item->setParam('class', trim($this->t->value, '`'));

            // check 'as'
            if ($this->peek() == EPQ_T_AS) {
                $this->next();

                // expect an identifier
                if ($this->peek() != EPQ_T_IDENTIFIER) {
                    $this->syntax_error("An alias name is expected");
                } else {
                    $this->next();
                    // get the alias
                    $item->setParam('alias', $this->t->value);
                }
            }

            $items[] = $item;
            
        } while (($t = $this->peek()) == ',');

        // create a from node
        if (!($node = $this->node(EPQ_N_FROM))) {
            return false;
        }
        $node->addChildren($items);

        return $node;
    }

    /**
     * Parses the 'where' clause
     * @return false|epQueryNode
     */
    protected function where() {
        $this->message(__METHOD__);

        // expect 'where'
        if ($this->peek() != EPQ_T_WHERE) {
            return false;
        }
        $this->next();
        
        // create a where node
        if (!($node = $this->node(EPQ_N_WHERE))) {
            return false;
        }
        
        // parse or expression
        if (!($e = $this->logic())) {
            return false;
        }

        // add or expression into where node
        $node->addChild($e, 'expr');

        return $node;
    }

    /**
     * Parses logical or expression
     * @return false|epQueryNode
     */
    protected function logic() {
        $this->message(__METHOD__);

        // is next '('?
        if ($this->peek() == '(') {
            $e1 = $this->paren_l();
        } else {
            // parse logical and expr
            $e1 = $this->factor();
        }

        if (!$e1) {
            return false;
        }

        // parse logical or exprs
        while (($t = $this->peek()) == EPQ_T_OR || $t == EPQ_T_AND) {
            $this->next();
            $op = $this->t->value;
            
            // is next '('?
            if ($this->peek() == '(') {
                $e2 = $this->paren_l();
            } else {
                // parse logical and expr
                $e2 = $this->factor();
            }

            if (!$e2) {
                $this->syntax_error("invalid expression after 'or'");
            }
            
            if (!($node = $this->node(EPQ_N_EXPR_LOGIC))) {
                return false;
            }
            
            $node->setParam('op', $op);
            $node->addChildren(array($e1, $e2));
            
            $e1 = $node;
        }

        return $e1;
    }

    /**
     * Parse logic expression within parentheses
     * @return false|epQueryNode
     */
    protected function paren_l() {
        $this->message(__METHOD__);
        
        // expect '('
        if ($this->peek() != '(') {
            return false;
        }
        $this->next();
        
        // parse expression (simple expression)
        if (!($e = $this->logic())) { 
            $this->syntax_error("Invalid expression in parentheses");
        }
        
        // expect ')'
        if ($this->peek() != ')') {
            $this->syntax_error("')' is expected");
        } else {
            $this->next();
        }
        
        // make a paren expr node
        if (!($node = $this->node(EPQ_N_EXPR_PAREN))) {
            return false;
        }

        // add child expr into node
        if ($e) {
            $node->addChild($e, 'expr');
        }

        return $node;
    }

    /**
     * Parses function expression
     * @return false|epQueryNode
     */
    protected function functions() {
        $this->message(__METHOD__);

        $t = $this->peek();

        // strcmp
        if ($t == EPQ_T_STRCMP) {
            $node = $this->strcmp();
            return $node;
        }
        // soundex
        else if ($t == EPQ_T_SOUNDEX) {
            $node = $this->soundex();
            return $node;
        }
        // something wrong
        return false;
    }

    /**
     * Parses factor expression
     * @return false|epQueryNode
     */
    protected function factor() {
        $this->message(__METHOD__);

        // parse left hand variable
        if (!($var = $this->primary())) {
        //if (!($var = $this->variable())) {
            return false;
        }
        
        // 1. comparision op
        if ($this->_isComparisonOp($t = $this->peek())) {
            $this->next();
            
            // create the comparison node
            if (!($node = $this->node(EPQ_N_EXPR_COMPARISON))) {
                return false;
            }

            // add left hand variable into to node
            $node->addChild($var, 'left');

            // set operator ('==' => '=')
            $node->setParam('op', $t == EPQ_T_EQUAL ? '=' : $this->t->value);

            // parse right side
            if ($right = $this->add()) {
                // add right hand variable into to node
                $node->addChild($right, 'right');
            }

            return $node;
        }
        
        // 2. is [not] null
        if ($t == EPQ_T_IS) {

            // parse 'is' node
            if (!($node = $this->is())) {
                return false;
            }
            
            // add left hand variable into to node
            $node->addChild($var, 'var');
            return $node;
        }
        
        // 3. not [between|like]
        if ($t == EPQ_T_NOT) {
            
            // parse 'not' node
            if (!($node = $this->not())) {
                return false;
            }

            // add the var to the method child
            $node->addChild($var, 'var');
            return $node;
        }
        
        // 4. between
        if ($t == EPQ_T_BETWEEN) {
            if (!($node = $this->between())) {
                return false;
            }
            $node->addChild($var, 'var');
            return $node;
        }
        
        // 5. like
        if ($t == EPQ_T_LIKE) {
            if (!($node = $this->like())) {
                return false;
            }
            $node->addChild($var, 'var');
            return $node;
        }

        // 6. contains
        if ($t == EPQ_T_CONTAINS) {
            if (!($node = $this->contains())) {
                return false;
            }
            $node->addChild($var, 'var');
            return $node;
        }

        // 7. in
        if ($t == EPQ_T_IN) {
            if (!($node = $this->in())) {
                return false;
            }
            $node->addChild($var, 'var');
            return $node;
        }

        // something wrong
        return false;
    }

    /**
     * Parse the simple expression (i.e. the additive expression)
     * @return false|epQueryNode
     */
    protected function add() {
        $this->message(__METHOD__);
        
        if (!($e1 = $this->mul())) {
            return false;
        }

        $t = $this->peek();
        while ($t == '+' || $t == '-') {

            $this->next();

            if (!($e2 = $this->mul())) {
                $this->syntax_error("invalid multiplicative expression");
            }

            // create a logical and node
            if (!($node = $this->node(EPQ_N_EXPR_ADD))) {
                return false;
            }

            $node->setParam('op', $t);
            $node->addChildren(array('left' => $e1, 'right' => $e2));

            $e1 = $node;
            
            $t = $this->peek();
        }

        return $e1;
    }

    /**
     * Parse multiplicative expression (in additive expression)
     * @return false|epQueryNode
     */
    protected function mul() {

        $this->message(__METHOD__);
        
        // parse unary expr
        if (!($e1 = $this->unary())) {
            return false;
        }

        $t = $this->peek();
        while ($t == '*' || $t == '/') {

            $this->next();

            if (!($e2 = $this->unary())) {
                $this->syntax_error("invalid unary expression in multiplicative expression");
            }

            if (!($node = $this->node(EPQ_N_EXPR_MUL))) {
                return false;
            }

            $node->setParam('op', $t);
            $node->addChildren(array('left' => $e1, 'right' => $e2));

            $e1 = $node;
            
            $t = $this->peek();
        }

        return $e1;
    }

    /**
     * Parses unary expression (in multiplicative expression)
     * @return false|epQueryNode
     */
    protected function unary() {

        $this->message(__METHOD__);
        
        $t = $this->peek();
        if ($t == '+' || $t == '-') {
            
            $this->next();
            
            if (!($e = $this->unary())) {
                $this->syntax_error("invalid unary expression in unary expression");
            }
            
            if (!($node = $this->node(EPQ_N_EXPR_UNARY))) {
                return false;
            }
            
            $node->setParam('op', $t);
            $node->addChild($e, 'expr');
            
            return $node;
        }

        return $this->primary();
    }

    /**
     * Parses primary in unary expression
     * @return false|epQueryNode
     */
    protected function primary() {
        $this->message(__METHOD__);
        
        // simple expression in ()? 
        if (($t = $this->peek()) == '(') {
            return $this->paren_a();
        } 
        // string
        else if ($t == EPQ_T_STRING) {
            $this->next();
            $node = $this->node(EPQ_N_STRING);
            $node->setParam('val', $this->unquote($this->t->value));
            return $node;
        }
        // number
        else if ($t == EPQ_T_INTEGER || $t == EPQ_T_FLOAT) {
            $this->next();
            $node = $this->node(EPQ_N_NUMBER);
            $node->setParam('val', $this->t->value);
            return $node;
        }
        // boolean
        else if ($t == EPQ_T_TRUE || $t == EPQ_T_FALSE) {
            $this->next();
            $node = $this->node(EPQ_N_BOOLEAN);
            // force it to a boolean value
            if (strtolower($this->t->value) == 'false') {
                $this->t->value = false;
            } else {
                $this->t->value = true;
            }
            $node->setParam('val', $this->t->value);
            return $node;
        }
        // placeholder
        else if ($t == '?') {
            $this->next();
            $node = $this->node(EPQ_N_PLACEHOLDER);
            $node->setParam('aindex', $this->aindex ++);
            return $node;
        }
        // try functions
        else if ($v = $this->functions()) {
            return $v;
        }
        // try variable 
        else if ($v = $this->variable()) {
            return $v;
        }
        // something wrong
        return false;
    }

    /**
     * Parse arithmatic expression within parentheses
     * @return false|epQueryNode
     */
    protected function paren_a() {
        $this->message(__METHOD__);
        
        // expect '('
        if ($this->peek() != '(') {
            return false;
        }
        $this->next();
        
        // parse expression (simple expression)
        if (!($e = $this->add())) { 
            $this->syntax_error("Invalid expression in parentheses");
        }
        
        // expect ')'
        if ($this->peek() != ')') {
            $this->syntax_error("')' is expected");
        } else {
            $this->next();
        }
        
        // make a paren expr node
        if (!($node = $this->node(EPQ_N_EXPR_PAREN))) {
            return false;
        }

        // add child expr into node
        if ($e) {
            $node->addChild($e, 'expr');
        }

        return $node;
    }

    /**
     * Parses 'is': is [not] null
     * @return false|epQueryNode
     */
    protected function is() {
        $this->message(__METHOD__);

        // consume 'select' if exists (ok if not)
        if ($this->peek() != EPQ_T_IS) {
            return false;
        }
        $this->next();
        
        // create the 'is' node
        if (!($node = $this->node(EPQ_N_EXPR_IS))) {
            return false;
        }

        $op = "is";

        // not?
        if ($this->peek() == EPQ_T_NOT) {
            $this->next();
            $op .= " not";
        }

        // expecting null
        if ($this->peek() != EPQ_T_NULL) {
            $this->syntax_error("'null' is expected");
        } else {
            $this->next();
            $op .= " null";
        }

        $node->setParam('op', $op);

        return $node;
    }

    /**
     * Parses 'not': not [like | between <expr1> and <expr2>] 
     * @return false|epQueryNode
     */
    protected function not() {
        $this->message(__METHOD__);

        // expect 'not'
        if ($this->peek() != EPQ_T_NOT) {
            return false;
        }
        $this->next();
        
        // create a 'not' node
        if (!($node = $this->node(EPQ_N_EXPR_NOT))) {
            return false;
        }
        
        // like
        if (($t = $this->peek()) == EPQ_T_LIKE) {
            if (!($like = $this->like())) {
                return false;
            }
            $like->addChild($node, 'not');
            return $like;
        }
        
        // between 
        if ($t == EPQ_T_BETWEEN) {
            if (!($between = $this->between())) {
                return false;
            }
            $between->addChild($node, 'not');
            return $between;
        } 

        $this->syntax_error("'between' or 'like' is expected");
        return false;
    }

    /**
     * Parses between
     * @return false|epQueryNode
     */
    protected function between() {
        $this->message(__METHOD__);
        
        // expect 'between' 
        if ($this->peek() != EPQ_T_BETWEEN) {
            return false;
        }
        $this->next();

        // expr1 
        if (!($e1 = $this->add())) {
            $this->syntax_error('Invalid expression after between');
            return false;
        }

        // expect 'and'
        if ($this->peek() != EPQ_T_AND) {
            $this->next();
            $this->syntax_error("'and' is expected after 'between'");
            return false;
        }
        $this->next();
        
        // expr2
        if (!($e2 = $this->add())) {
            $this->syntax_error('Invalid expression after between/and');
            return false;
        }

        // add expr1 and expr2 into node
        if (!($node = $this->node(EPQ_N_EXPR_BETWEEN))) {
            return false;
        }

        // add two expressions into node
        $node->addChildren(array('expr1' => $e1, 'expr2' => $e2));

        return $node;
    }

    /**
     * Parses like
     * @return false|epQueryNode
     */
    protected function like() {
        $this->message(__METHOD__);
        
        // expect 'like' 
        if ($this->peek() != EPQ_T_LIKE) {
            return false;
        }
        $this->next();

        // get the pattern
        if (!($pattern = $this->pattern())) {
            return false;
        }
        
        // make the like node
        if (!($node = $this->node(EPQ_N_EXPR_LIKE))) {
            return false;
        }

        // set pattern
        $node->addChild($pattern, "pattern");

        return $node;
    }

    /**
     * Parses the pattern for the 'like' expression
     * @return string
     */
    protected function pattern() {
        
        $pattern = '';

        // do we have the starting '%'?
        if ($this->peek() == '%') {
            $pattern = '%';
            $this->next();
        }

        // exhaust all allowed tokens
        $t = $this->peek();
        while ($t == EPQ_T_IDENTIFIER
               || $t == EPQ_T_STRING
               || $t == '?' 
               || $t == '_' 
               || $t == '['  
               || $t == '-' 
               || $t == ']' 
               || $t == '^') {
            $this->next();
            $pattern .= $this->t->value;
            $t = $this->peek();
        }
        
        // do we have the ending '%'?
        if ($this->peek() == '%') {
            $pattern .= '%';
            $this->next();
        }

        // create a pattern node
        if (!($node = $this->node(EPQ_N_PATTERN))) {
            return false;
        }

        // it could be a single '?' - a placeholder
        if ($pattern == '?') {
            if (!($pattern = $this->node(EPQ_N_PLACEHOLDER))) {
                return false;
            }
            $pattern->setParam('aindex', $this->aindex ++);
            $node->addChild($pattern, 'val');
        } else {
            $node->setParam('val', $this->unquote($pattern));
        }
        
        return $node;
    }

    /**
     * Parses contains
     * @return false|epQueryNode
     */
    protected function contains() {
        $this->message(__METHOD__);
        
        // expect 'contains'
        if ($this->peek() != EPQ_T_CONTAINS) {
            return false;
        }
        $this->next();
        
        // expect '('
        if ($this->peek() != '(') {
            $this->syntax_error("'(' is expected after 'contains'");
        } else {
            $this->next();
        }
        
        // expect identifier
        $name = '';
        if (($t = $this->peek()) != EPQ_T_IDENTIFIER && $t != '?') {
            $this->syntax_error("class name/alias or placeholder '?' is expected after 'contains('");
        } else {
            $this->next();
            $name = $this->t->value;
        }

        // expect ')'
        if ($this->peek() != ')') {
            $this->syntax_error("')' is expected after 'contains'");
        } else {
            $this->next();
        }
        
        // make a 'contains' node
        if (!($node = $this->node(EPQ_N_CONTAINS))) {
            return false;
        }
        
        // set the name param or placeholder in 'contains'
        if ($name == '?') {
            // a placeholder
            $ph = $this->node(EPQ_N_PLACEHOLDER);
            $ph->setParam('aindex', $this->aindex ++);
            $node->addChild($ph, 'arg');
        } 
        else {
            // regular string
            $node->setParam('arg', $name);
        }

        return $node;
    }

    /**
     * Parses in
     * @return false|epQueryNode
     */
    protected function in() {
        $this->message(__METHOD__);
        
        // expect 'in'
        if ($this->peek() != EPQ_T_IN) {
            return false;
        }
        $this->next();
        
        // expect '('
        if ($this->peek() != '(') {
            $this->syntax_error("'(' is expected after 'in'");
        } else {
            $this->next();
        }

        // make an 'in' item node
        // to store all the in items
        if (!($items = $this->node(EPQ_N_IN_ITEMS))) {
            return false;
        }

        // exhaust attribute parts
        $t = $this->peek();
        do {

            // consume ','
            if ($t == ',') {
                $this->next();
                $t = $this->peek();
            }
            
            // expect string, number, placeholder, and identifier
            if ($t == EPQ_T_STRING) {
                $this->next();
                $arg = $this->node(EPQ_N_STRING);
                $arg->setParam('val', $this->unquote($this->t->value));
            } else if ($t == EPQ_T_INTEGER || $t == EPQ_T_FLOAT) {
                $this->next();
                $arg = $this->node(EPQ_N_NUMBER);
                $val = $this->t->value;
                $arg->setParam('val', $val);
            /*
             * Future work
            } else if ($t == EPQ_T_IDENTIFIER) {
                $this->next();
                $arg = $this->node(EPQ_N_IDENTIFIER);
                $val = $this->t->value;
                if (strpos($val, '`') === 0) {
                    $val = substr($val, 1, strlen($val) - 2);
                }
                $arg->setParam('val', $val);
            */
            } else if ($t == '?') {
                $this->next();
                $arg = $this->node(EPQ_N_PLACEHOLDER);
                $arg->setParam('aindex', $this->aindex ++);
            } else {
                $this->syntax_error('string or number is expected');
                break;
            }

            $items->addChild($arg);
            
            // peek next
            $t = $this->peek();
            
        } while ($t == ',');

        // expect ')'
        if ($this->peek() != ')') {
            $this->syntax_error("')' is expected after 'in'");
        } else {
            $this->next();
        }
        
        // make a 'in' node
        if (!($node = $this->node(EPQ_N_IN))) {
            return false;
        }
        
        $node->addChild($items, 'items');

        return $node;
    }

    /**
     * Parses soundex
     * @return false|epQueryNode
     */
    protected function soundex() {
        $this->message(__METHOD__);
        
        // expect 'soundex'
        if ($this->peek() != EPQ_T_SOUNDEX) {
            return false;
        }
        $this->next();
        
        // expect '('
        if ($this->peek() != '(') {
            $this->syntax_error("'(' is expected after 'soundex'");
        } else {
            $this->next();
        }
        
        // expect string 
        $arg = false;
        if (($t = $this->peek()) == EPQ_T_STRING) {
            $this->next();
            $arg = $this->t->value;
        } else {
            $arg = $this->variable();
        }

        if (!$arg) {
            $this->syntax_error("string or variable expected as argument for 'soundex'");
            return false;
        }

        // expect ')'
        if ($this->peek() != ')') {
            $this->syntax_error("')' is expected after 'soundex'");
        } else {
            $this->next();
        }
        
        // make a 'soundex' node
        if (!($node = $this->node(EPQ_N_FUNC_SOUNDEX))) {
            return false;
        }
        
        // set the arg in 'soundex'
        if (is_object($arg)) {
            $node->addChild($arg, 'arg');
        } else {
            $node->setParam('arg', $arg);
        }

        return $node;
    }

    /**
     * Parses strcmp
     * @return false|epQueryNode
     */
    protected function strcmp() {
        $this->message(__METHOD__);
        
        // expect 'strcmp'
        if ($this->peek() != EPQ_T_STRCMP) {
            return false;
        }
        $this->next();
        
        // expect '('
        if ($this->peek() != '(') {
            $this->syntax_error("'(' is expected after 'strcmp'");
        } else {
            $this->next();
        }
        
        // expect string 
        if (!($arg1 = $this->primary())) {
            $this->syntax_error("string or variable expected as argument for 'strcmp'");
            return false;
        }

        if ($this->peek() != ',') {
            $this->syntax_error("two arguments expected for 'strcmp('");
        } else {
            $this->next();
        }

        // expect string 
        if (!($arg2 = $this->primary())) {
            $this->syntax_error("string or variable expected as argument for 'strcmp'");
            return false;
        }

        // expect ')'
        if ($this->peek() != ')') {
            $this->syntax_error("')' is expected after 'strcmp'");
        } else {
            $this->next();
        }
        
        // make a 'strcmp' node
        if (!($node = $this->node(EPQ_N_FUNC_STRCMP))) {
            return false;
        }

        // set the arg in 'strcmp'
        if (is_object($arg1)) {
            $node->addChild($arg1, 'arg1');
        } else {
            $node->setParam('arg1', $arg1);
        }
        if (is_object($arg2)) {
            $node->addChild($arg2, 'arg2');
        } else {
            $node->setParam('arg2', $arg2);
        }
        
        return $node;
    }

    /**
     * Parses argument for aggregate functions
     * @return false|string|epQueryNode
     */
    protected function argument() {
        
        // '*' is allowed
        if (($t = $this->peek()) == '*') {
            $this->next();
            return $this->t->value;
        }
        
        // now try regular variable
        return $this->variable();
    }

    /**
     * Parses a variable (question mark is accepted)
     * @return false|epQueryNode
     */
    protected function variable() {
        $this->message(__METHOD__);
        
        // array to hold parts of a variable
        $parts = array();

        // exhaust attribute parts
        $t = $this->peek();
        do {

            // consume '.'
            if ($t == '.') {
                $this->next();
                $t = $this->peek();
            }

            // hitting 'contains'?
            if ($t == EPQ_T_CONTAINS) {
                // done if so
                break;
            }
            
            // expect identifier or placeholder
            if ($t == EPQ_T_IDENTIFIER) {
                $this->next();
                $part = $this->node(EPQ_N_IDENTIFIER);
                $val = $this->t->value;
                if (strpos($val, '`') === 0) {
                    $val = substr($val, 1, strlen($val) - 2);
                }
                $part->setParam('val', $val);
            } else if ($t == '?') {
                $this->next();
                $part = $this->node(EPQ_N_PLACEHOLDER);
                $part->setParam('aindex', $this->aindex ++);
            } else {
                $this->syntax_error('variable or placeholder (?) is expected.');
                break;
            }
            
            // put part into parts 
            $parts[] = $part;
            
            // peek next
            $t = $this->peek();
            
        } while ($t == '.');

        // create a variable node
        if (!($node = $this->node(EPQ_N_VARIABLE))) {
            return false;
        }
        
        // set variable name
        $node->addChildren($parts);
        
        return $node;
    }

    /**
     * Parses the orderby clause
     * @return false|epQueryNode
     */
    protected function orderby() {
        $this->message(__METHOD__);
        
        // expect 'order'
        if ($this->peek() != EPQ_T_ORDER) {
            return false;
        }
        $this->next();

        // expect 'by'
        if (($t = $this->peek()) != EPQ_T_BY) {
            $this->syntax_error("'by' is expected after 'order'");
        } else {
            $this->next();
        }

        // array to hold orderby's
        $orderbys = array();

        // check if 'random()' follows
        if (($t = $this->peek()) == EPQ_T_RANDOM) {
            $this->next();

            // expect '('
            if (($this->peek()) != '(') {
                $this->syntax_error("'(' is expected after 'order by random'");
            } else {
                $this->next();
            }

            // expect ')'
            if (($this->peek()) != ')') {
                $this->syntax_error("')' is expected after 'order by random('");
            } else {
                $this->next();
            }

            // make an orderby item node
            if (!($item = $this->node(EPQ_N_ORDERBY_ITEM))) {
                continue;
            }
            $item->setParam('direction', 'random');

            // add item to array
            $orderbys[] = $item;
        }
        
        // ordinary order by's
        else {

            // parse orderby items
            do {

                // move forward if ','
                if ($t == ',') {
                    $this->next();
                }

                // parser variable
                if (!($var = $this->variable())) {
                    $this->syntax_error("Invalid variable after 'order by'");
                    continue;
                } 

                // make an orderby item node
                if (!($item = $this->node(EPQ_N_ORDERBY_ITEM))) {
                    continue;
                }
                $item->addChild($var, 'var');
                $item->setParam('direction', 'asc');

                // asc or desc
                if (($t = $this->peek()) == EPQ_T_ASC || $t == EPQ_T_DESC) {
                    $this->next();
                    if ($t == EPQ_T_DESC) {
                        $item->setParam('direction', 'desc');
                    }
                }

                // add item to array
                $orderbys[] = $item;

                $t = $this->peek();

            } while ($t == ',');

        }

        // make an orderby node
        if (!($node = $this->node(EPQ_N_ORDERBY))) {
            continue;
        }
        
        // add all orderby items
        $node->addChildren($orderbys);
        
        return $node;
    }

    /**
     * Parses the limit clause
     * @return false|epQueryNode
     */
    protected function limit() {
        $this->message(__METHOD__);

        // expect 'limit'
        if ($this->peek() != EPQ_T_LIMIT) {
            return false;
        }
        $this->next();

        // expect integer
        if (($t = $this->peek()) != EPQ_T_INTEGER && $t != '?') {
            $this->syntax_error("integer is expected after 'limit'");
            return false;
        }
        $start = $this->primary();

        // create a 'limit' node
        if (!($node = $this->node(EPQ_N_LIMIT))) {
            return false;
        }

        // ',' follows?
        $length = false;
        if ($this->peek() == ',') {
            $this->next();
            if (($t = $this->peek()) != EPQ_T_INTEGER && $t != '?') {
                $this->syntax_error("integer is expected after 'limit ,'");
            } else {
                $length = $this->primary();
            }
        }

        // set params to node
        $node->addChild($start, 'start');
        if ($length) {
            $node->addChild($length, 'length');
        }
        
        return $node;
    }

    /**
     * Checks if a token is an aggregate function
     * @param string $t token type
     * @return boolean
     */
    protected function _isAggregateFunc($t) {
        return $t == EPQ_T_AVG 
            || $t == EPQ_T_COUNT
            || $t == EPQ_T_MAX
            || $t == EPQ_T_MIN
            || $t == EPQ_T_SUM;
    }

    /**
     * Checks if token is an comparision operator
     * @param string $t token type
     * @return boolean
     */
    protected function _isComparisonOp($t) {
        return $t == '='
            || $t == '>'
            || $t == '<'
            || $t == EPQ_T_EQUAL
            || $t == EPQ_T_NEQUAL
            || $t == EPQ_T_LE
            || $t == EPQ_T_GE;
    }

    /**
     * Create a new node and set it as the current node
     * @return epQueryNode
     */
    protected function node($type) {
        
        // create a node with type
        $this->n = new epQueryNode($type);
        
        // set line and char
        if (isset($this->t) && isset($this->t->line)) {
            $this->n->setParam('line', $this->t->line);
        }
        if (isset($this->t) && isset($this->t->char)) {
            $this->n->setParam('char', $this->t->char);
        }

        // return this node
        return $this->n; 
    }

    /**
     * Unquote a string
     * @param string $s
     * @return string
     */
    protected function unquote($s) {
        $start = 0;
        if ($s[$start] == '"' || $s[$start] == "'") {
            $start ++;
        }
        $end = strlen($s) - 1;
        if ($s[$end] == '"' || $s[$end] == "'") {
            $end --;
        }
        return substr($s, $start, $end-$start+1);
    }

}

?>
