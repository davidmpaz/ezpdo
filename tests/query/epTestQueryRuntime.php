<?php

/**
 * $Id: epTestQueryRuntime.php 1038 2007-02-11 01:38:59Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1038 $ $Date: 2007-02-10 20:38:59 -0500 (Sat, 10 Feb 2007) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.query
 */

/**#@+
 * need epTestCase and epUtils
 */
include_once(dirname(__FILE__).'/../src/epTestCase.php');
include_once(EP_SRC_BASE.'/epUtils.php');
/**#@-*/

/**
 * The unit test class for {@link epQueryParser}  
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1038 $ $Date: 2007-02-10 20:38:59 -0500 (Sat, 10 Feb 2007) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.query
 */
class epTestQueryRuntime extends epTestCase {

    /**
     * The 4 authors
     */
    static protected $authors = array(
        // 0
        array(
            'name' => 'Erich Gamma',
            'contact' => array(
                'phone' => '1111111',
                'zipcode' => '01010',
                ),
            ),
        // 1
        array(
            'name' => 'Richard Helm',
            'contact' => array(
                'phone' => '2222222',
                'zipcode' => '02020',
                ),
            ),
        // 2
        array(
            'name' => 'Ralph Johnson',
            'contact' => array(
                'phone' => '3333333',
                'zipcode' => '03030',
                ),
            ),
        // 3
        array(
            'name' => 'John Vlissides',
            'contact' => array(
                'phone' => '4444444',
                'zipcode' => '04040',
                ),
            ),
        ); 

    /**
     * The 5 books
     */
    static protected $books = array(
        // 0
        array(
            'title' => 'Design Patterns',
            'pages' => 395,
            'price' => 100.01,
            ),
        // 1
        array(
            'title' => 'Contributing to Eclipse: Principles, Patterns, and Plugins',
            'pages' => 320,
            'price' => 200.02,
            ),
        // 2
        array(
            'title' => 'Contributing to Eclipse: Principles, Patterns, and Plugins (2)',
            'pages' => 320,
            'price' => 300.03,
            ),
        // 3
        array(
            'title' => 'Implementing Application Frameworks: Object-Oriented Frameworks at Work',
            'pages' => 728,
            'price' => 400.04,
            ),
        // 4
        array(
            'title' => 'Pattern Hatching : Design Patterns Applied (Software Patterns Series)',
            'pages' => 172,
            'price' => 500.05,
            ),
        ); 

    /**#@+
     * Array to keep track of oids
     */
    protected $oid_bookstore = 0;
    protected $oid_books = array();
    protected $oid_authors = array();
    protected $oid_contacts = array();
    /**#@-*/

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
     * Returns an array of config options for a db type
     * @param string $db_lib the DBAL used for testing
     * @param string $dbtype
     * @return array
     */
    function _config($db_lib, $dbtype) {
        
        // source directory
        $source_dirs = EP_TESTS . '/classes/';
        
        // compiled file name
        $compiled_file = "compiled.ezpdo.$dbtype";
        
        switch ($dbtype) {
            case 'mysql': 
                $default_dsn = "mysql://ezpdo:pdoiseasy@localhost/ezpdo";
                break;
            
            case 'pgsql': 
                $default_dsn = "pgsql://ezpdo:pdoiseasy@localhost/ezpdo";
                break;

            case 'sqlite': 
                if ($db_lib == 'adodb') {
                    $default_dsn = "sqlite://books.db";
                } else {
                    $default_dsn = "sqlite://./books.db";
                }
                break;
        }

        return array(
            'source_dirs' => $source_dirs, 
            'compiled_file' => $compiled_file, 
            'default_dsn' => $default_dsn,
            'db_lib' => $db_lib
            );
    }

    /**
     * Delete all existing objects and refill with new objects
     * @param string $db_lib the DBAL to be used for testing
     * @param string $dbtype the type of the database to be tested
     */
    function _start($db_lib, $dbtype) {
        
        // delete in-memory singletons (manages, factories)
        $this->_destroy();
        
        // make the directory for compiled info
        @mkdir('compiled');

        // load runtime core and config
        include_once(EP_ROOT.'/ezpdo_runtime.php');
        epLoadConfig(dirname(__FILE__).'/config.ini');
        
        // get manager
        $m = epManager::instance();
        foreach($this->_config($db_lib, $dbtype) as $option => $value) {
            $m->setConfigOption($option, $value);
        }

        // remove all base, authors and books
        $m->deleteAll('eptBase');
        $m->deleteAll('eptAuthor');
        $m->deleteAll('eptBook');
        $m->deleteAll('eptBookstore');
        $m->deleteAll('eptContact');

        // create bookstore
        $this->assertTrue($bookstore = $a = $m->create('eptBookstore'));

        // create authors
        $authors = array();
        foreach(self::$authors as $a_) {
            $this->assertTrue($a = $m->create('eptAuthor'));
            foreach($a_ as $k => $v) {
                if (!is_array($v)) {
                    $a[$k] = $v;
                } else {
                    $this->assertTrue($x = $m->create('ept'.ucfirst($k)));
                    foreach($v as $k_ => $v_) {
                        $x[$k_] = $v_;
                    }
                    $a[$k] = $x;
                }
            }
            $authors[] = $a;
        }
        
        // create authors
        $books = array();
        foreach(self::$books as $b_) {
            $this->assertTrue($b = $m->create('eptBook'));
            foreach($b_ as $k => $v) {
                $b[$k] = $v;
            }
            $books[] = $b;
        }
        
        // create object relationships
        $bookstore->books = $books;
        $bookstore->authors = $authors;
        $books[0]->authors = array($authors[0], $authors[1], $authors[2], $authors[3]);
        $books[1]->authors = array($authors[0]);
        $books[2]->authors = array($authors[1]);
        $books[3]->authors = array($authors[2]);
        $books[4]->authors = array($authors[3]);
        $authors[0]->books = array($books[0], $books[1]);
        $authors[1]->books = array($books[0], $books[2]);
        $authors[2]->books = array($books[0], $books[3]);
        $authors[3]->books = array($books[0], $books[4]);

        // save all objects into db
        $m->flush();

        // collect oids
        $this->oid_bookstore = $bookstore->oid;
        
        $this->oid_books = array();
        foreach($books as $book) {
            $this->oid_books[] = $book->oid;
        }

        $this->oid_authors= array();
        $this->oid_contacts = array();
        foreach($authors as $author) {
            $this->oid_authors[] = $author->oid;
            $this->oid_contacts[] = $author->contact->oid;
        }

        // debug
        //var_dump($this->oid_bookstore);
        //var_dump($this->oid_books);
        //var_dump($this->oid_authors);
        //var_dump($this->oid_contacts);

        // evict them all from memory
        $m->evictAll('eptBookstore');
        $m->evictAll('eptBooks');
        $m->evictAll('eptAuthor');
        $m->evictAll('eptContact');
        
        return true;
    }

    /**
     * Delete all existing objects
     */
    function _end() {
        $m = epManager::instance();
        $m->deleteAll('eptBase');
        $m->deleteAll('eptAuthor');
        $m->deleteAll('eptBook');
        $m->deleteAll('eptBookstore');
        $m->deleteAll('eptContact');
        @unlink(dirname(__FILE__).'/ezpdo.log');
        @unlink('books.db');
        @epRmDir(dirname(__FILE__).'/compiled');
        return true;
    }

    /**
     * Run all tests 
     * @param string $dbal (adodb, peardb)
     * @param string $dbtype (mysql, sqlite)
     */
    function _runTests($dbal, $dbtype) {
        
        echo "EZOQL tests for $dbal/$dbtype started.. " . epNewLine();
        
        echo "  setting up..";
        $this->_start($dbal, $dbtype);
        echo "done " . epNewLine();
        
        if ($methods = get_class_methods(__CLASS__)) {
            // go through each _testXXX methods
            foreach($methods as $method) {

                // skip non test methods
                if (0 !== strpos($method, '_test')) {
                    continue;
                }

                // run the test
                echo "  $method..";
                $this->assertTrue($this->$method());
                echo "done " . epNewLine();
            }
        }

        echo "  tearing down.. ";
        $this->_end();
        echo "done " . epNewLine();

        echo "  complete!" . epNewLine();
    }

    /**
     * Test adodb & mysql
     */
    function testAdodbMysql() {
        
        // skip testing adodb + mysql if not allowed
        if (!$this->canTestAdodb() || !$this->canTestMysql()) {
            return;
        }

        $this->_runTests('adodb', 'mysql');
    }

    /**
     * Test peardb & mysql
     */
    function testPearMysql() {
        
        // skip testing peardb + mysql if not allowed
        if (!$this->canTestPeardb() || !$this->canTestMysql()) {
            return;
        }

        $this->_runTests('peardb', 'mysql');
    }

    /**
     * Test pdo & mysql
     */
    function testPdoMysql() {
        
        // skip testing pdo + mysql if not allowed
        if (!$this->canTestPdo('mysql') || !$this->canTestMysql()) {
            return;
        }

        $this->_runTests('pdo', 'mysql');
    }

    /**
     * Test adodb & pgsql
     */
    function testAdodbPgsql() {
        
        // skip testing adodb + pgsql if not allowed
        if (!$this->canTestAdodb() || !$this->canTestPgsql()) {
            return;
        }

        $this->_runTests('adodb', 'pgsql');
    }

    /**
     * Test peardb & pgsql
     */
    function testPearPgsql() {
        
        // skip testing peardb + mysql if not allowed
        if (!$this->canTestPeardb() || !$this->canTestPgsql()) {
            return;
        }

        $this->_runTests('peardb', 'pgsql');
    }

    /**
     * Test pdo & pgsql
     */
    function testPdoPgsql() {
        
        // skip testing pgsql if not allowed
        if (!$this->canTestPdo('pgsql') || !$this->canTestPgsql()) {
            return;
        }

        $this->_runTests('pdo', 'pgsql');
    }

    /**
     * Test adodb & sqlite
     */
    function testAdodbSqlite() {

        // skip testing sqlite if not allowed
        if (!$this->canTestAdodb() || !$this->canTestSqlite()) {
            return;
        }

        $this->_runTests('adodb', 'sqlite');
    }

    /**
     * Test peardb & mysql
     */
    function testPearSqlite() {
        
        // skip testing sqlite if not allowed
        if (!$this->canTestPeardb() || !$this->canTestSqlite()) {
            return;
        }

        $this->_runTests('peardb', 'sqlite');
    }


    /**
     * Test pdo & sqlite
     */
    function testPdoSqlite() {
        
        // skip testing sqlite if not allowed
        if (!$this->canTestPdo('sqlite') || !$this->canTestSqlite()) {
            return;
        }

        $this->_runTests('pdo', 'sqlite');
    }

    // ----------------------------
    // !!!query test cases below!!!
    // ----------------------------

    // test oid
    function _testQuery0() {
        $this->assertTrue($m = epManager::instance());
        
        // bookstore
        $this->assertTrue($os = $m->query(
            "from eptBookstore as bstr where bstr.oid == ?", $this->oid_bookstore
            ));
        $this->assertTrue(count($os) == 1);
        $this->assertTrue($os[0]['oid'] == $this->oid_bookstore);

        // books
        foreach($this->oid_books as $oid) {
            $this->assertTrue($os = $m->query(
                "from eptBook as b where b.oid == ?", $oid
                ));
            $this->assertTrue(count($os) == 1);
            $this->assertTrue($os[0]['oid'] == $oid);
        }

        // authors
        foreach($this->oid_authors as $oid) {
            $this->assertTrue($os = $m->query(
                "from eptAuthor as a where a.oid == ?", $oid
                ));
            $this->assertTrue(count($os) == 1);
            $this->assertTrue($os[0]['oid'] == $oid);
        }

        // contacts
        foreach($this->oid_contacts as $oid) {
            $this->assertTrue($os = $m->query(
                "from eptContact as c where c.oid == ?", $oid
                ));
            $this->assertTrue(count($os) == 1);
            $this->assertTrue($os[0]['oid'] == $oid);
        }

        return true;
    }

    function _testQuery1() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query("from eptBook as b where b.pages = 172"));
        $this->assertTrue(count($os) == 1);
        $this->assertTrue($os[0]->title == 'Pattern Hatching : Design Patterns Applied (Software Patterns Series)');
        return true;
    }

    function _testQuery2() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query("from eptBook as b where b.pages > 172"));
        $this->assertTrue(count($os) == 4);
        return true;
    }

    function _testQuery3() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query("from eptBook as b where b.pages >= 172"));
        $this->assertTrue(count($os) == 5);
        return true;
    }

    function _testQuery4() {
        $this->assertTrue($m = epManager::instance());
        $this->assertFalse($os = $m->query("from eptBook as b where b.pages < 172"));
        $this->assertTrue(count($os) == 0);
        return true;
    }

    function _testQuery5() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query("from eptBook where pages = 172"));
        $this->assertTrue(count($os) == 1);
        $this->assertTrue($os[0]->title == 'Pattern Hatching : Design Patterns Applied (Software Patterns Series)');
        return true;
    }

    function _testQuery6() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query("from eptBook where pages > 172"));
        $this->assertTrue(count($os) == 4);
        return true;
    }

    function _testQuery7() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query("from eptBook where pages >= 172"));
        $this->assertTrue(count($os) == 5);
        return true;
    }

    function _testQuery8() {
        $this->assertTrue($m = epManager::instance());
        $this->assertFalse($os = $m->query("from eptBook where pages < 172"));
        $this->assertTrue(count($os) == 0);
        return true;
    }

    function _testQuery9() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query(
            "from eptAuthor as a where a.books.contains(b) AND b.pages = 728"
            ));
        $this->assertTrue(count($os) == 1);
        return true;
    }

    function _testQuery10() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query(
            "from eptAuthor as a where b.pages = 728 and a.books.contains(b)"
            ));
        $this->assertTrue(count($os) == 1);
        return true;
    }

    function _testQuery11() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query(
            "from eptBookstore as bstr where bstr.books.contains(bk) and bk.pages == 728"
            ));
        $this->assertTrue(count($os) == 1);
        return true;
    }

    function _testQuery12() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query(
            "from eptBookstore as bstr where bk.pages == 728 and bstr.books.contains(bk)"
            ));
        $this->assertTrue(count($os) == 1);
        return true;
    }

    function _testQuery13() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query(
            "from eptBookstore as bstr where " 
            . "bstr.books.contains(bk1) and bk1.pages == 728"
            . "bstr.books.contains(bk2) and bk2.pages == 395"
            ));
        $this->assertTrue(count($os) == 1);
        return true;
    }

    function _testQuery14() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query(
            "from eptBookstore as bstr where " 
            . "bstr.books.contains(bk) and bk.pages == 728"
            . "bstr.authors.contains(a) and a.name like '%Ralph%'"
            ));
        $this->assertTrue(count($os) == 1);
        return true;
    }

    function _testQuery15() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query(
            "from eptBookstore as bstr where " 
            . "bstr.books.contains(bk) and bk.pages == 728"
            . "bstr.authors.contains(a) and a.name like '%Ralph%'"
            ));
        $this->assertTrue(count($os) == 1);
        return true;
    }

    function _testQuery16() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query(
            "from eptAuthor as a where " 
            . "a.contact.phone = '4444444' and a.contact.zipcode = '04040'"
            ));
        $this->assertTrue(count($os) == 1);
        return true;
    }

    function _testQuery18() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query(
            "from eptBookstore as bstr where "
            . "bstr.books.contains(b) "
            . "and b.authors.contains(a) and a.contact.zipcode = '04040'"
            ));
        $this->assertTrue(count($os) == 1);
        return true;
    }

    function _testQuery19() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query(
            "from eptBookstore as bstr where "
            . "bstr.books.contains(b) and bstr.books.contains(b2)"
            . "and b.authors.contains(a) and a.contact.zipcode = '04040'"
            . "and b2.pages = 172"
            ));
        $this->assertTrue(count($os) == 1);
        return true;
    }

    function _testQuery20() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query(
            "from eptBook as b where "
            . "b.price > 400.00 and (b.pages < 200 or b.pages > 400)"
            ));
        $this->assertTrue(count($os) == 2);
        return true;
    }

    function _testQuery21() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query(
            "from eptBook as b where "
            . "(b.pages < 200 or b.pages > 400) and  b.price > 400.00"
            ));
        $this->assertTrue(count($os) == 2);
        return true;
    }

    function _testQuery22() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query(
            "from eptBook as b where "
            . "(b.pages < 200 and b.price > 400.00) or (b.pages > 400 and b.price > 400.00)"
            ));
        $this->assertTrue(count($os) == 2);
        return true;
    }
    
    // avg
    function _testQuery23() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query(
            "avg(b.pages) from eptBook as b where "
            . "(b.pages < 200 and b.price > 400.00) or (b.pages > 400 and b.price > 400.00)"
            ));
        $this->assertTrue($r == (172 + 728) / 2);
        return true;
    }
    
    // sum
    function _testQuery24() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query(
            "sum(b.pages) from eptBook as b where b.price > 400.00 and (b.pages < 200 or b.pages > 400)"
            ));
        $this->assertTrue($r == 172 + 728);
        return true;
    }
    
    // min
    function _testQuery25() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query(
            "min(b.pages) from eptBook as b where b.price > 400.00"
            ));
        $this->assertTrue($r == 172);
        return true;
    }

    // max
    function _testQuery26() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query("max(b.pages) from eptBook as b where b.pages < 200"));
        $this->assertTrue($r == 172);
        return true;
    }

    // count
    function _testQuery27() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query("count(*) from eptBook as b where b.pages < 400"));
        $this->assertTrue($r == 4);
        return true;
    }
    // contains array
    function _testQuery28() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query(
            "from eptBook as b where b.authors.contains(?)", 
            array('name' => 'Ralph Johnson')
            ));
        $this->assertTrue(count($r) == 2);
        return true;
    }

    // contains array 2
    function _testQuery29() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query(
            "from eptBook as b where b.authors.contains(?)", 
            array('contact' => array(
                'zipcode' => '03030',
                ))
            ));
        $this->assertTrue(count($r) == 2);
        return true;
    }

    // contains object
    function _testQuery30() {
        $this->assertTrue($m = epManager::instance());
        
        // create an exmaple author object
        $author = $m->create('eptAuthor');
        foreach($author as $k => $v) {
            if ($k != 'oid') {
                $author[$k] = null;
            }
        }
        $author->name = 'Ralph Johnson';
        $author->epSetCommittable(false); // false: prevented from being committed

        $this->assertTrue($r = $m->query("from eptBook as b where b.authors.contains(?)", $author));
        $this->assertTrue(count($r) == 2);
        return true;
    }

    // contains object 2
    function _testQuery31() {
        $this->assertTrue($m = epManager::instance());
        
        // create an exmaple contact object
        $author = $m->create('eptAuthor');
        foreach($author as $k => $v) {
            if ($k != 'oid') {
                $author[$k] = null;
            }
        }
        $author->epSetCommittable(false); // false: prevented from being committed

        // create author's contact
        $author->contact = $m->create('eptContact');
        foreach($author->contact as $k => $v) {
            if ($k != 'oid') {
                $author->contact[$k] = null;
            }
        }
        $author->contact->zipcode = '03030';
        $author->contact->epSetCommittable(false); // false: prevented from being committed
        
        $this->assertTrue($r = $m->query("from eptBook as b where b.authors.contains(?)", $author));
        $this->assertTrue(count($r) == 2);
        return true;
    }

    // contains object 3 (committed)
    function _testQuery32() {
        $this->assertTrue($m = epManager::instance());
        
        // create an exmaple contact object
        $author = $m->find('from eptAuthor where oid = ?', $this->oid_authors[2]);

        // search by existing object
        $this->assertTrue($r = $m->query("from eptBook as b where b.authors.contains(?)", $author[0]));
        $this->assertTrue(count($r) == 2);
        return true;
    }

    // one-valued = array
    function _testQuery33() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query(
            "from eptBook as b where b.authors.contains(a) and a.contact = ?", 
            array('zipcode' => '01010')
            ));
        $this->assertTrue(count($r) == 2);
        return true;
    }

    // one-valued = object
    function _testQuery34() {
        $this->assertTrue($m = epManager::instance());

        // create a contact (example object)
        $contact = $m->create('eptContact');
        foreach($contact as $k => $v) {
            if ($k != 'oid') {
                $contact[$k] = null;
            }
        }
        $contact->zipcode = '03030';
        $contact->epSetCommittable(false); // false: prevented from being committed

        $this->assertTrue($r = $m->query("from eptBook as b where b.authors.contains(a) and a.contact = ? order by b.oid", $contact));
        $this->assertTrue(count($r) == 2);
        $this->assertTrue((integer)$r[0]->oid == (integer)$this->oid_books[0]);
        $this->assertTrue((integer)$r[1]->oid == (integer)$this->oid_books[3]);
        return true;
    }

    // one-valued = existing object
    function _testQuery35() {
        $this->assertTrue($m = epManager::instance());
        $contact = $m->find("from eptContact where zipcode = '03030'");
        $this->assertTrue($r = $m->query("from eptBook as b where b.authors.contains(a) and a.contact = ? order by b.oid", $contact[0]));
        $this->assertTrue(count($r) == 2);
        $this->assertTrue((integer)$r[0]->oid == (integer)$this->oid_books[0]);
        $this->assertTrue((integer)$r[1]->oid == (integer)$this->oid_books[3]);
        return true;
    }

    // subclasses
    function _testQuery36() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query("from eptBase where 1"));
        // 4 authors, 4 contacts, 5 books 
        // (note that bookstore is not based off eptBase)
        $this->assertTrue(count($r) == 13);
        return true;
    }

    // subclasses: count
    function _testQuery37() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query("count(*) from eptBase where 1"));
        // 4 authors, 4 contacts, 5 books 
        // (note that bookstore is not based off eptBase)
        $this->assertTrue($r == 13);
        return true;
    }

    // subclasses: min
    function _testQuery38() {
        $this->assertTrue($m = epManager::instance());
        $this->assertNotNull($r = $m->query("min(oid) from eptBase where 1"));
        $this->assertTrue($r == min(min($this->oid_books), min($this->oid_authors), min($this->oid_contacts)));
        return true;
    }

    // subclasses: max
    function _testQuery39() {
        $this->assertTrue($m = epManager::instance());
        $this->assertNotNull($r = $m->query("max(oid) from eptBase where 1"));
        $this->assertTrue($r == max(max($this->oid_books), max($this->oid_authors), max($this->oid_contacts)));
        return true;
    }

    // subclasses: min
    function _testQuery40() {
        $this->assertTrue($m = epManager::instance());
        $this->assertNotNull($r = $m->query("min(oid) from eptBase where 1"));
        $this->assertTrue($r == min(min($this->oid_books), min($this->oid_authors), min($this->oid_contacts)));
        return true;
    }

    // subclasses: avg
    function _testQuery41() {
        $this->assertTrue($m = epManager::instance());
        $this->assertNotNull($r = $m->query("avg(oid) from eptBase where 1"));
        $sum = array_sum($this->oid_books) + array_sum($this->oid_authors) + array_sum($this->oid_contacts);
        $count = count($this->oid_books) + count($this->oid_authors) + count($this->oid_contacts);
        $this->assertTrue($r == $sum/$count);
        return true;
    }
    
    // subclasses: limit
    function _testQuery42() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query("from eptBase where 1 limit 5"));
        $this->assertTrue(count($r) == 5);
        return true;
    }
    
    // subclasses: limit w/ offset
    function _testQuery43() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query("from eptBase where 1 limit 2, 5"));
        $this->assertTrue(count($r) == 5);
        return true;
    }

    // subclasses: orderby asc
    function _testQuery44() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query("from eptBase where 1 order by oid"));
        $oids = array_merge($this->oid_books, $this->oid_authors, $this->oid_contacts);
         sort($oids);
         $this->assertTrue($r[0]->oid == $oids[0]);
         // 4 authors, 4 contacts, 5 books
         // (note that bookstore is not based off eptBase)
         for ($i = 0; $i < 13; $i++) {
             $this->assertTrue($r[$i]->oid == $oids[$i]);
         }
         return true;
    }
    
    // subclasses: orderby desc
    function _testQuery45() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query("from eptBase where 1 order by oid desc"));
        $oids = array_merge($this->oid_books, $this->oid_authors, $this->oid_contacts);
        rsort($oids);
        $this->assertTrue($r[0]->oid == $oids[0]);
        // 4 authors, 4 contacts, 5 books
        // (note that bookstore is not based off eptBase)
        for ($i = 0; $i < 13; $i++) {
            $this->assertTrue($r[$i]->oid == $oids[$i]);
        }
        return true;
    }
    
    // subclasses: orderby asc limit
    function _testQuery46() {
         $this->assertTrue($m = epManager::instance());
         $this->assertTrue($r = $m->query("from eptBase where 1 order by oid limit 5"));
         $this->assertTrue(count($r) == 5);
         $oids = array_merge($this->oid_books, $this->oid_authors, $this->oid_contacts);
         sort($oids);
         $this->assertTrue($r[0]->oid == $oids[0]);
         // 4 authors, 4 contacts, 5 books
         // (note that bookstore is not based off eptBase)
         for ($i = 0; $i < 5; $i++) {
             $this->assertTrue($r[$i]->oid == $oids[$i]);
         }
         return true;
    }

    // subclasses: orderby asc limit offset
    function _testQuery47() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query("from eptBase where 1 order by oid asc limit 5, 5"));
        $this->assertTrue(count($r) == 5);
        $oids = array_merge($this->oid_books, $this->oid_authors, $this->oid_contacts);
        sort($oids);
        $this->assertTrue($r[0]->oid == $oids[5]);
        // 4 authors, 4 contacts, 5 books
        // (note that bookstore is not based off eptBase)
        for ($i = 0; $i < 5; $i++) {
             $this->assertTrue($r[$i]->oid == $oids[5 + $i]);
        }
        return true;
    }
    
    // subclasses: orderby desc limit
    function _testQuery48() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query("from eptBase where 1 order by oid desc limit 5"));
        $this->assertTrue(count($r) == 5);
        $oids = array_merge($this->oid_books, $this->oid_authors, $this->oid_contacts);
        rsort($oids);
        $this->assertTrue($r[0]->oid == $oids[0]);
        // 4 authors, 4 contacts, 5 books
        // (note that bookstore is not based off eptBase)
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($r[$i]->oid == $oids[$i]);
        }
        return true;
    }
    
    // subclasses: orderby asc limit offset
    function _testQuery49() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query("from eptBase where 1 order by oid desc limit 5, 5"));
        $this->assertTrue(count($r) == 5);
        $oids = array_merge($this->oid_books, $this->oid_authors, $this->oid_contacts);
        rsort($oids);
        $this->assertTrue($r[0]->oid == $oids[5]);
        // 4 authors, 4 contacts, 5 books
        // (note that bookstore is not based off eptBase)
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($r[$i]->oid == $oids[5 + $i]);
        }
        return true;
    }

    // order by random()
    function _testQuery50() {
        
        $n = 3; // limit length

        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query("from eptAuthor where 1 order by random() limit " . $n));
        $this->assertTrue(count($r) == $n);

        // don't want to check randomness, but let's make sure objects are distinct 
        for($i = 0; $i < $n; $i ++) {
            for($j = $i + 1; $j < $n; $j ++) {
                $this->assertTrue($r[$i]->oid != $r[$j]->oid);
            }
        }

        return true;
    }

    // order by random() - 2
    function _testQuery51() {
        
        $n = 3; // limit length

        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query("from eptBook as b where b.pages >= 172 order by random() limit " . $n));
        $this->assertTrue(count($r) == $n);

        // don't want to check randomness, but let's make sure objects are distinct 
        for($i = 0; $i < $n; $i ++) {
            for($j = $i + 1; $j < $n; $j ++) {
                $this->assertTrue($r[$i]->oid != $r[$j]->oid);
            }
        }

        return true;
    }

    // order by random() - 3 - subclasses
    function _testQuery52() {
        
        $n = 5; // limit length

        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($r = $m->query("from eptBase where 1 order by random() limit 5, " . $n));
        $this->assertTrue(count($r) == $n);

        // don't want to check randomness, but let's make sure objects are distinct 
        for($i = 0; $i < $n; $i ++) {
            $this->assertTrue($eoid_i = $m->encodeUoid($r[$i]));
            for($j = $i + 1; $j < $n; $j ++) {
                $this->assertTrue($eoid_j = $m->encodeUoid($r[$j]));
                $this->assertTrue($eoid_i != $eoid_j);
            }
        }

        return true;
    }

    function _testQuery53() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query("from eptBook as b where b.pages in ( 395, 172 ) order by pages"));
        $this->assertTrue(count($os) == 2);
        $this->assertTrue($os[0]->pages == 172);
        $this->assertTrue($os[1]->pages == 395);
        return true;
    }

    function _testQuery54() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query("from eptAuthor as a where a.name in ( 'Erich Gamma', 'Richard Helm' ) order by name"));
        $this->assertTrue(count($os) == 2);
        $this->assertTrue($os[0]->name == 'Erich Gamma');
        $this->assertTrue($os[1]->name == 'Richard Helm');
        return true;
    }

    function _testQuery55() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query("from eptBook as b where b.pages in (?) order by pages", array(172,395)));
        $this->assertTrue(count($os) == 2);
        $this->assertTrue($os[0]->pages == 172);
        $this->assertTrue($os[1]->pages == 395);
        return true;
    }

    function _testQuery56() {
        $this->assertTrue($m = epManager::instance());
        $this->assertTrue($os = $m->query("from eptAuthor as a where a.name in (?) order by name", array('Richard Helm', 'Erich Gamma')));
        $this->assertTrue(count($os) == 2);
        $this->assertTrue($os[0]->name == 'Erich Gamma');
        $this->assertTrue($os[1]->name == 'Richard Helm');
        return true;
    }
}

if (!defined('EP_GROUP_TEST')) {
    $tm = microtime(true);
    $t = new epTestQueryRuntime;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
    $elapsed = microtime(true) - $tm;
    echo epNewLine() . 'Time elapsed: ' . $elapsed . ' seconds' . "\n";
}

?>
