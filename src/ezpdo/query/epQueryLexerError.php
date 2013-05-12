<?php

/**
 * $Id: epQueryLexerError.php 1038 2007-02-11 01:38:59Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Moises Paz <davidmpaz@gmail.com>
 * @version $Revision: 1038 $
 * @package ezpdo
 * @subpackage ezpdo.query
 */
namespace ezpdo\query;

use ezpdo\base\parser\epLexerError as epLexerError;

/**
 * The error class for the EZOQL lexer and parser. It contains:
 * + msg, the error message
 * + value, the corresponding string value of the current token being processed
 * + line, the number of the starting line in source code from which error occurs
 * + char, the position of the starting char in the starting line from which this error occurs
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1038 $
 * @package ezpdo
 * @subpackage ezpdo.query
 */
class epQueryError extends epLexerError {
    /**
     * Constructor
     * @param integer $msg (the error message)
     * @param string $value (token value)
     * @param integer $line (line number)
     * @param integer $char (starting char in line)
     */
    public function __construct($msg, $value, $line = -1, $char = -1) {
        parent::__construct($msg, $value, $line, $char);
    }
}
