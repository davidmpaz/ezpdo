<?php

/**
 * $Id: epTestQueryBuilder.php 1048 2007-04-13 02:31:17Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1048 $ $Date: 2007-04-12 22:31:17 -0400 (Thu, 12 Apr 2007) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.query
 */

/**#@+
 * need runtime testcase (under ../runtime) and epQueryBuilder 
 */
include_once(dirname(__FILE__).'/../runtime/epTestRuntime.php');
include_once(EP_SRC_QUERY.'/epQueryBuilder.php');
/**#@-*/

/**
 * The unit test class for {@link epQueryBuilder}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1048 $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.query
 */
class epTestQueryBuilder extends epTestRuntime {

    /**
     * Test builder {@link epQueryBuilder}
     * @param string $q the query string
     * @param array $args the array of arguments
     * @param string $expect the expected resultant SQL statement
     * @param boolean $debug whether to print out debugging info
     * @return void
     */
    function _testBuilder($q, $args = array(), $expect, $debug = false) {

        static $tests = 0;

        $tests ++;
        echo "  testBuilder$tests...";

        // setup manager
        $this->_setUp('adodb', 'mysql');
        $this->assertTrue($this->m);

        // parse query string
        $this->assertNotNull($p = new epQueryParser($q));
        $t0 = microtime(true);
        $root = $p->parse();
        $t = microtime(true) - $t0;
        $this->assertTrue($root);
        
        // debug
        if ($debug) {
            echo 'Parser : ' . $t . ' s' . "\n";
        }

        // build sql statement
        $b = new epQueryBuilder($root, $q, $args);
        $this->assertNotNull($b);
        $t0 = microtime(true);
        $sql = $b->build();
        $t = microtime(true) - $t0;

        // debug
        if ($debug) {
            echo 'Builder: ' . $t . ' s' . "\n";
            echo "EZOQL        : $q\n";
        }

        $r = true;
        
        //print_r($sql);

        if (is_string($expect)) {
            
            // debug
            if ($debug) {
                echo "SQL Expected : $expect\n";
                echo "SQL Actual   : " . $sql[0] . "\n";
            }

            $this->assertTrue($r = ($sql[0] === $expect));

        } else {
            
            for ($i = 0; $i < count($expect); $i++) {
                
                // debug
                if ($debug) {
                    echo "SQL Expected [$i] : " . $expect[$i] . "\n";
                    echo "SQL Actual   [$i] : " . $sql[$i] . "\n";
                }
                
                $this->assertTrue($r_ = ($sql[$i] === $expect[$i]));
                
                if ($r_ == false) {
                    $r = false;
                }
            }
        }
        
        echo "done\n";
        
        // return whether result matches expected
        return $r;
    }

    /*
     * Tests need to include:
     *      placeholder for strings
     *      placeholder for objects
     *      placeholder for array
     *      rename table
     *      rename variable
     *      one to (1|M) (one level deep)
     *      one to (1|M) (multi-level deep)
     *      M to (1|M) (one level deep)
     *      M to (1|M) (multi-level deep)
     *      multi-table select
     *      and it repeated for multi-level deep 'from' table
     */
    // simple placeholder
    function testBuilder1() {
        $q = "from eptItemMagazine as m where m.? == ? limit ?,3";
        $args = array('name', 'Harry Potter', 1);
        $e = "SELECT DISTINCT `m`.* FROM `eptItemMagazine` AS `m` WHERE `m`.`name`='Harry Potter' LIMIT 3 OFFSET 1";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // change in table and variable names
    function testBuilder2() {
        $q = "from eptContactInfo as c where c.? == ? limit ?,3";
        $args = array('phone', '1234567', 1);
        $e = "SELECT DISTINCT `c`.* FROM `wierdTableName` AS `c` WHERE `c`.`desc`='1234567' LIMIT 3 OFFSET 1";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder3() {
        $q = "from eptAuthor as a where a.name like %amma%";
        $args = array();
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` WHERE `a`.`name` LIKE '%amma%'";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder4() {
        $q = "from eptBook as book where book.pages > ?";
        $args = array(0);
        $e = "SELECT DISTINCT `book`.* FROM `eptBook` AS `book` WHERE `book`.`pages`>0";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder5() {
        $q = "from eptBook as book where book.pages < ?";
        $args = array(0);
        $e = "SELECT DISTINCT `book`.* FROM `eptBook` AS `book` WHERE `book`.`pages`<0";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }
    
    function testBuilder6() {
        $q = "from eptBook as book where book.title LIKE 'title%'";
        $args = array();
        $e = "SELECT DISTINCT `book`.* FROM `eptBook` AS `book` WHERE `book`.`title` LIKE 'title%'";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder7() {
        $q = "from eptBase as base where base.uuid = ?";
        $args = array(0);
        $e = array();
        $e[] = "SELECT DISTINCT `base`.* FROM `eptBase` AS `base` WHERE `base`.`uuid`='0'";
        $e[] = "SELECT DISTINCT `base`.* FROM `eptAuthor` AS `base` WHERE `base`.`uuid`='0'";
        $e[] = "SELECT DISTINCT `base`.* FROM `eptBook` AS `base` WHERE `base`.`uuid`='0'";
        $e[] = "SELECT DISTINCT `base`.* FROM `eptContact` AS `base` WHERE `base`.`uuid`='0'";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder8() {
        $q = "from eptAuthor as a where a.age > 1 + 2";
        $args = array();
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` WHERE `a`.`age`>1+2";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder9() {
        $q = "from eptAuthor as a, eptBook as b where a.age > b.pages + 1";
        $args = array();
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a`, `eptBook` AS `b` WHERE `a`.`age`>`b`.`pages`+1";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder10() {
        $q = "from eptAuthor as a where a.age between ? and ?";
        $args = array(30, 40);
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` WHERE `a`.`age` BETWEEN 30 AND 40";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder11() {
        $q = "from eptAuthor as a, eptBook as b where a.contact.phone like '%478%'";
        $args = array();
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a`, `eptBook` AS `b` LEFT JOIN `_ez_relation_eptauthor_eptcontact` AS `_1` ON `_1`.var_a = 'contact' AND (`_1`.class_a = 'eptAuthor' AND `_1`.oid_a = `a`.`eoid`) LEFT JOIN `eptContact` AS `_2` ON `_1`.base_b = 'eptContact' AND `_1`.class_b = 'eptContact' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`phone` LIKE '%478%'";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }
    
    function testBuilder12() {
        $q = "from eptAuthor as a where a.contact.phone = a.name";
        $args = array();
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` LEFT JOIN `_ez_relation_eptauthor_eptcontact` AS `_1` ON `_1`.var_a = 'contact' AND (`_1`.class_a = 'eptAuthor' AND `_1`.oid_a = `a`.`eoid`) LEFT JOIN `eptContact` AS `_2` ON `_1`.base_b = 'eptContact' AND `_1`.class_b = 'eptContact' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`phone`=`a`.`name`";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }
    
    function testBuilder13() {
        $q = "from eptAuthor as a where a.contact.phone between ? and ?";
        $args = array(30, 40);
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` LEFT JOIN `_ez_relation_eptauthor_eptcontact` AS `_1` ON `_1`.var_a = 'contact' AND (`_1`.class_a = 'eptAuthor' AND `_1`.oid_a = `a`.`eoid`) LEFT JOIN `eptContact` AS `_2` ON `_1`.base_b = 'eptContact' AND `_1`.class_b = 'eptContact' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`phone` BETWEEN '30' AND '40'";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder14() {
        $q = "from eptAuthor as a where a.contact.phone is null";
        $args = array(30, 40);
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` LEFT JOIN `_ez_relation_eptauthor_eptcontact` AS `_1` ON `_1`.var_a = 'contact' AND (`_1`.class_a = 'eptAuthor' AND `_1`.oid_a = `a`.`eoid`) LEFT JOIN `eptContact` AS `_2` ON `_1`.base_b = 'eptContact' AND `_1`.class_b = 'eptContact' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`phone` IS NULL";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder15() {
        $q = "from eptAuthor as a where a.contact.phone is not null";
        $args = array(30, 40);
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` LEFT JOIN `_ez_relation_eptauthor_eptcontact` AS `_1` ON `_1`.var_a = 'contact' AND (`_1`.class_a = 'eptAuthor' AND `_1`.oid_a = `a`.`eoid`) LEFT JOIN `eptContact` AS `_2` ON `_1`.base_b = 'eptContact' AND `_1`.class_b = 'eptContact' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`phone` IS NOT NULL";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder16() {
        $q = "avg(b.pages) from eptBook as b where b.title LIKE '%Design%'";
        $args = array('name', 'Harry Potter', 1);
        $e = "SELECT DISTINCT avg(`b`.`pages`) FROM `eptBook` AS `b` WHERE `b`.`title` LIKE '%Design%'";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder17() {
        $q = "from eptBook as b where b.authors.contains(?)";
        $args = array('xyz');
        $e = "SELECT DISTINCT `b`.* FROM `eptBook` AS `b` LEFT JOIN `_ez_relation_eptauthor_eptbook` AS `_3` ON `_3`.var_a = 'authors' AND (`_3`.class_a = 'eptBook' AND `_3`.oid_a = `b`.`eoid`) LEFT JOIN `eptAuthor` AS `_2xyz` ON `_3`.base_b = 'eptAuthor' AND `_3`.class_b = 'eptAuthor' AND `_3`.oid_b = `_2xyz`.`eoid` WHERE 1=1";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder18() {
        $q = "from eptBook as b where b.authors.contains(a) and a.contact = ?";
        $args = array(array('zipcode' => '12345'));
        $e = "SELECT DISTINCT `b`.* FROM `eptBook` AS `b` LEFT JOIN `_ez_relation_eptauthor_eptbook` AS `_3` ON `_3`.var_a = 'authors' AND (`_3`.class_a = 'eptBook' AND `_3`.oid_a = `b`.`eoid`) LEFT JOIN `eptAuthor` AS `_2a` ON `_3`.base_b = 'eptAuthor' AND `_3`.class_b = 'eptAuthor' AND `_3`.oid_b = `_2a`.`eoid` LEFT JOIN `_ez_relation_eptauthor_eptcontact` AS `_4` ON `_4`.var_a = 'contact' AND (`_4`.class_a = 'eptAuthor' AND `_4`.oid_a = `_2a`.`eoid`) LEFT JOIN `eptContact` AS `_5` ON `_4`.base_b = 'eptContact' AND `_4`.class_b = 'eptContact' AND `_4`.oid_b = `_5`.`eoid` WHERE `_5`.`zipcode`='12345'";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test 1 - M relationship, no deep levels
    function testBuilder19() {
        $q = "from eptReviewer as r where r.books.contains(b) AND b.author = ?";
        $args = array('George McFly');
        $e = "SELECT DISTINCT `r`.* FROM `eptReviewer` AS `r` LEFT JOIN `_ez_relation_eptitembooknonfiction_eptreviewer` AS `_3` ON `_3`.var_a = 'books' AND (`_3`.class_a = 'eptReviewer' AND `_3`.oid_a = `r`.`eoid`) LEFT JOIN `eptItemBookNonFiction` AS `_2b` ON `_3`.base_b = 'eptItemBookNonFiction' AND `_3`.class_b = 'eptItemBookNonFiction' AND `_3`.oid_b = `_2b`.`eoid` WHERE `_2b`.`author`='George McFly'";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test M - 1 relationship, no deep levels
    function testBuilder20() {
        $q = "from eptItemBookNonFiction as b where b.reviewer.oid = ?";
        $args = array('15');
        $e = "SELECT DISTINCT `b`.* FROM `eptItemBookNonFiction` AS `b` LEFT JOIN `_ez_relation_eptitembooknonfiction_eptreviewer` AS `_1` ON `_1`.var_a = 'reviewer' AND (`_1`.class_a = 'eptItemBookNonFiction' AND `_1`.oid_a = `b`.`eoid`) LEFT JOIN `eptReviewer` AS `_2` ON `_1`.base_b = 'eptReviewer' AND `_1`.class_b = 'eptReviewer' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`eoid`=15";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test 1 - M relationship, 3 levels deep
    function testBuilder21() {
        $q = "from eptEmployee as e where e.sales.contains(s) AND s.amount = ?";
        $args = array('10.00');
        $e = "SELECT DISTINCT `e`.* FROM `otherEmployee` AS `e` LEFT JOIN `_ez_relation_eptemployee_eptsale` AS `_6` ON `_6`.var_a = 'sales' AND (`_6`.class_a = 'eptEmployee' AND `_6`.oid_a = `e`.`eoid`) LEFT JOIN `eptSale` AS `_2s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSale' AND `_6`.oid_b = `_2s`.`eoid` LEFT JOIN `eptSaleBook` AS `_3s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleBook' AND `_6`.oid_b = `_3s`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_4s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleMultimedia' AND `_6`.oid_b = `_4s`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_5s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleMultimediaDownload' AND `_6`.oid_b = `_5s`.`eoid` WHERE `_2s`.`amount`='10.00' OR `_3s`.`amount`='10.00' OR `_4s`.`amount`='10.00' OR `_5s`.`amount`='10.00'";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test M - 1 relationship, 3 levels deep
    function testBuilder22() {
        $q = "from eptPublisher as p where p.books.contains(b) and b.oid = ?";
        $args = array('5');
        $e = "SELECT DISTINCT `p`.* FROM `eptPublisher` AS `p` LEFT JOIN `_ez_relation_eptitembook_eptpublisher` AS `_5` ON `_5`.var_a = 'books' AND (`_5`.class_a = 'eptPublisher' AND `_5`.oid_a = `p`.`eoid`) LEFT JOIN `eptItemBookFiction` AS `_2b` ON `_5`.base_b = 'eptItemBook' AND `_5`.class_b = 'eptItemBookFiction' AND `_5`.oid_b = `_2b`.`eoid` LEFT JOIN `eptItemBookNonFiction` AS `_3b` ON `_5`.base_b = 'eptItemBook' AND `_5`.class_b = 'eptItemBookNonFiction' AND `_5`.oid_b = `_3b`.`eoid` LEFT JOIN `eptItemBookFictionFantasy` AS `_4b` ON `_5`.base_b = 'eptItemBook' AND `_5`.class_b = 'eptItemBookFictionFantasy' AND `_5`.oid_b = `_4b`.`eoid` WHERE `_2b`.`eoid`=5 OR `_3b`.`eoid`=5 OR `_4b`.`eoid`=5";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test M - 1 AND 1 - M relationship
    function testBuilder23() {
        $q = "from eptItemBookNonFiction as b where b.publisher.oid = ? AND b.editors.contains(e) AND e.oid = ?";
        $args = array('5', '10');
        $e = "SELECT DISTINCT `b`.* FROM `eptItemBookNonFiction` AS `b` LEFT JOIN `_ez_relation_eptitembook_eptpublisher` AS `_1` ON `_1`.var_a = 'publisher' AND (`_1`.class_a = 'eptItemBookNonFiction' AND `_1`.oid_a = `b`.`eoid`) LEFT JOIN `eptPublisher` AS `_2` ON `_1`.base_b = 'eptPublisher' AND `_1`.class_b = 'eptPublisher' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `_ez_relation_epteditor_eptitembook` AS `_5` ON `_5`.var_a = 'editors' AND (`_5`.class_a = 'eptItemBookNonFiction' AND `_5`.oid_a = `b`.`eoid`) LEFT JOIN `eptEditor` AS `_4e` ON `_5`.base_b = 'eptEditor' AND `_5`.class_b = 'eptEditor' AND `_5`.oid_b = `_4e`.`eoid` WHERE (`_2`.`eoid`=5) AND (`_4e`.`eoid`=10)";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test 1 - 1 relationship with From being deep
    function testBuilder24() {
        $q = "from eptItem as i where i.store.oid = ?";
        $args = array('5');
        $e = array (
          0 => "SELECT DISTINCT `i`.* FROM `eptItemMagazine` AS `i` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemMagazine' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`eoid`=5",
          1 => "SELECT DISTINCT `i`.* FROM `eptItemBookFiction` AS `i` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemBookFiction' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`eoid`=5",
          2 => "SELECT DISTINCT `i`.* FROM `eptItemBookNonFiction` AS `i` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemBookNonFiction' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`eoid`=5",
          3 => "SELECT DISTINCT `i`.* FROM `eptItemBookFictionFantasy` AS `i` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemBookFictionFantasy' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`eoid`=5",
          4 => "SELECT DISTINCT `i`.* FROM `eptItemRecordingCD` AS `i` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemRecordingCD' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`eoid`=5",
          5 => "SELECT DISTINCT `i`.* FROM `eptItemRecordingTape` AS `i` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemRecordingTape' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`eoid`=5",
          6 => "SELECT DISTINCT `i`.* FROM `eptItemVideoDVD` AS `i` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemVideoDVD' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`eoid`=5",
          7 => "SELECT DISTINCT `i`.* FROM `eptItemVideoVHS` AS `i` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemVideoVHS' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`eoid`=5",
        );
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test 1 - M relationship with From being deep
    function testBuilder25() {
        $q = "from eptItemBook as b where b.editors.contains(e) AND e.oid = ?";
        $args = array('5');
        $e = array (
          0 => "SELECT DISTINCT `b`.* FROM `eptItemBookFiction` AS `b` LEFT JOIN `_ez_relation_epteditor_eptitembook` AS `_3` ON `_3`.var_a = 'editors' AND (`_3`.class_a = 'eptItemBookFiction' AND `_3`.oid_a = `b`.`eoid`) LEFT JOIN `eptEditor` AS `_2e` ON `_3`.base_b = 'eptEditor' AND `_3`.class_b = 'eptEditor' AND `_3`.oid_b = `_2e`.`eoid` WHERE `_2e`.`eoid`=5",
          1 => "SELECT DISTINCT `b`.* FROM `eptItemBookNonFiction` AS `b` LEFT JOIN `_ez_relation_epteditor_eptitembook` AS `_3` ON `_3`.var_a = 'editors' AND (`_3`.class_a = 'eptItemBookNonFiction' AND `_3`.oid_a = `b`.`eoid`) LEFT JOIN `eptEditor` AS `_2e` ON `_3`.base_b = 'eptEditor' AND `_3`.class_b = 'eptEditor' AND `_3`.oid_b = `_2e`.`eoid` WHERE `_2e`.`eoid`=5",
          2 => "SELECT DISTINCT `b`.* FROM `eptItemBookFictionFantasy` AS `b` LEFT JOIN `_ez_relation_epteditor_eptitembook` AS `_3` ON `_3`.var_a = 'editors' AND (`_3`.class_a = 'eptItemBookFictionFantasy' AND `_3`.oid_a = `b`.`eoid`) LEFT JOIN `eptEditor` AS `_2e` ON `_3`.base_b = 'eptEditor' AND `_3`.class_b = 'eptEditor' AND `_3`.oid_b = `_2e`.`eoid` WHERE `_2e`.`eoid`=5",
        );
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test 1 - 1 relationship with From being deep and where being deep
    function testBuilder26() {
        $q = "from eptItem as i where i.sale.type = ? AND i.cost = ?";
        $args = array('0', '0.00');
        $e = array (
            0 => "SELECT DISTINCT `i`.* FROM `eptItemMagazine` AS `i` LEFT JOIN `_ez_relation_eptitem_eptsale` AS `_1` ON `_1`.var_a = 'sale' AND (`_1`.class_a = 'eptItemMagazine' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptSale` AS `_2` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSale' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `eptSaleBook` AS `_3` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleBook' AND `_1`.oid_b = `_3`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_4` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimedia' AND `_1`.oid_b = `_4`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_5` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimediaDownload' AND `_1`.oid_b = `_5`.`eoid` WHERE (`_2`.`type`=0 OR `_3`.`type`=0 OR `_4`.`type`=0 OR `_5`.`type`=0) AND (`i`.`cost`='0.00')",
            1 => "SELECT DISTINCT `i`.* FROM `eptItemBookFiction` AS `i` LEFT JOIN `_ez_relation_eptitem_eptsale` AS `_1` ON `_1`.var_a = 'sale' AND (`_1`.class_a = 'eptItemBookFiction' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptSale` AS `_2` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSale' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `eptSaleBook` AS `_3` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleBook' AND `_1`.oid_b = `_3`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_4` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimedia' AND `_1`.oid_b = `_4`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_5` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimediaDownload' AND `_1`.oid_b = `_5`.`eoid` WHERE (`_2`.`type`=0 OR `_3`.`type`=0 OR `_4`.`type`=0 OR `_5`.`type`=0) AND (`i`.`cost`='0.00')",
            2 => "SELECT DISTINCT `i`.* FROM `eptItemBookNonFiction` AS `i` LEFT JOIN `_ez_relation_eptitem_eptsale` AS `_1` ON `_1`.var_a = 'sale' AND (`_1`.class_a = 'eptItemBookNonFiction' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptSale` AS `_2` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSale' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `eptSaleBook` AS `_3` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleBook' AND `_1`.oid_b = `_3`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_4` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimedia' AND `_1`.oid_b = `_4`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_5` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimediaDownload' AND `_1`.oid_b = `_5`.`eoid` WHERE (`_2`.`type`=0 OR `_3`.`type`=0 OR `_4`.`type`=0 OR `_5`.`type`=0) AND (`i`.`cost`='0.00')",
            3 => "SELECT DISTINCT `i`.* FROM `eptItemBookFictionFantasy` AS `i` LEFT JOIN `_ez_relation_eptitem_eptsale` AS `_1` ON `_1`.var_a = 'sale' AND (`_1`.class_a = 'eptItemBookFictionFantasy' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptSale` AS `_2` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSale' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `eptSaleBook` AS `_3` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleBook' AND `_1`.oid_b = `_3`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_4` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimedia' AND `_1`.oid_b = `_4`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_5` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimediaDownload' AND `_1`.oid_b = `_5`.`eoid` WHERE (`_2`.`type`=0 OR `_3`.`type`=0 OR `_4`.`type`=0 OR `_5`.`type`=0) AND (`i`.`cost`='0.00')",
            4 => "SELECT DISTINCT `i`.* FROM `eptItemRecordingCD` AS `i` LEFT JOIN `_ez_relation_eptitem_eptsale` AS `_1` ON `_1`.var_a = 'sale' AND (`_1`.class_a = 'eptItemRecordingCD' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptSale` AS `_2` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSale' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `eptSaleBook` AS `_3` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleBook' AND `_1`.oid_b = `_3`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_4` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimedia' AND `_1`.oid_b = `_4`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_5` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimediaDownload' AND `_1`.oid_b = `_5`.`eoid` WHERE (`_2`.`type`=0 OR `_3`.`type`=0 OR `_4`.`type`=0 OR `_5`.`type`=0) AND (`i`.`cost`='0.00')",
            5 => "SELECT DISTINCT `i`.* FROM `eptItemRecordingTape` AS `i` LEFT JOIN `_ez_relation_eptitem_eptsale` AS `_1` ON `_1`.var_a = 'sale' AND (`_1`.class_a = 'eptItemRecordingTape' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptSale` AS `_2` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSale' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `eptSaleBook` AS `_3` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleBook' AND `_1`.oid_b = `_3`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_4` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimedia' AND `_1`.oid_b = `_4`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_5` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimediaDownload' AND `_1`.oid_b = `_5`.`eoid` WHERE (`_2`.`type`=0 OR `_3`.`type`=0 OR `_4`.`type`=0 OR `_5`.`type`=0) AND (`i`.`cost`='0.00')",
            6 => "SELECT DISTINCT `i`.* FROM `eptItemVideoDVD` AS `i` LEFT JOIN `_ez_relation_eptitem_eptsale` AS `_1` ON `_1`.var_a = 'sale' AND (`_1`.class_a = 'eptItemVideoDVD' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptSale` AS `_2` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSale' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `eptSaleBook` AS `_3` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleBook' AND `_1`.oid_b = `_3`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_4` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimedia' AND `_1`.oid_b = `_4`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_5` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimediaDownload' AND `_1`.oid_b = `_5`.`eoid` WHERE (`_2`.`type`=0 OR `_3`.`type`=0 OR `_4`.`type`=0 OR `_5`.`type`=0) AND (`i`.`cost`='0.00')",
            7 => "SELECT DISTINCT `i`.* FROM `eptItemVideoVHS` AS `i` LEFT JOIN `_ez_relation_eptitem_eptsale` AS `_1` ON `_1`.var_a = 'sale' AND (`_1`.class_a = 'eptItemVideoVHS' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptSale` AS `_2` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSale' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `eptSaleBook` AS `_3` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleBook' AND `_1`.oid_b = `_3`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_4` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimedia' AND `_1`.oid_b = `_4`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_5` ON `_1`.base_b = 'eptSale' AND `_1`.class_b = 'eptSaleMultimediaDownload' AND `_1`.oid_b = `_5`.`eoid` WHERE (`_2`.`type`=0 OR `_3`.`type`=0 OR `_4`.`type`=0 OR `_5`.`type`=0) AND (`i`.`cost`='0.00')",
        ); 
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test 1 - 1 relationship with From being deep and a second From
    function testBuilder27() {
        $q = "from eptItem as i, eptEmployee as e where i.store.oid = e.store.oid";
        $args = array('0', '0.00');
        $e = array (
          0 => "SELECT DISTINCT `i`.* FROM `eptItemMagazine` AS `i`, `otherEmployee` AS `e` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemMagazine' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `_ez_relation_eptemployee_eptstore` AS `_3` ON `_3`.var_a = 'store' AND (`_3`.class_a = 'eptEmployee' AND `_3`.oid_a = `e`.`eoid`) LEFT JOIN `eptStore` AS `_4` ON `_3`.base_b = 'eptStore' AND `_3`.class_b = 'eptStore' AND `_3`.oid_b = `_4`.`eoid` WHERE `_2`.`eoid`=`_4`.`eoid`",
          1 => "SELECT DISTINCT `i`.* FROM `eptItemBookFiction` AS `i`, `otherEmployee` AS `e` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemBookFiction' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `_ez_relation_eptemployee_eptstore` AS `_3` ON `_3`.var_a = 'store' AND (`_3`.class_a = 'eptEmployee' AND `_3`.oid_a = `e`.`eoid`) LEFT JOIN `eptStore` AS `_4` ON `_3`.base_b = 'eptStore' AND `_3`.class_b = 'eptStore' AND `_3`.oid_b = `_4`.`eoid` WHERE `_2`.`eoid`=`_4`.`eoid`",
          2 => "SELECT DISTINCT `i`.* FROM `eptItemBookNonFiction` AS `i`, `otherEmployee` AS `e` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemBookNonFiction' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `_ez_relation_eptemployee_eptstore` AS `_3` ON `_3`.var_a = 'store' AND (`_3`.class_a = 'eptEmployee' AND `_3`.oid_a = `e`.`eoid`) LEFT JOIN `eptStore` AS `_4` ON `_3`.base_b = 'eptStore' AND `_3`.class_b = 'eptStore' AND `_3`.oid_b = `_4`.`eoid` WHERE `_2`.`eoid`=`_4`.`eoid`",
          3 => "SELECT DISTINCT `i`.* FROM `eptItemBookFictionFantasy` AS `i`, `otherEmployee` AS `e` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemBookFictionFantasy' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `_ez_relation_eptemployee_eptstore` AS `_3` ON `_3`.var_a = 'store' AND (`_3`.class_a = 'eptEmployee' AND `_3`.oid_a = `e`.`eoid`) LEFT JOIN `eptStore` AS `_4` ON `_3`.base_b = 'eptStore' AND `_3`.class_b = 'eptStore' AND `_3`.oid_b = `_4`.`eoid` WHERE `_2`.`eoid`=`_4`.`eoid`",
          4 => "SELECT DISTINCT `i`.* FROM `eptItemRecordingCD` AS `i`, `otherEmployee` AS `e` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemRecordingCD' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `_ez_relation_eptemployee_eptstore` AS `_3` ON `_3`.var_a = 'store' AND (`_3`.class_a = 'eptEmployee' AND `_3`.oid_a = `e`.`eoid`) LEFT JOIN `eptStore` AS `_4` ON `_3`.base_b = 'eptStore' AND `_3`.class_b = 'eptStore' AND `_3`.oid_b = `_4`.`eoid` WHERE `_2`.`eoid`=`_4`.`eoid`",
          5 => "SELECT DISTINCT `i`.* FROM `eptItemRecordingTape` AS `i`, `otherEmployee` AS `e` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemRecordingTape' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `_ez_relation_eptemployee_eptstore` AS `_3` ON `_3`.var_a = 'store' AND (`_3`.class_a = 'eptEmployee' AND `_3`.oid_a = `e`.`eoid`) LEFT JOIN `eptStore` AS `_4` ON `_3`.base_b = 'eptStore' AND `_3`.class_b = 'eptStore' AND `_3`.oid_b = `_4`.`eoid` WHERE `_2`.`eoid`=`_4`.`eoid`",
          6 => "SELECT DISTINCT `i`.* FROM `eptItemVideoDVD` AS `i`, `otherEmployee` AS `e` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemVideoDVD' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `_ez_relation_eptemployee_eptstore` AS `_3` ON `_3`.var_a = 'store' AND (`_3`.class_a = 'eptEmployee' AND `_3`.oid_a = `e`.`eoid`) LEFT JOIN `eptStore` AS `_4` ON `_3`.base_b = 'eptStore' AND `_3`.class_b = 'eptStore' AND `_3`.oid_b = `_4`.`eoid` WHERE `_2`.`eoid`=`_4`.`eoid`",
          7 => "SELECT DISTINCT `i`.* FROM `eptItemVideoVHS` AS `i`, `otherEmployee` AS `e` LEFT JOIN `_ez_relation_eptitem_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptItemVideoVHS' AND `_1`.oid_a = `i`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `_ez_relation_eptemployee_eptstore` AS `_3` ON `_3`.var_a = 'store' AND (`_3`.class_a = 'eptEmployee' AND `_3`.oid_a = `e`.`eoid`) LEFT JOIN `eptStore` AS `_4` ON `_3`.base_b = 'eptStore' AND `_3`.class_b = 'eptStore' AND `_3`.oid_b = `_4`.`eoid` WHERE `_2`.`eoid`=`_4`.`eoid`",
        );
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // order by random()
    function testBuilder28() {
        $q = "from eptAuthor as a where a.contact.phone = a.name order by random()";
        $args = array();
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` LEFT JOIN `_ez_relation_eptauthor_eptcontact` AS `_1` ON `_1`.var_a = 'contact' AND (`_1`.class_a = 'eptAuthor' AND `_1`.oid_a = `a`.`eoid`) LEFT JOIN `eptContact` AS `_2` ON `_1`.base_b = 'eptContact' AND `_1`.class_b = 'eptContact' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`phone`=`a`.`name` ORDER BY RANDOM()";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // multi relationships class.class.class
    function testBuilder29() {
        // build test stuff
        $q = "from eptEmployee as e where e.sales.contains(s) AND s.item.cost = ?";
        $args = array('10.00');
        $e = "SELECT DISTINCT `e`.* FROM `otherEmployee` AS `e` LEFT JOIN `_ez_relation_eptemployee_eptsale` AS `_6` ON `_6`.var_a = 'sales' AND (`_6`.class_a = 'eptEmployee' AND `_6`.oid_a = `e`.`eoid`) LEFT JOIN `eptSale` AS `_2s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSale' AND `_6`.oid_b = `_2s`.`eoid` LEFT JOIN `eptSaleBook` AS `_3s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleBook' AND `_6`.oid_b = `_3s`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_4s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleMultimedia' AND `_6`.oid_b = `_4s`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_5s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleMultimediaDownload' AND `_6`.oid_b = `_5s`.`eoid` LEFT JOIN `_ez_relation_eptitem_eptsale` AS `_7` ON `_7`.var_a = 'item' AND (`_7`.class_a = 'eptSale' AND `_7`.oid_a = `_2s`.`eoid` OR `_7`.class_a = 'eptSaleBook' AND `_7`.oid_a = `_3s`.`eoid` OR `_7`.class_a = 'eptSaleMultimedia' AND `_7`.oid_a = `_4s`.`eoid` OR `_7`.class_a = 'eptSaleMultimediaDownload' AND `_7`.oid_a = `_5s`.`eoid`) LEFT JOIN `eptItemMagazine` AS `_8` ON `_7`.base_b = 'eptItem' AND `_7`.class_b = 'eptItemMagazine' AND `_7`.oid_b = `_8`.`eoid` LEFT JOIN `eptItemBookFiction` AS `_9` ON `_7`.base_b = 'eptItem' AND `_7`.class_b = 'eptItemBookFiction' AND `_7`.oid_b = `_9`.`eoid` LEFT JOIN `eptItemBookNonFiction` AS `_10` ON `_7`.base_b = 'eptItem' AND `_7`.class_b = 'eptItemBookNonFiction' AND `_7`.oid_b = `_10`.`eoid` LEFT JOIN `eptItemBookFictionFantasy` AS `_11` ON `_7`.base_b = 'eptItem' AND `_7`.class_b = 'eptItemBookFictionFantasy' AND `_7`.oid_b = `_11`.`eoid` LEFT JOIN `eptItemRecordingCD` AS `_12` ON `_7`.base_b = 'eptItem' AND `_7`.class_b = 'eptItemRecordingCD' AND `_7`.oid_b = `_12`.`eoid` LEFT JOIN `eptItemRecordingTape` AS `_13` ON `_7`.base_b = 'eptItem' AND `_7`.class_b = 'eptItemRecordingTape' AND `_7`.oid_b = `_13`.`eoid` LEFT JOIN `eptItemVideoDVD` AS `_14` ON `_7`.base_b = 'eptItem' AND `_7`.class_b = 'eptItemVideoDVD' AND `_7`.oid_b = `_14`.`eoid` LEFT JOIN `eptItemVideoVHS` AS `_15` ON `_7`.base_b = 'eptItem' AND `_7`.class_b = 'eptItemVideoVHS' AND `_7`.oid_b = `_15`.`eoid` WHERE `_8`.`cost`='10.00' OR `_9`.`cost`='10.00' OR `_10`.`cost`='10.00' OR `_11`.`cost`='10.00' OR `_12`.`cost`='10.00' OR `_13`.`cost`='10.00' OR `_14`.`cost`='10.00' OR `_15`.`cost`='10.00'";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // different leaves on same path expression tree
    function testBuilder30() {
        $q = "from eptItemBookNonFiction as b where b.reviewer.name = ? AND b.reviewer.age = ?";
        $args = array('Bob', '45');
        $e = "SELECT DISTINCT `b`.* FROM `eptItemBookNonFiction` AS `b` LEFT JOIN `_ez_relation_eptitembooknonfiction_eptreviewer` AS `_1` ON `_1`.var_a = 'reviewer' AND (`_1`.class_a = 'eptItemBookNonFiction' AND `_1`.oid_a = `b`.`eoid`) LEFT JOIN `eptReviewer` AS `_2` ON `_1`.base_b = 'eptReviewer' AND `_1`.class_b = 'eptReviewer' AND `_1`.oid_b = `_2`.`eoid` WHERE (`_2`.`abcde`='Bob') AND (`_2`.`cdefg`=45)";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // multiple contains
    function testBuilder31() {
        $q = "from eptReviewer as r where (r.books.contains(b1) AND b1.author = ?) AND (r.books.contains(b2) AND b2.author = ?)";
        $args = array('Bob', 'Sue');
        $e = "SELECT DISTINCT `r`.* FROM `eptReviewer` AS `r` LEFT JOIN `_ez_relation_eptitembooknonfiction_eptreviewer` AS `_3` ON `_3`.var_a = 'books' AND (`_3`.class_a = 'eptReviewer' AND `_3`.oid_a = `r`.`eoid`) LEFT JOIN `eptItemBookNonFiction` AS `_2b1` ON `_3`.base_b = 'eptItemBookNonFiction' AND `_3`.class_b = 'eptItemBookNonFiction' AND `_3`.oid_b = `_2b1`.`eoid` LEFT JOIN `_ez_relation_eptitembooknonfiction_eptreviewer` AS `_4` ON `_4`.var_a = 'books' AND (`_4`.class_a = 'eptReviewer' AND `_4`.oid_a = `r`.`eoid`) LEFT JOIN `eptItemBookNonFiction` AS `_2b2` ON `_4`.base_b = 'eptItemBookNonFiction' AND `_4`.class_b = 'eptItemBookNonFiction' AND `_4`.oid_b = `_2b2`.`eoid` WHERE ((`_2b1`.`author`='Bob')) AND ((`_2b2`.`author`='Sue'))";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // multiple contains
    function testBuilder32() {
        $q = "from eptEmployee as e where (e.sales.contains(s1) AND s1.item.cost = ?) AND (e.sales.contains(s2) AND s2.item.cost = ?)";
        $args = array('10', '20');
        $e = "SELECT DISTINCT `e`.* FROM `otherEmployee` AS `e` LEFT JOIN `_ez_relation_eptemployee_eptsale` AS `_6` ON `_6`.var_a = 'sales' AND (`_6`.class_a = 'eptEmployee' AND `_6`.oid_a = `e`.`eoid`) LEFT JOIN `eptSale` AS `_2s1` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSale' AND `_6`.oid_b = `_2s1`.`eoid` LEFT JOIN `eptSaleBook` AS `_3s1` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleBook' AND `_6`.oid_b = `_3s1`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_4s1` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleMultimedia' AND `_6`.oid_b = `_4s1`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_5s1` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleMultimediaDownload' AND `_6`.oid_b = `_5s1`.`eoid` LEFT JOIN `_ez_relation_eptemployee_eptsale` AS `_7` ON `_7`.var_a = 'sales' AND (`_7`.class_a = 'eptEmployee' AND `_7`.oid_a = `e`.`eoid`) LEFT JOIN `eptSale` AS `_2s2` ON `_7`.base_b = 'eptSale' AND `_7`.class_b = 'eptSale' AND `_7`.oid_b = `_2s2`.`eoid` LEFT JOIN `eptSaleBook` AS `_3s2` ON `_7`.base_b = 'eptSale' AND `_7`.class_b = 'eptSaleBook' AND `_7`.oid_b = `_3s2`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_4s2` ON `_7`.base_b = 'eptSale' AND `_7`.class_b = 'eptSaleMultimedia' AND `_7`.oid_b = `_4s2`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_5s2` ON `_7`.base_b = 'eptSale' AND `_7`.class_b = 'eptSaleMultimediaDownload' AND `_7`.oid_b = `_5s2`.`eoid` LEFT JOIN `_ez_relation_eptitem_eptsale` AS `_8` ON `_8`.var_a = 'item' AND (`_8`.class_a = 'eptSale' AND `_8`.oid_a = `_2s1`.`eoid` OR `_8`.class_a = 'eptSaleBook' AND `_8`.oid_a = `_3s1`.`eoid` OR `_8`.class_a = 'eptSaleMultimedia' AND `_8`.oid_a = `_4s1`.`eoid` OR `_8`.class_a = 'eptSaleMultimediaDownload' AND `_8`.oid_a = `_5s1`.`eoid`) LEFT JOIN `eptItemMagazine` AS `_9` ON `_8`.base_b = 'eptItem' AND `_8`.class_b = 'eptItemMagazine' AND `_8`.oid_b = `_9`.`eoid` LEFT JOIN `eptItemBookFiction` AS `_10` ON `_8`.base_b = 'eptItem' AND `_8`.class_b = 'eptItemBookFiction' AND `_8`.oid_b = `_10`.`eoid` LEFT JOIN `eptItemBookNonFiction` AS `_11` ON `_8`.base_b = 'eptItem' AND `_8`.class_b = 'eptItemBookNonFiction' AND `_8`.oid_b = `_11`.`eoid` LEFT JOIN `eptItemBookFictionFantasy` AS `_12` ON `_8`.base_b = 'eptItem' AND `_8`.class_b = 'eptItemBookFictionFantasy' AND `_8`.oid_b = `_12`.`eoid` LEFT JOIN `eptItemRecordingCD` AS `_13` ON `_8`.base_b = 'eptItem' AND `_8`.class_b = 'eptItemRecordingCD' AND `_8`.oid_b = `_13`.`eoid` LEFT JOIN `eptItemRecordingTape` AS `_14` ON `_8`.base_b = 'eptItem' AND `_8`.class_b = 'eptItemRecordingTape' AND `_8`.oid_b = `_14`.`eoid` LEFT JOIN `eptItemVideoDVD` AS `_15` ON `_8`.base_b = 'eptItem' AND `_8`.class_b = 'eptItemVideoDVD' AND `_8`.oid_b = `_15`.`eoid` LEFT JOIN `eptItemVideoVHS` AS `_16` ON `_8`.base_b = 'eptItem' AND `_8`.class_b = 'eptItemVideoVHS' AND `_8`.oid_b = `_16`.`eoid` LEFT JOIN `_ez_relation_eptitem_eptsale` AS `_17` ON `_17`.var_a = 'item' AND (`_17`.class_a = 'eptSale' AND `_17`.oid_a = `_2s2`.`eoid` OR `_17`.class_a = 'eptSaleBook' AND `_17`.oid_a = `_3s2`.`eoid` OR `_17`.class_a = 'eptSaleMultimedia' AND `_17`.oid_a = `_4s2`.`eoid` OR `_17`.class_a = 'eptSaleMultimediaDownload' AND `_17`.oid_a = `_5s2`.`eoid`) LEFT JOIN `eptItemMagazine` AS `_18` ON `_17`.base_b = 'eptItem' AND `_17`.class_b = 'eptItemMagazine' AND `_17`.oid_b = `_18`.`eoid` LEFT JOIN `eptItemBookFiction` AS `_19` ON `_17`.base_b = 'eptItem' AND `_17`.class_b = 'eptItemBookFiction' AND `_17`.oid_b = `_19`.`eoid` LEFT JOIN `eptItemBookNonFiction` AS `_20` ON `_17`.base_b = 'eptItem' AND `_17`.class_b = 'eptItemBookNonFiction' AND `_17`.oid_b = `_20`.`eoid` LEFT JOIN `eptItemBookFictionFantasy` AS `_21` ON `_17`.base_b = 'eptItem' AND `_17`.class_b = 'eptItemBookFictionFantasy' AND `_17`.oid_b = `_21`.`eoid` LEFT JOIN `eptItemRecordingCD` AS `_22` ON `_17`.base_b = 'eptItem' AND `_17`.class_b = 'eptItemRecordingCD' AND `_17`.oid_b = `_22`.`eoid` LEFT JOIN `eptItemRecordingTape` AS `_23` ON `_17`.base_b = 'eptItem' AND `_17`.class_b = 'eptItemRecordingTape' AND `_17`.oid_b = `_23`.`eoid` LEFT JOIN `eptItemVideoDVD` AS `_24` ON `_17`.base_b = 'eptItem' AND `_17`.class_b = 'eptItemVideoDVD' AND `_17`.oid_b = `_24`.`eoid` LEFT JOIN `eptItemVideoVHS` AS `_25` ON `_17`.base_b = 'eptItem' AND `_17`.class_b = 'eptItemVideoVHS' AND `_17`.oid_b = `_25`.`eoid` WHERE ((`_9`.`cost`='10' OR `_10`.`cost`='10' OR `_11`.`cost`='10' OR `_12`.`cost`='10' OR `_13`.`cost`='10' OR `_14`.`cost`='10' OR `_15`.`cost`='10' OR `_16`.`cost`='10')) AND ((`_18`.`cost`='20' OR `_19`.`cost`='20' OR `_20`.`cost`='20' OR `_21`.`cost`='20' OR `_22`.`cost`='20' OR `_23`.`cost`='20' OR `_24`.`cost`='20' OR `_25`.`cost`='20'))";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // oid in "IS" node
    function testBuilder33() {
        $q = "from eptEmployee as e where e.oid IS NULL";
        $args = array('10', '20');
        $e = "SELECT DISTINCT `e`.* FROM `otherEmployee` AS `e` WHERE `e`.`eoid` IS NULL";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // relationship that is null
    function testBuilder34() {
        $q = "from eptEmployee as e where e.store IS NULL AND e.sales IS NOT NULL";
        $args = array('10', '20');
        $e = "SELECT DISTINCT `e`.* FROM `otherEmployee` AS `e` LEFT JOIN `_ez_relation_eptemployee_eptstore` AS `_1` ON `_1`.var_a = 'store' AND (`_1`.class_a = 'eptEmployee' AND `_1`.oid_a = `e`.`eoid`) LEFT JOIN `eptStore` AS `_2` ON `_1`.base_b = 'eptStore' AND `_1`.class_b = 'eptStore' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `_ez_relation_eptemployee_eptsale` AS `_3` ON `_3`.var_a = 'sales' AND (`_3`.class_a = 'eptEmployee' AND `_3`.oid_a = `e`.`eoid`) LEFT JOIN `eptSale` AS `_4` ON `_3`.base_b = 'eptSale' AND `_3`.class_b = 'eptSale' AND `_3`.oid_b = `_4`.`eoid` LEFT JOIN `eptSaleBook` AS `_5` ON `_3`.base_b = 'eptSale' AND `_3`.class_b = 'eptSaleBook' AND `_3`.oid_b = `_5`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_6` ON `_3`.base_b = 'eptSale' AND `_3`.class_b = 'eptSaleMultimedia' AND `_3`.oid_b = `_6`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_7` ON `_3`.base_b = 'eptSale' AND `_3`.class_b = 'eptSaleMultimediaDownload' AND `_3`.oid_b = `_7`.`eoid` WHERE (`_2`.`eoid` IS NULL) AND (`_4`.`eoid` IS NOT NULL OR `_5`.`eoid` IS NOT NULL OR `_6`.`eoid` IS NOT NULL OR `_7`.`eoid` IS NOT NULL)";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test soundex
    function testBuilder35() {
        $q = "from eptAuthor as a where soundex(a.name) = 0";
        $args = array();
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` WHERE soundex(`a`.`name`)=0";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test strcmp
    function testBuilder36() {
        $q = "from eptAuthor as a where strcmp(a.name, a.name) = 0";
        $args = array();
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` WHERE strcmp(`a`.`name`,`a`.`name`)=0";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test ezoql keyword
    function testBuilder37() {
        $q = "from eptContactInfo as c where c.`contains` LIKE '%test%'";
        $args = array();
        $e = "SELECT DISTINCT `c`.* FROM `wierdTableName` AS `c` WHERE `c`.`contains` LIKE '%test%'";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test in
    function testBuilder38() {
        return;
        $q = "from eptAuthor as a where name in ('John', 'Bill')";
        $args = array();
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` WHERE `a`.`name` in('John', 'Bill')";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test in
    function testBuilder39() {
        $q = "from eptAuthor as a where name in (?)";
        $args = array(array('John','Bill'));
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` WHERE `a`.`name` in('John', 'Bill')";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test in
    function testBuilder40() {
        $q = "from eptAuthor as a where name in (?)";
        $args = array('John');
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` WHERE `a`.`name` in('John')";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test in
    function testBuilder41() {
        $q = "from eptAuthor as a where age in (18, 20)";
        $args = array();
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` WHERE `a`.`age` in('18', '20')";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test in
    function testBuilder42() {
        $q = "from eptAuthor as a where age in (?)";
        $args = array(array(18, 20));
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` WHERE `a`.`age` in('18', '20')";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test in
    function testBuilder43() {
        $q = "from eptEmployee as e where e.sales.contains(s) AND s.amount in(10.90, 39.95)";
        $args = array();
        $e = "SELECT DISTINCT `e`.* FROM `otherEmployee` AS `e` LEFT JOIN `_ez_relation_eptemployee_eptsale` AS `_6` ON `_6`.var_a = 'sales' AND (`_6`.class_a = 'eptEmployee' AND `_6`.oid_a = `e`.`eoid`) LEFT JOIN `eptSale` AS `_2s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSale' AND `_6`.oid_b = `_2s`.`eoid` LEFT JOIN `eptSaleBook` AS `_3s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleBook' AND `_6`.oid_b = `_3s`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_4s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleMultimedia' AND `_6`.oid_b = `_4s`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_5s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleMultimediaDownload' AND `_6`.oid_b = `_5s`.`eoid` WHERE `_2s`.`amount` in('10.90', '39.95') OR `_3s`.`amount` in('10.90', '39.95') OR `_4s`.`amount` in('10.90', '39.95') OR `_5s`.`amount` in('10.90', '39.95')";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test in
    function testBuilder44() {
        $q = "from eptEmployee as e where e.sales.contains(s) AND s.amount in(?)";
        $args = array(array('10.90', '39.95'));
        $e = "SELECT DISTINCT `e`.* FROM `otherEmployee` AS `e` LEFT JOIN `_ez_relation_eptemployee_eptsale` AS `_6` ON `_6`.var_a = 'sales' AND (`_6`.class_a = 'eptEmployee' AND `_6`.oid_a = `e`.`eoid`) LEFT JOIN `eptSale` AS `_2s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSale' AND `_6`.oid_b = `_2s`.`eoid` LEFT JOIN `eptSaleBook` AS `_3s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleBook' AND `_6`.oid_b = `_3s`.`eoid` LEFT JOIN `eptSaleMultimedia` AS `_4s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleMultimedia' AND `_6`.oid_b = `_4s`.`eoid` LEFT JOIN `eptSaleMultimediaDownload` AS `_5s` ON `_6`.base_b = 'eptSale' AND `_6`.class_b = 'eptSaleMultimediaDownload' AND `_6`.oid_b = `_5s`.`eoid` WHERE `_2s`.`amount` in('10.90', '39.95') OR `_3s`.`amount` in('10.90', '39.95') OR `_4s`.`amount` in('10.90', '39.95') OR `_5s`.`amount` in('10.90', '39.95')";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test in
    function testBuilder45() {
        $q = "from eptAuthor as a where oid in (?)";
        $args = array(array(18, 20));
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` WHERE `a`.`eoid` in('18', '20')";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    // test not
    function testBuilder46() {
        $q = "from eptAuthor as a where name not like '%test%' and age not between 7 and 10";
        $args = array(array(18, 20));
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` WHERE (`a`.`name` NOT LIKE '%test%') AND (`a`.`age` NOT BETWEEN 7 AND 10)";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

}

if (!defined('EP_GROUP_TEST')) {
    $tm = microtime(true);
    $t = new epTestQueryBuilder;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
    $elapsed = microtime(true) - $tm;
    echo epNewLine() . 'Time elapsed: ' . $elapsed . ' seconds' . "\n";
}

?>
