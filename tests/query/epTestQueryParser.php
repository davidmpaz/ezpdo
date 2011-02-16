<?php

/**
 * $Id: epTestQueryParser.php 1038 2007-02-11 01:38:59Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1038 $ $Date: 2007-02-10 20:38:59 -0500 (Sat, 10 Feb 2007) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.query
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/../src/epTestCase.php');

/**#@+
 * need epQueryParser rfor testing
 */
include_once(EP_SRC_QUERY.'/epQueryParser.php');
/**#@-*/

/**
 * The unit test class for {@link epQueryParser}  
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1038 $ $Date: 2007-02-10 20:38:59 -0500 (Sat, 10 Feb 2007) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.query
 */
class epTestQueryParser extends epTestCase {
    
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
     * test parsing an empty string 
     */
    function testEmpty() {
        $p = new epQueryParser("");
        $this->assertNotNull($p);
        $this->assertFalse($p->parse());
    }

    /**
     * test parsing select from  
     * (select is optional)
     */
    function testSelectFrom() {
        $p = new epQueryParser("select from MyClass");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT [char: 1, line: 1]
  ::from::
  EPQ_N_FROM [char: 12, line: 1]
    EPQ_N_FROM_ITEM [char: 12, class: MyClass, line: 1]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing from  
     */
    function testFrom() {
        $p = new epQueryParser("from MyClass");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 5, line: 1]
    EPQ_N_FROM_ITEM [char: 5, class: MyClass, line: 1]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }
    
    /**
     * test parsing from as  
     */
    function testFromAs() {
        $p = new epQueryParser("from MyClass as myc");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing where  with simple expression
     */
    function testFromAsWhere1() {
        $p = new epQueryParser("from MyClass as myc where a = b");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_EXPR_COMPARISON [char: 28, line: 1, op: =]
      ::left::
      EPQ_N_VARIABLE [char: 26, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: a]
      ::right::
      EPQ_N_VARIABLE [char: 30, line: 1]
        EPQ_N_IDENTIFIER [char: 30, line: 1, val: b]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing where  with complex expression 
     */
    function testFromAsWhere2() {
        $p = new epQueryParser("from MyClass as myc where myc.a = -(c+d)*(e+f)");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_EXPR_COMPARISON [char: 32, line: 1, op: =]
      ::left::
      EPQ_N_VARIABLE [char: 31, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 31, line: 1, val: a]
      ::right::
      EPQ_N_EXPR_MUL [char: 46, line: 1, op: *]
        ::left::
        EPQ_N_EXPR_UNARY [char: 40, line: 1, op: -]
          ::expr::
          EPQ_N_EXPR_PAREN [char: 40, line: 1]
            ::expr::
            EPQ_N_EXPR_ADD [char: 39, line: 1, op: +]
              ::left::
              EPQ_N_VARIABLE [char: 37, line: 1]
                EPQ_N_IDENTIFIER [char: 37, line: 1, val: c]
              ::right::
              EPQ_N_VARIABLE [char: 39, line: 1]
                EPQ_N_IDENTIFIER [char: 39, line: 1, val: d]
        ::right::
        EPQ_N_EXPR_PAREN [char: 46, line: 1]
          ::expr::
          EPQ_N_EXPR_ADD [char: 45, line: 1, op: +]
            ::left::
            EPQ_N_VARIABLE [char: 43, line: 1]
              EPQ_N_IDENTIFIER [char: 43, line: 1, val: e]
            ::right::
            EPQ_N_VARIABLE [char: 45, line: 1]
              EPQ_N_IDENTIFIER [char: 45, line: 1, val: f]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing from/where/between  
     */
    function testFromWhereBetweenAnd() {
        $p = new epQueryParser("from MyClass as myc where myc.a between x+1 and y*2");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_EXPR_BETWEEN [char: 51, line: 1]
      ::expr1::
      EPQ_N_EXPR_ADD [char: 43, line: 1, op: +]
        ::left::
        EPQ_N_VARIABLE [char: 40, line: 1]
          EPQ_N_IDENTIFIER [char: 40, line: 1, val: x]
        ::right::
        EPQ_N_NUMBER [char: 43, line: 1, val: 1]
      ::expr2::
      EPQ_N_EXPR_MUL [char: 51, line: 1, op: *]
        ::left::
        EPQ_N_VARIABLE [char: 48, line: 1]
          EPQ_N_IDENTIFIER [char: 48, line: 1, val: y]
        ::right::
        EPQ_N_NUMBER [char: 51, line: 1, val: 2]
      ::var::
      EPQ_N_VARIABLE [char: 31, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 31, line: 1, val: a]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing where/like 
     */
    function testFromWhereLike1() {
        $p = new epQueryParser("from MyClass as myc where myc.a like %ABC%");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_EXPR_LIKE [char: 42, line: 1]
      ::pattern::
      EPQ_N_PATTERN [char: 42, line: 1, val: %ABC%]
      ::var::
      EPQ_N_VARIABLE [char: 31, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 31, line: 1, val: a]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing where/like 
     */
    function testFromAsWhereLike2() {
        $p = new epQueryParser("from MyClass as myc where myc.a like %[a-f]ABC_%");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_EXPR_LIKE [char: 48, line: 1]
      ::pattern::
      EPQ_N_PATTERN [char: 48, line: 1, val: %[a-f]ABC_%]
      ::var::
      EPQ_N_VARIABLE [char: 31, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 31, line: 1, val: a]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing where/from/as/contains
     */
    function testFromAsWhereContains() {
        $p = new epQueryParser("from MyClass as myc where myc.var1.contains(x)");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_CONTAINS [arg: x, char: 46, line: 1]
      ::var::
      EPQ_N_VARIABLE [char: 35, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 31, line: 1, val: var1]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing where/from/as/contains/placeholder
     */
    function testFromAsWhereContainsPlaceholder() {
        $p = new epQueryParser("from MyClass as myc where myc.var1.contains(?)");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_CONTAINS [char: 46, line: 1]
      ::arg::
      EPQ_N_PLACEHOLDER [aindex: 0, char: 46, line: 1]
      ::var::
      EPQ_N_VARIABLE [char: 35, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 31, line: 1, val: var1]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing from/as/where/orderby
     */
    function testFromAsWhereOrderBy() {
        $p = new epQueryParser("from MyClass as myc where myc.var1 > 1 order by myc.y");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_EXPR_COMPARISON [char: 35, line: 1, op: >]
      ::left::
      EPQ_N_VARIABLE [char: 31, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 31, line: 1, val: var1]
      ::right::
      EPQ_N_NUMBER [char: 37, line: 1, val: 1]
  ::orderby::
  EPQ_N_ORDERBY [char: 53, line: 1]
    EPQ_N_ORDERBY_ITEM [char: 53, direction: asc, line: 1]
      ::var::
      EPQ_N_VARIABLE [char: 53, line: 1]
        EPQ_N_IDENTIFIER [char: 48, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 53, line: 1, val: y]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing from/as/where/orderby/random
     */
    function testFromAsWhereOrderByRandom() {
        $p = new epQueryParser("from MyClass as myc where myc.var1 > 1 order by random()");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_EXPR_COMPARISON [char: 35, line: 1, op: >]
      ::left::
      EPQ_N_VARIABLE [char: 31, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 31, line: 1, val: var1]
      ::right::
      EPQ_N_NUMBER [char: 37, line: 1, val: 1]
  ::orderby::
  EPQ_N_ORDERBY [char: 56, line: 1]
    EPQ_N_ORDERBY_ITEM [char: 56, direction: random, line: 1]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }


    /**
     * test parsing from/as/where/orderby/random/limit
     */
    function testFromAsWhereOrderByRandomLimit() {
        $p = new epQueryParser("from eptAuthor where 1 order by random() limit 5");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 5, line: 1]
    EPQ_N_FROM_ITEM [char: 5, class: eptAuthor, line: 1]
  ::orderby::
  EPQ_N_ORDERBY [char: 40, line: 1]
    EPQ_N_ORDERBY_ITEM [char: 40, direction: random, line: 1]
  ::limit::
  EPQ_N_LIMIT [char: 47, line: 1]
    ::start::
    EPQ_N_NUMBER [char: 47, line: 1, val: 5]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing from/as/where/orderby(multiple)
     */
    function testFromAsWhereOrderByMulti() {
        $p = new epQueryParser("from MyClass as myc where myc.var1 > 1 order by myc.y, myc.z desc");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_EXPR_COMPARISON [char: 35, line: 1, op: >]
      ::left::
      EPQ_N_VARIABLE [char: 31, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 31, line: 1, val: var1]
      ::right::
      EPQ_N_NUMBER [char: 37, line: 1, val: 1]
  ::orderby::
  EPQ_N_ORDERBY [char: 61, line: 1]
    EPQ_N_ORDERBY_ITEM [char: 53, direction: asc, line: 1]
      ::var::
      EPQ_N_VARIABLE [char: 53, line: 1]
        EPQ_N_IDENTIFIER [char: 48, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 53, line: 1, val: y]
    EPQ_N_ORDERBY_ITEM [char: 60, direction: desc, line: 1]
      ::var::
      EPQ_N_VARIABLE [char: 60, line: 1]
        EPQ_N_IDENTIFIER [char: 55, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 60, line: 1, val: z]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing from/as/where/limit
     */
    function testFromAsWhereLimit() {
        $p = new epQueryParser("from MyClass as myc where myc.var1 > 1 limit 3,56");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_EXPR_COMPARISON [char: 35, line: 1, op: >]
      ::left::
      EPQ_N_VARIABLE [char: 31, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 31, line: 1, val: var1]
      ::right::
      EPQ_N_NUMBER [char: 37, line: 1, val: 1]
  ::limit::
  EPQ_N_LIMIT [char: 45, line: 1]
    ::start::
    EPQ_N_NUMBER [char: 45, line: 1, val: 3]
    ::length::
    EPQ_N_NUMBER [char: 48, line: 1, val: 56]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing [avg|max|min|sum]/from/where/as/limit
     */
    function testAvgMaxMinSumFromAsWhereLimit() {

        $afuncs = array('avg', 'max', 'min', 'sum');

        foreach($afuncs as $afunc) {

            $p = new epQueryParser($afunc . "(myc.var1) from MyClass as myc where myc.var2 > 1 limit 3,56");
            $this->assertNotNull($p);
            $this->assertTrue($node = $p->parse());
            $expect = <<< EXPECT
EPQ_N_SELECT []
  ::aggregate::
  EPQ_N_AGGREGATE [char: 13, func: $afunc, line: 1]
    ::arg::
    EPQ_N_VARIABLE [char: 9, line: 1]
      EPQ_N_IDENTIFIER [char: 5, line: 1, val: myc]
      EPQ_N_IDENTIFIER [char: 9, line: 1, val: var1]
  ::from::
  EPQ_N_FROM [char: 30, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 19, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 34, line: 1]
    ::expr::
    EPQ_N_EXPR_COMPARISON [char: 49, line: 1, op: >]
      ::left::
      EPQ_N_VARIABLE [char: 45, line: 1]
        EPQ_N_IDENTIFIER [char: 40, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 45, line: 1, val: var2]
      ::right::
      EPQ_N_NUMBER [char: 51, line: 1, val: 1]
  ::limit::
  EPQ_N_LIMIT [char: 59, line: 1]
    ::start::
    EPQ_N_NUMBER [char: 59, line: 1, val: 3]
    ::length::
    EPQ_N_NUMBER [char: 62, line: 1, val: 56]
EXPECT;
            $this->assertTrue($this->_match($node, $expect));
        }
    }

    /**
     * test parsing count/from/where/as/limit
     */
    function testCountFromAsWhereLimit() {
        $p = new epQueryParser("count(*) from MyClass as myc where myc.var2 > 1 limit 3,56");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::aggregate::
  EPQ_N_AGGREGATE [arg: *, char: 8, func: count, line: 1]
  ::from::
  EPQ_N_FROM [char: 25, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 14, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 29, line: 1]
    ::expr::
    EPQ_N_EXPR_COMPARISON [char: 44, line: 1, op: >]
      ::left::
      EPQ_N_VARIABLE [char: 40, line: 1]
        EPQ_N_IDENTIFIER [char: 35, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 40, line: 1, val: var2]
      ::right::
      EPQ_N_NUMBER [char: 46, line: 1, val: 1]
  ::limit::
  EPQ_N_LIMIT [char: 54, line: 1]
    ::start::
    EPQ_N_NUMBER [char: 54, line: 1, val: 3]
    ::length::
    EPQ_N_NUMBER [char: 57, line: 1, val: 56]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing count/from/where/as/limit with question marks
     */
    function testCountFromAsWhereLimitWithQMarks() {
        $p = new epQueryParser("count(?) from MyClass as myc where myc.var2.? > 1 limit ?,56");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::aggregate::
  EPQ_N_AGGREGATE [char: 8, func: count, line: 1]
    ::arg::
    EPQ_N_VARIABLE [char: 7, line: 1]
      EPQ_N_PLACEHOLDER [aindex: 0, char: 7, line: 1]
  ::from::
  EPQ_N_FROM [char: 25, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 14, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 29, line: 1]
    ::expr::
    EPQ_N_EXPR_COMPARISON [char: 46, line: 1, op: >]
      ::left::
      EPQ_N_VARIABLE [char: 45, line: 1]
        EPQ_N_IDENTIFIER [char: 35, line: 1, val: myc]
        EPQ_N_IDENTIFIER [char: 40, line: 1, val: var2]
        EPQ_N_PLACEHOLDER [aindex: 1, char: 45, line: 1]
      ::right::
      EPQ_N_NUMBER [char: 48, line: 1, val: 1]
  ::limit::
  EPQ_N_LIMIT [char: 56, line: 1]
    ::start::
    EPQ_N_PLACEHOLDER [aindex: 2, char: 56, line: 1]
    ::length::
    EPQ_N_NUMBER [char: 59, line: 1, val: 56]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test from/as/where + q96
     */
    function testFromAsWhereQ96() {
        $p = new epQueryParser("from Book as b where b.`as` = 100");
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
    EPQ_N_EXPR_COMPARISON [char: 28, line: 1, op: =]
      ::left::
      EPQ_N_VARIABLE [char: 24, line: 1]
        EPQ_N_IDENTIFIER [char: 21, line: 1, val: b]
        EPQ_N_IDENTIFIER [char: 24, line: 1, val: as]
      ::right::
      EPQ_N_NUMBER [char: 30, line: 1, val: 100]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing count/from/where/as with parentheses
     */
    function testFromAsWhereParen1() {

        $p = new epQueryParser($q = "from Book as b where b.y LIKE '%something%' and (b.x < 100 or b.x > 200)");
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
    EPQ_N_EXPR_LOGIC [char: 72, line: 1, op: and]
      EPQ_N_EXPR_LIKE [char: 30, line: 1]
        ::pattern::
        EPQ_N_PATTERN [char: 30, line: 1, val: %something%]
        ::var::
        EPQ_N_VARIABLE [char: 24, line: 1]
          EPQ_N_IDENTIFIER [char: 21, line: 1, val: b]
          EPQ_N_IDENTIFIER [char: 24, line: 1, val: y]
      EPQ_N_EXPR_PAREN [char: 72, line: 1]
        ::expr::
        EPQ_N_EXPR_LOGIC [char: 68, line: 1, op: or]
          EPQ_N_EXPR_COMPARISON [char: 53, line: 1, op: <]
            ::left::
            EPQ_N_VARIABLE [char: 52, line: 1]
              EPQ_N_IDENTIFIER [char: 50, line: 1, val: b]
              EPQ_N_IDENTIFIER [char: 52, line: 1, val: x]
            ::right::
            EPQ_N_NUMBER [char: 55, line: 1, val: 100]
          EPQ_N_EXPR_COMPARISON [char: 66, line: 1, op: >]
            ::left::
            EPQ_N_VARIABLE [char: 65, line: 1]
              EPQ_N_IDENTIFIER [char: 62, line: 1, val: b]
              EPQ_N_IDENTIFIER [char: 65, line: 1, val: x]
            ::right::
            EPQ_N_NUMBER [char: 68, line: 1, val: 200]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing count/from/where/as with parentheses
     */
    function testFromAsWhereParen2() {
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
    }

    /**
     * test parsing where/boolean(false)
     */
    function testFromWhereBooleanFalse() {
        $p = new epQueryParser("from MyClass as myc where a = FaLsE");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_EXPR_COMPARISON [char: 28, line: 1, op: =]
      ::left::
      EPQ_N_VARIABLE [char: 26, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: a]
      ::right::
      EPQ_N_BOOLEAN [char: 30, line: 1, val: false]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing where/boolean(true)
     */
    function testFromWhereBooleanTrue() {
        $p = new epQueryParser("from MyClass as myc where a = tRUe");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_EXPR_COMPARISON [char: 28, line: 1, op: =]
      ::left::
      EPQ_N_VARIABLE [char: 26, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: a]
      ::right::
      EPQ_N_BOOLEAN [char: 30, line: 1, val: true]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing where/in(numbers)
     */
    function testFromWhereInNumbers() {
        $p = new epQueryParser("from MyClass as myc where a in (123, 234)");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_IN [char: 41, line: 1]
      ::items::
      EPQ_N_IN_ITEMS [char: 31, line: 1]
        EPQ_N_NUMBER [char: 33, line: 1, val: 123]
        EPQ_N_NUMBER [char: 37, line: 1, val: 234]
      ::var::
      EPQ_N_VARIABLE [char: 26, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: a]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

    /**
     * test parsing where/in(strings)
     */
    function testFromWhereInStrings() {
        $p = new epQueryParser("from MyClass as myc where a in ('123', '234')");
        $this->assertNotNull($p);
        $this->assertTrue($node = $p->parse());
        $expect = <<< EXPECT
EPQ_N_SELECT []
  ::from::
  EPQ_N_FROM [char: 16, line: 1]
    EPQ_N_FROM_ITEM [alias: myc, char: 5, class: MyClass, line: 1]
  ::where::
  EPQ_N_WHERE [char: 20, line: 1]
    ::expr::
    EPQ_N_IN [char: 45, line: 1]
      ::items::
      EPQ_N_IN_ITEMS [char: 31, line: 1]
        EPQ_N_STRING [char: 33, line: 1, val: 123]
        EPQ_N_STRING [char: 39, line: 1, val: 234]
      ::var::
      EPQ_N_VARIABLE [char: 26, line: 1]
        EPQ_N_IDENTIFIER [char: 26, line: 1, val: a]
EXPECT;
        $this->assertTrue($this->_match($node, $expect));
    }

}

if (!defined('EP_GROUP_TEST')) {
    $tm = microtime(true);
    $t = new epTestQueryParser;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
    $elapsed = microtime(true) - $tm;
    echo epNewLine() . 'Time elapsed: ' . $elapsed . ' seconds' . "\n";
}

?>
