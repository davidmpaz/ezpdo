<?php

/**
 * $Id: epTestUtils.php 782 2006-02-08 13:02:38Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 782 $ $Date: 2006-02-08 08:02:38 -0500 (Wed, 08 Feb 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.base.utils
 */
namespace ezpdo\tests\base\utils;

use ezpdo\base\epUtils;
use ezpdo\tests\src\epTestCase;

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/../../src/epTestCase.php');

/**
 * need ezpdo utils
 */
include_once(EP_SRC_BASE.'/epUtils.php');

/**
 * The unit test class for {@link epLog}
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 782 $ $Date: 2006-02-08 08:02:38 -0500 (Wed, 08 Feb 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.base
 */
class epTestUtils extends epTestCase {

    /**
     * Test function epUtils::epArrayGet() in epUtils.php
     */
    function test_epArrayGet() {

        // make sure method exists
        //$this->assertTrue(function_exists('ezpdo\base\epArrayGet'));

        // array to be tested
        $array = array('a' => array('b' => array('c' => array('d' => 'x'))));

        // tests '.'
        $this->assertTrue($rt = epUtils::epArrayGet($array, '.'));
        $this->assertTrue($rt == $array);

        // tests '/'
        $this->assertTrue($rt = epUtils::epArrayGet($array, '/'));
        $this->assertTrue($rt == $array);

        // tests 'a'
        $this->assertTrue($a = epUtils::epArrayGet($array, 'a'));
        $this->assertTrue($a == $array['a']);

        // tests 'a.b'
        $this->assertTrue($a_b = epUtils::epArrayGet($array, 'a.b'));
        $this->assertTrue($a_b == $array['a']['b']);

        // tests 'a/b'
        $this->assertTrue($a_b = epUtils::epArrayGet($array, 'a.b'));
        $this->assertTrue($a_b == $array['a']['b']);

        // tests 'a.b.c'
        $this->assertTrue($a_b_c = epUtils::epArrayGet($array, 'a.b.c'));
        $this->assertTrue($a_b_c == $array['a']['b']['c']);

        // tests 'a/b/c'
        $this->assertTrue($a_b_c = epUtils::epArrayGet($array, 'a/b/c'));
        $this->assertTrue($a_b_c == $array['a']['b']['c']);

        // tests 'a.b.c.d'
        $this->assertTrue($a_b_c_d = epUtils::epArrayGet($array, 'a.b.c.d'));
        $this->assertTrue($a_b_c_d == $array['a']['b']['c']['d']);

        // tests 'a/b/c/d'
        $this->assertTrue($a_b_c_d = epUtils::epArrayGet($array, 'a/b/c/d'));
        $this->assertTrue($a_b_c_d == $array['a']['b']['c']['d']);
    }

    /**
     * Test function epArraySet() in epUtils.php
     */
    function test_epArraySet() {

        // make sure method exists
        //$this->assertTrue(function_exists('ezpdo\base\epArraySet'));

        // array to be tested
        $array = array('a' => array('b' => array('c' => array('d' => 'x'))));

        // test assignment on reference (fails now, could be a php bug!)
        $x = md5($array['a']['b']['c']['d']);
        epUtils::epArraySet($array, 'a.b.c.d', $x);
        $this->assertTrue($x == $array['a']['b']['c']['d']);

        // again
        $x = md5($array['a']['b']['c']['d']);
        epUtils::epArraySet($array, 'a.b.c.d', $x);
        $this->assertTrue($x == $array['a']['b']['c']['d']);
    }

    /**
     * Test function epFilesInDir() in epUtils.php
     */
    function test_epXml2Array_epValue2Xml() {

        // make sure methods exist
        //$this->assertTrue(function_exists('ezpdo\base\epValue2Xml'));
        //$this->assertTrue(function_exists('ezpdo\base\epXml2Array'));

        // an associated array to be tested
        $array0 = array('a' => array('b' => array('c' => 'x')));
        $array = $array0;
        $array['b'] = $array0;
        $array['c'] = $array0;
        $array['d'] = $array0;

        // unserialize array into xml
        $this->assertTrue($xml = epUtils::epValue2Xml($array));

        // serialize xml into array
        $this->assertTrue($array_2 = epUtils::epXml2Array($xml));

        // unserialize the new array into xml
        $this->assertTrue($xml_2 = epUtils::epValue2Xml($array_2));

        // make sure the two xml strings are the same
        $this->assertTrue($xml == $xml_2);
    }

    /**
     * Test function epFilesInDir() in epUtils.php
     */
    function test_epFilesInDir() {

        // make sure method exists
        //$this->assertTrue(function_exists('ezpdo\base\epFilesInDir'));

        // get all files under EP_ROOT
        $files = epUtils::epFilesInDir(EP_ROOT);
        $this->assertTrue(count($files) > 0);
    }

}

if (!defined('EP_GROUP_TEST')) {
    $t = new epTestUtils;
    if ( epUtils::epIsWebRun() ) {
        $t->run(new \HtmlReporter());
    } else {
        $t->run(new \TextReporter());
    }
}

?>
