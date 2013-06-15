<?php

/**
 * $Id: epDbPortFactory.php 992 2006-06-01 11:04:15Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Moises Paz <davidmpaz@gmail.com>
 * @version $Revision: 992 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
namespace ezpdo\db;

use ezpdo\base\epFactory;
use ezpdo\base\epSingleton;

use ezpdo\orm\epFieldMap;
use ezpdo\orm\epClassMap;

use ezpdo\db\exception\epExceptionDbFactory;
use ezpdo\db\port\epDbPortSqlite as epDbPortSqlite;
use ezpdo\db\port\epDbPortPostgres as epDbPortPostgres;

/**
 * Class of database portability factory
 *
 * The factory creates one portability object for each database type
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 992 $ $Date: 2006-06-01 07:04:15 -0400 (Thu, 01 Jun 2006) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbPortFactory implements epFactory, epSingleton  {

    /**#@+
     * Used for return value to avoid reference notice in 5.0.x and up
     * @var bool
     */
    static public $false = false;
    static public $true = true;
    static public $null = null;
    /**#@-*/

    /**
     * db portabilities created
     * @var array
     */
    private $dbps = array();

    /**
     * Constructor
     */
    private function __construct() {
    }

    /**
     * Implements factory method {@link epFactory::make()}
     * @param string $dbtype
     * @return epDbPortable|null
     * @access public
     * @static
     */
    public function &make($dbtype) {
        return $this->get($dbtype, false); // false: no tracking
    }

    /**
     * Implement factory method {@link epFactory::track()}
     * @param string $dbtype
     * @return epDbPortable
     * @access public
     */
    public function &track() {
        $args = func_get_args();
        return $this->get($args[0], true); // true: tracking
    }

    /**
     * Either create db portability object or find one
     * @param $dbtype
     * @param bool tracking or not
     * @return epDbPortable
     * @throws epExceptionDbPortFactory
     */
    private function & get($dbtype, $tracking = false) {

        // check if dsn is empty
        if (empty($dbtype)) {
            throw new epExceptionDbPortFactory('Database type is empty');
            return self::$null;
        }

        // check if class map has been created
        if (isset($this->dbps[$dbtype])) {
            return $this->dbps[$dbtype];
        }

        // check if it's in tracking mode
        if ($tracking) {
            return self::$null;
        }

        // instantiate the right db port object
        $port_class = 'epDbPort' . $dbtype;
        if (!file_exists($port_class_file = EP_SRC_DB . '/port/' . $port_class . '.php')) {
            // in case we don't have a special portability class, use the default
            $dbp = new epDbPortable();
        } else {
            $port_class = "ezpdo\\db\\port\\$port_class";
            $dbp = new $port_class;
        }

        // check if portability object is created successfully
        if (!$dbp) {
            throw new epExceptionDbPortFactory('Cannot instantiate portability class for [' . $dbtype . ']');
            return self::$null;
        }

        // cache it
        $this->dbps[$dbtype] = & $dbp;

        return $this->dbps[$dbtype];
    }

    /**
     * Implement factory method {@link epFactory::allMade()}
     * Return all db connections made by factory
     * @return array
     * @access public
     */
    public function allMade() {
        return array_values($this->dbps);
    }

    /**
     * Implement factory method {@link epFactory::removeAll()}
     * @return void
     */
    public function removeAll() {
        $this->dbps = array();
    }

    /**
     * Implements {@link cpSingleton} interface
     * @return epDbPortFactory
     * @access public
     */
    static public function &instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Implement {@link epSingleton} interface
     * Forcefully destroy old instance (only used for tests).
     * After reset(), {@link instance()} returns a new instance.
     */
    static public function destroy() {
        self::$instance = null;
    }

    /**
     * epDbPortFactory instance
     */
    static private $instance;
}
