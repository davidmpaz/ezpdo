<?php

/**
 * $Id: epQueryNode.php 1048 2007-04-13 02:31:17Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Moises Paz <davidmpaz@gmail.com>
 * @version $Revision: 1048 $
 * @package ezpdo
 * @subpackage ezpdo.query
 */
namespace ezpdo\query;

use ezpdo\runtime\epObject;

use ezpdo\base\epUtils;
use ezpdo\base\epBase as epBase;

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
    //public function __toString($indent = '') {
    public function __invoke($indent = '') {
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
                        $s .= $child_->__invoke($indent);
                    }
                } else {
                    $s .= $child->__invoke($indent);
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
