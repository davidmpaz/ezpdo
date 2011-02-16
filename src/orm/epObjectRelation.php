<?php

/**
 * $Id: epObjectRelation.php 936 2006-05-12 19:14:09Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 936 $ $Date: 2006-05-12 15:14:09 -0400 (Fri, 12 May 2006) $
 * @package ezpdo
 * @subpackage ezpdo.orm
 */

/**
 * Need {@link epValidateable} interface (in epBase.php) 
 */
if (interface_exists('epValidateable')) {
    include_once(EP_SRC_BASE.'/epBase.php');
}

/**
 * Class of object relationship  
 * 
 * The class makes association between two objects: 
 * (class_a, oid_a) and (class_b, oid_b)
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 936 $ $Date: 2006-05-12 15:14:09 -0400 (Fri, 12 May 2006) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epObjectRelation implements epValidateable {
    
    /**
     * The name of class a 
     * @var string
     * @orm char(64) index(class_oid_a)
     */
    public $class_a;
    
    /**
     * The oid of object a
     * @var integer
     * @orm integer(11) index(class_oid_a)
     */
    public $oid_a;

    /**
     * The relational field (var) of object a
     * @var string
     * @orm char(64)
     */
    public $var_a;
    
    /**
     * The name of the base class of b (specified in the orm tag)
     * @var string
     * @orm char(64)
     */
    public $base_b;
    
    /**
     * The name of class b
     * @var string
     * @orm char(64)
     */
    public $class_b;
    
    /**
     * The oid of object a
     * @var integer
     * @orm integer(11)
     */
    public $oid_b;
    
    /**
     * Constructor
     * @param string $class_a
     * @param integer $oid_a
     * @param string $var_a
     * @param string $class_b
     * @param integer $oid_b
     */
    public function __construct($class_a = null, $oid_a = null, $var_a = null, $base_b = null, $class_b = null, $oid_b = null) {
        $this->class_a = $class_a;
        $this->oid_a = $oid_a;
        $this->var_a = $var_a;
        $this->base_b = $base_b;
        $this->class_b = $class_b;
        $this->oid_b = $oid_b;
    }
    
    /**
     * Check if the object is valid
     * 
     * Implements the {@link epValidateable} interface
     * @param bool $recursive (ignored)
     * @return true|string (error msg)
     */
    public function isValid($recursive) {
        
        // array to hold errors
        $errors = array();
        
        // get the manager
        if (!($m = epManager::instance())) {
            $errors[] = 'cannot get manager';
            return $errors;
        }
        
        // 
        // check object a
        // 
        
        if (!$this->class_a) {
            $errors[] = 'class_a is empty';
        }
    
        if (!$this->oid_a) {
            $errors[] = 'oid_a is zero (invalid oid) or empty';
        }
        
        if (!$this->var_a) {
            $errors[] = 'var_a is empty';
        }
        
        // check if object a
        if ($this->class_a) {
            
            // check if class a exists
            if (!($cm = $m->getClassMap($this->class_a))) {
                $errors[] = 'class_a [' . $this->class_a . '] no longer exists';
            }
            
            // check if var_a and object_a exists
            else {
                
                // check if var_a exists 
                if ($this->var_a) {
                    if (!$cm->getField($this->var_a)) {
                        $errors[] = 'var_a [' . $this->var_a . '] no longer exists in class_a [' . $this->class_a . ']';
                    }
                }
                
                // check if object_a exists
                if (!($obj_a = $m->get($this->class_a, $this->oid_a))) {
                    $errors[] = 'object of class_a [' . $this->class_a . '] with oid [' . $this->oid_a . '] no longer exists';
                }
            }
        }
        
        // 
        // check object b
        // 
        if (!$this->base_b) {
            $errors[] = 'base_b is empty';
        }
        else {
            // check if base_b exists
            if (!($cm = $m->getClassMap($this->base_b))) {
                $errors[] = 'base_b [' . $this->base_b . '] no longer exists';
            }
        }
        
        if (!$this->class_b) {
            $errors[] = 'class_b is empty';
        }
        
        if (!$this->oid_b) {
            $errors[] = 'oid_b is zero (invalid oid) or empty';
        }
        
        // check if object b
        if ($this->class_b) {
            
            // check if class b exists
            if (!($cm = $m->getClassMap($this->class_b))) {
                $errors[] = 'class_b [' . $this->class_b . '] no longer exists';
            }
            // check if object_b exists
            else {
                // check if object_b exists
                if (!($obj_b = $m->get($this->class_b, $this->oid_b))) {
                    $errors[] = 'object of class_b [' . $this->class_b . '] with oid [' . $this->oid_b . '] no longer exists';
                }
            }
        }
        
        // either return array of errors or true
        return $errors ? $errors : true;
    }
    
}

?>
