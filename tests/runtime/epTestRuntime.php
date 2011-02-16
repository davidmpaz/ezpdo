<?php

/**
 * $Id: epTestRuntime.php 547 2005-09-28 22:26:33Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 547 $ $Date: 2005-09-28 18:26:33 -0400 (Wed, 28 Sep 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
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
 * The unit test class for inverses   
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 547 $ $Date: 2005-09-28 18:26:33 -0400 (Wed, 28 Sep 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 */
class epTestRuntime extends epTestCase {
    
    /**
     * The cached manager
     * @var epManager
     */
    protected $m = false;

    /**
     * Destroy singletons to force reconstruction
     */
    function _destroy() {
        
        // destroy class map factory
        include_once(EP_SRC_ORM.'/epClassMap.php');
        epClassMapFactory::destroy();
        
        // destroy db connections
        include_once(EP_SRC_DB.'/epDbObject.php');
        epDbFactory::destroy();
        
        // destroy manager
        include_once(EP_SRC_RUNTIME.'/epManager.php');
        epManager::destroy();
    }

    /**
     * setup before each test
     * @param string $dbal (adodb or peardb)
     * @param string $db (mysql, pgsql, or sqlite)
     */
    function _setUp($dbal, $db) {

        // destroy singletons
        $this->_destroy();

        // load config.xml for compiler
        include_once(EP_SRC_BASE.'/epConfig.php');
        $cfg = & epConfig::load(dirname(__FILE__)."/config.xml");
        $this->assertTrue(!empty($cfg));

        // set dblib
        $cfg->set('db_lib', $dbal);
        
        // make input/output path absolute (fixed)
        $source_dirs = EP_TESTS . '/classes/';
        $cfg->set('source_dirs', $source_dirs);

        // set compiled dir
        switch($db) {
            
            case 'mysql': 
                $compiled_file = $cfg->get('test/compiled_file/mysql');
                $default_dsn = $cfg->get('test/default_dsn/mysql');
                break;
            
            case 'pgsql': 
                $compiled_file = $cfg->get('test/compiled_file/pgsql');
                $default_dsn = $cfg->get('test/default_dsn/pgsql');
                $cfg->set('default_oid_column', 'eoid'); // oid is special in pgsql
                break;

            case 'sqlite': 
                $compiled_file = $cfg->get('test/compiled_file/sqlite/'.$dbal);
                $default_dsn = $cfg->get('test/default_dsn/sqlite/'.$dbal);
                break;
        }
        
        $cfg->set('compiled_file', $compiled_file);
        $cfg->set('default_dsn', $default_dsn);

        // force compile so default_dsn gets into class map
        $cfg->set('force_compile', true);
        
        // get epManager instance
        include_once(EP_SRC_RUNTIME.'/epManager.php');
        $this->m = null; // force a new instance
        $this->m = & epManager::instance();
        $this->assertTrue($this->m);

        // set config to manager
        $this->assertTrue($this->m->setConfig($cfg));
        
        // assert source_dirs is correct
        $this->assertTrue($this->m->getConfigOption('source_dirs') === $source_dirs);
        
        // assert source_dirs is correct
        $this->assertTrue($this->m->getConfigOption('compiled_file') === $compiled_file);
        
        // assert default_dsn is correct
        $this->assertTrue($this->m->getConfigOption('default_dsn') === $default_dsn);
    }
    
}

?>
