#!/opt/lampp/bin/php
<?php

/**
 * Script to show compiled class files
 * 
 * Usage: 
 * $ ./epshow.sh -f compiled.ezpdo file
 * $ ./epshow.sh -h 
 * 
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision$ $Date: 2011-03-01 18:51:24 -0500 (Tue, 01 March 2011) $
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
   echo "  1. Shows a specified compiled file.\n";
   echo "     ./epshow.sh -f <compiled_file>\n\n";
   echo "  2. Display this help message)\n";
   echo "     ./epc.sh -h\n\n";
}

/**
 * Parse argv
 * @param array $argv
 * @return false|string (the file)
 */
function parseArgv($argv) {
   
   // check if we have either 2 or 3 args
   if (!isset($argv) || !$argv || !is_array($argv)) {
      displayUsage();
      return false;
   }
   
   switch(count($argv)) {
   
   case 2:
      
      // check the second argument
      if ($argv[1] = '-c' || $argv[1] = '--config') {
         displayError("File name missing after '" . $argv[1] . "'");
      }
      
      // only two argument, display usage
      displayUsage();
      return false;
      
   case 3:
      
      // check the second argument
      switch($argv[1]) {
      
      case '-f':
      case '--file': 
         // check if the specified file exists
         if (!file_exists($argv[2])) {
            displayError("File [" . $argv[2] . "] does not exist.");
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
if (!($file = parseArgv($argv))) {
   exit();
}

// include class maps
include_once(EP_SRC_ORM . '/epClassMap.php');

// load file and print
$file = file_get_contents($file);

$cmf = unserialize($file);
echo $cmf;
?>
