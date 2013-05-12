<?php

/**
 * $Id: epTag.php 1013 2006-09-27 01:55:43Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler.tag
 */
namespace ezpdo\compiler\tag;

/**
 * Class to parse an ezpdo orm tag value
 *
 * The class takes the tag value as the input and dissects it
 * into orm attributes. To get the attribute value, use {@link get()}.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler.tag
 * @abstract
 */
abstract class epTag {

    /**
     * Attribute and values
     * @var array (keyed by attribute name)
     */
    protected $attrs;

    /**
     * Returnrs all orm attributes
     * @return array (of string)
     */
    public function getAll() {
         return array_keys($this->attrs);
    }

    /**
     * Returns the value of an attribute
     * @param string attribute name
     * @return null|string
     */
    public function get($attr) {
        if (!isset($this->attrs[$attr])) {
            return null;
        }
        return $this->attrs[$attr];
    }

    /**
     * Parse the tag value string
     * @return bool
     */
    abstract public  function parse($value);
}
