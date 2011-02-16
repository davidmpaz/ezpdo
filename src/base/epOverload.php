<?php

/**
 * $Id: epOverload.php 578 2005-10-19 00:36:17Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 578 $ $Date: 2005-10-18 20:36:17 -0400 (Tue, 18 Oct 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */

/**
 * need epBase and epUtils
 */
include_once(EP_SRC_BASE.'/epBase.php');

/**
 * Exception class for {@link epOverload}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 578 $ $Date: 2005-10-18 20:36:17 -0400 (Tue, 18 Oct 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */
class epExceptionOverload extends epException {
}

/**
 * Base class for overloading foreign classes
 * 
 * This class provides a wrapper to "naturalize" a foreign class
 * so that all its public methods can be called directly. 
 * 
 * Sometimes you might want to have the same function defined in the 
 * wrapper as in the foreign class, but you still want to call that 
 * function in the foreign class 'directly'. That is -not- to use 
 * the lengthy $this->getForeignObject()->foo(). But using $this->foo() 
 * in this case ends up calling the method defined in the wrapper. 
 * 
 * Solution: You can set the foreign method prefix to avoid ambiguity. 
 * For example, the foreign class "F" has a method foo(), 
 * <pre>
 * class F {
 *   // ...
 *   function foo() {
 *     // ...
 *   }
 *   // ...
 * }
 * </pre>
 * and you have the same method defined in the wrapper class and 
 * it needs to call the F::foo(), you can do this.
 * <pre>
 * class W extends epOverload {
 * 
 *   function __construct() {
 *     parent::__construct(func_get_args());
 *     // ...
 *     $this->setForeignMethodPrefix('f_');
 *     // ...
 *   }
 * 
 *   function foo() {
 *     // using prefix "f_" to call F::foo()
 *     $this->f_foo();
 *     // ...
 *   }
 *   // ...
 * }
 * </pre>
 * This way W::foo() won't end up calling itself (which may lead to an 
 * endless loop). 
 * 
 * Note that as of PHP 5.0.2, methods are *case-insensitive* when they 
 * are called, although functions, such as get_class_methods(), return 
 * the case-sensitive names (See {@link http://docs.php.net/en/migration5.html}). 
 * This means calling $this->Foo() has the same effect as calling $this->foo()!
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 578 $ $Date: 2005-10-18 20:36:17 -0400 (Tue, 18 Oct 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */
class epOverload {

    /**
     * The instance of the foreign class
     * @var object
     */
    protected $foreign_object;

    /**
     * The prefix for calling foreign methods
     * @var string
     */
    protected $foreign_method_prefix = '';
    
    /**
     * whether to convert epOverload arguments to original object (see {@link _convertArgs()})
     * @var boolean
     */
    protected $convert_args = true;
    
    /**
     * Constructor
     * @param string $class_name the foreign class name
     * @param  mixed ... parameters the foreign class's constructor
     * @throws epExceptionOverload
     */
    function __construct() {

        $args = func_get_args();
        
        // must have at least one argument (the foreign class name)
        if ( count($args) == 0 ) {
            throw new epExceptionOverload("no argument supplied");
            return;
        }

        // if args is a single array
        if ( count($args) == 1 ) {
            if ( is_array($args[0]) ) {
                $args = $args[0];
            }
        }

        // pop off the foreign class name (the first argument)
        $foreign_class = array_shift($args);

        // create the foreign object
        $this->newForeignObject($foreign_class, $args);
        if ( !$this->getForeignObject() ) {
            throw new epExceptionOverload('cannot instantiate ' . $$foreign_class);
        }

    }
    
    /**
     * Returns whether to convert epOverload arguments to original object
     * @return boolean
     */
    public function getConvertArguments() {
        return $this->convert_args;
    }
    
    /**
     * Set whether to convert epOverload arguments to original object
     * @param boolean $convert_args
     * @return void
     */
    public function setConvertArguments($convert_args = true) {
        $this->convert_args = $convert_args;
    }
    
    /**
     * Set the prefix for calling methods defined in foreign class
     * @param string $prefix
     * @return string
     */
    public function getForeignMethodPrefix() {
        return $this->foreign_method_prefix;
    }

    /**
     * Set the prefix for calling methods defined in foreign class
     * @param string $prefix
     * @return void
     */
    public function setForeignMethodPrefix($prefix) {
        $this->foreign_method_prefix = $prefix;
    }

   /**
    * Implement the magic method __call() so that we can call methods 
    * in the foreign class directly
    * 
    * @param string $method the method name 
    * @param array $args the array of parameters 
    * @return mixed
    */
    public function __call($method, $args) {
        
        // check if the foreign obj has been created
        if ( !$this->foreign_object ) {
            throw new epExceptionOverload("foreign object is null (method $method cannot be called)");
            return self::$false;
        }
        
        // rip off prefix if defined
        if (!empty($this->foreign_method_prefix)) {
            $method = preg_replace('/^'.$this->foreign_method_prefix.'/', '', $method);
        }
        
        // check if the foreign class has the method
        if ( !method_exists($this->foreign_object, $method) ) {
            throw new epExceptionOverload("method $method does not exist in class " . get_class($this->foreign_object));
            return self::$false;
        }

        // If args contains epOverload, we need to translate
        // it to the foreign object.
        if ($this->convert_args) {
            $args = $this->_convertArgs($args);
        }

        // actually call the method in the foreign class
        return call_user_func_array(array($this->foreign_object, $method), $args);
    }

    /**
     * Return the foreign object
     * @return type of the foreign class
     */
    public function &getForeignObject() {
        return $this->foreign_object;
    }

    /**
     * Return the foreign object
     * @return bool
     */
    public function setForeignObject($foreign_object) {
        $this->foreign_object = & $foreign_object;
    }

    /**
     * Create the foreign object with a given array of arguments
     * @param string $foreign_class name of the foreign class
     * @param arrary $args arguments for object creatatoin
     * @return bool
     * @access private
     */
    protected function &newForeignObject($foreign_class, $args) {
        $this->foreign_object = & epNewObject($foreign_class, $args); 
        return !is_null($this->foreign_object);    
    }

    /**
     * If args contains epOverload, we need to convert it to the foreign object
     * in order not to confuse the foreign object
     * @return void
     * @access private
     */
    protected function &_convertArgs($args) {

        reset($args);
        
        while ( list($index, $arg) = each($args) ) {
            
            // skip null arg
            if ( is_null($arg) ) {
                continue;
            }
            
            // check if arg is epOverload
            if ( $arg instanceof epOverload ) {
                $args[$index] = $arg->getForeignObject();
            }
            
            // if array, convert recursively
            if ( is_array($arg) ) {
                $args[$index] = & $this->_convertArgs($arg);
            }
        }
        
        return $args;
    }
    
} // end of class epOverload

?>
