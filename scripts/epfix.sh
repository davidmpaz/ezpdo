#!/opt/lampp/bin/php
<?php

/**
 * WARNING: !!!INCOMPLETE!!!!
 */

/**
 * Script to repair object relational table
 * 
 * Usage: 
 * $ ./epr.sh -c config.xml
 * $ ./epr.sh -h 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 333 $ $Date: 2005-07-16 07:00:39 -0400 (Sat, 16 Jul 2005) $
 * @package ezpdo
 * @subpackage script
 */

/**
 * need ezpdo runtime
 */
include_once(dirname(__FILE__).'/../ezpdo_runtime.php');

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
   echo "  1. Repair with default config.xml.\n";
   echo "     (You need to have config.xml in the current directory.)\n";
   echo "     ./epr.sh\n\n";
   echo "  2. Repair with a specified config.xml.\n";
   echo "     ./epr.sh -c <config_file>\n\n";
   echo "  3. Display this help message)\n";
   echo "     ./epr.sh -h\n\n";
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
   exit(-1);
}

// load the specified config
if ($config_file != 'config.xml') {
   epLoadConfig($config_file);
}

// get ezpdo manager
$m = epManager::instance();

// disable auto_flush
$m->setConfigOption('auto_flush', false);

// load all epObjectRelation objects
if (!($ors = $m->get('epObjectRelation'))) {
   displayError("No object relationship entry is found. No need to repair.");
   exit(-1);
}

echo count($ors) . " relationship entries are found.\n";

// loop through all relationship objects
$deleted = 0;
foreach($ors as $or) {
   // check if the object is valid
   if (true !== ($errors = $or->isValid(false))) { // false: no recursion
      echo "Invalid relationship entry [" . $or->epGetObjectId() . "]: " . implode(", ", $errors) . ". ";
      $or->delete();
      $deleted ++;
      echo "Deleted.\n";
   }
}
echo $deleted . " relationship entries are invalid and deleted.\n";

exit(0);

?>
