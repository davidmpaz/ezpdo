<?php

/**
 * $Id: epHtmlReporter.php 202 2005-04-18 01:24:14Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 202 $ $Date: 2005-04-17 21:24:14 -0400 (Sun, 17 Apr 2005) $
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
 * Customized Html reporter for ezpdo tests
 * 
 * Modified from (@link http://www.lastcraft.com/display_subclass_tutorial.php#subclass). 
 * Credit to lastcraft.com.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 202 $ $Date: 2005-04-17 21:24:14 -0400 (Sun, 17 Apr 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.src
 */
class epHtmlReporter extends HtmlReporter {
    
    function ShowPasses() {
        $this->HtmlReporter();
    }
    
    function paintPass($message) {
        parent::paintPass($message);
        print "<span class=\"pass\">Pass</span>: ";
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        print implode("-&gt;", $breadcrumb);
        print "->$message<br />\n";
    }
    
    function _getCss() {
        return parent::_getCss() . ' .pass { color: blue; }';
    }
}

?>
