<?php

/**
 * $Id: ezpdo.php 944 2006-05-12 19:31:23Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 944 $ $Date: 2006-05-12 15:31:23 -0400 (Fri, 12 May 2006) $
 * @package ezpdo
 * @subpackage base
 */

/**
 * report all errors except E_NOTICE
 */
//error_reporting(E_ALL & ~E_NOTICE);
error_reporting(E_ALL);

/**
 * The ezpdo root directory 
 */
if (!defined('EP_ROOT')) {
    define('EP_ROOT', dirname(__FILE__));
}

/**
 * The ezpdo src directory
 */
if (!defined('EP_SRC')) {
    define('EP_SRC', EP_ROOT.'/src');
}

/**
 * The ezpdo src base directory
 */
if (!defined('EP_SRC_BASE')) {
    define('EP_SRC_BASE', EP_SRC.'/base');
}

/**
 * The ezpdo src base directory
 */
if (!defined('EP_SRC_BASE_PARSER')) {
    define('EP_SRC_BASE_PARSER', EP_SRC_BASE.'/parser');
}

/**
 * The ezpdo src cache directory
 */
if (!defined('EP_SRC_CACHE')) {
    define('EP_SRC_CACHE', EP_SRC.'/cache');
}

/**
 * The ezpdo src compiler directory
 */
if (!defined('EP_SRC_COMPILER')) {
    define('EP_SRC_COMPILER', EP_SRC.'/compiler');
}

/**
 * The ezpdo src db directory
 */
if (!defined('EP_SRC_DB')) {
    define('EP_SRC_DB', EP_SRC.'/db');
}

/**
 * The ezpdo src orm directory
 */
if (!defined('EP_SRC_ORM')) {
    define('EP_SRC_ORM', EP_SRC.'/orm');
}

/**
 * The ezpdo src query directory
 */
if (!defined('EP_SRC_QUERY')) {
    define('EP_SRC_QUERY', EP_SRC.'/query');
}

/**
 * The ezpdo src runtime directory
 */
if (!defined('EP_SRC_RUNTIME')) {
    define('EP_SRC_RUNTIME', EP_SRC.'/runtime');
}

/**
 * The ezpdo external libs directory
 */
if (!defined('EP_LIBS')) {
    define('EP_LIBS', EP_ROOT.'/libs');
}

/**
 * The ezpdo external lib simpletest directory
 */
if (!defined('EP_LIBS_SIMPLETEST')) {
    define('EP_LIBS_SIMPLETEST', EP_LIBS.'/simpletest');
}

/**
 * The ezpdo external lib adodb directory
 */
if (!defined('EP_LIBS_ADODB')) {
    define('EP_LIBS_ADODB', EP_LIBS.'/adodb');
}

/**
 * The ezpdo external lib pears directory
 */
if (!defined('EP_LIBS_PEAR')) {
    define('EP_LIBS_PEAR', EP_LIBS.'/pear');
}

/**
 * The default ezpdo log directory
 */
if (!defined('EP_LOG')) {
    define('EP_LOG', EP_ROOT.'/log');
}

/**
 * The ezpdo tests directory
 */
if (!defined('EP_TESTS')) {
    define('EP_TESTS', EP_ROOT.'/tests');
}

/**
 * The ezpdo tests src directory
 */
if (!defined('EP_TESTS_SRC')) {
    define('EP_TESTS_SRC', EP_TESTS.'/src');
}

?>
