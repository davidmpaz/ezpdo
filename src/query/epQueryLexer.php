<?php

/**
 * $Id: epQueryLexer.php 1038 2007-02-11 01:38:59Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1038 $
 * @package ezpdo
 * @subpackage ezpdo.query
 */

/**#@+
 * need epLexer
 */
include_once(EP_SRC_BASE_PARSER.'/epLexer.php');
/**#@-*/

/**#@+
 * EZOQL tokens
 */
epDefine('EPQ_T_AND');
epDefine('EPQ_T_AS');
epDefine('EPQ_T_ASC');
epDefine('EPQ_T_AVG');
epDefine('EPQ_T_BETWEEN');
epDefine('EPQ_T_BY');
epDefine('EPQ_T_CONTAINS');
epDefine('EPQ_T_COUNT');
epDefine('EPQ_T_DESC');
epDefine('EPQ_T_EQUAL');
epDefine('EPQ_T_FALSE');
epDefine('EPQ_T_FLOAT');
epDefine('EPQ_T_FROM');
epDefine('EPQ_T_IDENTIFIER');
epDefine('EPQ_T_IN');
epDefine('EPQ_T_INTEGER');
epDefine('EPQ_T_IS');
epDefine('EPQ_T_GE');
epDefine('EPQ_T_LE');
epDefine('EPQ_T_LIKE');
epDefine('EPQ_T_LIMIT');
epDefine('EPQ_T_MAX');
epDefine('EPQ_T_MIN');
epDefine('EPQ_T_NEQUAL');
epDefine('EPQ_T_NEWLINE');
epDefine('EPQ_T_NOT');
epDefine('EPQ_T_NULL');
epDefine('EPQ_T_OR');
epDefine('EPQ_T_ORDER');
epDefine('EPQ_T_RANDOM');
epDefine('EPQ_T_SELECT');
epDefine('EPQ_T_SOUNDEX');
epDefine('EPQ_T_STRCMP');
epDefine('EPQ_T_STRING');
epDefine('EPQ_T_SUM');
epDefine('EPQ_T_TRUE');
epDefine('EPQ_T_WHERE');
epDefine('EPQ_T_UNKNOWN');
/**#@-*/

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

/**
 * EZOQL Lexer that breaks an EZOQL query string into tokens. 
 * 
 * Usage: 
 * <code>
 * // $s contains the EZOQL source
 * $l = new epQueryLexer($s);
 * 
 * // now go through tokens one by one
 * while ($t = $l.next()) {
 *     // process the token
 *     // ......
 * }
 * </code>
 * 
 * You can also go back a token like this, 
 * <code>
 * $t = $l->back();
 * </code>
 * 
 * @package ezpdo
 * @subpackage ezpdo.query
 * @version $id$
 * @author Oak Nauhygon <ezpdo4php@ezpdo.net> 
 */
class epQueryLexer extends epLexer {

    /**
     * Associative array holds all EZOQL keywords
     * @var array
     */
    static public $oql_keywords = 
        array(
            'and'        => EPQ_T_AND,
            'as'         => EPQ_T_AS,
            'asc'        => EPQ_T_ASC,
            'ascending'  => EPQ_T_ASC,
            'avg'        => EPQ_T_AVG,
            'between'    => EPQ_T_BETWEEN,
            'by'         => EPQ_T_BY,
            'contains'   => EPQ_T_CONTAINS,
            'count'      => EPQ_T_COUNT,
            'desc'       => EPQ_T_DESC,
            'descending' => EPQ_T_DESC,
            'from'       => EPQ_T_FROM,
            'false'      => EPQ_T_FALSE,
            'in'         => EPQ_T_IN,
            'is'         => EPQ_T_IS,
            'like'       => EPQ_T_LIKE,
            'limit'      => EPQ_T_LIMIT,
            'max'        => EPQ_T_MAX,
            'min'        => EPQ_T_MIN,
            'not'        => EPQ_T_NOT,
            'null'       => EPQ_T_NULL,
            'or'         => EPQ_T_OR,
            'order'      => EPQ_T_ORDER,
            'random'     => EPQ_T_RANDOM,
            'select'     => EPQ_T_SELECT,
            'soundex'    => EPQ_T_SOUNDEX,
            'strcmp'     => EPQ_T_STRCMP,
            'sum'        => EPQ_T_SUM,
            'true'       => EPQ_T_TRUE,
            'where'      => EPQ_T_WHERE,
            );

    /**
     * Constructor
     * @param string $s 
     */
    public function __construct($s = '') {
        parent::__construct($s, self::$oql_keywords);
    }

    /**
     * Overrides {@link epLexer::_next()}
     * Returns the newline token
     */
    protected function _next() {
        $type = parent::_next();
        return ($type == "\n") ? EPQ_T_NEWLINE : $type; 
    }

    /**
     * Overrides {@link epLexer::number()}
     * Reads a decimal number 
     * @param string $ch The starting char: a digit or '.' 
     */
    protected function number($ch) {
        return (EPL_T_FLOAT == parent::number($ch)) ? EPQ_T_FLOAT : EPQ_T_INTEGER;
    }

    /**
     * Overrides {@link epLexer::string}
     */
    protected function string($ender) {
        parent::string($ender); return EPQ_T_STRING;
    }

    /**
     * Returns an identifier or a keyword
     * @param string $ch the starting char
     * @return string
     */
    protected function identifier($ch) {
        $idkw = parent::identifier($ch); 
        return ($idkw == EPL_T_IDENTIFIER) ? EPQ_T_IDENTIFIER : $idkw;
    }

    /**
     * Override {@link epLexer::readLiteral()}
     * Reads literals: ==, !=, <>, <=, >=, &&, ||
     * @param string $ch The starting char
     * @return false
     */
    protected function literal($ch) {

        // == 
        if ($ch == '=' && $this->peekc() == '=') {
            $this->getc();
            return EPQ_T_EQUAL;
        } 
        
        // ^= 
        else if ($ch == '^' && $this->peekc() == '=') {
            $this->getc();
            return EPQ_T_NEQUAL;
        } 
        
        // != 
        else if ($ch == '!' && $this->peekc() == '=') {
            $this->getc();
            return EPQ_T_NEQUAL;
        } 
        
        // <>
        else if ($ch == '<' && $this->peekc() == '>') {
            $this->getc();
            return EPQ_T_NEQUAL;
        }
        
        // <= 
        else if ($ch == '<' && $this->peekc() == '=') {
            $this->getc();
            return EPQ_T_LE;
        }
        
        // >=
        else if ($ch == '>' && $this->peekc() == '=') {
            $this->getc();
            return EPQ_T_GE;
        } 
        
        // &&
        else if ($ch == '&' && $this->peekc() == '&') {
            $this->getc();
            return EPQ_T_AND;
        } 
        
        // ||
        else if ($ch == '|' && $this->peekc() == '|') {
            $this->getc();
            return EPQ_T_OR;
        } 
        
        return false;
    }

}

?>
