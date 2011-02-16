<?php

/**
 * $Id: add.php 295 2005-06-30 16:29:13Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 295 $ $Date: 2005-06-30 12:29:13 -0400 (Thu, 30 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.cyclic.one
 */

/**
 * Need EZPDO runtime API
 */
include_once(dirname(__FILE__) . '/../../../ezpdo_runtime.php');

// get the persistence manager
$m = epManager::instance();

// create a
$a = $m->create('A');

// create b
$b = $m->create('B');

// create c
$c = $m->create('C');

// object associations
$a->b = $b;
$b->c = $c;
$c->a = $a;

// flush explicitly
$m->flush();

echo "Objects of class A/B/C are persisted. Use `php print.php` to check.\n";

?>
