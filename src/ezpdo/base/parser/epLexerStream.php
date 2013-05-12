<?php

/**
 * $Id: epLexerStream.php 945 2006-05-12 19:34:14Z nauhygon $
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
 * A stream class for the lexer
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 945 $
 * @package ezpdo
 * @subpackage ezpdo.base.parser
 */
class epLexerStream extends epBase {

    /**
     * The string
     * @var string
     */
    protected $s = false;

    /**
     * The current position
     * @var integer
     */
    protected $pos = 0;

    /**
     * The current line number (starting from 1 instead of 0)
     * @var integer
     */
    protected $line = 1;

    /**
     * The char position in the current line (starting from 1 instead of 0)
     * @var integer
     */
    protected $char = 1;

    /**
     * Constructor
     * @param string $s
     */
    function __construct($s) {
        // need to remove cariage return
        $this->s = str_replace("\r", '', $s);
        $this->pos = 0;
        $this->line = 1;
        $this->char = 1;
    }

    /**
     * Returns the string associated to the stream
     * @return string
     */
    public function getInput() {
        return $this->s;
    }

    /**
     * Get one byte and move cursor forward
     * @return false|string (false if end has reached or the current byte)
     */
    public function getc() {
        if ($this->pos < strlen($this->s)) {
            $c = $this->s[$this->pos];
            $this->pos ++;
            if ($c == "\n") {
                $this->line ++;
                $this->char = 1;
            } else {
                $this->char ++;
            }
            return $c;
        }
        return false; // end has reached
    }

    /**
     * Unget one byte
     * @return false|string (false if the beginning has reached or the "ungotten" byte)
     */
    public function ungetc() {
        $this->pos --;
        if ($this->pos < 0) {
            $this->pos = 0;
            return false;
        }

        $c = $this->s[$this->pos];
        if ($c == "\n") {
            $this->line --;
            $this->char = $this->_line_length();
        } else {
            $this->char --;
        }

        return $c;
    }

    /**
     * Peek the next byte
     * @return false|string (same as get())
     */
    public function peek() {
        $c = $this->getc();
        $this->ungetc();
        return $c;
    }

    /**
     * Returns the current line number
     * @return integer
     */
    public function line() {
        return $this->line;
    }

    /**
     * Returns the char position in current line
     * @return integer
     */
    public function char() {
        return $this->char;
    }

    /**
     * Compute the length of the current line
     * @return integer
     * @access private
     */
    protected function _line_length() {

        // line length
        $len = 0;

        // move backward to find the beginning
        $pos = $this->pos - 1;
        while ($pos >= 0 && $this->s[$pos] != "\n") {
            $pos --;
            $len ++;
        }

        // move forward to find the end
        $pos = $this->pos;
        while ($this->s[$pos] != "\n" && $pos < strlen($this->s)) {
            $pos ++;
            $len ++;
        }

        return $len;
    }
}
