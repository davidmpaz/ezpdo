<?php

/**
 * $Id: epSingleton.php 606 2005-11-09 12:47:40Z nauhygon $
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
 * Interface of singleton
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base
 */
interface epSingleton  {

    /**
     * Forcefully delete old instance (only used for tests).
     * After reset(), {@link instance()} returns a new instance.
     */
    static public function destroy();

    /**
     * Return the single instance of the class
     * @return object
     */
    static public function &instance();
}
