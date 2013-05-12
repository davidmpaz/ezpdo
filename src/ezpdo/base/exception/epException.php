<?php

/**
 * $Id: epException.php 606 2005-11-09 12:47:40Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base.exception
 */
namespace ezpdo\base\exception;

/**
 * Base class of ezpdo exception
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 606 $ $Date: 2005-11-09 07:47:40 -0500 (Wed, 09 Nov 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base.exception
 */
class epException extends \Exception {

    /**
     * Constructor
     * @param string $msg
     * @param integer code
     */
    public function __construct($msg, $code = 0) {
        parent::__construct($msg, $code);
    }
}
