#!/opt/lampp/bin/php
<?php

/**
 * Script to check if EZPDO can run in your environment
 * 
 * Usage: 
 * $ ./check.sh
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 618 $ $Date: 2005-11-24 15:45:34 -0500 (Thu, 24 Nov 2005) $
 * @package ezpdo
 * @subpackage script
 */

/**
 * Global var (indicates if any problem found)
 */
$error = false;

/**
 * Report version 
 * @param string $var
 */
function ver_error($ver) {
    global $error;
    $error = true;
    echo "EZPDO requires PHP version 5.0.3 or higher. Your current version is $ver.\n";
    exit();
}

/**
 * Report a missing extension 
 * @param string $ext extenstion name
 * @param string $ccfg compile config
 */
function ext_error($ext, $ccfg, $optional = false) {
    global $error;
    $error = true;
    if ($optional) {
        echo "[Optional] ";
    }
    echo "Extension $ext is not present in PHP. Please compile with options ($ccfg)\n";
}

/**
 * Check if a file exists in the include path
 * @param string $file Name of the file to look for
 * @return bool TRUE if the file exists, FALSE if it does not
 * @author Aidan Lister <aidan@php.net>
 * @see http://aidan.dotgeek.org/lib/?file=function.file_exists_incpath.php
 */
function is_file_in_inc_dir($file) {
   $paths = explode(PATH_SEPARATOR, get_include_path());
   foreach ($paths as $path) {
       $fullpath = $path . DIRECTORY_SEPARATOR . $file;
       if (file_exists($fullpath)) {
           return true;
       }
   }
   return false;
}

/**
 * Check if PEAR::DB is > 1.7.2
 */
function check_pear_db() {
   global $error;

   // in case we don't see PEAR::DB installed
   if (!is_file_in_inc_dir('DB.php')) {
      echo "[Optional] PEAR DB is not installed. You may install 1.7.2 or up\n";
      echo "           *only* if you decide to use it. ADODb is used by default.\n";
      $error = true;
      return;
   }

   // otherwise let's check its version
   include_once('DB.php');
   if (version_compare($db_ver = DB::apiVersion(), '1.7.2') < 0) {
      echo "[Optional] Version of PEAR DB is $db_ver. Upgrade to 1.7.2 or up\n";
      echo "           *only* if you decide to use it. ADODb is used by default.\n";
      $error = true;
   }
}

// check PHP version
if (version_compare($ver = phpversion(), "5.0.4", "<")) {
    ver_error($ver);
}

// check spl
if (!extension_loaded('spl')) {
    ext_error('spl', '--enable-spl');
}

// check tokenizer
if (!extension_loaded('tokenizer')) {
    ext_error('tokenizer', '--enable-tokenizer');
}

// check xml
if (!extension_loaded('xml')) {
    ext_error('xml', '--enable-xml');
}

// check simplexml
if (!extension_loaded('simplexml')) {
    ext_error('xml', '--enable-simplexml', true); // true: optioinal
}

// check sqlite (optional)
if (!extension_loaded('sqlite')) {
    ext_error('sqlite', '--enable-sqlite', true); // true: optional
}

// check mysql (optional)
if (!extension_loaded('mysql')) {
    ext_error('mysql', '--enable-mysql', true); // true: optional
}

// check pear::db
check_pear_db();

// is the system ready for EZPDO?
if (!$error) {
    echo "Your PHP environment is READY for EZPDO!\n";
}

?>
