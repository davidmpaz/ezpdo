<?php

/**
 * $Id: epTestDb.php 969 2006-05-19 12:20:19Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 969 $ $Date: 2006-05-19 08:20:19 -0400 (Fri, 19 May 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.orm
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/../src/epTestCase.php');

/**
 * need epConfig
 */
include_once(EP_SRC_BASE.'/epConfig.php');

/**
 * need epUtils
 */
include_once(EP_SRC_BASE.'/epUtils.php');

/**
 * Unit test class for {@link epDbAdodb}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 969 $ $Date: 2006-05-19 08:20:19 -0400 (Fri, 19 May 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.orm
 */
class epTestDb extends epTestCase {
    
    protected $cfg = false;

    /**
     * Return the DSN for a database type from config.xml
     * @return string
     */
    function _getDsn($dbtype) {
        
        // load the config file
        if (!$this->cfg) {
            // load config.xml
            $this->cfg = & epConfig::load(dirname(__FILE__)."/config.xml");
            $this->assertTrue(!empty($this->cfg));
        } 

        $this->assertTrue($dsn = $this->cfg->get('dbs/'.strtolower($dbtype)));

        return $dsn;
    }
    
    /**
     * test {@link epDb}
     * @param string db library
     * @return void
     */
    function _testMysql($db) {
        
        // create a test table
        $table = "test_" . date('Y_m_d_g_i_s');
        
        // create -only- if not exists
        // note: must have oid column for peardb lastInsertId() to work!!!
        $sql = ' CREATE TABLE IF NOT EXISTS ' . $table . ' ('
            . ' oid INT NOT NULL auto_increment,'
            . ' col_char CHAR(64) NOT NULL,'
            . ' col_int INT NOT NULL,'
            . ' col_timestamp TIMESTAMP NOT NULL,'
            . ' PRIMARY KEY (oid)'
            . ' )';

        $this->assertTrue($rs = $db->execute($sql));

        // test insert/read
        for($i = 1; $i <= 20; $i ++) {

            $col_char = $db->quote(md5($i));
            $col_int = rand(1, 1000000);

            // insert
            $sql = "INSERT INTO " . $db->quoteId($table) . " (" . $db->quoteId('col_char') . ", " . $db->quoteId('col_int') . ") "
                . "VALUES (" . $db->quote($col_char) . ", " . $db->quote($col_int) . ")";

            $this->assertTrue($rs = $db->execute($sql));

            // check last insert id
            $this->assertTrue($db->lastInsertId($table, 'oid') == $i);

            // read all
            $sql = "SELECT * from " . $db->quoteId($table);
            $this->assertTrue($rs = $db->execute($sql));
            $this->assertTrue($db->rsRows() == $i);

            // read record by this id
            $sql = "SELECT * from " . $db->quoteId($table) . " where " . $db->quoteId('oid') . " = $i";
            $this->assertTrue($rs = $db->execute($sql));
            $this->assertTrue($db->rsRows() == 1);
        }

        // test delete
        for($i = 20; $i >= 1; $i --) {

            // delete
            $sql = "DELETE FROM " . $db->quoteId($table) . " WHERE " . $db->quoteId('oid') . " = $i";
            $this->assertTrue($rs = $db->execute($sql));

            // check #records
            $sql = "SELECT * from " . $db->quoteId($table);
            $this->assertTrue($rs = $db->execute($sql));
            $this->assertTrue($db->rsRows() == $i - 1);

            // read record by this id (should get nothing)
            $sql = "SELECT * from " . $db->quoteId($table) . " where oid = $i";
            $this->assertTrue($rs = $db->execute($sql));
            $this->assertTrue($db->rsRows() == 0);
        }

        // empty table?
        $sql = "SELECT * from " . $db->quoteId($table);
        $this->assertTrue($rs = $db->execute($sql));
        $this->assertTrue($db->rsRows() == 0);

        // drop the table
        $sql = ' DROP TABLE IF EXISTS ' . $db->quoteId($table) . ';';
        $this->assertTrue($rs = $db->execute($sql));
    }

    /**
     * test adodb wrapper
     */
    function testDbAdodbMysql() {

        // skip testing mysql if not allowed
        if (!$this->canTestMysql()) {
            return;
        }

        include_once(EP_SRC_DB.'/epDbAdodb.php');
        echo "test adodb mysql..";
        $this->assertTrue($dsn = $this->_getDsn('mysql'));
        $db = new epDbAdodb($dsn);
        $this->assertTrue(!is_null($db));
        $this->_testMysql($db);
        echo "done " . epNewLine();
    }

    /**
     * test peardb wrapper
     */
    function testDbPeardbMysql() {

        // skip testing mysql if not allowed
        if (!$this->canTestMysql()) {
            return;
        }

        // skip test if no PEAR DB installed
        if (!epFileExistsIncPath('DB.php')) {
            return;
        }

        echo "test peardb mysql..";
        include_once(EP_SRC_DB.'/epDbPeardb.php');
        $this->assertTrue($dsn = $this->_getDsn('mysql'));
        $db = new epDbPeardb($dsn);
        $this->assertTrue(!is_null($db));
        $this->_testMysql($db);
        echo "done " . epNewLine();
    }

    /**
     * test {@link epDb} with SQLite
     * @param string db library
     * @return void
     */
    function _testSqlite($db) {

        // create a test table
        //$table = "test_" . date('Y_m_d_g_i_s');
        $table = "test_sqlite";

        if (!$db->tableExists($table)) {

            // create -only- if not exists
            // note: must have oid column for peardb lastInsertId() to work!!!
            $sql = ' CREATE TABLE ' . $db->quoteId($table) . ' ('
                . ' oid INTEGER PRIMARY KEY,'
                . ' col_char CHAR(64) NOT NULL,'
                . ' col_int INT NOT NULL'
                //. ' col_timestamp TIMESTAMP NOT NULL'
                . ' )'; 

            $this->assertTrue($rs = $db->execute($sql));

        }

        // test insert/read
        for($i = 1; $i <= 20; $i ++) {

            $col_char = md5($i);
            $col_int = rand(1, 1000000);

            // insert
            $sql = "INSERT INTO " . $db->quoteId($table) . " (" . $db->quoteId('col_char') . ", " . $db->quoteId('col_int') . ") "
                . "VALUES (" . $db->quote($col_char) . ", " . $db->quote($col_int) . ");";

            $this->assertTrue($rs = $db->execute($sql));

            // check last insert id
            $this->assertTrue($db->lastInsertId($table, 'oid') == $i);

            // read all
            $sql = "SELECT * from " . $db->quoteId($table);
            $this->assertTrue($rs = $db->execute($sql));
            $this->assertTrue($db->rsRows() == $i);

            // read record by this id
            $sql = "SELECT * from " . $db->quoteId($table) . " where " . $db->quoteId('oid') . " = $i";
            $this->assertTrue($rs = $db->execute($sql));
            $this->assertTrue($db->rsRows() == 1);
        }

        // test delete
        for($i = 20; $i >= 1; $i --) {

            // delete
            $sql = "DELETE FROM " . $db->quoteId($table) . " WHERE " . $db->quoteId('oid') . " = $i";
            $this->assertTrue($rs = $db->execute($sql));

            // check #records
            $sql = "SELECT * from " . $db->quoteId($table);
            $this->assertTrue($rs = $db->execute($sql));
            $this->assertTrue($db->rsRows() == $i - 1);

            // read record by this id (should get nothing)
            $sql = "SELECT * from " . $db->quoteId($table) . " where " . $db->quoteId('oid')  . " = $i";
            $this->assertTrue($rs = $db->execute($sql));
            $this->assertTrue($db->rsRows() == 0);
        }

        // empty table?
        $sql = "SELECT * from " . $db->quoteId($table);
        $this->assertTrue($rs = $db->execute($sql));
        $this->assertTrue($db->rsRows() == 0);

        // drop the table
        $sql = ' DROP TABLE ' . $db->quoteId($table) . ';';
        $this->assertTrue($rs = $db->execute($sql));
    }

    /**
     * test {@link epDb} with SQLite3
     *
     * This is the same test as for version 2 but excluding the use of the
     * rsRows() method, current SQLite3 driver doesn't support row count on
     * last result set obtained.
     *
     * @param string db library
     * @return void
     */
    function _testSqlite3($db) {

        // create a test table
        //$table = "test_" . date('Y_m_d_g_i_s');
        $table = "test_sqlite";

        if (!$db->tableExists($table)) {

            // create -only- if not exists
            // note: must have oid column for peardb lastInsertId() to work!!!
            $sql = ' CREATE TABLE ' . $db->quoteId($table) . ' ('
                . ' oid INTEGER PRIMARY KEY,'
                . ' col_char CHAR(64) NOT NULL,'
                . ' col_int INT NOT NULL'
                //. ' col_timestamp TIMESTAMP NOT NULL'
                . ' )';

            $this->assertTrue($rs = $db->execute($sql));

        }

        // test insert/read
        for($i = 1; $i <= 20; $i ++) {

            $col_char = md5($i);
            $col_int = rand(1, 1000000);

            // insert
            $sql = "INSERT INTO " . $db->quoteId($table) . " (" . $db->quoteId('col_char') . ", " . $db->quoteId('col_int') . ") "
                . "VALUES (" . $db->quote($col_char) . ", " . $db->quote($col_int) . ");";

            $this->assertTrue($rs = $db->execute($sql));

            // check last insert id
            $this->assertTrue($db->lastInsertId($table, 'oid') == $i);

            // read all
            $sql = "SELECT * from " . $db->quoteId($table);
            $this->assertTrue($rs = $db->execute($sql));
            // adodb sqlite3 driver always returns 1
            $this->assertTrue($db->rsRows() == 1);
            // workaround to check row number
            // @TODO

            // read record by this id
            $sql = "SELECT * from " . $db->quoteId($table) . " where " . $db->quoteId('oid') . " = $i";
            $this->assertTrue($rs = $db->execute($sql));
            // never fails because of the same as above
            $this->assertTrue($db->rsRows() == 1);
            // testing something in real
            $this->assertNotNull($db->rsGetCol('col_char'));
        }

        // test delete
        for($i = 20; $i >= 1; $i --) {

            // delete
            $sql = "DELETE FROM " . $db->quoteId($table) . " WHERE " . $db->quoteId('oid') . " = $i";
            $this->assertTrue($rs = $db->execute($sql));

            // check #records
            $sql = "SELECT * from " . $db->quoteId($table);
            $this->assertTrue($rs = $db->execute($sql));
            // this always evaluate to 1 in SQLite3
            $this->assertTrue($db->rsRows() == 1);

            $sql = "SELECT COUNT(*) as count from " . $db->quoteId($table);
            $this->assertTrue($rs = $db->execute($sql));
            $this->assertTrue($db->rsGetCol('count') == $i - 1);

            // read record by this id (should get nothing)
            $sql = "SELECT * from " . $db->quoteId($table) . " where " . $db->quoteId('oid')  . " = $i";
            $this->assertTrue($rs = $db->execute($sql));
            // adodb sqlite3 driver always returns 1
            $this->assertTrue($db->rsRows() == 1);
            // test real values
            $this->assertFalse($rs->_queryID->fetchArray());

        }

        // empty table?
        $sql = "SELECT COUNT(*) as count from " . $db->quoteId($table);
        $this->assertTrue($rs = $db->execute($sql));
        $this->assertTrue($db->rsGetCol('count') == 0);
        // fetch oprtaion just to release the table locking
        $this->assertFalse($rs->_queryID->fetchArray());

        // drop the table
        $sql = ' DROP TABLE ' . $db->quoteId($table) . ';';
        $this->assertTrue($rs = $db->execute($sql));
    }

    /**
     * test adodb wrapper with SQLite
     */
    function testDbAdodbSqlite() {

        // skip testing sqlite if not allowed
        if (!$this->allowTestSqlite()) {
            return;
        }

        echo "test adodb sqlite v2..";
        include_once(EP_SRC_DB.'/epDbAdodb.php');
        $this->assertTrue($dsn = $this->_getDsn('sqlite_adodb'));
        $this->assertTrue($db = new epDbAdodb($dsn));
        $this->_testSqlite($db);
        echo "done " . epNewLine();

        echo "test adodb sqlite v3..";
        $this->assertTrue($dsn = $this->_getDsn('sqlite3_adodb'));
        $this->assertTrue($db = new epDbAdodb($dsn));
        $this->_testSqlite3($db);
        echo "done " . epNewLine();
    }

    /**
     * test peardb wrapper with SQLite
     */
    function testDbPeardbSqlite() {

        // skip testing sqlite if not allowed
        if (!$this->allowTestSqlite()) {
            return;
        }

        // skip test if no PEAR DB installed
        if (!epFileExistsIncPath('DB.php')) {
            return;
        }

        echo "test peardb sqlite v2..";
        include_once(EP_SRC_DB.'/epDbPeardb.php');
        $this->assertTrue($dsn = $this->_getDsn('sqlite_peardb'));
        $this->assertTrue($db = new epDbPeardb($dsn));
        $this->_testSqlite($db);
        echo "done " . epNewLine();

        // peardb doesnt seems to support sqlite3, looking forward to MDB2
    }

}

if (!defined('EP_GROUP_TEST')) {
    $t = new epTestDb;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
}

?>
