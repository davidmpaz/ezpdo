<?php

/**
 * $Id: print_a.php 372 2005-08-04 04:40:24Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 372 $ $Date: 2005-08-04 00:40:24 -0400 (Thu, 04 Aug 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.cyclic.one
 */

/**
 * Need EZPDO runtime API
 */
include_once(dirname(__FILE__) . '/../../../ezpdo_runtime.php');

// get the persistence manager
$m = epManager::instance();

// get all objects of class A
if (!($as = $m->get('A'))) {
    echo "Cannot find any object of class A\n";
    exit();
} 

// go through each group and access vars in object as in array
foreach($as as $a) {
    
    echo '$a' . "\n";
    echo $a;
    echo "\n";
    
    echo '$a[\'b\']' . "\n";
    echo $a['b'];
    echo "\n";
    
    echo '$a[\'b\'][\'c\']' . "\n";
    echo $a['b']['c'];
    echo "\n";
    
    echo '$a[\'b\'][\'c\'][\'a\']' . "\n";
    echo $a['b']['c']['a'];
    echo "\n";
}

?>
