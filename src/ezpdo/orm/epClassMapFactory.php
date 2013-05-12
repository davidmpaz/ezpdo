<?php

/**
 * $Id: epClassMapFactory.php 998 2006-06-05 12:57:26Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Moises Paz <davidmpaz@gmail.com>
 * @version $Revision: 998 $ $Date: 2006-06-05 08:57:26 -0400 (Mon, 05 Jun 2006) $
 * @package ezpdo
 * @subpackage ezpdo.orm
 */
namespace ezpdo\orm;

use ezpdo\base\epFactory;
use ezpdo\base\epSingleton;
use ezpdo\base\epValidateable;
use ezpdo\orm\exception\epExceptionClassMapFactory;

/**
 * The factory class of ezpdo class mapping info.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 998 $ $Date: 2006-06-05 08:57:26 -0400 (Mon, 05 Jun 2006) $
 * @package ezpdo
 * @subpackage ezpdo.orm
 */
class epClassMapFactory implements epFactory, epSingleton, epValidateable {

    /**#@+
     * Used for return value to avoid reference notice in 5.0.x and up
     * @var bool
     */
    static public $false = false;
    static public $true = true;
    static public $null = null;
    /**#@-*/

    /**
     * class_maps created
     * @var array
     */
    private $class_maps = array();

    /**
     * Constructor
     */
    private function __construct() {
    }

    /**
     * Implements magic function __toString() for debugging
     * @return string
     */
    public function __toString(){
        $s = '';
        foreach ($this->allMade() as $cm) {
            $s .= $cm . "\n";
        }
        return $s;
    }

    /**
     * Implements factory method {@link epFactory::make()}
     * @param string $class_name
     * @return epClassMap|null
     * @access public
     * @static
     */
    public function &make($class_name) {
        return $this->get($class_name, false); // false: no tracking
    }

    /**
     * Implement factory method {@link epFactory::track()}
     * @param string $class_name
     * @return epClassMap
     * @access public
     */
    public function &track() {
        $args = func_get_args();
        return $this->get($args[0], true); // true: tracking
    }

    /**
     * Either create a class map (if not tracking) or retrieve it from cache
     * @param $class_name
     * @param bool tracking or not
     * @return epClassMap
     * @throws epExceptionClassMapFactory
     */
    private function & get($class_name, $tracking = false) {

        // check class name
        if (empty($class_name)) {
            throw new epExceptionClassMapFactory('Class name is empty');
            return self::$null;
        }

        // check if class map has been created
        if (isset($this->class_maps[$class_name])) {
            return $this->class_maps[$class_name];
        }

        // check if it's in tracking mode
        if ($tracking) {
            return self::$null;
        }

        // otherwise create
        $this->class_maps[$class_name] = new epClassMap($class_name);

        return $this->class_maps[$class_name];
    }

    /**
     * Implement factory method {@link epFactory::allMade()}
     * Return all class_maps made by factory
     * @return array
     * @access public
     */
    public function allMade() {
        return array_values($this->class_maps);
    }

    /**
     * Implement factory method {@link epFactory::removeAll()}
     * Remove all class_maps made
     * @return void
     */
    public function removeAll() {
         $this->class_maps = array();
    }

    /**
     * Remove a class map from factory. Called by {@link epDbUpdate::_updateSchema}
     * when a class was renamed.
     * @param string $class
     * @return void
     */
    public function remove($class) {
        if (isset($this->class_maps[$class])) {
            unset($this->class_maps[$class]);
        }
    }

    /**
     * Sort class maps by key (only for testing)
     * @return void
     * @access public
     */
    public function sort() {
        ksort($this->class_maps);
    }

    /**
     * Check if the class maps are valid
     *
     * Implements the {@link epValidateable} interface
     *
     * @param bool $recursive (unused)
     * @return true|string (error msg)
     */
    public function isValid($recursive) {

        // error messages
        $errors = array();

        // check if the classes of the relational fields exists
        // fix bug #54 (http://www.ezpdo.net/bugs/index.php?do=details&id=54)
        if (true !== ($errors_ = $this->_validateRelationshipFields())) {
            $errors = array_merge($errors, $errors_);
        }

        // either return array of errors or true
        return $errors ? $errors : true;
    }

    /**
     * Validate that relation fields have their related classes compiled.
     * @return true|array of strings (error msgs)
     */
    protected function _validateRelationshipFields() {

        // array to keep errors
        $errors = array();

        // loop through the class maps
        foreach($this->class_maps as $class => $cm) {

            // get all non-primitive fields
            // (false: non-recursive to avoid double checking)
            if (!($npfs = $cm->getNonPrimitive(false))) {
                continue;
            }

            // loop through relational field maps
            foreach($npfs as $fm) {
                // check the inverse of the field
                $errors = array_merge($errors, $this->_validateRelationshipField($fm, $class));
            }
        }

        // either return array of errors or true
        return $errors ? $errors : true;
    }

    /**
     * Validate related class and inverse on a field map
     * @param epFieldMap $fm the field map to be checked
     * @param string $class the name of the class that the field belongs to
     * @return array (errors)
     */
    protected function _validateRelationshipField(&$fm, $class) {

        // array to hold error messages
        $errors = array();

        //
        // 1. check the opposite class for the field
        //

        // string for class and field
        $class_field = '[' . $class . '::' . $fm->getName() . ']';

        // does the relation field have the related class defined?
        if (!($rclass = $fm->getClass())) {
            // shouldn't happend
            $errors[] = $class_field . ' does not have opposite class specified';
            return $errors;
        }

        // does the related class exist?
        if (!isset($this->class_maps[$rclass])) {
            // alert if not
            $errors[] = 'Class [' . $rclass . '] for ' . $class_field . ' does not exist';
            return $errors;
        }

        //
        // 2. check inverse of the field
        //

        // does this field have an inverse?
        if (!($inverse = $fm->getInverse())) {
            return $errors;
        }

        // get the related class map
        $rcm = $this->class_maps[$rclass];

        // get all fields point to the current class in the related class
        $rfields = $rcm->getFieldsOfClass($class);

        // 2.a. default inverse (that is, set to true)
        if (true === $inverse) {

            // the related class must have only one relationship var to the current class
            if (!$rfields) {
                $errors[] = 'No inverse found for ' . $class_field;
            }

            // more than one fields pointing to the current class
            else if (count($rfields) > 1) {
                $errors[] = 'Ambiguilty in the inverse of ' . $class_field;
            }

            // set up the inverses
            else {
                $rfms = array_values($rfields);
                $fm->setInverse($rfms[0]->getName());
                $rfms[0]->setInverse($fm->getName());
            }

            return $errors;
        }

        // 2.b. inverse is specified

        // check if inverse exists
        if (!isset($rfields[$fm->getClass().':'.$inverse]) || !$rfields[$fm->getClass().':'.$inverse]) {
            $errors[] = 'Inverse of ' . $class_field . ' (' . $fm->getClass() . '::' . $inverse . ') does not exist';
            return $errors;
        }

        // get the field map for the inverse
        $rfm = $rfields[$fm->getClass().':'.$inverse];

        // set up the inverse on the other side -only if- inverse on the other side
        // is -not- already set or set to default
        if (!($rinverse = $rfm->getInverse()) || $rinverse === true) {
            $rfm->setInverse($fm->getName());
            return $errors;
        }

        // if specified, check duality
        if ($class != $rfm->getClass() || $rinverse != $fm->getName()) {
            $errors[] = 'Inverse of [' . $rcm->getName() . '::' . $fm->getName() . '] is not specified as ' . $class_field;
        }

        return $errors;
    }

    /**
     * Switches DSN at runtime.
     *
     * Working assumption the class hierarchy of the input classes
     * of which you want to change DSN should be in one database
     * (i.e. one DSN) as well as their relationship tables.
     *
     * If no class name is specified, all compiled classes will
     * change their DSN to the new one.
     *
     * @param string $dsn The targeted DSN
     * @param array $classes The classes to change to the target DSNs
     * @return true
     */
    public function setDsn($dsn, $classes = false) {

        // make sure non-empty dsn
        if (!$dsn) {
            return false;
        }

        // if no class is specified
        if (!$classes) {
            foreach($this->class_maps as $class => &$cm) {
                $cm->setDsn($dsn);
            }
            return true;
        }

        // array to keep track of classes included
        $classes_done = array();

        // go through each class
        foreach($classes as $class) {

            // true: tracking only, no creation
            if (!($cm = & $this->get($class, true))) {
                continue;
            }

            // add this class if not already in array
            if (!in_array($cm->getName(), $classes_done)) {
                $cm->setDsn($dsn);
            }

            // get all its children
            if (!($children = $cm->getChildren())) {
                continue;
            }

            // include all its children
            foreach($children as &$child) {

                // add this class if not already in array
                if (!in_array($child->getName(), $classes_done)) {
                    $child->setDsn($dsn);
                }
            }
        }

        return true;
    }

    /**
     * Returns all relation fields that involves the given class
     * @param string $class
     * @return array
     */
    public function getRelationFields($class) {

        $fields = array();

        // get the class map and loop thru all ancestors
        $cm = $this->get($class);
        while ($cm) {

            // loop through the class maps
            foreach($this->class_maps as $class => $cm_) {
                if ($fields_ = $cm_->getFieldsOfClass($cm->getName())) {
                    $fields = array_merge($fields, $fields_);
                }
            }

            // get all non primitive fields of this class
            // not recursive as we already are doing recursion
            if ($fields_ = $cm->getNonPrimitive(false)) {
                $fields = array_merge($fields, $fields_);
            }

            // get parent of the current class
            $cm = $cm->getParent();
        }

        return $fields;
    }

    /**
     * Serialize the singleton factory
     * @param bool $sort whether to sort class maps by name before serialization
     * @return false|string
     */
    static public function serialize($sort = true) {

        // get the class map factory
        $cmf = & epClassMapFactory::instance();

        // need to sort the class maps
        if ($sort) {
            $cmf->sort();
        }

        // serialize the factory
        return serialize($cmf);
    }

    /**
     * Unserialize the singleton factory
     * Make instance() consistent
     * @param string serialized data
     * @return null|epClassMapFactory
     */
    static public function &unserialize($scmf) {

        // sanity check
        if (!$scmf) {
            return self::$null;
        }

        // serialize
        if (!($cmf = unserialize($scmf))) {
            return self::$null;
        }

        self::$instance = & $cmf;

        return self::$instance;
    }

    /**
     * Implements {@link epSingleton} interface
     * @return epClassMapFactory
     * @access public
     */
    static public function &instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Implement {@link epSingleton} interface
     * Forcefully destroy old instance (only used for tests).
     * After reset(), {@link instance()} returns a new instance.
     */
    static public function destroy() {
        self::$instance = null;
    }

    /**
     * epClassMapFactory instance
     */
    static private $instance;
}
