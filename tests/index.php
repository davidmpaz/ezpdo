<?php

/**
 * $Id: index.php 1012 2006-07-31 01:21:46Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1012 $ $Date: 2006-07-30 21:21:46 -0400 (Sun, 30 Jul 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.base
 */

/**
 * Set exec time to 300 seconds (allow slow machine to finish)
 */
set_time_limit(300);

/**
 * set testing flag
 */
define('EP_TESTING_NOW', true);

/**
 * group testing flag
 */
define('EP_GROUP_TEST', true);

/**
 * coverage test flag
 * (requires spikephpcoverage and xdebug)
 */
define('EP_COVERAGE_TEST', false);

/**
 * need ezpdo.php
 */
include_once(dirname(__FILE__).'/../ezpdo.php');

/**
 * need ezpdo utils
 */
include_once(EP_SRC_BASE.'/epUtils.php');

/**#@+
 * need simpletest
 */
include_once(EP_LIBS_SIMPLETEST . '/unit_tester.php');
include_once(EP_LIBS_SIMPLETEST . '/reporter.php');
/**#@-*/

/**
 * Includes coveverage test
 */
if (EP_COVERAGE_TEST) {
    include_once(dirname(__FILE__) . '/coverage.php');
}

$t = new GroupTest('All ezpdo tests');

// get all epTestXxxx files
$files = epFilesInDir();

// add each test file into group
foreach($files as $file) {
    
    // get file base name
    $filename = basename($file);
    
    // exclude this script
    if ($filename == basename(__FILE__)) {
        continue;
    }
    
    // exclude epTestCase.php
    if ($filename == 'epTestCase.php') {
        continue;
    }

    // exclude epTestRuntime.php
    if ($filename == 'epTestRuntime.php') {
        continue;
    }

    // exclude epTestCache.php
    if ($filename == 'epTestCache.php') {
        continue;
    }

    // exclude non testcase files
    if (!preg_match('/^epTest\w+\.php$/', $filename)) {
        continue;
    }
    
    // add this test file into group
    $t->addTestFile($file);
}

// start coverage 
if (EP_COVERAGE_TEST) {
    startCoverage($c_recorder, $c_reporter);
}

$status = false;
if (epIsWebRun()) {
    
    // start timer
    $tm = microtime(true);
    
    // use the web reporter
    $status = $t->run(new HtmlReporter());
    
    // stop timer
    $elapsed = microtime(true) - $tm;
    
    // output timing
    echo epNewLine() . 'Time elapsed: ' . $elapsed . ' seconds';

} else {
    
    // use the cli reporter from Rephlux
    include_once(EP_TESTS . '/src/epCliReporter.php');
    
    $status = $t->run(new epCliReporter());
}

// end coverage 
if (EP_COVERAGE_TEST) {
    
    // suppress undefined token warning
    error_reporting(E_ALL ^ E_NOTICE);
    
    // generate coverage reports
    endCoverage($c_recorder, $c_reporter);
}

// exit code (important for rephlux): 0 (success) or -1 (failure)
$status ? exit(0) : exit(-1);

?>
