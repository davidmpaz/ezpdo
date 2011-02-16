<?php

/**
 * $Id: epTestCase.php 812 2006-02-13 23:59:15Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 812 $ $Date: 2006-02-13 18:59:15 -0500 (Mon, 13 Feb 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.src
 */

/**
 * need ezpdo.php
 */
include_once(dirname(__FILE__).'/../../ezpdo.php');

/**#@+
 * need simpletest
 */
include_once(EP_LIBS_SIMPLETEST . '/unit_tester.php');
include_once(EP_LIBS_SIMPLETEST . '/reporter.php');
/**#@-*/

/**
 * The base class for EZPDO test cases
 * 
 * The class extends the SimpleTest UnitTestCase class to allow easy 
 * configuration for test cases.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 812 $ $Date: 2006-02-13 18:59:15 -0500 (Mon, 13 Feb 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.src
 */
class epTestCase extends UnitTestCase {
    
    /**
     * Whether to allow Adodb tests 
     * @var boolean
     * @static
     */
    public static $allow_test_adodb = true;

    /**
     * Whether to allow Peardb tests 
     * @var boolean
     * @static
     */
    public static $allow_test_peardb = true;

    /**
     * Whether to allow PDO tests
     * (Currently set to false)
     * @var boolean
     */
    public static $allow_test_pdo = false;
    
    /**
     * Whether to allow mysql tests 
     * @var boolean
     * @static
     */
    public static $allow_test_mysql = true;

    /**
     * Whether to allow pgsql tests 
     * @var boolean
     * @static
     */
    public static $allow_test_pgsql = true;

    /**
     * Whether to allow sqlite tests
     * @var boolean
     */
    public static $allow_test_sqlite = true;
    
    /**
     * If no param, returns if we allow Adodb tests. 
     * If param is boolean, set whehter we allow Adodb tests.
     * @param null|boolean $v
     * @return boolean
     */
    function allowTestAdodb($v = null) {
        if (is_null($v)) {
            return self::$allow_test_adodb;
        } else if (is_boolean($v)) {
            self::$allow_test_adodb = $v;
        }
    }

    /**
     * Checks if we can test Adodb
     * @return boolean
     */
    function canTestAdodb() {
        // return false if Adodb tests are not allowed
        return $this->allowTestAdodb();
    }

    /**
     * If no param, returns if we allow Peardb tests. 
     * If param is boolean, set whehter we allow Peardb tests.
     * @param null|boolean $v
     * @return boolean
     */
    function allowTestPeardb($v = null) {
        if (is_null($v)) {
            return self::$allow_test_peardb;
        } else if (is_boolean($v)) {
            self::$allow_test_peardb = $v;
        }
    }

    /**
     * Checks if we can test Peardb. Pear DB installation is checked.
     * @return boolean
     */
    function canTestPeardb() {
        // return false if Peardb tests are not allowed
        if (!$this->allowTestPeardb()) {
            return false;
        }
        // skip test if no PEAR DB installed
        if (!epFileExistsIncPath('DB.php')) {
            return false;
        }
        return true;
    }

    /**
     * If no param, returns if we allow Pdo tests. 
     * If param is boolean, set whehter we allow Peardb tests.
     * @param null|boolean $v
     * @return boolean
     */
    function allowTestPdo($v = null) {
        if (is_null($v)) {
            return self::$allow_test_pdo;
        } else if (is_boolean($v)) {
            self::$allow_test_pdo = $v;
        }
    }

    /**
     * Checks if we can test PDO. PDO extension installation is checked.
     * @param string $drive the pdo drive (default to 'myslq')
     * @return boolean
     */
    function canTestPdo($drive = 'mysql') {
        // return false if Peardb tests are not allowed
        if (!$this->allowTestPdo()) {
            return false;
        }
        // skip if pdo extension is not loaded
        if (!extension_loaded('pdo')  || !extension_loaded('pdo_'.$drive)) {
            return false;
        }
        return true;
    }

    /**
     * If no param, returns if we allow mysql tests. 
     * If param is boolean, set whehter we allow mysql tests.
     * @param null|boolean $v
     * @return boolean
     */
    function allowTestMysql($v = null) {
        if (is_null($v)) {
            return self::$allow_test_mysql;
        } else if (is_boolean($v)) {
            self::$allow_test_mysql = $v;
        }
    }

    /**
     * Checks if mysql database for testing is ready
     * @var string $dsn 
     * @return boolean
     */
    function canTestMysql($dsn = 'mysql://ezpdo:pdoiseasy@localhost/ezpdo') {
        
        // return false if mysql tests are not allowed
        if (!$this->allowTestMySql()) {
            return false;
        }

        // need adodb and exceptions
        include_once(EP_LIBS_ADODB.'/adodb-exceptions.inc.php');
        require_once(EP_LIBS_ADODB.'/adodb.inc.php');
        
        // try to connect db now
        $okay = false;
        try { 
            if ($db = @ADONewConnection($dsn)) {
                $okay = true;
                $db->Close();
            }
        } catch(Exception $e) {
            // simply catch the exception 
        }
        
        return $okay;
    }

    /**
     * If no param, returns if we allow pgsql tests. 
     * If param is boolean, set whehter we allow pgsql tests.
     * @param null|boolean $v
     * @return boolean
     */
    function allowTestPgsql($v = null) {
        if (is_null($v)) {
            return self::$allow_test_pgsql;
        } else if (is_boolean($v)) {
            self::$allow_test_pgsql = $v;
        }
    }

    /**
     * Checks if pgsql database for testing is ready
     * @var string $dsn 
     * @return boolean
     */
    function canTestPgsql($dsn = 'pgsql://ezpdo:pdoiseasy@localhost/ezpdo') {
        
        // return false if mysql tests are not allowed
        if (!$this->allowTestPgSql()) {
            return false;
        }

        // need adodb and exceptions
        include_once(EP_LIBS_ADODB.'/adodb-exceptions.inc.php');
        require_once(EP_LIBS_ADODB.'/adodb.inc.php');
        
        // try to connect db now
        $okay = false;
        try { 
            if ($db = @ADONewConnection($dsn)) {
                $okay = true;
                $db->Close();
            }
        } catch(Exception $e) {
            // simply catch the exception 
        }
        
        return $okay;
    }

    /**
     * If no param, returns if we allow sqlite tests. 
     * If param is boolean, set whehter we allow sqlite tests.
     * @param null|boolean $v
     * @return boolean
     */
    function allowTestSqlite($v = null) {
        if (is_null($v)) {
            return self::$allow_test_sqlite;
        } else if (is_boolean($v)) {
            self::$allow_test_sqlite = $v;
        }
    }

    /**
     * Checks if we can test sqite database
     * @var string $dsn 
     * @return boolean
     * @todo db file checking for pdo
     */
    function canTestSqlite() {
        return $this->allowTestSqlite();
    }

    /**
     * Setup output dir 
     */
    public function setUp() {
        epMkDir('output');
    }

    /**
     * tearDown: rmove output dir 
     */
    public function tearDown() {
        epRmDir('output');
    }
}

?>
