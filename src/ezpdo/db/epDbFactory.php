<?php

/**
 * $Id: epDbFactory.php 1044 2007-03-08 02:25:07Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author Trevan Richins <developer@ckiweb.com>
 * @author David Moises Paz <davidmpaz@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
namespace ezpdo\db;

use ezpdo\base\epFactory;
use ezpdo\base\epSingleton;
use ezpdo\db\exception\epExceptionDbFactory;

use ezpdo\runtime\epArray;
use ezpdo\runtime\epObject;
use ezpdo\runtime\epManager;

/**
 * Class of database connection factory
 *
 * The factory creates databases with given DSNs and maintains
 * a one(DSN)-to-one(epDbObject isntance) mapping.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbFactory implements epFactory, epSingleton  {

    /**#@+
     * Consts for DB abstraction layer libs
     */
    const DBL_ADODB  = "adodb";
    const DBL_ADODB_PDO = "adodb_pdo";
    const DBL_PEARDB = "peardb";
    const DBL_PDO = "pdo";
    /**#@-*/

    /**#@+
     * Used for return value to avoid reference notice in 5.0.x and up
     * @var bool
     */
    static public $false = false;
    static public $true = true;
    static public $null = null;
    /**#@-*/

    /**
     * The array of DBALs supported
     * @var array
     */
    static public $dbls_supported = array(
        self::DBL_ADODB,
        self::DBL_ADODB_PDO,
        self::DBL_PEARDB,
        self::DBL_PDO,
        );

    /**
     * The current DB abstraction lib in use
     */
    private $dbl = epDbFactory::DBL_ADODB;

    /**
     * db connections created
     * @var array
     */
    private $dbs = array();

    /**
     * Constructor
     */
    private function __construct() {
    }

    /**
     * Get the current DBA (DB abstraction lib)
     * @return string
     */
    function getDbLib() {
        return $this->dbl;
    }

    /**
     * Set the current DBA (DB abstraction lib)
     * @param string self::DBL_ADODB|self::DBL_PEARDB
     * @return void
     */
    function setDbLib($dbl) {

        // lower case dbl name
        $dbl = strtolower($dbl);

        // is dbl supported?
        if (!in_array($dbl, self::$dbls_supported)) {
            throw new epExceptionDbFactory('Db library [' . $dbl . '] unsupported.');
        }

        // set the current dbl
        $this->dbl = $dbl;
    }

    /**
     * Implements factory method {@link epFactory::make()}
     * @param string $dsn
     * @return epDbObject|null
     * @access public
     * @static
     */
    public function &make($dsn) {
        return $this->get($dsn, false); // false: no tracking
    }

    /**
     * Implement factory method {@link epFactory::track()}
     * @param string $dsn
     * @return epDbObject
     * @access public
     */
    public function &track() {
        $args = func_get_args();
        return $this->get($args[0], true); // true: tracking
    }

    /**
     * Either create a class map (if not tracking) or retrieve it from cache
     * @param $dsn
     * @param bool tracking or not
     * @return null|epDbObject
     * @throws epExceptionDbFactory
     */
    private function &get($dsn, $tracking = false) {

        // check if dsn is empty
        if (empty($dsn)) {
            throw new epExceptionDbFactory('DSN is empty');
            return self::$null;
        }

        // check if class map has been created
        if (isset($this->dbs[$dsn])) {
            return $this->dbs[$dsn];
        }

        // check if it's in tracking mode
        if ($tracking) {
            return self::$null;
        }

        // otherwise create
        switch($this->dbl) {

            case self::DBL_ADODB:
                $this->dbs[$dsn] = new epDbObject(new epDbAdodb($dsn));
                break;

            case self::DBL_ADODB_PDO:
                $this->dbs[$dsn] = new epDbObject(new epDbAdodbPdo($dsn));
                break;

            case self::DBL_PEARDB:
                $this->dbs[$dsn] = new epDbObject(new epDbPeardb($dsn));
                break;

            case self::DBL_PDO:
                $this->dbs[$dsn] = new epDbObject(new epDbPdo($dsn));
                break;
        }

        return $this->dbs[$dsn];
    }

    /**
     * Implement factory method {@link epFactory::allMade()}
     * Return all db connections made by factory
     * @return array
     * @access public
     */
    public function allMade() {
        return array_values($this->dbs);
    }

    /**
     * Implement factory method {@link epFactory::removeAll()}
     * Remove all db connections made
     * @return void
     */
    public function removeAll() {

        // close all db connections
        if ($this->dbs) {
            foreach($this->dbs as $db) {
                $db->connection()->close();
            }
        }

        // wipe out all db connections
        $this->dbs = array();
    }

    /**
     * Implements {@link epSingleton} interface
     * @return epDbFactory
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
        if (self::$instance) {
            self::$instance->removeAll();
        }
        self::$instance = null;
    }

    /**
     * epDbFactory instance
     */
    static private $instance;
}
