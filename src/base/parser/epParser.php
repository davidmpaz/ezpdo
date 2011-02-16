<?php

/**
 * $Id: epParser.php 945 2006-05-12 19:34:14Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 945 $
 * @package ezpdo
 * @subpackage ezpdo.parser
 */

/**#@+
 * need epLexer
 */
include_once(EP_SRC_BASE_PARSER.'/epLexer.php');
/**#@-*/

/**
 * A base class for a recursive-descent parser (see 
 * {@link http://en.wikipedia.org/wiki/Recursive_descent_parser}).
 * 
 * The class provides nuts and bolts for the implementation
 * of a full-blown parser: lexer hookup, token navigation, 
 * error reporting and debugging methods.
 * 
 * The class is used as the base class for the EZOQL query parser
 * ({@link epQueryParser}) and the ORM tag parser ({@link 
 * epTagParser}).
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 945 $
 * @package ezpdo
 * @subpackage ezpdo.parser
 */
abstract class epParser extends epBase {
    
    /**
     * The lexer the parser needs to call
     * @var epQueryLexer
     */
    protected $_lexer = false;
    
    /**
     * Array to keep all errors
     * @var array 
     */
    protected $errors = array();

    /**
     * The current error
     * @var epQueryError
     */
    protected $_error = false;

    /**
     * Is the parse in verbose mode?
     * @var boolean
     */
    protected $_verbose = false;

    /**
     * The current token 
     * @var null|false|epToken
     */
    protected $t = false;
    
    /**
     * The peeked token 
     * @var null|false|epToken
     */
    protected $p = false;
    
    /**
     * Constructor
     * @param string $s
     * @param boolean $v(verbose)
     */
    public function __construct($s = '', $verbose = false) {
        $this->initialize($s);
        $this->verbose($verbose);
    }

    /**
     * Initialize the parser
     * @param string $s
     * @param boolean $force(force to reset errors)
     */
    public function initialize($s) {
        
        // set string to lexer
        if (!$this->_lexer) {
            $this->_lexer = new epLexer($s);
        } else {
            $this->_lexer->initialize($s);
        }

        // clear errors
        $this->errors = array();
    }

    /**
     * Returns the string being parsed
     * @return false|string
     */
    public function getInput() {
        if (!$this->_lexer) {
            return false;
        }
        return $this->_lexer->getInput();
    }

    /**
     * Set or unset parser to be in verbose mode
     * @return boolean
     */
    public function verbose($v = null) {
        if (!is_null($v)) {
            $this->_verbose = $v;
        }
        return $this->_verbose;
    }
    
    /**
     * Returns the errors raised
     * @return array
     */
    public function errors() {
        return $this->errors;
    }
    
    /**
     * Parses the stream and return the root node
     * @return false|epQueryNode
     */
    abstract public function parse($s = '');

    /**
     * Returns the type of the next token
     * @return false|string(false if eof)
     */
    protected function next() {
        $this->t = $this->_lexer->next();
        // no new lines
        while ($this->t && $this->t->value == "\n") {
            $this->t = $this->_lexer->next();
        }
        return $this->_t();
    }

    /**
     * Returns the type of the previous token
     * @param boolean $ignore_nl (wether to ignore new line)
     * @return false|string(false if beginning is reached)
     */
    protected function back() {
        $this->t = $this->_lexer->back();
        // skip new lines
        while ($this->t && $this->t->value == "\n") {
            $this->t = $this->_lexer->back();
        }
        return $this->_t();
    }

    /**
     * Peek the type of the next token
     * @return epToken
     */
    protected function peek() {
        // peek the next token
        $t = $this->_lexer->peek();
        // ignore new lines 
        while ($t && ($t->value == "\n")) {
            $this->_lexer->next();
            $t = $this->_lexer->peek();
        }
        // set up peeked token
        return $this->_p($t);
    }

    /**
     * Raise an error message.
     * 
     * The error message is stored in an array that can be 
     * retrieved later.
     * 
     * This method can also be used to retrieve the curernt 
     * error if you don't give any argument. 
     * 
     * @param string $msg
     * @return epQueryError
     */
    protected function error($msg = '') {
        
        // if empty message, return the current error 
        if (!$msg) {
            return $this->_error;
        }

        // create a new error object
        $v = ''; $l = 0; $c = 0;
        if ($this->t) {
            $v = $this->t->value;
            $l = $this->t->line;
            $c = $this->t->char;
        } 
        $this->_error = new epLexerError($msg, $v, $l, $c);

        // keep all errors
        $this->errors[] = $this->_error;
        
        // print out error message
        $emsg = $msg . ' at EOF';
        if ($this->t && $this->t->type !== false) {
            $emsg = $this->_error->__toString();
        }
        $this->message("Error: $emsg");
                       
        // return this error
        return $this->_error;
    }

    /**
     * Raise syntax error
     * @param string $msg
     * @return void
     * @access private
     */
    protected function syntax_error($msg = 'unspecified') {
        $this->error('syntax error (' . $msg . ')');
    }
    
    /**
     * output message in verbose mode
     * @return void
     * @access private
     */
    protected function message($msg, $force = false) {
        if ($force || $this->_verbose) {
            // remove class identifier from method names
            $msg = str_replace(__CLASS__ . '::', '', $msg);
            echo $msg . "\n";
        }
    }

    /**
     * Process current token
     * @return false|string(type of the current token type)
     * @access private
     */
    protected function _t() {

        // debug
        if ($this->_verbose) {
            $msg = "EOF";
            if ($this->t) {
                $msg = $this->t->type . " (". $this->t->value . ") @ ";
                $msg .= "(" . $this->t->line . ", " . $this->t->char . ")";
            }
            $this->message(__METHOD__ . ": " . $msg);
        }
        
        return $this->t->type;
    }

    /**
     * Process token being peeked
     * @return false|string(type of the current token type)
     * @access private
     */
    protected function _p($t) {

        $this->p = $t;

        // no token?
        if (!$t) {
            // debug
            if ($this->_verbose) {
                $this->message(__METHOD__ . ": EOF");
            }
            return false;
        }

        // debug 
        if ($this->_verbose) {
            $msg = "EOF";
            if ($t->type) {
                $msg = "$t->type ($t->value) @ ($t->line, $t->char)";
            }
            $this->message(__METHOD__ . ": " . $msg);
        }

        return $t->type;
    }

}

?>
