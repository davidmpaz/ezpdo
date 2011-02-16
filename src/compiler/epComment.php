<?php

/**
 * $Id: epComment.php 1013 2006-09-27 01:55:43Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */

/**
 * Class of a ezpdo comment block
 * 
 * The class takes comments in source code as the input and 
 * parses it into tag-value pairs. Usage:
 * <pre>
 * $c = new epComment($comment);
 * $c->getTagValue('var');
 * </pre>
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
class epComment {
    
    /**
     * The array that holds tag-values
     * @var array
     */
    protected $tag_values = array();
    
    /**
     * Constructor
     * @param string
     */
    public function __construct($comment) { 
        $this->parse($comment);
    }
    
    /**
     * Check if comment has a particular tag
     * @param string tag name
     * @return bool
     */
    public function hasTag($tag_name) {
        if ($tag_name) {
            return array_key_exists($tag_name, $this->tag_values);
        }
        return false;
    }
    
    /**
     * Returns all tags
     * @return array (tag-value pairs) 
     */
    public function getTags() {
        return $this->tag_values;
    }
    
    /**
     * Returns the value of a tag
     * @param string tag name
     * @return false|string false if tag not found or tag value (null if tag value not set)
     */
    public function getTagValue($tag_name) {
        if (!$this->hasTag($tag_name)) {
            return false;
        }
        return $this->tag_values[$tag_name];
    }
    
    /**
     * Preprocess comment (remove excessive space, comment boarder)
     * @param string the original comment
     * @return string the processed comment 
     */
    private function preproc($comment) {
    
        // remove comment boarders
        $comment = preg_replace(
    
            // patterns
            array(
                "/\n/",                // save our newlines, as they're considered part of '\s' in regex
                "/\s*\/+\**\s+/i",     // /* or /** or /*** or //*.. and trailing spaces
                "/^\s*\*\**\/?\s*/im", // *'s and trailing spaces on a new line
                "/\{\s*@\w*.*\}/i",    // ignore inline tags
                "/____ezpdonl____/",   // and then put the newlines back in
                ),

            // replacement
            array(
                "____ezpdonl____",
                " ", 
                " ", 
                "", 
                "\n"
                ), 
            
            $comment
            );
        
        return $comment;
    }
    
    /**
     * Parse the comment into tag-value array
     * @param string  
     * @return bool
     */
    private function parse($comment) {
        
        // check if comment is empty
        if (!$comment) {
            return false;
        }
        
        // preproc the comment
        $preproced = $this->preproc($comment);

        // split comments by line for processing
        $preproced = explode("\n", $preproced);
        
        foreach ($preproced as $line) {

            /**
             * split string into an array of tags and values. normally a
             * value follow a tag, but it's possible a tag does not have
             * a value following (ie an empty tag).
             */
            $pieces = preg_split("/(@\w+)\s+/", $line, -1, PREG_SPLIT_DELIM_CAPTURE);
            if (!$pieces) {
                return false;
            }

            // associate tags and values
            reset($pieces);
            $piece = next($pieces);
            do {
                
                // trim piece
                $piece = trim($piece);
                
                // is it a tag
                if (!$piece || !isset($piece[0]) || $piece[0] !== '@') {
                    $piece = next($pieces);
                    continue;
                }
                
                // process tag
                $tag = substr($piece, 1);
                
                // check if next piece is value
                $piece = next($pieces);
                
                // trim piece
                $piece = trim($piece);
                
                if (!$piece || $piece[0] === '@') {
                    // if the next value is a tag, no value for this tag
                    $this->tag_values[$tag] = null;
                } else {
                    $this->tag_values[$tag] = $piece;
                    $piece = next($pieces);
                }
                
            } while ($piece !== false);
        
        }
        
        return true;
    }

}

/**
 * Class to parse an ezpdo orm tag value 
 * 
 * The class takes the tag value as the input and dissects it 
 * into orm attributes. To get the attribute value, use {@link get()}. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 * @abstract
 */
abstract class epTag {
    
    /**
     * Attribute and values
     * @var array (keyed by attribute name)
     */
    protected $attrs;
    
    /**
     * Returnrs all orm attributes
     * @return array (of string) 
     */
    public function getAll() {
         return array_keys($this->attrs);
    }
    
    /**
     * Returns the value of an attribute
     * @param string attribute name
     * @return null|string
     */
    public function get($attr) {
        if (!isset($this->attrs[$attr])) {
            return null;
        }
        return $this->attrs[$attr];
    }

    /**
     * Parse the tag value string
     * @return bool
     */
    abstract public  function parse($value);
}

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
 * @subpackage ezpdo.compiler
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

/**
 * Class to parse an orm tag value of a variable
 * 
 * Three attributes for an orm tag for a variable
 * <ol>
 * <li>
 * name: the name of column the variable to be mapped to which can
 * be returned as null. if empty, the parser ({@link epClassParser}) 
 * uses the variable name as the column name. 
 * </li>
 * <li>
 * type: the type of column (see {@link epFieldMap::getSupportedTypes()}),
 * can be empty as well. If empty, the parser ({@link epClassParser}) 
 * tries to figure out the type by looking at other usual docblock tags.
 * The last resort is to treat it as a string. 
 * </li>
 * <li>
 * params: the params for the column type (can be empty) 
 * </li>
 * </ol>
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
class epVarTag extends epTag {

    /**
     * Impelment abstract method in {@link epTag}
     * Parse the tag value string
     * @return bool|array Error if array
     */
    public  function parse($value) {
        
        // sanity check
        if (!$value || !is_string($value)) {
            return false;
        }

        // test parser
        $p = new epTagParser($value);
        if (!$this->attrs = $p->parse()) {
            $errors = $p->errors();
            return $errors[0]->__toString();
        }
        
        return true;
    }
}

/** 
 * Need {@link epLexer} and {@link epParser} for epTagParser
 */
include_once(EP_SRC_BASE_PARSER.'/epParser.php');

/**#@+
 * Predefined tokens for the base lexer ({@link epLexer})
 */
epDefine('EPL_T_HAS');
epDefine('EPL_T_COMPOSED_OF');
epDefine('EPL_T_DATA_TYPE');
epDefine('EPL_T_OID');
epDefine('EPL_T_ONE');
epDefine('EPL_T_MANY');
epDefine('EPL_T_INDEX');
epDefine('EPL_T_INVERSE');
epDefine('EPL_T_UNIQUE');
/**#@-*/

/**
 * The lexer for ORM tag value
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
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

/**
 * The parser for ORM tag value
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
class epTagParser extends epParser {
    
    /**
     * The current field map params
     * @var array
     */
    protected $map = array();

    /**
     * Constructor
     * @param string $s
     * @param boolean $v(verbose)
     */
    public function __construct($s = '', $verbose = false) {
        $this->verbose($verbose);
        $this->errors = array();
        $this->_lexer = new epTagLexer($s);
    }

    /**
     * Parses the stream and return the root node
     * @param bool is_class Whether parsing a class map or not 
     * @return false
     */
    public function parse($is_class = false) {
        $this->message(__METHOD__);
        
        // is query empty?
        if ($this->peek() === false) {
            $this->error('Empty input');
            $this->message('parsing done');
            return false;
        }
        
        // parse and build field map
        $status = $this->fieldmap();
        $this->message('parsing done');
        if (!$status) {
            return false;
        }

        // return the parsed result
        return $this->map;
    }

    /**
     * Parse the tag statement
     * @return bool
     */
    protected function fieldmap() {
        
        $this->message(__METHOD__);

        // column name?
        if (($t = $this->peek()) == EPL_T_IDENTIFIER) {
            $this->map['name'] = $this->p->value;
            $this->next();
            $t = $this->peek();
        }

        // primitve data type?
        if ($t == EPL_T_DATA_TYPE) {
            return $this->primitive();
        }

        // has or composed_of?
        if ($t == EPL_T_HAS || $t == EPL_T_COMPOSED_OF) {
            return $this->relationship();
        }

        // error o.w.
        $this->syntax_error("Invalid var @orm tag: " . $this->getInput());

        return false;
    }

    /**
     * Parse primitive definition
     * @return bool
     */
    protected function primitive() {
        
        $this->message(__METHOD__);
        
        // expect an identifier
        if (EPL_T_DATA_TYPE != ($t = $this->peek())) {
            $this->syntax_error("Supported data type expected");
            return false;
        }

        // consume token
        $this->next();

        // create a primitive field map
        $this->map['type'] = $this->t->value;

        // check if '(' follows
        if ($this->peek() == '(') {
            if (false === ($params = $this->params())) {
                $this->syntax_error("Invalid parameters for [".$this->t->value."]");
                return false;
            }
            $this->map['params'] = join(',', $params);
        }

        // index or unique?
        $t = $this->peek();
        if ($t == EPL_T_INDEX || $t == EPL_T_UNIQUE) {
            
            // consume index or unique
            $this->next();
            
            // get key type
            $this->map['keytype'] = $keytype = $this->t->value;

            // get key name in ()
            if ($this->peek() == '(') {
                $params = $this->params();
                if (!$params || count($params) != 1) {
                    $this->syntax_error("Invalid parameter for " . $keytype);
                    return false;
                }
                $this->map['keyname'] = $params[0];
            }
            // nothing should exist beyond 'unique/index()'?
            else if ($this->peek() !== false) {
                $this->syntax_error("Invalid var @orm tag: " . $this->getInput());
                return false;
            }
        }

        return true;
    }

    /**
     * Parse a relatinship definition
     */
    protected function relationship() {
        
        $this->message(__METHOD__);

        $t = $this->peek();
        if ($t != EPL_T_HAS && $t != EPL_T_COMPOSED_OF) {
            $this->syntax_error("'has' or 'composed_of' is expected");
            return false;
        }
        $this->next();

        // fix bug 179: allow date type keywords to be class names
        $this->_lexer->toggleDataTypeTokens();

        // get type
        $type = epFieldMap::DT_HAS;
        if ($t == EPL_T_COMPOSED_OF) {
            $type = epFieldMap::DT_COMPOSED_OF;
        }

        // create a relationship field map
        $this->map['type'] = $type;
        $this->map['params'] = array();

        // one?
        $this->map['params']['is_many'] = false;
        if ($this->peek() == EPL_T_ONE) {
            $this->next();
        } 
        // many?
        else if ($this->peek() == EPL_T_MANY) {
            $this->next();
            $this->map['params']['is_many'] = true;
        }

        // class
        $this->map['params']['class'] = false;
        if ($this->peek() == EPL_T_IDENTIFIER) {
            $this->next();
            $this->map['params']['class'] = $this->t->value;
        } else {
            $this->syntax_error("Class name is expected");
            return false;
        }
        
        // toggle data types back
        $this->_lexer->toggleDataTypeTokens();

        // inverse
        $this->map['params']['inverse'] = false;
        if ($this->peek() == EPL_T_INVERSE) {
            
            // consume inverse
            $this->next();
            
            // get inverse parameters
            $params = $this->params();
            if (!$params || count($params) != 1) {
                $this->syntax_error("Invalid parameters for inverse");
                return false;
            }
            $this->map['params']['inverse'] = $params[0];
        }

        return true;
    }

    /**
     * Reads params within parenthesis
     * @return array
     */
    protected function params() { 
        
        $this->message(__METHOD__);
        
        if ('(' != $this->peek()) {
            $this->syntax_error("'(' expected");
            return false;
        }
        $t = $this->next();
        
        // get all params
        $params = array();
        do {
            
            // eat ','
            if ($t == ',') {
                $this->next();
            }

            // expect an identifier or a number
            $t = $this->peek();
            if ($t != EPL_T_IDENTIFIER && $t != EPL_T_FLOAT && $t != EPL_T_INTEGER) {
                $this->syntax_error("String or number is expected");
                return false;
            }

            // consume token
            $this->next();

            // get this param (string)
            $params[] = $this->t->value;
            
        } while (($t = $this->peek()) == ',');

        // consume the closing ')'
        if ($t == ')') {
            $this->next();
        }

        return $params;
    }

}

?>
