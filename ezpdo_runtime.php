<?php

/**
 * $Id: ezpdo_runtime.php 755 2006-01-01 02:58:58Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 755 $ $Date: 2005-12-31 21:58:58 -0500 (Sat, 31 Dec 2005) $
 * @package ezpdo
 * @subpackage base
 */

/**
 * Need basic definitions to src and lib directories
 */
include_once(dirname(__FILE__).'/ezpdo.php');

/**
 * Need persistence manager ({@link epManager}) 
 */
include_once(EP_SRC_RUNTIME.'/epManager.php');

/**
 * Sets up class autoloading (__autoload()) if it is not already defined 
 * and the configuration option 'autoload' in the persistence manager 
 * ({@link epManager}) is set to true. 
 * 
 * Sometimes the magic function __autoload() may also be defined in places 
 * outside EZPDO and they may end up conflicting with each other. The best 
 * is to have an option to turn off defining __autoload() in EZPDO and let 
 * the end user decide. In case the option 'autoload' is set to false, you 
 * can use method {@link epManager::autoload()} to load class files compiled 
 * by EZPDO in your own implementation of __autoload().
 * 
 * This method is called by {@link epManager::initialize()}.
 * 
 * @param string $class_name 
 */
function epSetupAutoload() {
    if (!function_exists('__autoload')) {
        function __autoload($class_name) {
            epManager::instance()->autoload($class_name);
        }
    }
}

/**
 * Load configuration from a file and set it to the EZPDO manager. 
 * 
 * If config file is not specified, it tries to load config.xml first. 
 * Then config.ini if config.xml not found from the current directory. 
 * 
 * @param string $file
 * @return bool 
 */
function epLoadConfig($file = false) {
    
    // use default config file?
    if (!$file) {
        // try config.ini first
        if (file_exists($file = 'config.ini')) {
            $file = 'config.ini';
        } else if (file_exists('config.xml')) {
            $file = 'config.xml';
        } else {
            return false;
        }
    } else {
        // check if the specified config file exists
        if (!file_exists($file)) {
            return false;
        }
    }
    
    // load the config file
    include_once(EP_SRC_BASE.'/epConfig.php');
    if (!($cfg = & epConfig::load($file))) {
        return false;
    }
    
    // set config to the EZPDO manager
    return epManager::instance()->setConfig($cfg);

}

/**
 * If we have config.xml or config.ini in the current directory, load it 
 * and set it to the manager
 */
epLoadConfig();

?>
