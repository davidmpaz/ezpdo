#!/opt/lampp/bin/php
<?php

/**
 * Script to update schema for current class files
 * 
 * Usage: 
 * $ ./epus.sh -c config.xml
 * $ ./epus.sh -h 
 * 
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision: 1015 $ $Date: 2011-03-07 18:51:24 -0500 (Mon, 07 March 2011) $
 * @package ezpdo
 * @subpackage script
 */

/**
 * need ezpdo.php
 */
include_once(realpath(dirname(__FILE__).'/../ezpdo.php'));

/**
 * need ezpdo utils
 */
include_once(EP_SRC_BASE.'/epUtils.php');

/**
 * Output error message on console
 */
function displayError($error_msg) {
   echo "Error: \n";
   echo "  " . ucfirst(trim($error_msg)) . "\n\n";
}

/**
 * Display usage of this script
 * @return void
 */
function displayUsage() {
   echo "Usage of " . basename(__FILE__) . ":\n\n"; 
   echo "  1. To compile with default config.xml.\n";
   echo "     (You need to have config.xml in the current directory.)\n";
   echo "     ./epus.sh\n\n";
   echo "  2. Compile with a specified config.xml.\n";
   echo "     ./epus.sh -c <config_file>\n\n";
   echo "  3. Display this help message)\n";
   echo "     ./epus.sh -h\n\n";
}

/**
 * Parse argv
 * @param array $argv
 * @return false|string (the config file)
 */
function parseArgv($argv) {
   
   // check if we have either 2 or 3 args
   if (!isset($argv) || !$argv || !is_array($argv)) {
      displayUsage();
      return false;
   }
   
   switch(count($argv)) {
   
   case 1:
      
      // check if config.xml can be found in the current dir
      if (!file_exists("config.xml")) {
         displayError("Cannot find 'config.xml' under the current directory.");
         displayUsage();
         return false;
      }
      
      // use config.xml (default) in the current directory
      return 'config.xml';
      
   case 2:
      
      // check the second argument
      if ($argv[1] = '-c' || $argv[1] = '--config') {
         displayError("Config file name missing after '" . $argv[1] . "'");
      }
      
      // only two argument, display usage
      displayUsage();
      return false;
      
   case 3:
      
      // check the second argument
      switch($argv[1]) {
      
      case '-c':
      case '--config': 
         // check if the specified config.xml exists
         if (!file_exists($argv[2])) {
            displayError("Config file [" . $argv[2] . "] does not exist.");
            return false;
         }
         return $argv[2];
      
      default:
         displayUsage();
         return false;
      }
   
   default:
      displayUsage();
   }
   
   return false;
}

// parse argv and check config file
if (!($config_file = parseArgv($argv))) {
   exit();
}

// load config from config file
include_once(EP_SRC_BASE.'/epConfig.php');
if (!($cfg = & epConfig::load($config_file))) {
   displayError("Loading config file [" . $config_file . "] failed.");
   exit();
}

// check for the proper config options
if ( !( $cfg->get('update_strategy') && $cfg->get('update_from') && 
        $cfg->get('auto_update') && $cfg->get('force_update')) ){
    displayError("Config file [" . $config_file . "] is not properly configured.");
    exit();
}

// call updater
include_once(EP_ROOT.'/ezpdo_runtime.php');
if (!($m = new epManager($cfg))) {
   displayError("Cannot instantiate EZPDO manager.");
   exit();
}

// now update
$m->alterTables();

?>
