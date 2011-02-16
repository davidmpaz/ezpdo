<?php

/**
 * $Id: epParser.php 1009 2006-06-28 07:42:07Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1009 $ $Date: 2006-06-28 03:42:07 -0400 (Wed, 28 Jun 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */

/** 
 * need epComment to parse DocBlocks
 */
include_once(EP_SRC_COMPILER.'/epComment.php');

/** 
 * Need {@link epConfigurableWithLog} as the superclass 
 */
include_once(EP_SRC_BASE.'/epConfigurableWithLog.php');

/**
 * The exception class for {@link epClassParser}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1009 $ $Date: 2006-06-28 03:42:07 -0400 (Wed, 28 Jun 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
class epExceptionParser extends epExceptionConfigurableWithLog {
}

/**
 * Class of ezpdo parser
 * 
 * The parser parses ({@link epClassParser::parse()}) a PHP source file 
 * and extracts ezpdo tags in the annotated PHP source code to build 
 * class maps through {@link epClassMapFactory}. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1009 $ $Date: 2006-06-28 03:42:07 -0400 (Wed, 28 Jun 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
class epClassParser extends epConfigurableWithLog {
    
    /**
     * Tokens that needs to be processed
     * @var array
     * @access protected
     * @static
     */
    static protected $tokens_to_process = array(
        '{', 
        '}', 
        ';', 
        '=', 
        'T_ABSTRACT', 
        'T_CLASS', 
        'T_CONST', 
        'T_EXTENDS', 
        'T_FUNCTION', 
        'T_STRING', 
        'T_VARIABLE', 
        );
    
    /**
     * The current file being parsed
     * @var string
     */
    protected $file;
    
    /**
     * The classes to be parsed
     * If null, all classes in the file are parsed.
     * @var array
     */
    protected $classes_to_parse; 
    
    /**
     * The scanner used 
     * @var epScanner
     */
    protected $scanner;
    
    /**
     * The current token cached
     * @var array|string
     */
    protected $token;
    
    /**
     * The current comment
     * @var string
     */
    protected $comment;
    
    /**
     * The FSM for the lexer
     * @var FSM
     */
    protected $fsm;
    
    /**
     * The FSM payload (unused)
     * @var array
     */
    protected $fsm_payload;
    
    /**
     * The cached epClassMapFactory instance
     * @var epClassMapFactory
     */
    protected $cmf;
    
    /**
     * The current class map we are working on
     * @var epClassMap
     */
    protected $cm;
    
    /**
     * If the class is abstract
     * has to be stored here since we know it is abstract
     * before we have a class map
     * @var bool
     */
    protected $abstract = false;
    
    /**
     * Indicator whether to skip the current class
     * @var bool
     */
    protected $skip_class = false;
        
    /**
     * Constructor
     * @param epConfig|array 
     * @access public
     * @see epConfig
     */
    public function __construct($config = null) {
        parent::__construct($config);
    }
    
    /**
     * Implement abstract method {@link epConfigurable::defConfig()}
     * @access public
     */
    public function defConfig() {
        return array();
    }
    
    /**
     * The major task of a parser: parse a file and build class maps
     * @param string $file file to be parsed
     * @param array classes to be parsed (all if not specified)
     * @return bool 
     * @access public
     * @see token_get_all() 
     */
    public function parse($file, $classes = null) { 
        
        // get instance of class map factory
        $this->cmf = epClassMapFactory::instance();
        
        // check if file exists
        if (!file_exists($file)) {
            throw new epExceptionParser('File [' . $file . '] does not exist.');
            return false;
        }
        
        // get file content
        $content = file_get_contents($file);
        if (empty($content)) {
            throw new epExceptionParser('File [' . $file . '] is empty.');
            return false;
        }

        // set the classes to be parsed
        $this->classes_to_parse = $classes;
        
        // keep track of the current file and tokens
        $this->file = $file;
        
        // reset comment
        $this->comment = '';
        
        // setup scanner
        if (!$this->scanner) {
            include_once(EP_SRC_COMPILER.'/epScanner.php');
            $this->scanner = new epScanner;
        }
        
        // setup FSM
        if (!$this->setupFSM()) {
            throw new epExceptionParser('Failed to setup FSM. Quit parsing.');
            return false;
        }
        
        // set input content to scanner
        $this->scanner->input($content);
        
        // go through tokens
        while (false !== ($this->token = $this->scanner->next())) {
            
            // get token name 
            $token_name = $this->token;
            if (is_array($this->token)) {
                $token_name = token_name($this->token[0]);
            }
            
            // intercept comments
            if ($token_name == 'T_COMMENT' || $token_name == 'T_DOC_COMMENT') {
                $this->comment = $this->token[1];
                continue;
            }
            
            // drive FSM with token name we care
            if (!in_array($token_name, epClassParser::$tokens_to_process)) {
                continue;
            }
            
            $this->fsm->process($token_name);
        }
        
        return true;
    }
    
    /**
     * Setup the FSM 
     * @return bool
     * @access private
     */
    private function setupFSM() {
        
        $this->fsm_payload = array();
        
        //include_once('FSM.php');
        //$this->fsm = new FSM('PS_0', $this->fsm_payload); 
        include_once(EP_LIBS_PEAR . '/FSM.php');
        $this->fsm = new epLib_FSM('PS_0', $this->fsm_payload); 
        if (!$this->fsm) {
            throw new epExceptionParser('Internal error: cannot create FSM for parser');
            return false;
        }
        
        /**
         * Add transitions into FSM
         * (Order matters)
         */
        // abstract class <class_name> extends <super_class> {
        $this->addTransition('T_ABSTRACT', 'PS_0',                  'PS_ABSTRACT',           'classAbstractHandler');
        $this->addTransition('T_CLASS',    'PS_ABSTRACT',           'PS_CLASS');
        $this->addTransition('T_CLASS',    'PS_0',                  'PS_CLASS');
        $this->addTransition('T_STRING',   'PS_CLASS',              'PS_CLASS_NAME',         'classHandler');
        $this->addTransition('T_EXTENDS',  'PS_CLASS_NAME',         'PS_CLASS_EXTENDS');
        $this->addTransition('T_STRING',   'PS_CLASS_EXTENDS',      'PS_CLASS_EXTENDS_NAME', 'classExtendsHandler');
        $this->addTransition('{',          'PS_CLASS_EXTENDS_NAME', 'PS_CLASS_{');
        $this->addTransition('{',          'PS_CLASS_NAME',         'PS_CLASS_{');
        
        // class method 
        $this->addTransition('T_FUNCTION', 'PS_CLASS_{',      'PS_CLASS_FUNC');
        $this->addTransition('{',          'PS_CLASS_FUNC',   'PS_CLASS_FUNC_{', 'classFuncHandler');
        $this->addTransition('}',          'PS_CLASS_FUNC_{', 'PS_CLASS_{');
        
        // class variable 
        $this->addTransition('T_VARIABLE', 'PS_CLASS_{',     'PS_CLASS_VAR',   'classVarHandler');
        $this->addTransition(';',          'PS_CLASS_VAR',   'PS_CLASS_{');
        $this->addTransition('=',          'PS_CLASS_VAR',   'PS_CLASS_VAR_=');
        $this->addTransition(';',          'PS_CLASS_VAR_=', 'PS_CLASS_{',     'classVarDefValHandler');
        
        // end of class 
        $this->addTransition('}',          'PS_CLASS_{',     'PS_0', 'classEndHandler');
        
        //setup default transition only for debugging
        //$this->fsm->setDefaultTransition('PS_0', array($this, 'errorHandler'));
        
        return true;
    }

    public function classAbstractHandler($symbol, $payload) {
        $this->abstract = true;
    }
    
    /**
     * Handles PS_CLASS_NAME
     * @param string $symbol
     * @param mixed $payload (unused)
     * @return void
     */
    public function classHandler($symbol, $payload) {
        
        // get the class name
        $class = $this->token[1];
        if (!$class) {
            throw new epExceptionParser('Empty class name in parsing');
            return;
        }
        
        // check if we need to parse this class
        if ($this->_skipClass($class)) {
            // reset current cm and skip if not
            $this->skip_class = true;
            return;
        }
        
        // build a class map with class name
        if (!($cm = & $this->cmf->make($class))) {
            throw new epExceptionParser('Cannot create class map for ' . $class);
            return;
        } 
        
        // use class name as default table name
        $table = $class;

        // append prefix if specified to table name
        if ($prefix = $this->getConfigOption('table_prefix')) {
            $table = epUnquote($prefix) . $table;
        }

        // set default table name
        $cm->setTable($table);

        // set default DSN 
        $cm->setDsn($this->getConfigOption('default_dsn'));

        // set default oid column name
        $cm->setOidColumn($this->getConfigOption('default_oid_column'));
        
        // set compile time to now
        $cm->setCompileTime();

        // remove all fields
        $cm->removeAllFields();

        // set the indexes and uniques to empty
        $cm->setIndexKeys();
        $cm->setUniqueKeys();

        // set the class path
        $cm->setClassFile($this->file);
        
        // set the abstract of the class
        $cm->setAbstract($this->abstract);
        // reset abstract
        $this->abstract = false;
        
        // log parsing class start
        $this->log('Parsing class [' . $class . ']', epLog::LOG_INFO);
        
        // parse the class comment
        $this->parseClassComment($cm, $this->comment);

        // reset the comment (fix bug #28)
        $this->comment = '';
        
        // set the current class map
        $this->cm = & $cm;
        
        // set skip_class to false so the follow-up event handler do their work
        $this->skip_class = false;
    }
    
    /**
     * Handles PS_CLASS_EXTENDS_NAME 
     * @param string $symbol
     * @param mixed $payload (unused)
     * @return void
     */
    public function classExtendsHandler($symbol, $payload) {
        
        // skip the current class?
        if ($this->skip_class) {
            return;
        }
        
        // get the parent class name
        $super_class = $this->token[1];
        if (!$super_class) {
            throw new epExceptionParser('Empty superclass name in parsing');
            return;
        }
        
        // build a class map with class name
        $cm_super = & $this->cmf->make($super_class);
        
        // warn if class map not created (should not happen)
        if (!$cm_super) {
            throw new epExceptionParser('Cannot create class map for ' . $class);
            return;
        } 
        
        // set parent-child
        $this->cm->setParent($cm_super);
        $cm_super->addChild($this->cm);
    }
    
    /**
     * Handles class var PS_CLASS_VAR
     * @param string $symbol
     * @param mixed $payload (unused)
     * @return void
     */
    public function classVarHandler($symbol, $payload) {
        
        // skip the current class?
        if ($this->skip_class) {
            return;
        }
        
        // get var name
        $var = $this->token[1]; 
        
        // remove $
        $var = str_replace('$', '', $var);
        
        // parse var comment into field map
        $fm = false;
        if ($this->comment) {
            
            // parse the var's comment for orm tag value
            $fm = $this->parseVarComment($var, $this->comment);

            // reset the comment (fix bug #28)
            $this->comment = '';
        }

        // add field map into the class map if it has type set
        if ($fm && $fm->getType()) {
            $this->cm->addField($fm);
        }
    }
    
    /**
     * Eat away all tokens until the end of the function
     * @param string $symbol
     * @param mixed $payload (unused)
     * @return void
     */
    public function classFuncHandler($symbol, $payload) {
        
        // skip the current class?
        if ($this->skip_class) {
            return;
        }
        
        // { level in class method
        $level = 1;
        
        // go through tokens
        while (false !== ($this->token = $this->scanner->next())) {
            
            // get token name 
            $token_name = $this->token;
            if (is_array($this->token)) {
                $token_name = token_name($this->token[0]);
            }

            // check if we have reached the end of the function
            if ($token_name == "{") {
                $level ++; 
            } else if ($token_name == "}") {
                $level --; 
            }
            
            // break if we have reached the end of the function
            if ($level == 0) {
                // !!!IMPORTANT!!! back one level to force FSM into "PS_CLASS_{" state
                $this->scanner->back();
                break;
            }
        }
        
    }

    /**
     * Handles class var PS_CLASS_VAR_DEFVAL
     * @param string $symbol
     * @param mixed $payload (unused)
     * @return void
     * @todo To be implemented
     */
    public function classVarDefValHandler($symbol, $payload) {
        
        // skip the current class?
        if ($this->skip_class) {
            return;
        }
        
        // placeholder
    }
    
    /**
     * Handles end of class ("}")
     * @param string $symbol
     * @param mixed $payload (unused)
     * @return void
     */
    public function classEndHandler($symbol, $payload) {
        
        // skip the current class?
        if ($this->skip_class) {
            return;
        }
        
        // log parsing class end
        $this->log('Parsing class [' . $this->cm->getName() . '] - end.', epLog::LOG_INFO);
    }
    
    /**
     * FSM Error handler (for debugging only). Called whenever the 
     * processing routine cannot find a better match for the current 
     * state and symbol.
     * @param string $symbol
     * @param mixed $payload (unused)
     */
    public function errorHandler($symbol, $payload) {
        // error handler
        epVarDump($this->token);
    }
    
    /**
     * A wrapper around FSM::addTransition()
     * @param string $symbol
     * @param string $state
     * @param string $nextState
     * @param string $action
     * @return void
     */
    private function addTransition($symbol, $state, $nextState, $action = '') { 
        if (!$action) {
            $this->fsm->addTransition($symbol, $state, $nextState);
        } else {
            $this->fsm->addTransition($symbol, $state, $nextState, array($this, $action));
        }
    }
    
    /**
     * Parse the comment of the class
     * @param epClassMap the class map 
     * @param string the comment of the class
     * @return bool
     */
    protected function parseClassComment(&$cm, $comment) {
        
        if (!($c = new epComment($comment))) {
            throw new epExceptionParser('Cannot parse comment for class [' . $cm->getName() . ']');
            return false;
        }
        
        // always harvest 'raw' customer tags
        $cm->setTags($c->getTags());

        if (!($value = $c->getTagValue('orm'))) {
            return true;
        }
        
        if (!($t = new epClassTag)) {
            throw new epExceptionParser('Cannot parse @orm tag for class [' . $cm->getName() . ']');
            return false;
        }

        if (!$t->parse($value)) {
            throw new epExceptionParser('Cannot parse @orm tag for class [' . $cm->getName() . ']');
            return false;
        }
        
        // database table name
        if ($table = $t->get('table')) {
            // append prefix to table name
            if ($prefix = $this->getConfigOption('table_prefix')) {
                $table = epUnquote($prefix) . $table;
            }
            $cm->setTable($table);
        } 
        
        // dsn of the database
        if ($dsn = $t->get('dsn')) {
            $cm->setDsn($dsn);
        } else {
            $cm->setDsn($this->getConfigOption('default_dsn'));
        }

        // oid column of the class
        if ($oid = $t->get('oid')) {
            $cm->setOidColumn($oid);
        } else {
            $cm->setOidColumn($this->getConfigOption('default_oid_column'));
        }

        return true;
    }
    
    /**
     * Parse the comment of the var (field)
     * @param string $var the name of the var
     * @param string $comment the comment associated to the var
     * @return epFieldMap
     */
    protected function parseVarComment($var, $comment) {
        
        $class_var = $this->cm->getName() . '::' . $var;
        
        // parse var comment
        $c = new epComment($comment);
        if (!$c) {
            throw new epExceptionParser('Cannot parse comment for var [' . $class_var . ']');
            return false;
        }
        
        // get the @orm tag value
        if (!($value = $c->getTagValue('orm'))) {
            //warn('No @orm tag for var [' . $class_var . ']. Ignored.');
            return false;
        }
        
        // parse var tag
        if (!($t = new epVarTag)) {
            throw new epExceptionParser('Cannot parse @orm tag for var [' . $class_var . ']');
            return false;
        }

        $error = $t->parse($value);
        if (is_string($error)) {
            throw new epExceptionParser('Error in parsing @orm tag for var [' . $class_var . ']: ' . $error);
            return false;
        }

        // call field map factory to create a field map
        if (!($fm = epFieldMapFactory::make($var, $t->get('type'), $t->get('params')))) {
            return false;
        }

        // always harvest 'raw' customer tags
        $fm->setTags($c->getTags());

        // set column name if set
        if ($column_name = $t->get('name')) {
            $fm->setColumnName($column_name);
        }
        
        // get key type
        if ($key_type = $t->get('keytype')) {

            // get key name
            if (!($key_name = $t->get('keyname'))) {
                $key_name = $var;
            }

            switch($key_type) {
                case 'unique':
                    $this->cm->addUniqueKey($key_name, $var);
                    break;
                case 'index':
                    $this->cm->addIndexKey($key_name, $var);
                    break;
            }
        }
        
        return $fm;
    }

    /** 
     * Check if class should be parsed
     * @param string $class the name of the class to be checked
     * @return bool
     * @access protected 
     */
    protected function _skipClass($class) {
        return $this->classes_to_parse && !in_array($class, $this->classes_to_parse);
    }
    
}

?>
