<?php

/**
 * $Id: classes.php 295 2005-06-30 16:29:13Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 295 $ $Date: 2005-06-30 12:29:13 -0400 (Thu, 30 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.cyclic.many
 */

/**
 * Base Class 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 295 $ $Date: 2005-06-30 12:29:13 -0400 (Thu, 30 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.cyclic.many
 */
class Base {
    
    /**
     * id
     * @var string
     * @orm char(64)
     */
    public $id;

    /**
     * Constructor
     */
    public function __construct($class = false) { 
        if (!$class) {
            $class = __CLASS__;
        }
        $this->id = uniqid($class . '-'); 
    }
}

/**
 * Class A (has B's)
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 295 $ $Date: 2005-06-30 12:29:13 -0400 (Thu, 30 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.cyclic.many
 */
class A extends Base {
    
    /**
     * @var B
     * @orm has many B
     */
    public $bs;

    /**
     * Constructor
     */
    public function __construct() { 
        parent::__construct(__CLASS__);
    }
}

/**
 * Class B (has C's)
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 295 $ $Date: 2005-06-30 12:29:13 -0400 (Thu, 30 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.cyclic.many
 */
class B extends Base {
    
    /**
     * @var C
     * @orm has many C
     */
    public $cs;

    /**
     * Constructor
     */
    public function __construct() { 
        parent::__construct(__CLASS__);
    }
}

/**
 * Class C (has A's)
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 295 $ $Date: 2005-06-30 12:29:13 -0400 (Thu, 30 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.cyclic.many
 */
class C extends Base {
    
    /**
     * @var A
     * @orm has many A
     */
    public $as;

    /**
     * Constructor
     */
    public function __construct() { 
        parent::__construct(__CLASS__);
    }
}

?>
