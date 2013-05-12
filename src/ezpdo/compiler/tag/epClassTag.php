<?php

/**
 * $Id: epClassTag.php 1013 2006-09-27 01:55:43Z nauhygon $
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
 * Class used to parse the value of an orm tag for a class
 *
 * Available attributes after parsing the tag value
 * <ol>
 * <li>
 * table: the table name for the class to be mapped to (can be null if not specified)
 * </li>
 * <li>
 * dsn: the dsn ({@link http://pear.php.net/manual/en/package.database.db.intro-dsn.php})
 * to the database that the table can be accessed. This attribute can also
 * be null, if so the parser ({@link epClassParser}) tries to use the default_dsn
 * specified in config.
 * </li>
 * </ol>
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler.tag
 */
class epClassTag extends epTag {

    /**
     * Impelment abstract method in {@link epTag}
     * Parse the tag value string
     * @return bool
     */
    public  function parse($value) {

        // sanity check
        if (!$value || !is_string($value)) {
            return false;
        }

        // break value into pieces
        $pieces = preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (!$pieces) {
            return true;
        }

        $table_found = false;
        foreach($pieces as $piece) {
            $piece = trim($piece);
            if (preg_match('/[\w\(\)]+:\/\//i', $piece)) {
                $this->attrs['dsn'] = $piece;
            }
            else if (preg_match('/oid\((.*)\)/i', $piece, $matches)) {
                $this->attrs['oid'] = trim($matches[1]);
            }
            else {
                if (!$table_found) {
                    $this->attrs['table'] = $piece;
                    $table_found = true;
                }
            }
        }

        return true;
    }

}
