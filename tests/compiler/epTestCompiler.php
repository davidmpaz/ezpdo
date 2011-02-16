<?php

/**
 * $Id: epTestCompiler.php 933 2006-05-12 19:07:43Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 933 $ $Date: 2006-05-12 15:07:43 -0400 (Fri, 12 May 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.base
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/../src/epTestCase.php');

/**
 * need ezpdo utils
 */
include_once(EP_SRC_BASE.'/epUtils.php');

/**
 * The unit test class for {@link epClassCompiler}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 933 $ $Date: 2006-05-12 15:07:43 -0400 (Fri, 12 May 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.compiler
 */
class epTestCompiler extends epTestCase {
    
    /**
     * The compiler instance
     */
    protected $c = false;
    
    /**
     * Methods runs before every test
     */
    function setUp() {
        
        // setup compiler not already
        if (!$this->c) {
            
            // load config.xml for compiler
            include_once(EP_SRC_BASE.'/epConfig.php');
            $this->assertTrue($cfg = epConfig::load(realpath(dirname(__FILE__))."/config.xml"));
            $this->assertTrue(!empty($cfg));

            // create a compiler object
            include_once(EP_SRC_COMPILER.'/epCompiler.php');
            $this->assertTrue($this->c = new epClassCompiler($cfg));
            
            // no validation to suppress errors
            $this->c->setConfigOption('validate_after_compile', false);
        }
    }

    /**
     * Erase output dir during teardown
     */
    function tearDown() {    
        epRmDir(dirname(__FILE__) . '/output');
    }

    /**
     * test epClassCompiler: auto-compile (compiles class loaded in memory)
     */
    function testCompilerAuto() {
        
        // make input/output path absolute
        $this->c->setConfigOption('source_dirs', EP_TESTS.'/classes/bookstore/src');

        // compile (static)
        $this->assertTrue($this->c->compile());

        // get the class map file output by static compile
        $compiled_dir = $this->c->getConfigOption('compiled_dir');
        $compiled_file_static = $this->c->getConfigOption('compiled_file');
        
        // validate file
        $this->assertTrue(!empty($compiled_file_static));
        
        // get class map factory
        $this->assertTrue($cmf = epClassMapFactory::instance());
        
        // get all classes and include files from class map factory
        $this->assertTrue($cms = $cmf->allMade());
        $class_files = array(); // array to key class-file pairs
        foreach($cms as $cm) {
            $class_files[$cm->getName()] = $cm->getClassFile();
        }
        
        // make sure we have some classes
        $this->assertTrue(!empty($class_files));
        
        // auto-compile preparation: include all class files
        foreach($class_files as $class => $file) {
            include_once($file);
        }
        
        $cmf_string_static = $this->_cmfToString($cmf);
        
        // --------------------------------------------------------------
        // compile by passing class names
        
        // before auto-compile lets remove all class maps in class factory
        $cmf->removeAll();
        
        // make sure there is no class map in class map factory
        $this->assertFalse($cms = $cmf->allMade());
        $this->assertTrue(count($cms) == 0);
        
        // make up a new class file name
        $compiled_file_auto = $compiled_file_static . '.2';
        
        // set config to use new class map file 
        $this->c->setConfigOption('compiled_file', $compiled_file_auto);
        
        // auto-compile each class
        foreach($class_files as $class => $file) {
            $this->assertTrue($this->c->compile($class));
        }
        
        // get contents of the two class map files
        $class_map_content_static = file_get_contents($this->c->getAbsolutePath($compiled_dir) . '/' . $compiled_file_static);
        $this->assertTrue($class_map_content_static);

        $class_map_content_auto = file_get_contents($this->c->getAbsolutePath($compiled_dir) . '/' .$compiled_file_auto);
        $this->assertTrue($class_map_content_auto);
        
        // check if results of static-compiled and auto-compiled are the same
        $cmf_string_auto = $this->_cmfToString($cmf);
        $this->assertTrue($cmf_string_static == $cmf_string_auto);
        
        // --------------------------------------------------------------
        // compile by passing objects 
        
        // before auto-compile lets remove all class maps in class factory
        $cmf->removeAll();
        
        // make sure there is no class map in class map factory
        $this->assertFalse($cms = $cmf->allMade());
        $this->assertTrue(count($cms) == 0);
        
        // make up a new class file name
        $compiled_file_auto = $compiled_file_static . '.3';
        
        // set config to use new class map file 
        $this->c->setConfigOption('compiled_file', $compiled_file_auto);
        
        // auto-compile each class
        foreach($class_files as $class => $file) {
            $this->assertTrue($o = new $class);
            $this->assertTrue($this->c->compile($o));
        }
        
        // get contents of the two class map files
        $class_map_content_static = file_get_contents($this->c->getAbsolutePath($compiled_dir) . '/' . $compiled_file_static);
        $this->assertTrue($class_map_content_static);
        $class_map_content_auto = file_get_contents($this->c->getAbsolutePath($compiled_dir) . '/' .$compiled_file_auto);
        $this->assertTrue($class_map_content_auto);
        
        // check if results of static-compiled and auto-compiled are the same
        $cmf_string_auto = $this->_cmfToString($cmf);
        $this->assertTrue($cmf_string_static == $cmf_string_auto);

    }
    
    /**
     * Debug: Print out all class maps in the factory
     */
    function _cmfToString(epClassMapFactory $cmf) {
        $s = '';
        $cms = $cmf->allMade();
        ksort($cms);
        foreach($cms as $cm) {
            $s .= 'class: ' . $cm->getName() . epNewLine();
            $fms = $cm->getAllFields();
            ksort($fms);
            foreach($fms as $fm) {
                $s .= '  field: ' . $fm->getName() . epNewLine();
            }
        }
        return $s;
    }
}

if (!defined('EP_GROUP_TEST')) {
    $t = new epTestCompiler;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
}

?>
