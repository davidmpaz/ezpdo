<?php

/**
 * $Id: epBase.php 606 2005-11-09 12:47:40Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */

/**
 * The nameable interface 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */
interface epNameable {
    
    /**
     * Get name
     * @return string 
     */
    public function getName();
    
    /**
     * Set name
     * @param string 
     */
    public function setName($name);

} // end of interface epNameable

/**
 * The identifiable interface  
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */
interface epIdentifiable {
    
    /**
     * Get unique id of the object
     * @return mixed (integer or string)
     */
    public function getId();

} // end of interface epWeighable

/**
 * The validateable interface 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */
interface epValidateable {
    
    /**
     * Check if object is in a valid state
     * @param bool whether to validate recursively (in case object contains other objects)
     * @param string error message if invalid
     * @return true|array of strings (error msgs)
     */
    public function isValid($recursive);

} // end of interface epValidateable

/**
 * Interface of singleton
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */
interface epSingleton  {

    /**
     * Forcefully delete old instance (only used for tests). 
     * After reset(), {@link instance()} returns a new instance.
     */
    static public function destroy();

    /**
     * Return the single instance of the class
     * @return object
     */
    static public function &instance();
}

/**
 * Interface of factory
 * 
 * Admittedly this is not exactly the Factory Method pattern outlined 
 * in the GOF book. It is rather a mix of Factory Method and Registry
 * patterns since it keeps track of (references to) objects it has 
 * manufactured. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */
interface epFactory { 
    
    /**
     * The major task of a factory is to make products (objects)
     * @param mixed ... object creation parameters
     * @return object
     */
    public function &make($class_name);

    /**
     * A factory can also track down a product it has produced by a 
     * certain criteria
     * @param  mixed ... search criteria 
     * @return object
     */
    public function &track(); 

    /**
     * Get all products (references) made by factory so far
     * @return array
     */
    public function allMade(); 
    
    /**
     * Remove all product references made by the factory 
     * @return void
     */
    public function removeAll(); 
}

/**
 * Base class of ezpdo exception
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */
class epException extends Exception {
    
    /**
     * Constructor
     * @param string $msg
     * @param integer code
     */
    public function __construct($msg, $code = 0) {
        parent::__construct($msg, $code);
    }
}

/**
 * The base class of ezpdo
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */
class epBase implements epNameable, epIdentifiable, epValidateable {

    /**#@+
     * Used for return value to avoid reference notice in 5.0.x and up
     * @var bool
     */
    static public $false = false;
    static public $true = true;
    static public $null = null;
    /**#@-*/

    /**
     * unique id 
     * @var mixed (integer or string)
     */
    protected $id;

    /**
     * object name
     * @var string 
     */
    protected $name;

    /**
     * Constructor
     * 
     * @param string name of the object
     * @param integer object id
     * @access public
     */
    function __construct($name = null) {
        $this->setName($name);
        $id = uniqid(); // generate an unique id
    }

    /**
     * Get object name
     * 
     * Implements interface {@link epNameable::getName()}
     * 
     * @return string
     * @access public
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set object name
     * 
     * Implements interface {@link epNameable::setName()}
     * 
     * @param string 
     * @access public
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Get object id
     * 
     * Implements interface {@link epIdentifiable::getId()}
     * 
     * @return string
     * @access public
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Implements interface {@link epValidateable::isValid()}
     * 
     * Check if object is in a valid state. Subclasses should override this method. 
     * 
     * @param bool whether to validate recursively (in case object contains other objects)
     * @return false|string (error msg)
     * @access public
     */
    public function isValid($recursive) {
        return true;
    }
    
    /**
     * Convert object to readable string
     * 
     * Implements the magic function __toString {@link http://us2.php.net/manual/en/language.oop5.magic.php}
     * Functions echo() and print() automatically calls this function.
     * Subclasses should override this method
     * 
     * @return string
     * @access public
     */
    public function __toString() {
        return 'Object (class: '.get_class($this).', name: '.$this->getName().', id: '.$this->getId().')'; 
    }
    
} // end of class epBase

?>
