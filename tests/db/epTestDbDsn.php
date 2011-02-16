<?php

/**
 * $Id: epTestDbDsn.php 439 2005-08-28 21:17:45Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 439 $ $Date: 2005-08-28 17:17:45 -0400 (Sun, 28 Aug 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.orm
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
 * test epDbDsn
 */
include_once(EP_SRC_DB.'/epDbDsn.php');

/**
 * Unit test class for {@link epDbAdodb}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 439 $ $Date: 2005-08-28 17:17:45 -0400 (Sun, 28 Aug 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.orm
 */
class epTestDbDsn extends epTestCase {
    
    /**
     * test epDbDsn with mysql dsn
     */
    function testDbDsn_mysql() {
        
        // components
        $phptype = 'mysql';
        $username = 'ezpdo';
        $password = 'pdoiseasy';
        $hostspec = 'localhost';
        $database = 'ezpdo';
        
        // the DSN string
        $dsname = $phptype . '://' . $username . ':' . $password . '@' . $hostspec . '/' . $database;
        
        // create an epDbDsn object
        $dsn = new epDbDsn($dsname);
        $this->assertTrue($dsn);

        // check components
        $this->assertTrue($dsn['dsn'] == $dsname);
        $this->assertTrue($dsn['username'] == $username);
        $this->assertTrue($dsn['password'] == $password);
        $this->assertTrue($dsn['hostspec'] == $hostspec);
        $this->assertTrue($dsn['database'] == $database);

        // test PDO dsn
        $pdo_dsn = $dsn->toPdoDsn($u, $p);
        $this->assertTrue($pdo_dsn == $phptype . ':dbname=' . $database . ';host=' . $hostspec);
        $this->assertTrue($u == $username);
        $this->assertTrue($p == $password);
    }
    
    /**
     * test epDbDsn::setDsn() (thru ArrayAccess)
     */
    function testDbDsn_setDsn() {
        
        // components
        $phptype = 'mysql';
        $username = 'ezpdo';
        $password = 'pdoiseasy';
        $hostspec = 'localhost';
        $database = 'ezpdo';
        
        // the DSN string
        $dsname = $phptype . '://' . $username . ':' . $password . '@' . $hostspec . '/' . $database;
        
        // create an epDbDsn object
        $dsn = new epDbDsn();
        $this->assertTrue($dsn);

        // set dsn through ArrayAccess
        $dsn['dsn'] = $dsname;

        // check components
        $this->assertTrue($dsn['dsn'] == $dsname);
        $this->assertTrue($dsn['username'] == $username);
        $this->assertTrue($dsn['password'] == $password);
        $this->assertTrue($dsn['hostspec'] == $hostspec);
        $this->assertTrue($dsn['database'] == $database);

        // test PDO dsn
        $pdo_dsn = $dsn->toPdoDsn($u, $p);
        $this->assertTrue($pdo_dsn == $phptype . ':dbname=' . $database . ';host=' . $hostspec);
        $this->assertTrue($u == $username);
        $this->assertTrue($p == $password);
    }

    /**
     * test epDbDsn with sqlite dsn
     */
    function testDbDsn_sqlite() {
        
        // components
        $phptype = 'sqlite';
        $username = '';
        $password = '';
        $hostspec = '';
        $database = '/full/unix/path/to/file.db';
        
        // the DSN string
        $dsname = $phptype . ':///' . $database;
        
        // create an epDbDsn object
        $dsn = new epDbDsn($dsname);
        $this->assertTrue($dsn);

        // check components
        $this->assertTrue($dsn['dsn'] == $dsname);
        $this->assertTrue($dsn['username'] == $username);
        $this->assertTrue($dsn['password'] == $password);
        $this->assertTrue($dsn['hostspec'] == $hostspec);
        $this->assertTrue($dsn['database'] == $database);
        
        // test PDO dsn
        $pdo_dsn = $dsn->toPdoDsn($u, $p);
        $this->assertTrue($pdo_dsn == $phptype . ':' . $database);
        $this->assertTrue($u == $username);
        $this->assertTrue($p == $password);
    }
    
}

if (!defined('EP_GROUP_TEST')) {
    $t = new epTestDbDsn;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
}

?>
