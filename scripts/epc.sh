#!/opt/lampp/bin/php
<?php

/**
 * Script to compile class files
 * 
 * Usage: 
 * $ ./epc.sh -c config.xml
 * $ ./epc.sh -h 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1015 $ $Date: 2006-10-30 18:51:24 -0500 (Mon, 30 Oct 2006) $
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
   echo "     ./epc.sh\n\n";
   echo "  2. Compile with a specified config.xml.\n";
   echo "     ./epc.sh -c <config_file>\n\n";
   echo "  3. Display this help message)\n";
   echo "     ./epc.sh -h\n\n";
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

// call compiler to compile
include_once(EP_SRC_COMPILER.'/epCompiler.php');
if (!($c = new epClassCompiler($cfg))) {
   displayError("Cannot instantiate EZPDO compiler.");
   exit();
}

// now compile
$c->compile();

?>
