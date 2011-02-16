<?php

/**
 * $Id: epContainer.php 970 2006-05-19 12:46:10Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 970 $ $Date: 2006-05-19 08:46:10 -0400 (Fri, 19 May 2006) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */

/**
 * need epBase
 */
include_once(EP_SRC_BASE.'/epBase.php');

/**
 * Class of epContainer
 * 
 * A container is a composite object. It can have 
 * its parent (if not root) and children. The class
 * is useful in constructing tree/graph structures.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 970 $ $Date: 2006-05-19 08:46:10 -0400 (Fri, 19 May 2006) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */
class epContainer extends epBase {

    /**
     * The parent object
     * @var epContainer
     */
    protected $parent = null;

    /**
     * The key (name) used for the children array 
     * @var string
     */
    protected $children_key;

    /**
     * Contained objects (children)
     * An associative arry using children_key
     * @var array 
     */
    protected $children = array();

    /**
     * Constructor
     */
    public function __construct($name = null, $children_key = 'Name') {
        parent::__construct($name);
        $this->setChildrenKey($children_key);
    }

    /**
     * Get the parent of this item
     * @return null|epContainer
     */
    public function &getParent() {
        return $this->parent;
    }

    /**
     * Get the parent of this item
     * @param epContainer &$parent the parent object
     * @return void
     */
    public function setParent(epContainer &$parent) {
        $this->parent =& $parent;
    }

    /**
     * Get the name of the children key
     * @return string
     */
    public function getChildrenKey() {
        return $this->children_key;
    }

    /**
     * Set the name for the children key
     * @param string
     * @return true if successful
     */
    public function setChildrenKey($children_key) {

        if ( empty($children_key) ) {
            return false;
        }

        $this->children_key = $children_key;
        return true;
    }

    /**
     * Unset children key
     * @return void
     */
    public function unsetChildrenKey() {
        $this->children_key = null;
    }

    /**
     * Get the children contained 
     * @param bool get all children if recusive
     * @param bool sort by weight or by the order inserted
     * @return array (ref)
     */
    public function &getChildren($recursive = false, $sort = false) {

        $children = array();
        if ( !empty($this->children) ) {
            $children = array_merge($children, array_values($this->children));
        }

        if ( $recursive ) {
            if ( !empty($this->children) ) {
                foreach($this->children as $child) {
                    $children = array_merge($children, $child->getChildren($recursive));
                }
            }
        }

        if ( $sort ) {
            usort($children, array($this, "sortChildren"));
        }

        return $children;
    }

    /**
     * Get sub child by id
     * @name string id of the child
     * @return epBase 
     */
    public function &getChild($id, $recursive = false) {

        if ( empty($this->children) ) {
            return self::$null;
        }

        // check if container has child by name
        if ( isset($this->children[$id]) ) {
            return $this->children[$id];
        }

        // done if not recursive
        if ( !$recursive ) {
            return self::$null;
        }

        // find child recursively
        foreach($this->children as $child) {
            return $child->getChild($id, $recursive);
        }

        return self::$null;
    }

    /**
     * Add a child
     * @param epBase a reference to the subchild to be added
     * @param bool whether to replace the existing child  with the same name
     * @return bool true if successful
     */
    public function addChild(epBase &$child, $replace = true) {
        
        // get the key from object
        eval('$id = $child->get' . $this->getChildrenKey() . '();');
        if ( empty($id) ) {
            return false;
        }

        // set this as the child's parent (if child is a container)
        if ( $child instanceof epContainer ) {
            $child->setParent($this);
        }

        // if replace or child not set
        if ( $replace || !isset($this->children[$id]) ) {
            $this->children[$id] = & $child;
            return true;
        }

        // adding child failed
        return false;
    }

    /**
     * Remove sub child by name
     * @return true if found and deleted; false otherwise
     */
    public function removeChild($name) {

        if ( empty($this->children) ) {
            return false;
        }

        if ( isset($this->children[$name]) ) {
            unset($this->children[$name]);
        }

        return true;
    }

    /**
     * Remove all sub children
     * @return void
     */
    public function removeAllChildren() {
        $this->children = array();
    }
    
    /**
     * Check if a given object is an ancestor 
     * @param object
     * @return bool
     * @todo To be implemented
     */
    public function isAncestor($o) {
    }
    
    /**
     * Sort an array of show columns (by defualt sort by weight)
     * @return integer -1 (a is lighter), 0 (tie), or +1 (b is lighter)
     * @access protected
     */
    protected function sortChildren($a, $b) {

        // get the children key
        $key = $this->getChildrenKey();
        
        // check if key is empty
        if ( empty($key) ) {
            // should not happen
            return 0; // treated as tie
        }
        
        eval('$id_a = $a->get' . $key . '();');
        eval('$id_b = $b->get' . $key . '();');

        if ( $id_a < $id_b ) {
            return -1;
        }

        if ( $id_a > $id_b ) {
            return +1;
        }

        return 0; // tie
    }

} // end of class epContainer

?>
