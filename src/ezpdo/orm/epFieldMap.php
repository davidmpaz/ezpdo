<?php

/**
 * $Id: epFieldMap.php 998 2006-06-05 12:57:26Z nauhygon $
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

use ezpdo\base\epBase;
use ezpdo\base\exception\epException;

/**
 * The base class of field mapping info (or "field map" in short)
 *
 * The class keeps ORM info of a variable in a class, i.e. how a variable in a
 * a class is mapped to a database column.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 998 $ $Date: 2006-06-05 08:57:26 -0400 (Mon, 05 Jun 2006) $
 * @package ezpdo
 * @subpackage ezpdo.orm
 */
class epFieldMap extends epBase {

    /**#@+
     * Primitive type constants that can be mapped to db column directly
     */
    const DT_BOOL     = 'bool';
    const DT_BOOLEAN  = 'boolean'; // same as bool
    const DT_BIT      = 'bit'; // same as bool
    const DT_INT      = 'int';
    const DT_INTEGER  = 'integer'; // same as int
    const DT_DECIMAL  = 'decimal';
    const DT_FLOAT    = 'float';
    const DT_REAL     = 'real'; // same as float
    const DT_CHAR     = 'char';
    const DT_CLOB     = 'clob';
    const DT_TEXT     = 'text';
    const DT_BLOB     = 'blob';
    const DT_DATE     = 'date';
    const DT_TIME     = 'time';
    const DT_DATETIME = 'datetime';
    /**#@-*/

    /**#@+
     * Relationship type constants
     */
    const DT_HAS         = 'has';
    const DT_COMPOSED_OF = 'composed_of';
    /**#@-*/

    /**
     * The class map this field map belongs to (for trackback)
     * @var epClassMap
     * @access protected
     */
    protected $class_map;

    /**
     * The base relationship field
     * @var epFieldMap
     */
    protected $base = null;

    /**
     * The database column type for the field to be mapped
     * @var string
     * @access protected
     */
    protected $type;

    /**
     * The database column for the field to be mapped
     * @var string
     * @access protected
     */
    protected $column_name;

    /**
     * Default value for the field
     * @var mixed
     * @access protected
     */
    protected $default_value;

    /**
     * Custom class tags
     * @var array
     * @access protected
     */
    protected $custom_tags = array();

    /**
     * Constructor
     * @param string name of the corresponding class
     * @param string $type type consts
     * @param false|epClassMap $class_map
     */
    public function __construct($name, $type = self::DT_CHAR, $class_map = false) {

        parent::__construct($name);

        $this->setType($type);

        if ($class_map) {
            $this->setClassMap($class_map);
        }
    }

    /**
     * Gets value of class_map
     * @return epClassMap
     * @access public
     */
    public function & getClassMap() {
        return $this->class_map;
    }

    /**
     * Sets value to class_map
     * @param epClassMap
     * @return void
     * @access public
     */
    public function setClassMap($class_map) {
        $this->class_map = $class_map;
    }

    /**
     * Returns the base (root) field map
     * @return null|epFieldMap
     */
    protected function &getBase() {

        // return cached one
        if ($this->base) {
            return $this->base;
        }

        // class map exists?
        if (!$this->class_map) {
            return self::$false;
        }

        // get the base
        if (!($this->base = $this->class_map->getBaseField($this->name))) {
            $this->base = $this;
        }

        return $this->base;
    }

    /**
     * Gets value of type
     * @return string
     * @access public
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Sets value to type
     * @param string
     * @return void
     * @access public
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * Gets value of column name
     * @return string
     * @access public
     */
    public function getColumnName() {
        // if no db column name assigned, used the var name
        if (!$this->column_name) {
            $this->setColumnName($this->getName());
        }
        return $this->column_name;
    }

    /**
     * Sets value to column_name
     * @param string
     * @return void
     * @access public
     */
    public function setColumnName($column_name) {
        $this->column_name = $column_name;
    }

    /**
     * Gets value of default_value
     * @return mixed
     * @access public
     */
    public function getDefaultValue() {
        return $this->default_value;
    }

    /**
     * Sets value to default_value
     * @param mixed
     * @return void
     * @access public
     */
    public function setDefaultValue($default_value) {
        $this->default_value = $default_value;
    }

    /**
     * Get class tags
     * @return array (keyed by tag name)
     * @access public
     */
    public function getTags() {
        return $this->custom_tags;
    }

    /**
     * Set class tags
     * @param array $tags class tags
     * @return bool
     * @access public
     */
    public function setTags($tags) {
        return $this->custom_tags = $tags;
    }

    /**
     * Get fields tag by name
     * @param string $tagName name of field tag
     * @return false|string the value of field tag or false if not set
     * @access public
     */
    public function getTag($tagName) {
        return (isset($this->custom_tags[$tagName]))
            ? $this->custom_tags[$tagName]
            : false;
    }

    /**
     * Set field tag by name
     * @param string $tagName name of field tag
     * @param string $tagValue value of field tag
     * @return bool
     * @access public
     */
    public function setTag($tagName, $tagValue) {
        return $this->custom_tags[$tagName] = $tagValue;
    }

    /**
     * Returns whether field is primitive
     * @return boolean
     * @access public
     */
    public function isPrimitive() {
        return true;
    }

    /**
     * Returns whether this field map is equal to $fm field map
     * Optionally can be skipped the name checking. Called by {@link epDbUpdate}.
     *
     * @param epFieldMap $fm
     * @param bool Whether to check also for name fileds
     * @return bool
     */
    public function equal($fm, $checkName = true){

        $result =
            $this->getColumnName() == $fm->getColumnName() &&
            $this->getDefaultValue() == $fm->getDefaultValue() &&
            $this->getType() == $fm->getType();

        if($checkName){
            return $result && ( $this->getName() == $fm->getName() );
        }

        return $result;
    }

    /**
     * Returns all supported column types
     * @return array
     */
    static public function getSupportedTypes() {

        return array(

            // primitive types
            self::DT_BOOL,
            self::DT_BOOLEAN,
            self::DT_BIT,
            self::DT_INT,
            self::DT_INTEGER,
            self::DT_DECIMAL,
            self::DT_FLOAT,
            self::DT_REAL,
            self::DT_CHAR,
            self::DT_CLOB,
            self::DT_TEXT,
            self::DT_BLOB,
            self::DT_DATE,
            self::DT_TIME,
            self::DT_DATETIME,

            // relationship types
            self::DT_HAS,
            self::DT_COMPOSED_OF,
            );
    }

    /**
     * Implements magic function __toString() for debugging
     * @return string
     */
    public function __toString() {
        $vars = array();
        foreach($this as $k => $v) {
            if($k == 'custom_tags'){
                foreach ($v as $k1 => $v1) {
                    $c[] = $k1 . ': ' . $v1;
                }
                $vars[] = $k . ': [' . implode('; ', $c) . ']';
            }
            elseif ($k != 'class_map') {
                $vars[] = $k . ': ' . $v;
            }
        }
        return '[epFieldMap] ' . implode(', ', $vars);
    }
}
