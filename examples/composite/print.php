<?php

/**
 * $Id: print.php 327 2005-07-15 13:36:42Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 327 $ $Date: 2005-07-15 09:36:42 -0400 (Fri, 15 Jul 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.composite
 */

/**
 * Need EZPDO runtime API
 */
include_once(dirname(__FILE__) . '/../../ezpdo_runtime.php');

/**
 * This script prints out all graphic objects
 */

// get the persistence manager
$m = epManager::instance();

// get graphics
$graphics = $m->get('Graphic');

// print all graphics and their one-level down children
if ($graphics) {
    
    foreach($graphics as $g) {
        
        // output graphic
        echo "---------\n";
        echo $g;
        
        // output its children
        echo "\nchildren: \n";
        if ($g->children) {
            $children = $g->children;
            foreach($children as $child) {
                echo "\n";
                echo $child;
                if ($child->parent) {
                    echo "  [parent myid]: " . $child->parent->myid . "\n";
                }
            }
        } else {
            echo "none\n";
        }
    }

} else {
    echo "No graphic is found.\n";
}

?>
