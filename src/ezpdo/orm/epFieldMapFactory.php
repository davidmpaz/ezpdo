<?php

/**
 * $Id: epFieldMapFactory.php 998 2006-06-05 12:57:26Z nauhygon $
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
