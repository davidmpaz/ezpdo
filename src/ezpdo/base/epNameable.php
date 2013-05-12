<?php

/**
 * $Id: epNameable.php 606 2005-11-09 12:47:40Z nauhygon $
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
 * The nameable interface
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base
 */
interface epNameable {

    /**
     * Get name
     * @return string
     */
    public function getName();

    /**
     * Set name
     * @param string
     */
    public function setName($name);

} // end of interface epNameable
