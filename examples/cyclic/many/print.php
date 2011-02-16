<?php

/**
 * $Id: print.php 295 2005-06-30 16:29:13Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 295 $ $Date: 2005-06-30 12:29:13 -0400 (Thu, 30 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.cyclic.many
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

// go through each group
foreach($as as $a) {
    
    echo '$a' . "\n";
    echo $a;
    echo "\n";
    
    echo '$a->bs[0]' . "\n";
    echo $a->bs[0];
    echo "\n";
    
    echo '$a->bs[0]->cs[0]' . "\n";
    echo $a->bs[0]->cs[0];
    echo "\n";
    
    echo '$a->bs[0]->cs[0]->as[0]' . "\n";
    echo $a->bs[0]->cs[0]->as[0];
    echo "\n";
}

?>
