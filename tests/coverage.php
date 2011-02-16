<?php

/**
 * $Id: coverage.php 573 2005-10-18 12:19:26Z nauhygon $
 * 
 * EZPDO Converage test functions
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 573 $ $Date: 2005-10-18 08:19:26 -0400 (Tue, 18 Oct 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.base
 */

/**
 * need ezpdo.php
 */
include_once(realpath(dirname(__FILE__).'/../ezpdo.php'));

/**
 * The ezpdo external lib spikecoverage directory
 */
if (!defined('EP_LIBS_COVERAGE')) {
    define('EP_LIBS_COVERAGE', EP_LIBS.'/spikephpcoverage/src');
}

/**#@+
 * Includes for coveverage test
 */
include_once(EP_LIBS_COVERAGE . "/CoverageRecorder.php");
include_once(EP_LIBS_COVERAGE . "/reporter/HtmlCoverageReporter.php");
/**#@-*/

/**
 * coverage start
 */
function startCoverage(&$c_recorder, &$c_reporter) {
    
    // check if we need coverage tests
    if (!defined('EP_COVERAGE_TEST') || !EP_COVERAGE_TEST) {
        return;
    }

    // set up coverage reporter
    $c_reporter = new HtmlCoverageReporter("EZPDO Test Coverage Report", "", "coverage");

    // set up include/exclude paths
    $includePaths = array(dirname(__FILE__) . "/../src");
    $excludePaths = array("benchmark", "examples", "docs", "libs", "tests");
    
    // set up recorder 
    $c_recorder = new CoverageRecorder($includePaths, $excludePaths, $c_reporter);
    $c_recorder->startInstrumentation();

    echo "Starting coverage test...\n\n";
}

/**
 * Coverage end
 */
function endCoverage(&$c_recorder, &$c_reporter) {
    
    // check if we need coverage tests
    if (!defined('EP_COVERAGE_TEST') || !EP_COVERAGE_TEST) {
        return;
    }

    $c_recorder->stopInstrumentation();
    $c_recorder->generateReport();
    $c_reporter->printTextSummary();
    
    echo "End of coverage test.\n\n";
}

?>
