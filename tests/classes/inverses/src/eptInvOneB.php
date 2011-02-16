<?php

/**
 * $Id: eptInvOneB.php 382 2005-08-09 12:52:51Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 382 $ $Date: 2005-08-09 08:52:51 -0400 (Tue, 09 Aug 2005) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */

/**
 * Need class eptInvBase
 */
include_once(dirname(__FILE__) . '/eptInvBase.php');

/**
 * Class to test inverses 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 382 $ $Date: 2005-08-09 08:52:51 -0400 (Tue, 09 Aug 2005) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.inverses
 */
class eptInvOneB extends eptInvBase  {
    
    /**
     * @var eptInvOneB
     * @orm has one eptInvOneA inverse(b)
     */
    public $a;
}

?>
