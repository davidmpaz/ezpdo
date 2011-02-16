<?php

/**
 * $Id: epCompiler.php 1028 2006-12-28 11:15:37Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1028 $ $Date: 2006-12-28 06:15:37 -0500 (Thu, 28 Dec 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */

/** 
 * Need {@link epConfigurableWithLog} as the superclass 
 */
include_once(EP_SRC_BASE.'/epConfigurableWithLog.php');

/** 
 * Need class map factory 
 */
include_once(EP_SRC_ORM.'/epClassMap.php');

/**
 * The exception class for {@link epClassCompiler}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1028 $ $Date: 2006-12-28 06:15:37 -0500 (Thu, 28 Dec 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
class epExceptionCompiler extends epException {
}

/**
 * Class of EZPDO class compiler
 * 
 * The compiler is responsible for parsing PHP source files and
 * generating runtime configuratoins (class and field maps).
 * 
 * There are two steps involved.
 * <ol>
 * <li>
 * Parse the annotated (commented with @orm tag) class source 
 * code, interpret the orm tags and create class maps through 
 * {@link epClassMapFactory}.
 * </li>
 * <li>
 * Walk though all class maps ({@link epClassMap}) created 
 * and generate runtime ORM configuration (which will be used 
 * by the runtime persistence manager {@link epManager}).
 * </li>
 * </ol>
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1028 $ $Date: 2006-12-28 06:15:37 -0500 (Thu, 28 Dec 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
class epClassCompiler extends epConfigurableWithLog {
    
    /**
     * The input files (transient)
     * @var array
     */
    protected $input_files;
    
    /**
     * The output directory (transient)
     * @var string
     */
    protected $compiled_dir;
    
    /**
     * The parser used for compiling
     * @var epClassParser
     */
    protected $parser;
    
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
        return array(
            "source_dirs" => "", // comma-seperated
            "source_exts" => "php", // comma-seperated
            "recursive" => true, // whether to read files recursively
            "compiled_dir" => ".", // default to pwd
            "default_dsn" => 'mysql://ezpdo:secret@localhost/ezpdo', // the default dns
            "default_oid_column" => 'oid', // the default oid column
            "compiled_file" => 'compiled.ezpdo', // the default class map file
            "backup_compiled" => true, // whether to backup old compiled file
            "force_compile" => false, // whether to force compile classes
            "validate_after_compile" => true, // whether to validate class maps after compile
            "warn_non_source_files" => false, // whether to warn on non_source files
            "table_prefix" => '', // table prefix
            );
    }
    
    /**
     * Preparation before compiling
     * 
     * <ol>
     * <li>
     * If a valid parameter is given, either an object or a class name, search 
     * for the source file(s) of the class.Otherwise, check input directories
     * from config and collect files to compile.
     * </li>
     * <li>
     * Check output directory; backup/create if necessary.
     * </li>
     * </ol>
     * 
     * Note that the list of input files are kept in data member 
     * {@link $input_files} and output dir is kept in {@link 
     * $compiled_dir}. If either is not set, this method should be 
     * called. 
     * 
     * @param object|string $o object or class name
     * @return bool
     * @throws epExceptionCompiler
     * @access private
     */
    protected function initialize($o = null) {
        
        // collect input files
        if (!($this->input_files = $this->getInputFiles($o))) {
            
            // for an object, we must have input files
            if ($o) {
                return false;
            }
            
            // otherwise, okay
            return true;
        } 
        
        // check output directories and collect files to compile
        if (!($this->compiled_dir = $this->prepareOutputDir())) {
            throw new epExceptionCompiler('Output directory not ready');
            return false;
        }
        
        return true;
    }
    
    /**
     * Collect files to compile
     * 
     * If a valid parameter is given, either an object or a class name, search 
     * for the source file(s) of the class.Otherwise, check input directories
     * from config and collect files to compile.
     * 
     * @param object|string $o object or a class name
     * @return false|array
     * @throws epExceptionCompiler
     */
    protected function getInputFiles($o = null) {
        
        // no input parameter?
        if (!$o) {
            // get input files from config
            return $this->getConfiguredInputFiles(
                $this->getConfigOption('recursive'), // get file recursively
                !$this->getConfigOption('force_compile') // only new file if not to force compile
                );
        }
        
        // check if $o is valid
        if (!is_string($o) && !is_object($o)) {
            throw new epExceptionCompiler('Invalid input parameter (should be object or string)');
            return false;
        }
        
        // get class name of the object
        $class = '';
        if (is_object($o)) {
            $class = get_class($o);
        } else {
            $class = $o;
        }
        
        // get source files for the class and its parent classes
        return $this->getInputFilesForClass($class);
    }
    
    /**
     * Collect files under configured directories
     * @return false|array 
     * @throws epExceptionCompiler
     */
    protected function getInputFilesForClass($class) {
        
        // check if class exists? if not, include all source files. 
        // fix bug #58: EZPDO should locate class file more aggressively
        if (!class_exists($class)) {
            // true: recursive, true: only new files
            if ($src_files = $this->getConfiguredInputFiles(true, true)) {
                // include src files one by one
                foreach($src_files as $src_file) {
                    include_once($src_file);
                }
            }
        }
        
        // double check if class is loaded
        if (!class_exists($class)) {
            throw new epExceptionCompiler('Class [' . $class . '] does not exist.');
            return false;
        }

        // work on the parent classes
        $files = array();
        if ($parent_class = get_parent_class($class)) {
            $parent_files = $this->getInputFilesForClass($parent_class);
            if ($parent_files !== false) {
                $files = array_merge($files, $parent_files);
            }
        }
        
        // get the class reflectcion 
        if (!($r = new ReflectionClass($class))) {
            throw new epExceptionCompiler('Cannot create reflection for class [' . $class . ']');
            return false;
        }
        
        // get the file name
        if (!($file = $r->getFileName())) {
            throw new epExceptionCompiler('Cannot get file name for class [' . $class . ']');
            return false;
        }
        
        // put file into array $input_files
        $files[] = $file;
        
        // weed out dup file names
        $files = array_merge($files);
        
        return $files;
    }
    
    /**
     * Collect files under configured directories
     * @param boolean $recursive whether to get files recursively
     * @param boolean $new_only only get new files 
     * @return boolean
     * @throws epExceptionCompiler
     */
    protected function getConfiguredInputFiles($recursive = false, $new_only = false) {
        
        // check input directories and collect files to compile
        if (!($source_dirs = $this->getConfigOption('source_dirs'))) {
            throw new epExceptionCompiler('No input directories specified');
            return false;
        }
        
        // clean up existing collection
        $input_files = array();
        
        // collect files in each input dir
        $source_dirs = explode(',', $source_dirs);
        foreach($source_dirs as $input_dir) {
            
            // if input dir is a relative path, make is absolute 
            $input_dir = $this->getAbsolutePath($input_dir); 

            // check if path is dir
            if (!is_dir($input_dir)) {
                throw new epExceptionCompiler('Input path [' . $input_dir . '] is not a direcotry. Ignored.');
                continue;
            }
            
            // check if dir exists
            if (!file_exists($input_dir)) {
                throw new epExceptionCompiler('Input path [' . $input_dir . '] does not exist. Ignored.');
                continue;
            }
            
            // collect all files in dir
            $files_in_dir = epFilesInDir($input_dir, $recursive, true); // true: absolute path 
            if ($files_in_dir) {
                $input_files = array_merge($input_files, $files_in_dir);
            }
        }

        // weed out non-source files
        $input_files = $this->getOnlySourceFiles($input_files);

        // if force_compile is disabled, only parse new files  
        if ($new_only) {
            $input_files = $this->getNewFiles($input_files);
        }

        return $input_files;
    }
    
    /**
     * Filter a given array of input files and returns only 
     * the newly modifed after last compile and those files 
     * that have not been compiled 
     * 
     * @param array $input_files
     * @return array
     */
    protected function getNewFiles($input_files) { 
        
        // get class map factory
        if (!($cmf = epClassMapFactory::instance())) {
            return $input_files;
        }
        
        // get all class maps
        if (!($cms = $cmf->allMade())) {
            // recompile all if no class map found at all
            return $input_files;
        }

        // arrays to keep track of new files to parse
        $new_files = array();
        
        // arrays to keep track of files compiled
        $compiled_files = array();

        // go through each 
        foreach($cms as &$cm) {
            
            // get the source file
            if (!($f = $cm->getClassFile())) {
                continue;
            }
            
            // is file in input files?
            if (in_array($f, $input_files) && !in_array($f, $compiled_files)) {
                // a file that's been compiled
                $compiled_files[] = $f;
            }

            // skip classes no need to compile
            if ($cm->needRecompile() && !in_array($f, $new_files)) {
                $new_files[] = $f;
            }
        }
        
        // now get the uncompiled files
        $uncompiled_files = array_diff($input_files, $compiled_files);

        // return both uncompiled files and new files
        return array_merge($new_files, $uncompiled_files);
    }

    /**
     * Get only the files that matches source extensions
     * @param array $input_files
     * @return array
     */
    protected function getOnlySourceFiles($input_files) { 

        // done if no input files
        if (!$input_files) {
            return $input_files;
        }
        
        // get source extensions allowed 
        $exts = $this->getConfigOption('source_exts');
        if (!$exts) {
            $exts = "php";
        }

        // come up with a pattern
        $exts = preg_split("/[\s,]+/", $exts, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach($exts as &$ext) {
            $ext = trim($ext);
            if ($ext[0] != '.') {
                $ext = '\.' . $ext;
            }
        }
        $pattern = '/(' . implode('|', $exts) . ')$/';
        
        // get 'warn_non_source_files' flag (as it used repeatedly below)
        $warn_non_source_files = $this->getConfigOption('warn_non_source_files');

        // now preg_match each file against pattern and collect src files
        $src_files = array();
        foreach($input_files as $input_file) {
            
            // collect source files
            if (preg_match($pattern, $input_file)) {
                $src_files[] = $input_file;
                continue;
            } 
            
            // output warning message
            if ($warn_non_source_files) {
                $this->log("File [$input_file] is not a PHP source file. Ignored.", epLog::LOG_INFO);
            }
        }

        return $src_files;
    }
        
    /**
     * Prepare output directory. 
     * @return false|string false if failed or string of the output dir
     * @throws epExceptionCompiler
     * @todo Backup for non-empty directory 
     */
    protected function prepareOutputDir() {
        
        // get output directory 
        $compiled_dir = $this->getConfigOption('compiled_dir'); 
        if (!$compiled_dir) {
            throw new epExceptionCompiler('Output directory not configured. ');
            return false;
        }
        
        // if compiled dir is a relative path, make is absolute 
        $compiled_dir = $this->getAbsolutePath($compiled_dir); 

        // check if output dir exists
        if (!file_exists($compiled_dir)) {
            if (!epMkDir($compiled_dir, 0700)) {
                throw new epExceptionCompiler('Cannot create output directory [' . $compiled_dir . ']');
                return false;
            }
        }
        
        // validate output dir
        if (!is_dir($compiled_dir)) {
            throw new epExceptionCompiler('Output directory [' . $compiled_dir . '] is not a directory');
            return false;
        }
        
        // is output dir writable?
        if (!is_writable($compiled_dir)) {
            throw new epExceptionCompiler('Output directory [' . $compiled_dir . '] is not writable.');
            return false;
        }
        
        return $compiled_dir;
    }
    
    /**
     * Compiles all input files and generate object relational mapping info
     * 
     * <ol>
     * <li>
     * If no parameter is given, this method does <b>static</b> compile. That 
     * is, it picks up all files in the input direcotry specified in config 
     * and compiles them all. 
     * </li>
     * <li>
     * If a valid parameter is given, either class name (string) or an object,
     * it auto-compiles the class. It will search all included files in memory
     * and find which source files to use for the class and compile them. 
     * </li>
     * </ol>
     * 
     * @param object|string $o either an object or a string
     * @return bool
     * @throws epExceptionCompiler
     * @access public
     */
    public function compile($o = null) { 
        
        // if auto-compile, or input files/output dir not ready
        if ($o || !$this->input_files || !$this->compiled_dir) {
            // do initialization
            if (!$this->initialize($o)) {
                return false;
            }
        }
        
        // compile all 
        return $this->doCompile();
    }

    /**
     * The actual parsing takes place here
     * 
     * <ol>
     * <li>Parse all input files and build internal class maps ({@link epClassMap}). </li>
     * <li>Generate runtime config from class maps</li>
     * </ol>
     * All above steps are carried out through interfacing with {@link epClassMapFactory}.
     * 
     * @return bool
     * @access protected
     */
    protected function doCompile() {
        
        // done if no input file to compile
        if (!$this->input_files) {
            return true;
        }
        
        $status = true;
        $status &= $this->preParse();
        $status &= $this->parseFiles();
        $status &= $this->postParse();
        return $status;
    }
    
    /**
     * Carry out one of the compile tasks (see {@link epClassCompiler::compile()}). 
     * Parse all input files and build internal class maps ({@link epClassMap}). 
     * @return bool
     * @access protected
     */
    protected function parseFiles() {
        
        if (!$this->input_files) {
            return true;
        }

        // parse each file
        $status = true;
        foreach($this->input_files as $file) {
            $this->log("Parsing file [$file] - start", epLog::LOG_INFO);
            try {
                $status &= $this->parseFile($file);

            } catch (Exception $e) {
                // log the exception
                $this->log("Exception caught with message '" . $e->getMessage() . "' in " . $e->getFile() . ":" . $e->getLine(), epLog::LOG_ERR);
                $this->log("Stack Trace:", epLog::LOG_ERR);
                $this->log($e->getTraceAsString(), epLog::LOG_ERR);

                // keep it going so that the user realizes there is a problem
                throw $e;
            }
            $this->log("Parsing file [$file] - end", epLog::LOG_INFO);
        }
        
        return $status;
    }
    
    /**
     * Parse an individual file
     * @return bool
     * @access protected
     */
    protected function parseFile($file) {
        
        // make sure we have the parser ready
        if (!$this->parser) {
            include_once(EP_SRC_COMPILER.'/epParser.php');
            $this->parser = new epClassParser($this->getConfig());
        }
        
        return $this->parser->parse($file);
    }
    
    /**
     * Things to be done before parsing the files.
     * 
     * Any actions to be taken before parsing the files can be put
     * into this method. Subclasses may override this method if 
     * there is a need to do any task before parsing the files. 
     * 
     * @return bool
     * @access protected
     */
    protected function preParse() { 
        return true;
    }
    
    /**
     * Post process after parsing all the files. 
     * 
     * At this point, the intermediate result has been generated 
     * which for example is kept in epClassMapFactory. This method 
     * uses the result to generate more results. 
     * 
     * @return bool
     * @access protected
     */
    protected function postParse() { 
        $status = true;
        $status &= $this->generateRuntimeConfig();
        return $status;
    }
    
    /**
     * Carry out one of the compile tasks (see {@link epClassCompiler::compile()}). 
     * Generate runtime config from class maps
     * @return bool
     * @access public
     * @todo To be implemented
     */
    public function generateRuntimeConfig() {
        
        // create runtime config generator
        include_once(EP_SRC_COMPILER.'/epGenerator.php');
        if (!($g = new epGenRuntimeConfig($this->getConfig()))) {
            throw new epExceptionCompiler('Cannot instantiate runtime config generator');
            return false;
        }
        
        // generate the runtime config (validate if asked)
        return $g->generate(
            $this->getConfigOption('validate_after_compile'), 
            $this->getConfigOption('backup_compiled')
            );
    }
    
}

?>
