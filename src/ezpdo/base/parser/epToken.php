<?php

/**
 * $Id: epToken.php 945 2006-05-12 19:34:14Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision: 945 $
 * @package ezpdo
 * @subpackage ezpdo.base.parser
 */
namespace ezpdo\base\parser;

use ezpdo\base\epUtils;
use ezpdo\base\epBase as epBase;

/**
 * The class of a token
 *
 * A token contains the following fields:
 * + type, the token type, either primitive string or EPL_T_xxx constants
 * + value, the corresponding string value of the token, for example
 * + line, the number of the line where this token is found
 * + char, the position of the starting char from which this token is found
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 945 $
 * @package ezpdo
 * @subpackage ezpdo.base.parser
 */
class epToken extends epBase {

    /**
     * Token type (default to unknown)
     * @var integer
     */
    public $type = EPL_T_UNKNOWN;

    /**
     * Token value
     * @var string
     */
    public $value = '';

    /**
     * Line number where token is located
     * @var integer
     */
    public $line = -1;

    /**
     * Char number where token is located on the line
     * @var integer
     */
    public $char = 0;

    /**
     * Constructor
     * @param integer $type (token type)
     * @param string $value (token value)
     * @param integer $line (line number)
     * @param integer $char (starting char in line)
     */
    public function __construct($type, $value, $line = -1, $char = -1) {
        $this->type = $type;
        $this->value = $value;
        $this->line = $line;
        $this->char = $char;
    }

    /**
     * Magic function __toString() (mostly for debugging)
     */
    public function __toString() {
        return $this->type . ': ' . $this->value
            . ' (' . $this->line . ', ' . $this->char . ')';
    }
}
