<?php

/**
 * $Id: epScanner.php 622 2005-11-27 11:51:28Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 622 $ $Date: 2005-11-27 06:51:28 -0500 (Sun, 27 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */

/**
 * The exception class for {@link epScanner}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 622 $ $Date: 2005-11-27 06:51:28 -0500 (Sun, 27 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
class epExceptionScanner extends epExceptionConfigurableWithLog {
}

/**
 * Class of a word scanner
 * 
 * The scanner tokenizes input content ({@link input()}) 
 * and provides a "scanner" interface which can go forward 
 * ({@link next()}) and backward (({@link back()}) ) 
 * along the tokens. It also correlates the tokens with line 
 * number ({@link line()}). 
 * 
 * Part of the credits for this scanner is due to phpDocumentor,
 * from which I borrowed some ideas to make it work. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 622 $ $Date: 2005-11-27 06:51:28 -0500 (Sun, 27 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
class epScanner {

    /**
     * List of tokens that can contain a newline
     * @var array
     */
    static public $newline_tokens = array(
        T_WHITESPACE,
        T_ENCAPSED_AND_WHITESPACE,
        T_COMMENT,
        T_DOC_COMMENT,
        T_OPEN_TAG,
        T_CLOSE_TAG,
        T_INLINE_HTML
        );
    
    /**
     * The input content
     * @var string 
     */
    protected $input;
    
    /**
     * tokenized array from {@link token_get_all()}
     * @var array
     */
    protected $tokens;
    
    /**
     * current token position
     * @var integer
     */
    protected $pos = 0;
    
    /**
     * current source line number 
     * @var integer
     */
    protected $line = 0;

    /**
     * Constructor
     * @param string input content
     */
    public function __construct($input = '') {
        if (!empty($input)) {
            $this->input($input);
        }
    }
    
    /**
     * get input if no param supplied or set input if param is a non-empty string
     * @param string input content
     * @return string|bool 
     */
    public function input($input = false) {
        
        // if input is false, return 
        if ($input === false) {
            return $this->input;
        }
        
        // trim the \r\n input content
        $input = rtrim(ltrim($input, "\r\n"));
        if (empty($input)) {
            return false;
        }
        
        // use reference to save memory
        $this->input = & $input;
        
        // unset the tokens so when next() is called the frist 
        // time, it will call reset()
        unset($this->tokens);
        
        return true;
    }
    
    /**
     * Tokenize input content and reset current 
     * token position and line number
     * @return void
     * @throws epExceptionScanner
     */
    public function reset() {

        // fix bug #81
        if (!function_exists('token_get_all')) {
            throw new epExceptionScanner('Tokenizer extension is not enabled.');
        }

        $this->tokens = @token_get_all($this->input);
        $this->pos = 0;
        $this->line = 0;
    }
    
    /**
     * Fetch the next token
     * @return string|array token from tokenizer
     */
    function next() {
        
        // check if we need to reset (tokenize input)
        if (empty($this->tokens)) {
            $this->reset();
        }
        
        // check if token at the cur position set
        if ( !isset($this->tokens[$this->pos]) ) {
            return false;
        }
        
        // keep track of the old line
        $oldline = $this->line;
        
        // now get the current token
        $word = $this->tokens[$this->pos++];
        
        // correlate line and token
        if ( is_array($word) ) {
            
            // count line num for special tokens ({@link $newline_tokens})
            if ( in_array($word[0], epScanner::$newline_tokens) ) {
                $this->line += substr_count($word[1], "\n");
            }
            
            // always skip whitespace
            if ( $word[0] == T_WHITESPACE )    {
                return $this->next();
            }
        }
        
        return $word;
    }
    
    /**
     * Go back one token (reverse of {@link next()})
     * @return false|string|array
     */
    public function back() {
        
        $this->pos --;
        
        // check if it's the beginning
        if ($this->pos < 0) {
            $this->pos = 0;
            return false;
        }
        
        $word = $this->tokens[$this->pos];
        
        if ( is_array($word) ) {
            
            if ( $word[0] == T_WHITESPACE )    
                return $this->next();
            
            if ( in_array($word[0], epScanner::$newline_tokens) ) {
                $this->line -= substr_count($word[1], "\n");
            }
        }

        return $word;
    }
    
    /**
     * Get the current line number
     * @return integer
     */
    public function line() {
        return $this->line;
    }

}

?>
