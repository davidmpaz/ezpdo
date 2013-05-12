<?php

/**
 * $Id: epTagLexer.php 1013 2006-09-27 01:55:43Z nauhygon $
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

use ezpdo\base\epUtils;
use ezpdo\orm\epFieldMap;
use ezpdo\base\parser\epLexer as epLexer;
use ezpdo\base\parser\epParser as epParser;

/**
 * Need {@link epLexer} and {@link epParser} for epTagParser
 */
include_once(EP_SRC_BASE_PARSER.'/epParser.php');

/**#@+
 * Predefined tokens for the base lexer ({@link epLexer})
 */
epUtils::epDefine('EPL_T_HAS');
epUtils::epDefine('EPL_T_COMPOSED_OF');
epUtils::epDefine('EPL_T_DATA_TYPE');
epUtils::epDefine('EPL_T_OID');
epUtils::epDefine('EPL_T_ONE');
epUtils::epDefine('EPL_T_MANY');
epUtils::epDefine('EPL_T_INDEX');
epUtils::epDefine('EPL_T_INVERSE');
epUtils::epDefine('EPL_T_UNIQUE');
/**#@-*/

/**
 * The lexer for ORM tag value
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler.tag
 */
class epTagLexer extends epLexer {

    /**
     * The keywords for ORM tag
     */
    static protected $tag_keywords = array(
        'has'          => EPL_T_HAS,
        'composed_of'  => EPL_T_COMPOSED_OF,
        'oid'          => EPL_T_OID,
        'one'          => EPL_T_ONE,
        'many'         => EPL_T_MANY,
        'index'        => EPL_T_INDEX,
        'inverse'      => EPL_T_INVERSE,
        'unique'       => EPL_T_UNIQUE,
        );

    /**
     * Constructor
     * @param string $s
     */
    public function __construct($s = '') {

        // add data types into keywords
        foreach(epFieldMap::getSupportedTypes() as $dt) {
            if ($dt != epFieldMap::DT_HAS && $dt != epFieldMap::DT_COMPOSED_OF) {
                self::$tag_keywords[$dt] = EPL_T_DATA_TYPE;
            }
        }

        // set keywords to lexer
        parent::__construct($s, self::$tag_keywords);
    }

    /**
     * Toggles the token type of data types between EPL_T_DATA_TYPE
     * and EPL_T_IDENTIFIER. This is to allow data types to be
     * used for class names.
     */
    public function toggleDataTypeTokens() {

        // get data types (including 'has' and 'composed_of')
        $dtypes = epFieldMap::getSupportedTypes();

        // go through all keywords
        foreach($this->keywords as $keyword => $token) {

            if (!in_array($keyword, $dtypes)) {
                continue;
            }

            // has
            if ($keyword == epFieldMap::DT_HAS) {
                $this->keywords[$keyword] =
                    ($token == EPL_T_HAS) ?
                    EPL_T_IDENTIFIER : EPL_T_HAS;
                continue;
            }

            // composed of
            if ($keyword == epFieldMap::DT_COMPOSED_OF) {
                $this->keywords[$keyword] =
                    ($token == EPL_T_COMPOSED_OF) ?
                    EPL_T_IDENTIFIER : EPL_T_COMPOSED_OF;
                continue;
            }

            // primitive types
            $this->keywords[$keyword] =
                ($token == EPL_T_DATA_TYPE) ?
                EPL_T_IDENTIFIER : EPL_T_DATA_TYPE;
        }
    }
}
