<?php

/**
 * $Id: Author.php 273 2005-06-27 02:07:49Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 273 $ $Date: 2005-06-26 22:07:49 -0400 (Sun, 26 Jun 2005) $
 * @package ezpdo_bench
 * @subpackage ezpdo_bench.index
 */

/**
 * The user class
 */
class Base {

    /**
     * Time that object is created
     * @orm time
     */
    public $created;

    /**
     * Time that object is last modified
     * @orm time
     */
    public $modified;
}

/**
 * The user class
 */
class User extends Base {
  
  /**
   * User name unique
   * @orm char(32) index(name)
   */
  public $name;

  /**
   * Groups the user is part of 
   * @orm has many Group inverse(users)
   */
  public $groups;

}

/**
 * The group class
 */
class Group extends Base {

  /**
   * Group name
   * @orm char(32) index(name)
   */
  public $name;

  /**
   * Users in group
   * @orm has many User inverse(groups)
   */
  public $users;

}

/**
 * The Thingy class
 */
class Thingy extends Base {

  /**
   * Your thingy's name
   * @orm char(32) index(name)
   */
  public $name;

   /**
    * Groups allowed to view
    * @orm has many Group
    */
  public $groups;

}


?>
