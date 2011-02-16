<?php

/**
 * $Id: classes.php 294 2005-06-30 12:13:25Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 294 $ $Date: 2005-06-30 08:13:25 -0400 (Thu, 30 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.sql_kwords
 */

/**
 * Class of a group
 * 
 * This class is intended to test SQL keywords being used as
 * class and var names
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 294 $ $Date: 2005-06-30 08:13:25 -0400 (Thu, 30 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.sql_kwords
 */
class Group {
    
    /**
     * from (test SQL keyword)
     * @var string
     * @orm char(64)
     */
    public $from;

    /**
     * where (test SQL keyword)
     * @var string
     * @orm char(64)
     */
    public $where;

    /**
     * like (test SQL keyword)
     * @var string
     * @orm char(64)
     */
    public $like;

    /**
     * order (test SQL keyword)
     * @var Order
     * @orm has many Order
     */
    public $order = false;

    /**
     * Constructor
     * @param string $name author name
     */
    public function __construct() { 
        $this->from = uniqid('from-'); 
        $this->where = uniqid('where-'); 
        $this->like = uniqid('like-'); 
    } 
}

/**
 * Class of an order
 * 
 * This class is intended to test SQL keywords being used as
 * class and var names
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 294 $ $Date: 2005-06-30 08:13:25 -0400 (Thu, 30 Jun 2005) $
 * @package ezpdo_ex
 * @subpackage ezpdo_ex.sql_kwords
 */
class Order {
    
    /**
     * update (test SQL keyword)
     * @var string
     * @orm char(64)
     */
    public $update;

    /**
     * insert (test SQL keyword)
     * @var string
     * @orm char(64)
     */
    public $insert;

    /**
     * delete (test SQL keyword)
     * @var string
     * @orm char(64)
     */
    public $delete;

    /**
     * group (test SQL keyword)
     * @var Group
     * @orm has many Group
     */
    public $group = false;

    /**
     * Constructor
     * @param string $name author name
     */
    public function __construct() { 
        $this->update = uniqid('update-'); 
        $this->insert = uniqid('insert-'); 
        $this->delete = uniqid('delete-'); 
    } 
}

?>
