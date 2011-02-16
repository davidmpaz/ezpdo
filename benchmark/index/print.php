<?php

/**
 * $Id: print.php 300 2005-07-01 19:28:12Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 300 $ $Date: 2005-07-01 15:28:12 -0400 (Fri, 01 Jul 2005) $
 * @package ezpdo_bench
 * @subpackage ezpdo_bench.index
 */

include_once(dirname(__FILE__) . '/common.php');

/**
 * Print all objects in a class
 */
function print_os($class) {
    
    $m = getManager();
    
    if (!($os = $m->getAll($class))) {
        echo "no object in class $class\n"; 
        return;
    }

    $i = 0;
    foreach($os as $o) {
        echo $o; echo "\n";
        if (($i ++) > 5) { 
            break; 
        }
    }
}   

print_os('User');
print_os('Group');
print_os('Thingy');

showPerfInfo();

?>
