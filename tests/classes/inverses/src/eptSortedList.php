<?php

/**
 * $Id: eptInvBase.php 382 2005-08-09 12:52:51Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005-08-09 08:52:51 -0400 (Tue, 09 Aug 2005) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.inverses
 */

/**
 * Class to test inverses: sorted list
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005-08-09 08:52:51 -0400 (Tue, 09 Aug 2005) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.inverses
 */
class eptSortedList {

    /**
     * @orm has one eptSortedList inverse(successor)
     */
    public $predecessor;
   
    /**
     * @orm has one eptSortedList inverse(predecessor)
     */
    public $successor;
   
    /**
     * @orm char(256)
     */
    public $entry;
}

?>
