<?php

/**
 * $Id: epTestLog.php 894 2006-04-04 21:52:14Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 894 $ $Date: 2006-04-04 17:52:14 -0400 (Tue, 04 Apr 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.base
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/../../src/epTestCase.php');

/**
 * need ezpdo utils
 */
include_once(EP_SRC_BASE.'/epUtils.php');

/**
 * need epLog to test
 */
include_once(EP_SRC_BASE.'/epLog.php');

/**
 * The unit test class for {@link epLog}  
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 894 $ $Date: 2006-04-04 17:52:14 -0400 (Tue, 04 Apr 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.base
 */
class epTestLog extends epTestCase {

    /**
     * Setup output dir 
     */
    public function setUp() {
        epRmDir('output');
        epMkDir('output');
    }

    /**
     * Teardown: remove output dir and its content
     */
    public function tearDown() {
        epRmDir('output');
    }

    /**
     * test epLog
     */
    function testLog() {

        // load config.xml
        $log_cfg = & epConfig::load(realpath(dirname(__FILE__))."/input/config.xml");
        $this->assertTrue(!empty($log_cfg));
        
        // make log file absolute path
        $log_file = realpath(dirname(__FILE__))."/output/test.log";
        
        // delete it first so it won't accumulate and become a huge file
        @unlink($log_file);
        
        $this->assertTrue($log_cfg->set('log_file', $log_file));
        $this->assertTrue($log_file == $log_cfg->get('log_file'));
        
        // config log with the log section only
        $logger = & epLog::instance();
        $this->assertTrue($logger);
        
        $logger->setConfig($log_cfg);
        
        // generate all messages
        $msgs = array();
        for ( $i = 0; $i < 10; $i ++ ) {
            $msg = 'log test message id ('.rand(0, 100000).') at ' . date('H:i:s');
            $msgs[] = $msg;
        }

        // log all messages
        foreach($msgs as $msg) {
            $status = $logger->log($msg);
            $this->assertTrue($status);
        }

        // figure out the log file 
        $log_file = $logger->getConfigOption('log_file');
        $this->assertTrue(file_exists($log_file));
        
        // check if the messages are in the log file
        $content = file_get_contents($log_file);
        $this->assertTrue(!empty($content));
        foreach($msgs as $msg) {
            $this->assertTrue(strstr($content, $content) != false);
        } 
    }

}

if (!defined('EP_GROUP_TEST')) {
    $t = new epTestLog;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
}

?>
