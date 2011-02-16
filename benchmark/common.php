<?php

/**
 * $Id: common.php 405 2005-08-15 13:23:23Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 405 $ $Date: 2005-08-15 09:23:23 -0400 (Mon, 15 Aug 2005) $
 * @package ezpdo_bench
 * @subpackage ezpdo_bench
 */

/**
 * start profiling if apd is installed
 */
if (function_exists('apd_set_pprof_trace')) {
    //apd_set_pprof_trace();
}

/**
 * Need EZPDO runtime API
 */
include_once(dirname(__FILE__) . '/../ezpdo_runtime.php');

// get the start time
$start_time = microtime(true);

/**
 * Get the manager and set options
 * @return epManager (singleton)
 */
function getManager() {
    // get the persistence manager
    $m = epManager::instance();
    $m->setConfigOption("log_queries", true);
    return $m;
}

/**
 * Get the number of queries
 * @return integer
 */
function numQueries() {
    $m = epManager::instance();
    $num_queries = 0;
    $db_queries = $m->getQueries();
    foreach($db_queries as $db => $queries) {
        $num_queries += count($queries);
    }
    return $num_queries;
}

/**
 * Dump queries
 * @return void
 */
function dumpQueries() {
    $m = epManager::instance();
    $db_queries = $m->getQueries();
    foreach($db_queries as $db => $queries) {
        echo "*** database: $db (" . count($queries) . ")***\n";
        if ($queries) {
            // remove "\n"
            $s = '';
            foreach($queries as &$q) {
                $q = str_replace("\n", '', $q);
                $s .= $q . "\n";
            }
            echo $s;
        }
    }
}

/**
 * Get memory usage
 * Source: http://us3.php.net/manual/en/function.memory-get-usage.php
 * @return integer (-1 if unknown) 
 */
function getMemUsage() {
      
    if (function_exists('memory_get_usage')) {
         return memory_get_usage();
     }
    
    else if (strpos( strtolower($_ENV["OS"]), 'windows') !== false) {
        // Windows workaround
        $output = array();
        exec('tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $output);
        if (!$output || !isset($output[5])) {
            return "unknown";
        }
        return substr($output[5], strpos($output[5], ':') + 1);
    }

    else {
        return -1;
    }
}

/**
 * Show a one-line performance info
 * @return void
 */
function showPerfInfo() {
    global $start_time;
    $seconds = microtime(true) - $start_time;
    $queries = numQueries();
    $bytes = getMemUsage();
    echo "(time: $seconds) (queries: $queries) (memory: $bytes bytes)\n";
}

?>
