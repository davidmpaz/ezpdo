<?php

/**
 * $Id: epFactory.php 606 2005-11-09 12:47:40Z nauhygon $
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
 * Interface of factory
 *
 * Admittedly this is not exactly the Factory Method pattern outlined
 * in the GOF book. It is rather a mix of Factory Method and Registry
 * patterns since it keeps track of (references to) objects it has
 * manufactured.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base
 */
interface epFactory {

    /**
     * The major task of a factory is to make products (objects)
     * @param mixed ... object creation parameters
     * @return object
     */
    public function &make($class_name);

    /**
     * A factory can also track down a product it has produced by a
     * certain criteria
     * @param  mixed ... search criteria
     * @return object
     */
    public function &track();

    /**
     * Get all products (references) made by factory so far
     * @return array
     */
    public function allMade();

    /**
     * Remove all product references made by the factory
     * @return void
     */
    public function removeAll();
}
