<?php

/**
 * $Id: eptBook.php 908 2006-04-06 12:37:03Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 908 $ $Date: 2006-04-06 08:37:03 -0400 (Thu, 06 Apr 2006) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */

/**
 * Need eptBase
 */
include_once(realpath(dirname(__FILE__)).'/eptBase.php');

/**
 * Class of a book
 * 
 * This is a test class for ezpdo
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 908 $ $Date: 2006-04-06 08:37:03 -0400 (Thu, 06 Apr 2006) $
 * @package ezpdo_t
 * @subpackage ezpdo_t.bookstore
 */
class eptBook extends eptBase {
    
    /**
     * Bool title
     * @var string
     * @orm title char(80)
     */
    public $title;
    
    /**
     * Number of pages
     * @var integer
     * @orm integer
     */
    public $pages = -1;

    /**
     * Price of the book (in dollar)
     * @var float
     * @orm decimal(5,2)
     */
    public $price = 0.0;

    /**
     * Is book recommended?
     * @var boolean
     * @orm boolean
     */
    public $recommended;

    /**
     * Long excerpt that needs to use clob
     * @var string
     * @orm text(8192)
     */
    public $excerpt;
    
    /**
     * The cover image
     * @var string
     * @orm blob
     */
    public $coverimg;
    
    /**
     * Date published
     * @var string
     * @orm date
     */
    public $pubdate;
    
    /**
     * Bookstore
     * @var eptBookstore
     * @orm has one eptBookstore
     */
    public $bookstore = false;
    
    /**
     * Book author (fictious: kept for testing "has_one")
     * @var eptAuthor
     * @orm has one eptAuthor
     */
    public $author = false;

    /**
     * Book authors 
     * @var eptAuthor
     * @orm has many eptAuthor
     */
    public $authors = false;

    /**
     * Constructor
     * @param string
     */
    public function __construct($title = '') { 
        parent::__construct();
        $this->title = $title;
    }
    
    /**
     * Counter of events
     * @var array (keyed by event) 
     */
    static public $counts = array();

    // event handler: onPreCreate
    static public function onPreCreate($params = null) {
        self::_inc('onPreCreate', $params);
    }

    // event handler: onLoad
    public function onCreate($params = null) {
        self::_inc('onCreate', $params);
    }

    // event handler: onPreLoad
    static public function onPreLoad($params = null) {
        self::_inc('onPreLoad', $params);
    }

    // event handler: onLoad
    public function onLoad($params = null) {
        self::_inc('onLoad', $params);
    }

    // event handler: onPreInsert
    public function onPreInsert($params = null) {
        self::_inc('onPreInsert', $params);
    }

    // event handler: onInsert
    public function onInsert($params = null) {
        self::_inc('onInsert', $params);
    }

    // event handler: onPreUpdate
    public function onPreUpdate($params = null) {
        self::_inc('onPreUpdate', $params);
    }

    // event handler: onUpdate
    public function onUpdate($params = null) {
        self::_inc('onUpdate', $params);
    }

    // event handler: onPreEvent
    public function onPreEvict($params = null) {
        self::_inc('onPreEvict', $params);
    }

    // event handler: onEvict
    public function onEvict($params = null) {
        self::_inc('onEvict', $params);
    }

    // event handler: onPreDelete
    public function onPreDelete($params = null) {
        self::_inc('onPreDelete', $params);
    }

    // event handler: onDeleteAll
    public function onDelete($params = null) {
        self::_inc('onDelete', $params);
    }

    // event handler: onPreDeleteAll
    static public function onPreDeleteAll($params = null) {
        self::_inc('onPreDeleteAll', $params);
    }

    // event handler: onDelete
    static public function onDeleteAll($params = null) {
        self::_inc('onDeleteAll', $params);
    }

    /**
     * Increment event counts
     * @param string $method (value of __METHOD__ from the caller method)
     * @param mixed $params 
     * @return void
     * @access protected
     */
    static public function _inc($method, $params = null) {
        
        // rip off 'eptListener::' in method name
        $method = str_replace(__CLASS__ . '::', '', $method);

        // check if counter for method (event) exists
        if (!isset(self::$counts[$method])) {
            self::$counts[$method] = 0;
        }

        // increase counter by 1
        self::$counts[$method] ++;

        // return the nubmer of calls to the event handler
        return self::$counts[$method];
    }

}

?>
