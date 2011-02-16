<?php

/**
 * $Id: epFieldMap.php 998 2006-06-05 12:57:26Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 998 $ $Date: 2006-06-05 08:57:26 -0400 (Mon, 05 Jun 2006) $
 * @package ezpdo
 * @subpackage ezpdo.orm
 */

/**
 * Need epBase class (base class for epFieldMap)
 */
include_once(EP_SRC_BASE.'/epBase.php');

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
     * Returns whether field is primitive
     * @return boolean
     * @access public
     */
    public function isPrimitive() {
        return true;
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
            if ($k != 'class_map') {
                $vars[] = $k . ': ' . $v;
            }
        }
        return '[epFieldMap] ' . implode(', ', $vars);
    }
}

/**
 * The field map for primitive types
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 998 $ $Date: 2006-06-05 08:57:26 -0400 (Mon, 05 Jun 2006) $
 * @package ezpdo
 * @subpackage ezpdo.orm
 */
class epFieldMapPrimitive extends epFieldMap {
    
    /**
     * Any parameters associated to the column type
     * For example, in "char(m)", m is the parameter.
     * Multiple parameters are seperated by commas
     * @var string
     * @access protected
     */
    protected $type_params = false;

    /**
     * Constructor
     * @param string name of the corresponding class
     * @param string $name
     * @param epClassMap $class_map
     * @param string $type type constants
     * @param array $type_params
     */
    public function __construct($name, $type = self::DT_CHAR, $type_params = array(), $class_map = null) {
        parent::__construct($name, $type, $class_map);
        if ($type_params) {
            $this->setTypeParams($type_params);
        }
    }
    
    /**
     * Gets parameters for type (comma seperated string)
     * @return string
     * @access public
     */
    public function getTypeParams() {
        if (is_array($this->type_params)) {
            return implode(',', $this->type_params);
        }
        return $this->type_params;
    }
     
    /**
     * Sets type params 
     * @param string|array $type_params
     * @return void
     * @access public
     */
    public function setTypeParams($type_params) {
        $this->type_params = $type_params;
    }

}

/**
 * Class of field map for relationship vars
 * 
 * A relationship field has two types, either "has" or "composed_of", 
 * corresponding to the @orm has/composed_of tag. 
 * 
 * A relationship field can be a single-valued or many-valued field and 
 * it must have a "related" class that it has relationship with. 
 * 
 * A relationship field can have a field as its inverse in its related 
 * class. When two fields are specified as inverses of one another,
 * they can be used to form bidirectional links. An update to one field 
 * will automatically trigger a corresponding update to the other
 * field, maintaining consistency between the fields. One or both fields 
 * can be mulitvalued, or both can be single-valued.
 * 
 * Using "inverse()" in the var @orm tag, you can make a pair of vars in 
 * two classes as inverses to each other. For example, 
 * <pre>
 *   // class A
 *   class classA {
 *     // @orm has one classB inverse(a)
 *     public $b;
 *   }
 * 
 *   // class B
 *   class classB {
 *     // @orm has one ClassA inverse(b)
 *     public $a;
 *   }
 * </pre>
 * 
 * Varibles classA::$b and classB::$a forms a bidirectional link. 
 * Assigning one object to another will also install relationship 
 * on the other direction. That is, 
 * <pre>
 *   $a-&gt;b = $b;
 * </pre>
 * is equivalent to 
 * <pre>
 *   $a-&gt;b = $b;
 *   $b-&gt;a = $a;
 * </pre>
 * if A::$b and B::$a are specified as inverses.   
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 998 $ $Date: 2006-06-05 08:57:26 -0400 (Mon, 05 Jun 2006) $
 * @package ezpdo
 * @subpackage ezpdo.orm
 */
class epFieldMapRelationship extends epFieldMap {
    
    /**#@+
     * Multiplicity  cconstants: one or many
     */
    const MT_ONE  = 'one';
    const MT_MANY = 'many';
    /**#@-*/

    /**
     * The related class
     * @var string 
     */
    protected $class = false;

    /**
     * The field map for the inverse in the related class. 
     * @var epFieldMap
     */
    protected $inverse = false;
    
    /**
     * Whether field is a many-valued field
     * @var boolean
     */
    protected $is_many = false;
    
    /**
     * Constructor
     * @param string $name name of the corresponding class
     * @param string $type either "has" or "composed_of"
     * @param string $class the name of the class related
     * @param epClassMap
     */
    public function __construct($name, $type, $class, $is_many = false, $inverse = false, $class_map = false) {
        
        parent::__construct($name, $type, $class_map);
        
        $this->setIsMany($is_many);
        $this->setClass($class);
        $this->setInverse($inverse);
    }
    
    /**
     * Is the data type of the field a primitive one? Always return false.
     * @return bool
     */
    public function isPrimitive() {
       return false;
    }
    
    /**
     * Is this a "has" relationship field?
     * @return bool
     */
    public function isHas() {
        return $this->type == self::DT_HAS;
    }
    
    /**
     * Is this a "composed_of" relationship field?
     * @return bool
     */
    public function isComposedOf() {
        return ($this->type == self::DT_COMPOSED_OF);
    }
    
    /**
     * Is this a "has one" or "composed_of one" (i.e. single-valued) relationship field?
     * @return bool
     */
    public function isSingle() {
        return (!$this->is_many);
    }
    
    /**
     * Is this a "has many" or "composed_of many" relationship field?
     * @return bool
     */
    public function setIsMany($is_many = true) {
        $this->is_many = $is_many;
    }
    
    /**
     * Is this a "has many" or "composed_of many" relationship field?
     * @return bool
     */
    public function isMany() {
        return $this->is_many;
    }
    
    /**
     * Is this a "has one" relationship field?
     * @return bool
     */
    public function isHasOne() {
        return ($this->type == self::DT_HAS && !$this->is_many);
    }
    
    /**
     * Is this a "has many" relationship field?
     * @return bool
     */
    public function isHasMany() {
        return ($this->type == self::DT_HAS && $this->is_many);
    }
    
    /**
     * Is this a "composed_of one" relationship field?
     * @return bool
     */
    public function isComposedOfOne() {
        return ($this->type == self::DT_COMPOSED_OF && !$this->is_many);
    }
    
    /**
     * Is this a "composed_of many" relationship field?
     * @return bool
     */
    public function isComposedOfMany() {
        return ($this->type == self::DT_COMPOSED_OF && $this->is_many);
    }
    
    /**
     * Retrurns the related class
     * @return false|string
     * @access public
     */
    public function getClass() {
        return $this->class;
    }

    /**
     * Set the related class
     * @param string $class
     * @access public
     */
    public function setClass($class) {
        $this->class = $class;
    }

    /**
     * Returns the inverse to this field 
     * @return string 
     */
    public function getInverse() {
        return $this->inverse;
    }
    
    /**
     * Set the inverse to this field
     * @param string $inverse
     */
    public function setInverse($inverse) {
        $this->inverse = $inverse;
    }

    /**
     * Find the base_a for relationship table
     * @return string
     */
    public function getBase_a() {
        return $this->getBase()->getClassMap()->getName();
    }

    /**
     * Find the base_b for relationship table
     * @return string
     */
    public function getBase_b() {
        return $this->getBase()->getClass();
    }
}

/**
 * The exception class for {@link epFieldMapFactory}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 998 $ $Date: 2006-06-05 08:57:26 -0400 (Mon, 05 Jun 2006) $
 * @package ezpdo
 * @subpackage ezpdo.orm
 */
class epExceptionFieldMapFactory extends epException { 
}

/**
 * The simple factory class of ezpdo field mapping info. 
 * 
 * The factory manufactures field maps ({@link epFieldMap}) according 
 * to given parameters.
 * 
 * Note that since the class map factory ({@link epClassMapFactory}) 
 * keeps all class maps and their field maps, there is no need for 
 * field map factory to keep the same information. Thus this factory
 * does not implement the {@link epFactory} interface, but only a 
 * static method of {@link make()}.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 998 $ $Date: 2006-06-05 08:57:26 -0400 (Mon, 05 Jun 2006) $
 * @package ezpdo
 * @subpackage ezpdo.orm
 */
class epFieldMapFactory {

    /**
     * Manufactures field map according to given parameters
     * @param string $name field name
     * @param string $type type of field (constants defined in epFieldMap)
     * @param array $params parameters
     * @return false|epFieldMap
     */
    static function &make($name, $type, $params = array()) {
        
        // the return value
        $fm = false;
        
        // must have field (var) name and type
        if (!$name || !$type) {
            return $fm;
        }
        
        switch (strtolower($type)) {
        
        // primitive types
        case epFieldMap::DT_BOOL:
        case epFieldMap::DT_BOOLEAN:
        case epFieldMap::DT_BIT:
        case epFieldMap::DT_INT:
        case epFieldMap::DT_INTEGER:
        case epFieldMap::DT_DECIMAL:
        case epFieldMap::DT_FLOAT:
        case epFieldMap::DT_REAL:
        case epFieldMap::DT_CHAR:
        case epFieldMap::DT_CLOB:
        case epFieldMap::DT_TEXT:
        case epFieldMap::DT_BLOB:
        case epFieldMap::DT_DATE:
        case epFieldMap::DT_TIME:
        case epFieldMap::DT_DATETIME: {
            $fm = new epFieldMapPrimitive($name, $type, $params);
            break;
        }
        
        case epFieldMap::DT_HAS:
        case epFieldMap::DT_COMPOSED_OF: {
            
            // create a relationship field
            $fm = new epFieldMapRelationship(
                $name, $type, 
                $params['class'], 
                $params['is_many'], 
                $params['inverse']
                );
            break;
        }
            
        default: 
            throw new epExceptionFieldMapFactory('Unrecognized field type [' . $type . ']');
        }

        return $fm;
    }

}

?>
