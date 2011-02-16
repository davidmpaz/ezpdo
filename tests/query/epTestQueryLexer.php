<?php

/**
 * $Id: epTestQueryLexer.php 931 2006-05-12 19:04:20Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 931 $ $Date: 2006-05-12 15:04:20 -0400 (Fri, 12 May 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.query
 */

/**#@+
 * need epTestCase and epQueryLexer for testing
 */
include_once(dirname(__FILE__).'/../src/epTestCase.php');
include_once(EP_SRC_QUERY.'/epQueryLexer.php');
/**#@-*/

/**
 * The unit test class for {@link epQueryLexer}  
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 931 $ $Date: 2006-05-12 15:04:20 -0400 (Fri, 12 May 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.query
 */
class epTestQueryLexer extends epTestCase {
    
    /**
     * test {@link epQueryLexer::back()} and {@link epQueryLexer::next()}
     */
    function testNextBack() {

        $js = <<< EZOQL
            from Book as book where book.title like ? order by book.uuid desc limit 0, 2
EZOQL;
        
        // create the lexer
        $l = new epQueryLexer($js);
        $this->assertNotNull($l);

        // test next
        $ts = array();
        while ($t = $l->next()) {
            $this->assertNotNull($l);
            $ts[] = $t;
        }

        // tokens total (including new lines) > 0
        $this->assertTrue(count($ts) > 0);
        
        // pop off the current token
        array_pop($ts);

        // test back()
        while ($t = $l->back()) {
            $t_ = array_pop($ts);
            $this->assertNotNull($t);
            $this->assertNotNull($t_);
            $this->assertEqual($t, $t_);
        }
    }

    /**
     * test decimal EPQ_T_INTEGER and EPQ_T_FLOAT
     */
    function testNumbers() {
        
        // create the lexer
        $l = new epQueryLexer;
        $this->assertNotNull($l);
        
        // try an integer 
        $l->initialize((string)($r = rand(1, 100000)));
        $this->assertNotNull($t = $l->next());
        $this->assertEqual($t->type, EPQ_T_INTEGER);
        $this->assertEqual($r, (integer)$t->value);

        // try a float
        $l->initialize((string)($r = (float)rand(1, 100000)/1000));
        $this->assertNotNull($t = $l->next());
        $this->assertEqual($t->type, EPQ_T_FLOAT);
        $this->assertTrue($r == (float)$t->value);

        // try a scientific number
        $l->initialize((string)($r = 456.789e123));
        $this->assertNotNull($t = $l->next());
        $this->assertEqual($t->type, EPQ_T_FLOAT);
        $this->assertTrue($r == (float)$t->value);
        
        // try another scientific number
        $l->initialize((string)($r = 456.789e-123));
        $this->assertNotNull($t = $l->next());
        $this->assertEqual($t->type, EPQ_T_FLOAT);
        $this->assertTrue($r == (float)$t->value);

        // try a bad float number 111.222.333, lexer picks only 111.222 
        $l->initialize("111.222.333");
        $this->assertNotNull($t = $l->next());
        $this->assertEqual($t->type, EPQ_T_FLOAT);
        $this->assertTrue(111.222 == (float)$t->value);
    }

    /**
     * test EPQ_T_STRING
     */
    function testString() {
        
        // create the lexer
        $l = new epQueryLexer;
        $this->assertNotNull($l);
        
        // try a string with double quote
        $l->initialize($s = '"abc"');
        $this->assertNotNull($t = $l->next());
        $this->assertEqual($t->type, EPQ_T_STRING);
        $this->assertEqual($s, $t->value);
        $this->assertTrue(count($l->errors()) == 0);
        
        // try a string with single quote
        $l->initialize($s = "'abc'");
        $this->assertNotNull($t = $l->next());
        $this->assertEqual($t->type, EPQ_T_STRING);
        $this->assertEqual($s, $t->value);
        $this->assertTrue(count($l->errors()) == 0);

        // try a string with single quote
        $l->initialize($s = "'abc\\n'");
        $this->assertNotNull($t = $l->next());
        $this->assertEqual($t->type, EPQ_T_STRING);
        $this->assertEqual($s, $t->value);
        $this->assertTrue(count($l->errors()) == 0);

        // try a string teminated unexpectedly. error is raised.
        $l->initialize($s = "'abc\nefg'");
        $this->assertNotNull($t = $l->next());
        $this->assertEqual($t->type, EPQ_T_STRING);
        $this->assertTrue(count($l->errors()) == 1);
    }

    /**
     * test keywords
     */
    function testKeywords() {
        
        // create the lexer
        $l = new epQueryLexer;
        $this->assertNotNull($l);
        
        // get keyword array
        $k_t = epQueryLexer::$oql_keywords;
        
        // keywords
        foreach($k_t as $k => $type) {
            $l->initialize($k);
            $this->assertNotNull($t = $l->next());
            $this->assertEqual($t->type, $type);
            $this->assertEqual($k, $t->value);
        }
        
        // non keywords : $keyword . 'x'
        foreach($k_t as $k => $type) {
            $l->initialize($s = $k . 'x');
            $this->assertNotNull($t = $l->next());
            $this->assertEqual($t->type, EPQ_T_IDENTIFIER);
            $this->assertEqual($s, $t->value);
        }
    }

    /**
     * test Peek at EOF
     */
    function testPeekAtEOF() {
        
        // create the lexer
        $l = new epQueryLexer("from classA where var = 1234");
        
        // read to end
        $i = 0;
        while (($t = $l->next()) !== false) {
            $i ++;
        }
        
        // assert 6 tokens: var x , y , z
        $this->assertEqual(6, $i);
        
        // eof. peek always returns false
        for ($i = 0; $i < 5; $i ++) {
            $t = $l->peek();
            $this->assertTrue($t === false);
        }
    }

}

if (!defined('EP_GROUP_TEST')) {
    $tm = microtime(true);
    $t = new epTestQueryLexer;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
    $elapsed = microtime(true) - $tm;
    echo epNewLine() . 'Time elapsed: ' . $elapsed . ' seconds' . "\n";
}

?>
