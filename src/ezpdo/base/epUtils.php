<?php

/**
 * $Id: epUtils.php 1028 2006-12-28 11:15:37Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision: 1028 $ $Date: 2006-12-28 06:15:37 -0500 (Thu, 28 Dec 2006) $
 * @package ezpdo
 * @subpackage ezpdo.base
 */
namespace ezpdo\base;

use ezpdo\runtime\epManager;

/**
 * define EP_CLI_RUN
 */
define('EP_CLI_RUN', (php_sapi_name() == 'cli') || php_sapi_name() == 'cgi'); //???

/**
 * The utilities class
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base
 */
class epUtils {

    /**
     * Check if the script is run on web (not cli)
     * @return bool
     * @see epIsCliRun()
     */
    static function epIsWebRun() {
        return !EP_CLI_RUN;
    }

    /**
     * Check if the script is run on cli
     * @return bool
     * @see epIsWebRun()
     */
    static function epIsCliRun() {
        return EP_CLI_RUN;
    }

    /**
     * Return either "<br/>" for Web run or "\n" for CLI
     * @return string
     */
    static function epNewLine() {
        return self::epIsWebRun() ? "<br/>" : "\n";
    }

    /**
     * Prepend indent to each line in a string
     * @param string
     * @return string
     */
    static function epIndentLines($str, $indent = "\t") {
        $str = str_replace("\r\n", "\n", $str);
        $str_output = $indent . str_replace("\n", "\n".$indent, $str);
        return $str_output;
    }

    /**
     * Define a constant to be the same of its name if value not given
     * @param string $s
     * @param mixed $v
     */
    static function epDefine($s, $v = null) {
        if (!defined($s)) {
            define($s, is_null($v) ? $s : $v);
        }
    }

    /**
     * Wrapper of Var_Dump
     * @param mixed any kind of eepression
     * @return void
     */
    static function epVarDump($var) {
        static $vd;
        if (self::epIsWebRun()) {
            include_once('Var_Dump.php');
            Var_Dump::displayInit(array('display_mode' => 'HTML4_Table'));
            Var_Dump::display($var);
        } else {
            var_dump($var);
        }
    }

    /**
     * Remove excessive whitespaces: replace multiple ' 's with single ' '
     * @param string
     * @return string
     */
    static function epRemoveWhiteSpaces($str) {
        $output_str = $str;
        $count = 1;
        while ($count) {
            $output_str = str_replace('  ', ' ', $output_str, $count);
        }
        return $output_str;
    }

    /**
     * Do str_replace() on substrings that match a search pattern in a long string
     * @param string pattern to match substr
     * @param string search for str_replace
     * @param string replacement for str_replace
     * @param string the original string
     * @return string the resulting string
     */
    static function epStrReplaceInMatched($pattern, $search, $replace, $long_string) {

        if (empty($pattern) || empty($search)) {
            return false;
        }

        $output = preg_replace_callback(
            $pattern,
            create_function(
                '$matches',
                'return str_replace(\''.$search.'\', \''.$replace.'\', $matches[0]);'
                ),
            $long_string
            );

        return $output;
    }

    /**
     * Converts string to boolean
     * <ul>
     * <li>string "true", "yes", "okay" => true</li>
     * <li>string "false", "no" => false</li>
     * <li>otherwise, same old value</li>
     * </ul>
     * @return mixed
     */
    static function epStr2Bool($s) {

        // for non-string, return original
        if (!is_string($s)) {
            return $s;
        }

        $p = strtolower($s);

        if ($p == 'true' || $p == 'yes' || $p == 'okay') {
            return true;
        } else if ($p == 'false' || $p == 'no') {
            return false;
        }

        // no match, return original
        return $s;
    }

    /**
     * Converts array value string to boolean recursively
     * @see epStr2Bool()
     * @return array
     */
    static function epArrayStr2Bool($array) {

        $array_b = $array;

        foreach($array as $key => $value) {

            if (!is_array($value)) {
                $array_b[$key] = self::epStr2Bool($value);
                continue;
            }

            $array_b[$key] = self::epArrayStr2Bool($array[$key]);
        }
        return $array_b;
    }


    /**
     * Check if a directory is empty
     * @param string directory path
     * @return bool
     */
    static function epIsDirEmpty($dir) {

        // if dir does not exist or is not a dir, yes, it's empty
        if (!file_exists($dir) || !is_dir($dir)) {
            return true;
        }

        // scan directory for files
        $files = scandir($dir);
        if (!is_array($files)) {
            return false;
        }

        // besides '.' and '..', anything else?
        return count($files) <= 2;
    }

    /**
     * Get all files (w/ full path) under directory
     * @param string directcory
     * @param bool true for recursive get
     * @param bool true for absolute path
     * @return array
     */
    static function epFilesInDir($dir = '.', $recursive = true, $absolute_path = true) {

        $files_in_dir = array();

        // done if dir does not exist or is not a dir
        if (!file_exists($dir) || !is_dir($dir)) {
            return $files_in_dir;
        }

        // scan directory for files
        $entities = scandir($dir);
        if (!is_array($entities)) {
            return $files_in_dir;
        }

        // go through each file/dir
        foreach($entities as $entity) {

            // ignore . and ..
            if ($entity == '.' || $entity == '..') {
                continue;
            }

            // get the path of the entity
            $path = $dir . '/' . $entity;

            // check if path is a file
            if (is_file($path)) {
                // rest is treated as a file
                if ($absolute_path) {
                    $files_in_dir[] = realpath($path);
                } else {
                    $files_in_dir[] = $path;
                }
                continue;
            }

            // check if path is a dir
            if (is_dir($path) && $recursive) {
                $files_in_dir = array_merge($files_in_dir, self::epFilesInDir($path, $recursive));
            }
        }

        return $files_in_dir;
    }

    /**
     * Make directory if not existing (with a fix to PHP5 mkdir())
     * @param string $dir
     * @param integer option
     * @return bool
     */
    static function epMkDir($dir, $option = 0700) {

        // does dir exist?
        if (file_exists($dir) || is_dir($dir)) {
            return true;
        }

        // PHP 5 mkdir breaks when recursively building a
        // directory that has a '//' in the middle.
        if (!self::epMkDir(dirname($dir), $option)) {
            return false;
        }

        return @mkdir($dir, $option);
    }

    /**
     * Remove all contents in a directory
     * @param string $dir
     * @return bool
     */
    static function epRmDir($dir) {

        $status = true;

        // glob all files under dir
        if ($objs = glob($dir . "/*")){

            // go through each object
            foreach($objs as $obj) {

                // delete dir
                if (is_dir($obj)) {
                    $status &= @rmdir($obj);
                }

                // delete file
                else {
                    $status &= @unlink($obj);
                }
            }
        }

        $status &= @rmdir($dir);

        return $status;
    }

    /**
     * Convert a (binary or char) string into an Ascii hex string
     * Work with {@ink epHex2Str()} in pair.
     * Copied from a post at {@link http://us3.php.net/manual/en/function.bin2hex.php}
     * @param string $s
     * @return string
     */
    static function epStr2Hex($s) {
        $len = strlen($s);
        $data = '';
        for ($i = 0; $i < $len; $i ++) {
            $data .= sprintf("%02x",ord(substr($s,$i,1)));
        }
        return $data;
    }

    /**
     * Unquote a string
     * @param string $s
     * @return string
     */
    static function epUnquote($s) {
        $start = 0;
        if ($s[$start] == '"' || $s[$start] == "'" || $s[$start] == "`") {
            $start ++;
        }
        $end = strlen($s) - 1;
        if ($s[$end] == '"' || $s[$end] == "'" ||  $s[$end] == "`") {
            $end --;
        }
        return substr($s, $start, $end-$start+1);
    }

    /**
     * Convert an Ascii hex string into a (binary or char) string.
     * Work with {@ink epStr2Hex()} in pair.
     * Copied from a post at {@link http://us3.php.net/manual/en/function.bin2hex.php}.
     * @param string $s
     * @return string
     */
    static function epHex2Str($s) {
        $len = strlen($s);
        $data = '';
        for ($i = 0; $i<$len; $i += 2) {
            $data .= chr(hexdec(substr($s, $i, 2)));
        }
        return $data;
    }

    /**
     * Backup a directory by renaming it and creating a new dir with the old name
     * @param string directory path
     * @param string the name of the backup directory
     * @return bool
     */
    static function epDirBackup($dir, $dir_bk = '') {

        // check if dir exists
        if (!file_exists($dir) || !is_dir($dir)) {
            return false;
        }

        // if $dir_bk is give empty, figure out one with bkup date
        if (empty($dir_bk)) {
            $dir_bk = $dir . '-bk-' . date('ymdhis');
        }

        // rename the existing
        if (!rename($dir, $dir_bk)) {
            return false;
        }

        // create the dir with the old name
        return mkdir($dir);
    }

    /**
     * Get value in a multi-dimensional associative array by
     * namespace-like ('a.b.c.d') or xpath-like ('a/b/c/d')
     * key. Paired with {@link epArraySet()}.
     *
     * For example, use epArrayGet($array, 'a.b.c.d') or
     * epArrayGet($array, 'a/b/c/d') to get value 'x' from
     * <pre>
     * $array = array(
     *   'a' => array(
     *     'b' => array(
     *       'c' => array(
     *         'd' => 'x'
     *       )
     *     )
     *   )
     * )
     * </pre>
     *
     * @param array|ArrayAccess $array
     * @param string $key namespace- or xpath-like key
     * @return mixed (array or scalar)
     */
    static function epArrayGet($array, $key) {

        $null = null;

        // sanity check
        if (!is_array($array) && !($array instanceof \ArrayAccess)) {
            return $null;
        }

        // break key into pieces
        $pieces = preg_split('/(\.|\/)/', $key, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($pieces)) {
            // if no piece, return the whole array
            return $array;
        }

        // use refence walk through the array
        $array_cur = $array;
        foreach($pieces as $piece) {

            // if key ($piece) not found, end of search
            if (!isset($array_cur[$piece])) {
                return $null;
            }

            // move to a deeper level
            $array_cur = $array_cur[$piece];
        }

        return $array_cur;
    }

    /**
     * Set value to a multi-dimensional associative array to a
     * location specified by a namespace-like ('a.b.c.d') or
     * xpath-like ('a/b/c/d') key. Paired with {@link epArrayGet()}.
     *
     * @param array|ArrayAccess $array
     * @param string namespace- or xpath-like key
     * @param mixed value to be set
     * @return false|array
     */
    static function epArraySet(&$array, $key, $value) {

        $false = false;

        // sanity check
        if (!is_array($array) && !($array instanceof \ArrayAccess)) {
            return $false;
        }

        // break key into pieces
        $pieces = preg_split('/(\.|\/)/', $key, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($pieces)) {
            return $false;
        }

        // use refence walk through the array
        $array_cur = & $array;
        $i = 0;
        $n = count($pieces);
        foreach($pieces as $piece) {

            //echo "$piece\n";

            // reach the last one yet?
            if (++$i == $n) {

                // assign value
                $array_cur[$piece] = $value;

            } else {

                // not yet. if key ($piece) not found, expand array
                if (!isset($array_cur[$piece])) {
                    $array_cur[$piece] = array();
                } else if (!is_array($array_cur[$piece])) {
                    // if non-array, make it an array
                    $array_cur[$piece] = array($array_cur[$piece]);
                }

                // move to a deeper level
                $array_cur = & $array_cur[$piece];
            }

        }

        return $array;
    }

    /**
     * Recursively merge two arrays.
     *
     * The non-array values of the first
     * array are replaced with the values in the second array of the
     * same keys. The difference between this method and the native
     * PHP array_merge_recursive() is that instead of collecting all
     * different values for the same key in both arrays, this method
     * replaces the value with the value in the second array. This is
     * useful, for example, for merging configuration options from
     * different sources.
     *
     * Here is an example to show the difference.
     *
     * <pre>
     * $array0 = array(
     *                "a" => array(
     *                            "b" => array(
     *                                        "d" => "d",
     *                                        "e" => "f",
     *                                        ),
     *                            "c" => array(
     *                                        "g" => "g",
     *                                        )
     *                            )
     *                );
     *
     * $array1 = array(
     *                "a" => array(
     *                            "c" => array(
     *                                        "g" => "changed",
     *                                        "h" => "new",
     *                                        )
     *                            ),
     *                );
     *
     *
     * echo "array0:\n";
     * print_r($array0);
     *
     * echo "array1:\n";
     * print_r($array1);
     *
     * $array_m1 = array_merge_recursive($array0, $array1);
     * echo "array_m1:\n";
     * print_r($array_m1);
     *
     * $array_m2 = epArrayMergeRecursive($array0, $array1);
     * echo "array_m2:\n";
     * print_r($array_m2);
     *
     * The output is
     *
     * array0:
     * Array
     * (
     *     [a] => Array
     *         (
     *             [b] => Array
     *                 (
     *                     [d] => d
     *                     [e] => f
     *                 )
     *             [c] => Array
     *                 (
     *                     [g] => g
     *                 )
     *         )
     * )
     *
     * array1:
     * Array
     * (
     *     [a] => Array
     *         (
     *             [c] => Array
     *                 (
     *                     [g] => changed
     *                     [h] => new
     *                 )
     *         )
     * )
     *
     * array_m1:
     * Array
     * (
     *     [a] => Array
     *         (
     *             [b] => Array
     *                 (
     *                     [d] => d
     *                     [e] => f
     *                 )
     *             [c] => Array
     *                 (
     *                     [g] => Array
     *                         (
     *                             [0] => g
     *                             [1] => changed
     *                         )
     *                     [h] => new
     *                 )
     *
     *         )
     *
     * )
     *
     * array_m2:
     * Array
     * (
     *     [a] => Array
     *         (
     *             [b] => Array
     *                 (
     *                     [d] => d
     *                     [e] => f
     *                 )
     *
     *             [c] => Array
     *                 (
     *                     [g] => changed
     *                     [h] => new
     *                 )
     *
     *         )
     *
     * )
     * </pre>
     */
    static function epArrayMergeRecursive($array1, $array2) {
        $merged = $array1;
        foreach($array2 as $key => $value) {
            if (!is_array($value)) {
                if (!isset($array1[$key]) || $array2[$key] !== $array1[$key]) {
                    $merged[$key] = $value;
                }
                continue;
            }
            if (!isset($array1[$key])) {
                $array1[$key] = array();
            }
            $merged[$key] = self::epArrayMergeRecursive($array1[$key], $value);
        }
        return $merged;
    }

    /**
     * Convert an xml file or string into an array
     * @param string $xml (xml file or string)
     * @return false|array
     */
    static function epXml2Array($xml) {

        // trim input
        $xml = trim($xml);

        // sanity check
        if (!$xml) {
            return false;
        }

        if ($xml[0] == '<') {
            // load an xml string
            if (($sxml = simplexml_load_string($xml)) === false) {
                return false;
            }
        }
        else {
            // load an xml file
            if (($sxml = simplexml_load_file($xml)) === false) {
                return false;
            }
        }

        return self::epSimpleXmlElement2Array($sxml);
    }

    /**
     * Convert SimpleXML element into an array
     * @param SimpleXMLElement $e
     * @return array
     * @author Robert Janeczek <rashid@ds.pg.gda.pl>
     * @see http://www.php.net/manual/en/ref.simplexml.php
     */
    static function epSimpleXmlElement2Array($e) {

        $array = array();

        foreach($e as $tag => $value) {

            // parse children
            $child = self::epSimpleXmlElement2Array($value);

            // if no children
            if (count($child) == 0) {
                // this is string value
                $child = (string)$value;
            }

            $array[$tag] = $child;
        }

        return $array;
    }

    /**
     * Convert a value into an XML string
     * @param array $array
     * @return string
     */
    static function epValue2Xml($value, $root = 'document', $version='1.0') {

        // convert value to xml body
        if (!($xml = self::epValue2XmlBody($value))) {
            return false;
        }

        // wrap xml body into a root node
        if ($root) {
            $xml = "<$root>$xml</$root>";
        }

        // add xml header (version)
        if ($version) {
            $xml = "<?xml version='$version'?>" . $xml;
        }

        return $xml;
    }

    /**
     * Convert a value into an XML body string (without xml version declaration etc.)
     * @param array $array
     * @return string
     */
    static function epValue2XmlBody($value) {

        // convert array into xml
        if (is_array($value) || is_object($value)) {
            $xml = '';
            foreach($value as $k => $v) {
                if ($k) {
                    $xml .= "<$k>" . self::epValue2XmlBody($v) . "</$k>";
                }
            }
            return $xml;
        }

        // a scalar value
        else if (is_scalar($value)) {

            // convert boolean into "true"/"false"
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            // all the rest types of scalar value
            return $value;
        }
    }

    /**
     * Creates an object of a class ($class) with arguments in an array ($args)
     * @param string $class
     * @param array $args array of arguments
     * @return object
     */
    static function &epNewObject($class, $args = array()) {

        $eval_str = "\$o = new $class";

        $argc = count($args);
        if ( $argc > 0 ) {
            $eval_str .= "(";
            for ( $i = 0; $i < $argc; $i ++ ) {
                $eval_str .= '$args[' . $i . ']';
                if ( $i != $argc - 1 ) {
                    $eval_str .= ", ";
                }
            }
            $eval_str .= ")";
        }

        $eval_str .= ";";

        // ensure we have the class reachable
        epManager::instance()->autoload($class);

        eval($eval_str);

        return $o;
    }

    /**
     * Checks if a path is an absolute path
     * @param string $path
     * @return bool
     */
    static function epIsAbsPath($path) {

        $path = trim($path);

        if (!$path) {
            return false;
        }

        return $path[0] == '/' || (isset($path[1]) && $path[1] == ':');
    }

    /**
     * Check if a file exists in the include path
     * @param string $file Name of the file to look for
     * @return bool TRUE if the file exists, FALSE if it does not
     * @author Aidan Lister <aidan@php.net>
     * @see http://aidan.dotgeek.org/lib/?file=function.file_exists_incpath.php
     */
    static function epFileExistsIncPath($file) {

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
     * Check if a method in class is static (and public)
     * (Note this function uses PHP5 ReflectionMethod {@link http://www.php.net/manual/en/language.oop5.reflection.php})
     * @param string|object $class class name or object
     * @param string $method
     */
    static function epIsMethodStatic($class, $method) {

        // get class name if param is object
        if (is_object($class)) {
            $class = get_class($class);
        }

        // check if class exists
        if (!class_exists($class)) {
            return false;
        }

        // use PHP5 ReflectionMethod
        try {
            if (!$m = new \ReflectionMethod($class, $method)) {
                return false;
            }
        }
        catch (\Exception $e) {
            return false;
        }

        // return if method is static
        return $m->isPublic() && $m->isStatic();
    }

    /**
     * Returns the extension in a file name (false if not found)
     * @param string $fname
     * @return false|string
     */
    static function epFileExtension($fname) {
        $fname = trim($fname);
        if (false === ($pos = strripos($fname, '.'))) {
            return false;
        }
        return substr($fname, $pos + 1);
    }

    /**
     * Return file unserialized content of the latest backup file
     *
     * @param string $path dir\file to find backups files
     * @return false|epClassMapFactory unserialized class map from backup
     */
    static function epGetBackup($path = '.'){
        // if file is provided load it and unserialize
        if(is_file($path) && ($content = file_get_contents($path))){
            return ($content) ? unserialize($content) : false;
        }
        // compiled dir was provided search last backup and load it
        $mtime = 0; $file = false;
        $files = self::epFilesInDir($path);
        foreach ($files as $f) {
            if( (strpos($f, '.backup.') !== false) && ($t = filemtime($f)) >= $mtime){
                $mtime = $t;
                $file = $f;
            }
        }
        if($file && ($content = file_get_contents($file))){
            return ($content) ? unserialize($content) : false;
        }
        return false;
    }

    /**
     * Deletes last backup made of the compiled file
     * @param strin $path
     */
    static function epRmBackup($path = null){
        // no path or file name
        if(! is_dir($path)){
            return false;
        }

        // compiled dir was provided search last backup and load it
        $mtime = 0; $file = false;
        $files = self::epFilesInDir($path);
        foreach ($files as $f) {
            if( (strpos($f, '.backup.') !== false) && ($t = filemtime($f)) >= $mtime){
                $mtime = $t;
                $file = $f;
            }
        }

        // found file, delete it!
        if($file){
            unlink($file);
            return true;
        }

        return false;
    }

    /**
     * Write into file the array passed, one line per array element
     * @param string $path
     * @param array $content
     * @return bool
     */
    static function epWriteToFile($path, $content){
        // if not file do nothing
        if(is_dir($path)){
            return false;
        }

        // write to file
        if(is_array($content) && file_put_contents($path, implode("\n", $content))){
            return true;
        }

        throw new epExceptionConfig('Cannot write into file or not exist at [' . $path . ']');
        return false;
    }

}
