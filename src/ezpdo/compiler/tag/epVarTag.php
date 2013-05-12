<?php

/**
 * $Id: epVarTag.php 1013 2006-09-27 01:55:43Z nauhygon $
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
 * Class to parse an orm tag value of a variable
 *
 * Three attributes for an orm tag for a variable
 * <ol>
 * <li>
 * name: the name of column the variable to be mapped to which can
 * be returned as null. if empty, the parser ({@link epClassParser})
 * uses the variable name as the column name.
 * </li>
 * <li>
 * type: the type of column (see {@link epFieldMap::getSupportedTypes()}),
 * can be empty as well. If empty, the parser ({@link epClassParser})
 * tries to figure out the type by looking at other usual docblock tags.
 * The last resort is to treat it as a string.
 * </li>
 * <li>
 * params: the params for the column type (can be empty)
 * </li>
 * </ol>
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler.tag
 */
class epVarTag extends epTag {

    /**
     * Impelment abstract method in {@link epTag}
     * Parse the tag value string
     * @return bool|array Error if array
     */
    public  function parse($value) {

        // sanity check
        if (!$value || !is_string($value)) {
            return false;
        }

        // test parser
        $p = new epTagParser($value);
        if (!$this->attrs = $p->parse()) {
            $errors = $p->errors();
            return $errors[0]->__toString();
        }

        return true;
    }
}
