<?php

/**
 * $Id: epTestQueryTmp.php 822 2006-02-14 22:08:48Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 822 $ $Date: 2006-02-14 17:08:48 -0500 (Tue, 14 Feb 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.query
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/../src/epTestCase.php');

/**#@+
 * need runtime testcase (under ../runtime) and epQueryBuilder 
 */
include_once(dirname(__FILE__).'/../runtime/epTestRuntime.php');
include_once(EP_SRC_QUERY.'/epQueryBuilder.php');
/**#@-*/

/**
 * The unit test class for {@link epQueryParser}  
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 822 $ $Date: 2006-02-14 17:08:48 -0500 (Tue, 14 Feb 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.query
 */
class epTestQueryTmp extends epTestRuntime {
    
    /**
     * Maximum number of items to insert to a table
     */
    const MAX_ITEMS = 10;
    
    /**
     * Check if node and expected match (ignore spaces)
     * @param sjNode $node
     * @param string $expect
     */
    function _match($node, $expect) {
        
        // get result from node
        $result = $node->__toString();
        
        // remove \r and space
        $expect = str_replace(array("\n", "\r", ' '), '', $expect);
        $result = str_replace(array("\n", "\r", ' '), '', $result);

        // trim
        $expect = trim($expect);
        $result = trim($result);

        return $expect == $result;
    }

    /**
     * test parsing count/from/where/as/limit with question marks
     */
    function _testParser() {

        //$p = new epQueryParser($q = "from Book as b where b.y LIKE '%something%' and (b.x < 100 or b.x > 200)");
        $p = new epQueryParser($q = "from Book as b where (b.y LIKE '%something%' and b.z > 10) or (b.x < 100 or b.x > 200)");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 13, line: 1]
    EPQ_N_FROM_ITEM [alias: b, char: 5, class: Book, line: 1]
  ::where::
  EPQ_N_WHERE [char: 15, line: 1]
    ::expr::
    EPQ_N_EXPR_LOGIC [char: 86, line: 1, op: or]
      EPQ_N_EXPR_PAREN [char: 58, line: 1]
        ::expr::
        EPQ_N_EXPR_LOGIC [char: 55, line: 1, op: and]
          EPQ_N_EXPR_LIKE [char: 31, line: 1]
            ::pattern::
            EPQ_N_PATTERN [char: 31, line: 1, val: %something%]
            ::var::
            EPQ_N_VARIABLE [char: 25, line: 1]
              EPQ_N_IDENTIFIER [char: 23, line: 1, val: b]
              EPQ_N_IDENTIFIER [char: 25, line: 1, val: y]
          EPQ_N_EXPR_COMPARISON [char: 53, line: 1, op: >]
            ::left::
            EPQ_N_VARIABLE [char: 52, line: 1]
              EPQ_N_IDENTIFIER [char: 49, line: 1, val: b]
              EPQ_N_IDENTIFIER [char: 52, line: 1, val: z]
            ::right::
            EPQ_N_NUMBER [char: 55, line: 1, val: 10]
      EPQ_N_EXPR_PAREN [char: 86, line: 1]
        ::expr::
        EPQ_N_EXPR_LOGIC [char: 82, line: 1, op: or]
          EPQ_N_EXPR_COMPARISON [char: 67, line: 1, op: <]
            ::left::
            EPQ_N_VARIABLE [char: 66, line: 1]
              EPQ_N_IDENTIFIER [char: 64, line: 1, val: b]
              EPQ_N_IDENTIFIER [char: 66, line: 1, val: x]
            ::right::
            EPQ_N_NUMBER [char: 69, line: 1, val: 100]
          EPQ_N_EXPR_COMPARISON [char: 80, line: 1, op: >]
            ::left::
            EPQ_N_VARIABLE [char: 79, line: 1]
              EPQ_N_IDENTIFIER [char: 76, line: 1, val: b]
              EPQ_N_IDENTIFIER [char: 79, line: 1, val: x]
            ::right::
            EPQ_N_NUMBER [char: 82, line: 1, val: 200]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
        //echo "$q\n";
        //echo $node;
        //var_dump($p->errors());
    }

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
    function testBuilder1() {
        $q = "from eptAuthor as a where a.name = 'Oak'";
        $args = false;
        $e = "SELECT DISTINCT `a`.* FROM `eptAuthor` AS `a` WHERE `a`.`name`='Oak'";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder2() {
        $q = "from eptBookstore as bstr where bstr.oid == ?";
        $args = array(1);
        $e = "SELECT DISTINCT `bstr`.* FROM `eptBookstore` AS `bstr` WHERE `bstr`.`eoid`=1";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }
    
    function testBuilder3() {
        $q = "from eptBook as b where b.authors.contains(?)";
        $args = array(array('name' => 'Ralph Johnson'));
        $e = "SELECT DISTINCT `b`.* FROM `eptBook` AS `b` LEFT JOIN `_ez_relation_eptauthor_eptbook` AS `_1` ON `_1`.var_a = 'authors' AND (`_1`.class_a = 'eptBook' AND `_1`.oid_a = `b`.`eoid`) LEFT JOIN `eptAuthor` AS `_2` ON `_1`.base_b = 'eptAuthor' AND `_1`.class_b = 'eptAuthor' AND `_1`.oid_b = `_2`.`eoid` WHERE `_2`.`name`='Ralph Johnson'";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder4() {
        $q = "from eptBook as b where b.authors.contains(a) and a.contact = ?";
        $args = array(array('zipcode' => '01010'));
        $e = "SELECT DISTINCT `b`.* FROM `eptBook` AS `b` LEFT JOIN `_ez_relation_eptauthor_eptbook` AS `_1` ON `_1`.var_a = 'authors' AND (`_1`.class_a = 'eptBook' AND `_1`.oid_a = `b`.`eoid`) LEFT JOIN `eptAuthor` AS `_2` ON `_1`.base_b = 'eptAuthor' AND `_1`.class_b = 'eptAuthor' AND `_1`.oid_b = `_2`.`eoid` LEFT JOIN `_ez_relation_eptauthor_eptcontact` AS `_3` ON `_3`.var_a = 'contact' AND (`_3`.class_a = 'eptAuthor' AND `_3`.oid_a = `_2`.`eoid`) LEFT JOIN `eptContact` AS `_4` ON `_3`.base_b = 'eptContact' AND `_3`.class_b = 'eptContact' AND `_3`.oid_b = `_4`.`eoid` WHERE `_4`.`zipcode`='01010'";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }

    function testBuilder5() {
        $q = "from eptItemMagazine as m where m.? == ? limit ?,3";
        $args = array('name', 'Harry Potter', 1);
        $e = "SELECT DISTINCT `m`.* FROM `eptItemMagazine` AS `m` WHERE `m`.`name`='Harry Potter' LIMIT 1,3";
        $this->assertTrue($this->_testBuilder($q, $args, $e));
    }
    */

}

if (!defined('EP_GROUP_TEST')) {
    $tm = microtime(true);
    $t = new epTestQueryTmp;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
    $elapsed = microtime(true) - $tm;
    echo epNewLine() . 'Time elapsed: ' . $elapsed . ' seconds' . "\n";
}

?>
