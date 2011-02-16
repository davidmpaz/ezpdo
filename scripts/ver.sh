#!/opt/lampp/bin/php
<?php

/**
 * Script to print out EZPDO versoin number 
 * 
 * Usage: 
 * $ ./ver.sh
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 308 $ $Date: 2005-07-04 13:15:15 -0400 (Mon, 04 Jul 2005) $
 * @package ezpdo
 * @subpackage script
 */

/**
 * Needs EZPDO runtime API
 */
include_once(dirname(__FILE__).'/../ezpdo_runtime.php');

// output EZPDO version number
echo "EZPDO " . epManager::instance()->version() . "\n";

?>
