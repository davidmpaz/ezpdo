<?php

/**
 * $Id: epFieldMapPrimitive.php 998 2006-06-05 12:57:26Z nauhygon $
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

    /**
     * Overrides {@link epFieldMap::equal()}
     *
     * @param epFieldMap $fm
     * @param bool $checkName
     * @return boolean|boolean
     */
    public function equal($fm, $checkName = true) {
        // not primitive ?
        if(! $fm->isPrimitive()){
            return false;
        }

        return
            parent::equal($fm, $checkName) &&
            // also compare type_params
            $this->getTypeParams() == $fm->getTypeParams();
    }

    /**
     * Whether $this is type compatible with $fm. This is: field tye of $this
     * can be changed to field type of $fm without loose data.
     * @param epFieldMapPrimitive $fm field map to verify against it
     * @return boolean
     */
    public function isTypeCompatible($fm){
        switch ($this->getType()) {
            case self::DT_BIT:
            case self::DT_BOOL:
            case self::DT_BOOLEAN:
                $type = $fm->getType();
                return
                    $type == self::DT_BIT ||
                    $type == self::DT_BOOL ||
                    $type == self::DT_BOOLEAN ||
                    $type == self::DT_INTEGER ||
                    $type == self::DT_INT;
                break;

            case self::DT_CHAR:
            case self::DT_CLOB:
                $type = $fm->getType();
                $lenght = $fm->getTypeParams();
                return
                    ($type == self::DT_CHAR || $type == self::DT_CLOB) &&
                    (int)$this->getTypeParams() >= (int)$lenght;
                break;

            case self::DT_INT:
            case self::DT_INTEGER:
                $type = $fm->getType();
                $lenght = $fm->getTypeParams();
                return
                    ($type == self::DT_INT ||
                    $type == self::DT_INTEGER ||
                    $type == self::DT_TIME ||
                    $type == self::DT_DATE ||
                    $type == self::DT_DATETIME) &&
                    (int)$this->getTypeParams() >= (int)$lenght;
                break;

            case self::DT_TIME:
            case self::DT_DATE:
            case self::DT_DATETIME:
                $type = $fm->getType();
                $lenght = $fm->getTypeParams();
                return
                    ($type == self::DT_INT ||
                    $type == self::DT_INTEGER ||
                    $type == self::DT_TIME ||
                    $type == self::DT_DATE ||
                    $type == self::DT_DATETIME) &&
                    (int)$this->getTypeParams() >= (int)$lenght;
                break;

            case self::DT_FLOAT:
            case self::DT_REAL:
                $type = $fm->getType();
                $lenght = $fm->getTypeParams();
                return
                    ($type == self::DT_REAL ||
                    $type == self::DT_FLOAT) &&
                    (int)$this->getTypeParams() >= (int)$lenght;
                break;

            case self::DT_BLOB:
                $lenght = $fm->getTypeParams();
                return
                    ($fm->getType() == self::DT_BLOB) &&
                    ((int)$this->getTypeParams() >= (int)$lenght);
                break;

            case self::DT_DECIMAL:
                $param = explode(',', $fm->getTypeParams());
                $thisparam = explode(',', $this->getTypeParams());
                return
                    ($fm->getType() == self::DT_DECIMAL) &&
                    (int)$thisparam[0] >= (int)$param[0] &&
                    (int)$thisparam[1] >= (int)$param[1];
                break;

            default:
                return false;
            break;
        }
        return true;
    }

}
