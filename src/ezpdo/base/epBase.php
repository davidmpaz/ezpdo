<?php

/**
 * $Id: epBase.php 606 2005-11-09 12:47:40Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base
 */
namespace ezpdo\base;

use ezpdo\base\exception\epException;

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
        $this->id = uniqid(); // generate an unique id
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
