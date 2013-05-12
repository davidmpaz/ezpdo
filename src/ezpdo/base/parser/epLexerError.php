<?php

/**
 * $Id: epLexerError.php 945 2006-05-12 19:34:14Z nauhygon $
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

/**#@+
 * need epBase and epUtil
 */
include_once(EP_SRC_BASE.'/epBase.php');
include_once(EP_SRC_BASE.'/epUtils.php');
/**#@-*/
/**
 * The error class for the lexer. It contains:
 * + msg, the error message
 * + value, the corresponding string value of the current token
 * + line, the number of the starting line in source
 * + char, the position of the starting char in the line
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 945 $
 * @package ezpdo
 * @subpackage ezpdo.base.parser
 */
class epLexerError extends epBase {

    /**
     * The error message
     * @var integer
     */
    protected $msg = '';

    /**
     * Token value
     * @var string
     */
    protected $value = '';

    /**
     * Line number where the error occurs
     * @var integer
     */
    protected $line = -1;

    /**
     * Char number where the error occurs
     * @var integer
     */
    protected $char = 0;

    /**
     * Constructor
     * @param integer $msg (the error message)
     * @param string $value (token value)
     * @param integer $line (line number)
     * @param integer $char (starting char in line)
     */
    public function __construct($msg, $value, $line = -1, $char = -1) {
        $this->msg = $msg;
        $this->value = $value;
        $this->line = $line;
        $this->char = $char;
    }

    /**
     * Magic function __toString()
     */
    public function __toString() {
        return $this->msg . ' @ line ' . $this->line
            . ' col ' . $this->char . ' [ ... ' . $this->value . ' ... ]';
    }
}
