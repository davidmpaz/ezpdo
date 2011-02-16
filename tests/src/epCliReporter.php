<?php

/**
 * $Id: epCliReporter.php 544 2005-09-28 21:30:22Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 544 $ $Date: 2005-09-28 17:30:22 -0400 (Wed, 28 Sep 2005) $
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

if (! defined('ST_FAILDETAIL_SEPARATOR')) {
    define('ST_FAILDETAIL_SEPARATOR', "->");
}

if (! defined('ST_FAILS_RETURN_CODE')) {
    define('ST_FAILS_RETURN_CODE', 1);
}

/**
 * A CLI reporter that is understood by Rephlux 
 * {@link http://rephlux.sourceforge.net/running.rephlux.php}
 * 
 * Minimal command line test displayer. Writes fail details to STDERR. Returns 0
 * to the shell if all tests pass, ST_FAILS_RETURN_CODE if any test fails.
 * 
 * Copied from Rephlux var/tests/lib. Credits to jon@bangoid.com. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 544 $ $Date: 2005-09-28 17:30:22 -0400 (Wed, 28 Sep 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.src
 */
class epCliReporter extends SimpleReporter {

    var $faildetail_separator = ST_FAILDETAIL_SEPARATOR;

    function CLIReporter($faildetail_separator = NULL) {
        $this->SimpleReporter();
        if (! is_null($faildetail_separator)) {
            $this->setFailDetailSeparator($faildetail_separator);
        }
    }

    /**
     * Print msg at the start of each test case
     */
    function paintCaseStart($test_name) {
        echo "Test case: $test_name\n";
    }

    function setFailDetailSeparator($separator) {
        $this->faildetail_separator = $separator;
    }

    /**
     * Return a formatted faildetail for printing.
     */
    function &_paintTestFailDetail(&$message) {
        $buffer = '';
        $faildetail = $this->getTestList();
        array_shift($faildetail);
        $buffer .= implode($this->faildetail_separator, $faildetail);
        $buffer .= $this->faildetail_separator . "$message\n";
        return $buffer;
    }

    /**
     * Paint fail faildetail to STDERR.
     */
    function paintFail($message) {
        parent::paintFail($message);
        echo 'FAIL' . $this->faildetail_separator . $this->_paintTestFailDetail($message);
    }

    /**
     * Paint exception faildetail to STDERR.
     */
    function paintException($message) {
        parent::paintException($message);
        echo 'EXCEPTION' . $this->faildetail_separator . $this->_paintTestFailDetail($message);
    }

    /**
     * Paint a footer with test case name, timestamp, counts of fails and
     * exceptions.
     */
    function paintFooter($test_name) {
        $buffer = $this->getTestCaseProgress() . '/' .
            $this->getTestCaseCount() . ' test cases complete: ';

        if (0 < ($this->getFailCount() + $this->getExceptionCount())) {
            $buffer .= $this->getPassCount() . " passes";
            if (0 < $this->getFailCount()) {
                $buffer .= ", " . $this->getFailCount() . " fails";
            }
            if (0 < $this->getExceptionCount()) {
                $buffer .= ", " . $this->getExceptionCount() . " exceptions";
            }
            $buffer .= ".\n";
            echo $buffer;
            exit(ST_FAILS_RETURN_CODE);
        } else {
            echo $buffer . $this->getPassCount() . " passes.\n";
        }
    }
}

?>

