<?php

/**
 * $Id: epValidateable.php 606 2005-11-09 12:47:40Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base
 */
namespace ezpdo\base;

/**
 * The validateable interface
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base
 */
interface epValidateable {

    /**
     * Check if object is in a valid state
     * @param bool whether to validate recursively (in case object contains other objects)
     * @param string error message if invalid
     * @return true|array of strings (error msgs)
     */
    public function isValid($recursive);

} // end of interface epValidateable
