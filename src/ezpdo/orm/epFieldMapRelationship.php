<?php

/**
 * $Id: epFieldMapRelationship.php 998 2006-06-05 12:57:26Z nauhygon $
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

    /**
     * Overrides {@link epFieldMap::equal()}
     *
     * @param epFieldMap $fm
     * @param bool $checkName
     * @return boolean|boolean
     */
    public function equal($fm, $checkName = true) {
        // is primitive?
        if($fm->isPrimitive()){
            return false;
        }

        return
            parent::equal($fm, $checkName) &&
            // also compare multiplicity and inverse relationship
            $this->isMany() == $fm->isMany() &&
            ! ( empty($this->inverse) || empty($fm->inverse) ) &&
            $this->getInverse() == $fm->getInverse();
    }

    /**
     * Whether $this is type compatible with $fm. This is: field type of $this
     * can be changed to field type of $fm without loose data.
     *
     * Since we won't allow change relationship type (for now ?), false is returned
     *
     * @param epFieldMapRelationship $fm field map to verify against it
     * @return boolean
     */
    public function isTypeCompatible($fm){
        return false;
    }
}
