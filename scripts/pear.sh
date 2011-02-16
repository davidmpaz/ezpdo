<?php

// need to use epFilesInDir() in epUtils
include_once(dirname(__FILE__) . "/../ezpdo.php");
include_once(EP_SRC_BASE . '/epUtils.php');
   
/**
 * Get EZPDO version
 * @return string
 */
function _getVersion() {
   return '1.0.4';
}

/**
 * Recursively get all files under directory
 * (File names are relative to EZPDO root)
 * @return array
 */
function _getFiles() {
   
   // grab all files and make the names relative
   $files = array();
   foreach(epFilesInDir(dirname(__FILE__) . '/..') as $file) {
      
      // weed out CVS dirs and files
      if (false !== stripos($file, 'cvs')) {
         continue;
      }

      // remvoe absolute path to ezpdo root
      $files[] = str_replace(EP_ROOT . '/', '', $file);
   }

   return $files;
}

/**
 * Aabsurdly we want to make every file "php" (simply want to put them in 
 * the same install directory for now!)
 * @return array (keyed by file names, filename => role)
 */
function _getExceptions() {
   $exceptions = array();
   foreach(_getFiles() as $file) {
      if (stripos($file, '.php') || stripos($file, '.php')) {
         $exceptions[$file] = 'php';
      }
      else if (stripos($file, '.sh')) {
         $exceptions[$file] = 'script';
      }
      else {
         $exceptions[$file] = 'src';
      }
   }
   return $exceptions;
}

require_once('PEAR/PackageFileManager.php');
$pkgm = new PEAR_PackageFileManager;

// set up general options
$e = $pkgm->setOptions(
   array(
      'package' => 'ezpdo', 
      'baseinstalldir' => 'ezpdo', 
      'version' => _getVersion(), 
      'license' => 'BSD',
      'packagedirectory' => '.', // put package.xml in pwd
      'filelistgenerator' => 'cvs',
      'state' => 'stable', 
      'notes' => 'See release news at http://www.ezpdo.net',
      'description' => 'EZPDO: A simple solution of object relational mapping and persistence solution for PHP',
      'summary' => 'EZPDO provides a lightweight and easy-to-use persistence solution for PHP',

      'maintainers' => array(
         array(
            "handle" => "nauhygon",
            "name" => "Oak Nauhygon",
            "email" => "ezpdo4php@gmail.com",
            "role" => "lead",
            )
         ), 

      'exceptions' => _getExceptions(), 
      )
   );

if (PEAR::isError($e)) {
   echo $e->getMessage();
   die();
}  

$e = $pkgm->writePackageFile();

if (PEAR::isError($e)) {
   echo $e->getMessage();
   die();
}  

?>
