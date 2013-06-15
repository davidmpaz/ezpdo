<?php

/**
 * $Id: epLexer.php 945 2006-05-12 19:34:14Z nauhygon $
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
 * Lexer that breaks a string into tokens.
 *
 * Usage:
 * <code>
 * // $s contains the source string
 * $l = new epLexer($s);
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
 * @subpackage ezpdo.base.parser
 * @version $id$
 * @author Oak Nauhygon <ezpdo4php@ezpdo.net>
 */
class epLexer extends epBase {

    /**
     * Array to keep all keywords
     * @var array
     */
    protected $keywords = array();

    /**
     * Keyword escaper
     * @var string
     */
    protected $keyword_escape = "`";

    /**
     * Array to keep all tokens
     * @var array
     */
    protected $tokens = array();

    /**
     * Cursor of the tokens (to facilitate back and forth)
     * @var integer
     */
    protected $cursor = false;

    /**
     * Array to keep all errors
     * @var array
     */
    protected $errors = array();

    /**
     * The current error
     * @var epLexerError
     */
    protected $error = null;

    /**
     * The current token value
     * @var integer
     */
    protected $value = '';

    /**
     * The starting line number for the current value
     * @var integer
     */
    protected $line_start = 1;

    /**
     * The char start position for the current value
     * @var integer
     */
    protected $char_start = 1;

    /**
     * Constructor
     * @param string $s
     */
    public function __construct($s = '', $keywords = array()) {

        // set keywords
        if ($keywords) {
            $this->keywords = $keywords;
        }

        // initialize lexer
        $this->initialize($s);
    }

    /**
     * Initialize the lexer
     * @param string $s the input string
     * @return void
     */
    public function initialize($s = '') {
        $this->tokens = array();
        $this->errors = array();
        $this->cursor = false;
        $this->value = '';
        $this->line_start = 1;
        $this->char_start = 1;
        $this->stream = new epLexerStream($s);
    }

    /**
     * Returns the string being parsed
     * @return false|string
     */
    public function getInput() {
        if (!$this->stream) {
            return false;
        }
        return $this->stream->getInput();
    }

    /**
     * Return the previous cursor
     * @return false|epToken
     */
    public function back() {

        if ($this->cursor == 0) {
            $this->cursor = false;
        }

        // no token parsed yet?
        if ($this->cursor === false) {
            return false;
        }

        // move back one token
        $this->cursor --;

        return $this->tokens[$this->cursor];
    }

    /**
     * Returns the next token
     * @param string $str
     * @return false|epToken
     */
    public function next() {

        // check if the next token has been parsed
        if (count($this->tokens) > 0) {

            // first time to read token?
            if ($this->cursor === false) {
                $this->cursor = 0;
                return $this->tokens[$this->cursor];
            }

            // cursor within boundary?
            if ($this->cursor + 1 < count($this->tokens)) {
                $this->cursor ++;
                return $this->tokens[$this->cursor];
            }
        }

        // get the next token. have we reached the end yet?
        if (false === ($type = $this->_next())) {
            return false;
        }

        // create a new token
        $t = new epToken($type, $this->value, $this->line_start, $this->char_start);
        if (!$t) {
            return false;
        }

        // collect the token
        $this->tokens[] = $t;

        // point the cursor to the last token
        $this->cursor = count($this->tokens) - 1;

        // return the token
        return $t;
    }

    /**
     * Peek the next token
     * @return eqpToken|false
     */
    public function peek() {
        if (($t = $this->next()) !== false) {
            $this->back();
        }
        return $t;
    }

    /**
     * Returns the next token type
     * @return string
     * @access private
     */
    protected function _next() {

        // preparation before reading next token
        if (false === ($ch = $this->prepare())) {
            return false;
        }

        // String constant
        if ($ch == '"' || $ch == "'") {
            return $this->string($ch);
        }

        // number
        else if ($this->isDigit($ch) || ($ch == '.' && $this->isDigit($this->peekc()))) {
            return $this->number($ch);
        }

        // identifier/keyword
        else if ($this->isIdChar($ch) || $ch == $this->keyword_escape) {
            // read identifier or keyword
            return $this->identifier($ch);
        }

        // reads literals
        if (false !== ($literal = $this->literal($ch))) {
            return $literal;
        }

        // just return this char
        return $ch;
    }

    /**
     * Preparation before reading next token: skip whitespaces etc.
     * @return string
     */
    protected function prepare() {

        // reset the current error to null
        $this->error = null;

        // reset the token value
        $this->value = '';

        // keep track of starting line and char position of the current token
        $this->line_start = $this->stream->line();
        $this->char_start = $this->stream->char();

        // ignore white space
        while (($ch = $this->getc()) !== false && $this->isSpace($ch)) {
            $this->value = '';
        }

        return $ch;
    }

    /**
     * Read a string constant
     * @param string $ender (the ending char, ' or ")
     * @return char (the last char read)
     */
    protected function string($ender) {

        $ch = '';
        $done = false;
        while (!$done) {

            $ch = $this->getc();

            if ($ch == "\n" || $ch === false) {
                $this->error("String terminated unexpectedly");
                return EPL_T_STRING;
            }

            if ($ch == $ender) {
                $done = true;
                break;
            }

            if ($ch == "\\") {

                if ($this->peekc() == "\n") {
                    // ignore if backslash is followed by a newline
                    $this->getc();
                    continue;
                }

                if ($this->escape() === false) {
                    $done = true;
                    break;
                }
            }
        }

        return EPL_T_STRING;
    }


    /**
     * Returns an identifier or a keyword
     * @param string $ch the starting char
     * @return string
     */
    protected function identifier($ch) {

        // $this->keyword_escape allows keyword to be treated as identifier
        $q96 = ($ch == $this->keyword_escape);

        $id = $ch;
        while (($ch = $this->getc()) !== false &&
               ($this->isIdChar($ch)
                || $this->isDigit($ch)
                || $q96 && $ch != $this->keyword_escape
                )) {
            $id .= $ch;
        }

        if ($q96 && $ch == $this->keyword_escape) {
            $id .= $ch;
        } else {
            if ($ch) {
                $this->ungetc();
            }
        }

        // is it a keyword (if no escaping)?
        $id = strtolower($id);
        if (!$q96 && isset($this->keywords[$id])) {
            return $this->keywords[$id];
        }

        return EPL_T_IDENTIFIER;
    }

    /**
     * Reads literals (for example, logicla operators ==, !=, <>, <=, >=, &&, ||)
     * @param string $ch The starting char
     * @return false
     */
    protected function literal($ch) {
        return false;
    }

    /**
     * Read escape char
     * @return false|string
     */
    protected function escape() {

        $ch = $this->getc();
        if ($ch == 'n'
            || $ch == 't'
            || $ch == 'v'
            || $ch == 'b'
            || $ch == 'r'
            || $ch == 'f'
            || $ch == 'a'
            || $ch == "\\"
            || $ch == '?'
            || $ch == "\'"
            || $ch == '"') {
            return $ch;
        }

        return false;
    }

    /**
     * Reads a decimal number
     * @param string $ch Tthe starting char: a digit or '.'
     * @return string
     */
    protected function number($ch) {

        $is_float = false;
        $seen_dot = false;

        // is it a float (ie starting with '.')?
        if ($ch == '.') {
            $is_float = true;
            $seen_dot = true;
            do {} while ( $this->isDigit( $ch = $this->getc() ) );
        }
        // it starts with a decimal digit
        else {
            do {} while ( $this->isDigit( $ch = $this->getc() ) );
        }

        // not the end of the stream yet?
        if ($ch !== false) {

            // a float (we have seen the integer part before '.', 'e', or 'E')
            if ((!$seen_dot && $ch == '.') || $ch == 'e' || $ch == 'E') {

                $is_float = true;

                if ($ch == '.') {
                    do {} while ( $this->isDigit( $ch = $this->getc() ) );
                }

                // scientific number?
                if ($ch == 'e' || $ch == 'E') {

                    $ch = $this->getc();
                    if ($ch == '+' || $ch == '-') {
                        $ch = $this->getc();
                    }

                    if (!$this->isDigit($ch)) {
                        $this->error('malformed exponent part in a decimal number');
                    }

                    do {} while ( $this->isDigit( $ch = $this->getc() ) );
                }
            }

            // put the last character back to the stream (if we haven't reached the end yet)
            if ($ch !== false) {
                $this->ungetc();
            }
        }

        return $is_float ? EPL_T_FLOAT : EPL_T_INTEGER;
    }

    /**
     * Is it an identifier letter?
     * @param char $c
     * @return boolean
     */
    protected function isIdChar($c) {
        return ('a' <= $c && $c <= 'z')
            || ('A' <= $c && $c <= 'Z')
            || $c == '_';
    }

    /**
     * Is it a decimal digit?
     * @param char $c
     * @return boolean
     */
    protected function isDigit($c) {
        return is_string($c) && '0' <= $c && $c <= '9';
    }

    /**
     * Is it a whitespace?
     * Note that newline excluded as it may become significant in certain cases.
     * @param char $c
     * @return boolean
     */
    protected function isSpace($c) {
        return is_string($c) && $c == ' '
            || $c == "\t"
            || $c == "\v"
            || $c == "\r"
            || $c == "\f";
    }

    /**
     * Get one char from the stream and append it to (token) value
     * @return char
     */
    protected function getc() {
        $c = $this->stream->getc();
        if ($c !== false) {
            $this->value .= $c;
        }
        return $c;
    }

    /**
     * Put back the last char into stream and remove it from (token) value
     * @return char (the previous char)
     */
    protected function ungetc() {
        $c = $this->stream->ungetc();
        if ($c !== false && $this->value) {
            $this->value = substr($this->value, 0, strlen($this->value) - 1);
        }
        return $c;
    }

    /**
     * Take a peek at the next char (no cursor moving)
     * @return char
     */
    protected function peekc() {
        return $this->stream->peek();
    }

    /**
     * Returns the errors raised
     * @return array
     */
    public function errors() {
        return $this->errors;
    }

    /**
     * Raise an error message.
     *
     * The error message is stored in an array that can be
     * retrieved later.
     *
     * @param string $msg
     * @return epLexerError
     */
    protected function error($msg = '') {

        // if empty message, return the current error
        if (!$msg) {
            return $this->error;
        }

        // create a new error object
        $this->error = new epLexerError(
            $msg, $this->value,
            $this->stream->line(), $this->stream->char()
            );

        // keep all errors
        $this->errors[] = $this->error;

        // return this error
        return $this->error;
    }

}
