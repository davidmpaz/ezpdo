<?php

/**
 * $Id: epManagerBase.php 1044 2007-03-08 02:25:07Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Moises Paz <davidmpaz@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
namespace ezpdo\runtime;

use ezpdo\base\epUtils;
use ezpdo\base\epSingleton;
use ezpdo\base\epConfigurableWithLog;
use ezpdo\base\exception\epExceptionConfigurableWithLog;

use ezpdo\db\epDb;
use ezpdo\db\epDbFactory;
use ezpdo\db\exception\epExceptionDb;
use ezpdo\db\exception\epExceptionDbAdodb;
use ezpdo\db\exception\epExceptionDbAdodbPdo;
use ezpdo\db\exception\epExceptionDbPeardb;

use ezpdo\orm\epClassMap;
use ezpdo\orm\epClassMapFactory;
use ezpdo\compiler\epClassCompiler;

use ezpdo\runtime\exception\epExceptionManagerBase;

/**#@+
 * Defines where to get objects
 */
define('EP_GET_FROM_DB',    1);
define('EP_GET_FROM_CACHE', 2);
define('EP_GET_FROM_BOTH',  3);
/**#@-*/

/**
 * The base class of ezpdo persistence manager
 *
 * The persistence manager provides an easy interface to create,
 * persist, cache, and retrieve objects, but does not deal with
 * object relationship mapping, which we leave to subclass
 * {@link epManager}. Doing so gives us a clean seperation of
 * concerns.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epManagerBase extends epConfigurableWithLog {

    /**
     * Array of allowed system events
     * @var array (of string)
     */
    static public $events = array(
        'onChange',
        'onPostChange',
        'onPreChange',
        'onCreate',
        'onPostCreate',
        'onPreCreate',
        'onDelete',
        'onPostDelete',
        'onPreDelete',
        'onPreLoad',
        'onLoad',
        'onPostLoad',
        'onPreRefresh',
        'onRefresh',
        'onPostReferesh',
        'onPreUpdate',
        'onUpdate',
        'onPostUpdate',
        'onPreInsert',
        'onInsert',
        'onPostInsert',
        'onPreDeleteAll',
        'onDeleteAll',
        'onPostDeleteAll',
        'onPreEvict',
        'onEvict',
        'onPostEvict',
        'onPreEvictAll',
        'onEvictAll',
        'onPostEvictAll',
        );

    /**
     * Array to cache the database connections for classes
     * @var array (of epDbObject keyed by class names)
     */
    protected $dbs = array();

    /**
     * Cached class compiler
     * @var epClassCompiler
     */
    protected $cc;

    /**
     * Cached EZOQL parser
     * @var epQuery
     */
    protected $q;

    /**
     * Cached class map factory
     * @var epClassMapFactory
     */
    protected $cmf;

    /**
     * Cached database factory
     * epDbFactory
     */
    protected $dbf;

    /**
     * Array to cache commited object instances
     * <pre>
     * array(
     *   'class_a' => array(
     *     'pdo_id_1' => class_a_instance_1,
     *     'pdo_id_2' => class_a_instance_2
     *     )
     *   ),
     *   'class_b' => array(
     *     'pdo_id_3' => class_a_instance_3,
     *     'pdo_id_4' => class_a_instance_4,
     *     'pdo_id_5' => class_a_instance_5
     *   )
     * )
     * </pre>
     * @var array
     */
    protected $objects_c = array();

    /**
     * Array to keep uncommited instances (newly created)
     * @var array (keyed by uid)
     */
    protected $objects_uc = array();

    /**
     * Array to keep global event listeners
     * @var array (of objects (listeners))
     */
    protected $listeners_g = array();

    /**
     * Array to keep local listeners
     * @var array (keyed by persistent class names)
     */
    protected $listeners_l = array();

    /**
     * The curernt transition
     * @var false|epTransition
     */
    protected $t = false;

    /**
     * The schema updater
     * @var false|epDbUpdate
     */
    protected $su = false;

    /**
     * Constructor
     * @param epConfig|array
     * @access public
     */
    public function __construct($config = null) {
        parent::__construct($config);
    }

    /**
     * Implement abstract method {@link epConfigurable::defConfig()}
     * @access public
     */
    public function defConfig() {
        return array(
            "compiled_dir" => "compiled", // default to compiled under current dir
            "compiled_file" => 'compiled.ezpdo', // the default class map file
            "backup_compiled" => true, // whether to backup old compiled file
            "default_dsn" => 'mysql://ezpdo:secret@localhost/ezpdo', // the default dns
            "check_table_exists" => true, // whether always check if table exists before db operation
            "table_prefix" => '', // table prefix (default to none)
            "relation_table" => "_ez_relation_", // the table name for object relations
            "split_relation_table" => true, // whether to split relation table
            "db_lib" => "adodb", // the DBAL (database abstraction) library to use
            "auto_flush" => false, // enable or disable auto flush at the end of script
            "flush_before_find" => true, // enable or disable auto flush before find()
            "auto_compile" => true, // enable or disable auto compile
            "autoload" => true, // enable or disable class autoloading
            "log_queries" => false, // enable logging queries (for debug only)
            "dispatch_events" => true, // whether to dispatch events (true by default)
            "default_oid_column" => 'eoid', // oid column name is default to 'eoid' now
            );
    }

    /**
     * Returns the current version of EZPDO
     * @return string
     */
    public function version() {
        return '1.1.6';
    }

    /**
     * Initialization
     * @param bool $force whether to force initialization
     * @return bool
     * @throws epExceptionManagerBase
     */
    protected function initialize($force = false) {

        // done if not forced, and class map and db factories are set
        if (!$force && $this->cmf && $this->dbf) {
            return true;
        }

        // load compiled info into manager
        if (!$this->_loadCompiled()) {
            return false;
        }

        // get the db factory
        include_once(EP_SRC_DB.'/epDbObject.php');
        if (!($this->dbf = & epDbFactory::instance())) {
            throw new epExceptionManagerBase('Cannot get db factory instance');
            return false;
        }

        // remove all connections
        $this->dbf->removeAll();

        // set the db lib to use
        $this->dbf->setDbLib($this->getConfigOption('db_lib'));

        // set up class autoloading if 'autoload' is set to true
        if ($this->getConfigOption('autoload')) {
            if (function_exists('epSetupAutoload')) {
                epSetupAutoload();
            }
        }

        // register auto flush as the shutdown function
        if ($this->getConfigOption('auto_flush')) {
            register_shutdown_function(array($this, 'flushAll'));
        }

        return true;
    }

    /**
     * Autoload copmiled class files
     *
     * This method should be called by the magic method __autoload()
     *
     * @param string $class the class name
     * @return bool
     */
    public function autoload($class) {

        // check if class has been compiled
        if (!($cm = & $this->_getMap($class))) {
            return false;
        }

        // include the class file
        include_once($cm->getClassFile());
        return true;
    }

    /**
     * Returns the total number of object in one class
     * @param string $class class name
     * @return false|integer (false if class does not exist)
     */
    public function count($class) {

        // check if class name is non-empty
        if (!$class) {
            return false;
        }

        // get class map for class
        if (!($cm = & $this->_getMap($class))) {
            return false;
        }

        // get db for the class
        if (!($db = & $this->_getDb($cm))) {
            return false;
        }

        // return the total number of object in class
        return $db->count($cm);
    }

    /**
     * Get all stored objects of a class
     * @param string class name
     * @param string option EP_GET_FROM_CACHE or EP_GET_FROM_DB or EP_GET_FROM_BOTH
     * @param array oids the specific oids to retrieve from the database
     * @return false|null|array (false if failure; null if none found)
     */
    public function getAll($class, $option = EP_GET_FROM_BOTH, $oids = null) {

        // get class map for class
        if (!($cm = & $this->_getMap($class))) {
            return false;
        }

        // get instances from cache only
        if ($option == EP_GET_FROM_CACHE) {

            // return all cached for the class if any
            if (isset($this->objects_c[$class])) {

                // return all if no oids specified
                if (!$oids) {
                    return array_values($this->objects_c[$class]);
                }

                // otherwise only objects with oids specified
                else {
                    $ros = array();
                    foreach($this->objects_c[$class] as $oid => &$o) {
                        if (in_array($oid, $oids)) {
                            $ros[] = $o;
                        }
                    }
                    return $ros;
                }
            }

            return null;
        }

        // event: onPreLoad
        $this->_dispatchEvent(array('onPreLoad'), $class, array('operation' => 'getAll'));

        // get db for the class
        if (!($db = & $this->_getDb($cm))) {
            return false;
        }

        // get instances from db only
        $ex = array();
        if ($option != EP_GET_FROM_DB) {
            if (isset($this->objects_c[$class])) {
                $ex = array_keys($this->objects_c[$class]);
            }
        }

        // fetch and cache them all
        if ($os = $db->fetch($cm, null, $ex, $oids)) {
            foreach($os as &$o) {
                $this->cache($o, $option == EP_GET_FROM_DB);
                // event: onLoad and onPostLoad
                $this->_dispatchEvent(array('onLoad', 'onPostLoad'), $o);
            }
        }

        // done if get from DB only
        if ($option == EP_GET_FROM_DB) {
            return $os;
        }

        /**
         * if it reaches this point, we are getting instances
         * from both db and cache, so return all that have been
         * cached.
         */
        if (isset($this->objects_c[$class])) {

            // return all if no oids specified
            if (!$oids) {
                return array_values($this->objects_c[$class]);
            }

            // otherwise only objects with oids specified
            else {
                $ros = array();
                foreach($this->objects_c[$class] as $oid => &$o) {
                    if (in_array($oid, $oids)) {
                        $ros[] = $o;
                    }
                }
                return $ros;
            }
        }

        // return null if nothing found
        return null;
    }

    /**
     * Get an instance for a class by object id
     *
     * Three options in getting an instance by object id:
     *
     * - EP_GET_FROM_CACHE: it only gets the instance from the cache.
     * false is returned if instance not found in cache
     *
     * - EP_GET_FROM_DB: it gets the instance from the datastore. if
     * an instance of the same id is in cache, it's refreshed.
     *
     * - EP_GET_FROM_BOTH: it tries to get the instance from the
     * cache first and, if not found, then the datastore.
     *
     * @param string class name
     * @param false|integer object id
     * @param string option EP_GET_FROM_CACHE or EP_GET_FROM_DB or EP_GET_FROM_BOTH
     * @return false|object|array
     * @throws epExceptionManagerBase
     * @access public
     */
    public function &get($class, $oid = false, $option = EP_GET_FROM_BOTH) {

        // trim class name
        $class = trim($class);

        // if oid is not unspecified. it must be false.
        if (!$oid && ($oid !== false)) {
			throw new epExceptionManagerBase(
				"Only false or valid object id (positive integer) is allowed for argument \$oid"
				);
        }

        // if oid is not unspecified
		if (!$oid) {
			// get all object of the class
			$os = $this->getAll($class, $option);
			return $os;
		}

        // event: onPreLoad
        $this->_dispatchEvent(array('onPreLoad'), $class, array('operation' => 'getOne', 'params' => $oid));

        // check the cache
        if (isset($this->objects_c[$class][$oid])) {

            // refresh object if get from db
            $o = & $this->objects_c[$class][$oid];
            if ($option == EP_GET_FROM_DB) {

                // false: no event dispatching
                if (!$this->_refresh($o, false)) {
                    return self::$false;
                }

                // event: onLoad, onPostLoad
                $this->_dispatchEvent(array('onLoad', 'onPostLoad'), $class, $oid);
            }

            return $o;
        }

        // are we getting from cache only?
        if ($option == EP_GET_FROM_CACHE) {
            return self::$false;
        }

        // create the object (false: no caching, false: no event dispatching)
        if (!($o = $this->_create($class, false, false))) {
            return self::$false;
        }

        // set object id
        $o->epSetObjectId($oid);

        // refresh object with oid (false: no event dispatching)
        if (!$this->_refresh($o, false)) {
            return self::$false;
        }

        // event: onLoad and onPostLoad
        $this->_dispatchEvent(array('onLoad', 'onPostLoad'), $o);

        return $o;
    }

    /**
     * Conduct object query with EZOQL (see more in {@link epQuery})
     * @param string $oql
     * @param  mixed ... parameters to replace ? in the query string
     * @return false|integer|float|array
     * @throws epExceptionQuery, epExceptionManagerBase
     */
    public function &query($oql) {

        if (!$oql) {
            return self::$false;
        }

        // get arguments
        $args = func_get_args();
        // remove the first argument (the query string)
        array_shift($args);

        // get the new query object
        if (!$this->q) {
            include_once(EP_SRC_RUNTIME . '/epQuery.php');
            $this->q = new epQuery;
        }

        // make sure manager is initialized
        $this->initialize();

        // translate the oql stmts into a sql stmt
        if (!($sql_stmts = $this->q->parse($oql, $args))) {
            return self::$false;
        }

        // the class maps involved in the query
        if (!($cms = $this->q->getClassMaps())) {
            return self::$false;
        }

        // use the first class map
        $db = & $this->_getDb($cms[0]);

        // before query, commit objects of the involved classes if
        // option 'flush_before_find' is set. (see explanation in
        // class epQuery)
        if ($this->getConfigOption('flush_before_find')) {
            foreach($cms as $cm) {
                $class = $cm->getName();
                $this->flush($class, true); // true: flush all
            }
        }

        // get the root class
        $class = $cms[0]->getName();

        // event: onPreLoad
        $this->_dispatchEvent(array('onPreLoad'), $class, array('operation' => 'query', 'params' => $oql));

        // get query parts for later uses
        $limit = $this->q->getLimit();
        $orderby = $this->q->getOrderBy();

        // aggregation function in query?
        if ($aggr_func = $this->q->getAggregateFunction()) {
            // delegate aggreation function query to database layer
            return $db->query($cms, $sql_stmts, $orderby, $limit, $aggr_func);
        }

        // delegate query to database layer (epDbObject)
        if ($os = $db->query($cms, $sql_stmts, $orderby, $limit)) {
            // cache them all
            foreach($os as &$o) {
                $this->cache($o);
                // event: onLoad and onPostLoad
                $this->_dispatchEvent(array('onLoad', 'onPostLoad'), $o);
            }
        }

        return $os;
    }

    /**
     * Find objects by non-null values specified in an example instance
     * Or if the first argument is a string, do an EZOQL query.
     *
     * Sometimes finding object from cache can be very expensive because of
     * the matching operation in epObject::epMatches(). So it may be more
     * desirable to get object from DB only and not to cache it. This is why
     * the argument $cache is in place.
     *
     * @param epObject|string $o The example object or the EZOQL query string
     * @param string $option Whether to get it from cache, data store, or both
     * @param bool $cache Whether to cache result (default to true)
     * @param bool $objs Whether to convert to objects or leave as uoids (default to true)
     * @return null|object|array
     */
    public function &find($o, $option = EP_GET_FROM_BOTH, $cache = true, $objs = true) {

        // check if the first parameter is string
        if (is_string($o)) {
			// if so, call query for EZOQL query
			$args = func_get_args();
			$result = call_user_func_array(array(&$this, 'query'), $args);
			return $result;
		}

        // get class name
        if (!($class = $this->_getClass($o))) {
            return self::$false;
        }

        // get class map for class
        if (!($cm = & $this->_getMap($class))) {
            return self::$false;
        }

        /**
         * If an object is used as an example object to find other objects,
         * it's instantly marked as a uncommitable object. You need to
         * explicitly reset the flag, i.e. $o->epSetCommittable(true),
         * for it to be committable.
         *
         * Especially when you choose to auto-flush all uncommited objects
         * including the example objects before quiting the script, make sure
         * you have flagged all of them committable.
         */
        $o->epSetCommittable(false, true);

        // do we need to flush before find() (if not find from cache)?
        if ($option != EP_GET_FROM_CACHE) {
            if ($this->getConfigOption('flush_before_find')) {
                $this->flush($class, true); // true: flush all
            }
        }

        // if EP_GET_FROM_DB, no search in cache
        $os_cache = array();
        if ($option != EP_GET_FROM_DB) {

            // find in cache first
            $os_cache = $this->_findInCache($o);

            // get instances from cache only
            if ($option == EP_GET_FROM_CACHE) {
                // done and return
                return $os_cache;
            }
        }

        // get oids to be excluded
        $ex = array();
        if ($option != EP_GET_FROM_DB) {
            if (isset($this->objects_c[$class])) {
                $ex = array_keys($this->objects_c[$class]);
            }
        }

        // get db for the class
        if (!($db = & $this->_getDb($cm))) {
            return self::$false;
        }

        // fetch by values set in example object $o
        if ($os = $db->fetch($cm, $o, $ex, null, $objs)) {
            if ($cache) {
                foreach($os as &$o) {
                    $this->cache($o, $option == EP_GET_FROM_DB); // force replace if EP_GET_FROM_DB
                    // event: onLoad and onPostLoad
                    $this->_dispatchEvent(array('onLoad', 'onPostLoad'), $o);
                }
            }
        }

        // done if get from DB only
        if ($option == EP_GET_FROM_DB) {
            return $os;
        }

        // merge objects found in db and cache
        $os = array_merge($os ? $os : array(), $os_cache ? $os_cache : array());

        return $os;
    }

    /**
     * Create a new instance of a class
     * @param string|object $class_or_obj class name or an object
     * @return null|epObject
     * @access public
     */
    public function &create($class_or_obj) {

        // get the argument for the object constructor
        $args = func_get_args();

        // remove the first argument
        array_shift($args);

        // call the lower _create()
        $o = & $this->_create($class_or_obj, true, true, $args);

        // add this newly create object into transaction
        $this->addObject_t($o);

        return $o;
    }

    /**
     * Low level create (called by {@link create()})
     * Create a new instance of a class
     *
     * Although this method is made public for {@link epDbObject} to be able
     * to call in assembling objects from database rows, it is <b>not</b>
     * recommended to be used. Please use {@epManager::create()} instead.
     *
     * @param string|object $class_or_obj class name or an object
     * @param boolean $caching whether to cache the newly created object
     * @param boolean $dispatch_event whether to dispatch event
     * @return null|epObject
     * @access public
     * @throws epExceptionManagerBase
     */
    public function &_create($class_or_obj, $caching = true, $dispatch_event = true, $args = array()) {

        // check if the argument is a string (class name)
        $class = '';
        $o = false;
        if (is_string($class_or_obj)) {
            $class = $class_or_obj;
        } else if (is_object($class_or_obj)) {
            $o = & $class_or_obj;
            $class = get_class($o);;
        } else {
            throw new epExceptionManagerBase('Argument unrecognized. It should be either object or class name (string)');
            return self::$null;
        }

        // check if class has been compiled
        if (!($cm = & $this->_getMap($class))) {

            // if not, check if auto_compile is enabled
            if (!$this->getConfigOption('auto_compile')) {

                // if not (auto_compile off), throw
                throw new epExceptionManagerBase(
                    'Class ' . $class . ' has not been compiled. '
                    . 'It cannot be made persistable. Either enable '
                    . 'auto_compile or compile manually');
                return self::$null;
            }

            // otherwise (auto compile enabled)
            if (!($cm = & $this->_compileClass($class))) {
                // return false if auto-compile fails
                return self::$null;
            }
        }

        // event: onPreCreate
        if ($dispatch_event) {
            $this->_dispatchEvent(array('onPreCreate'), $class, $args);
        }

        // in case the argument is not object, create it
        if (!$o) {
            // make a new instance
            if (!($o = & epUtils::epNewObject($class, $args))) {
                // throw if failed to create an object
                throw new epExceptionManagerBase('Cannot create object for class [' . $class . ']');
                return self::$null;
            }
        }

        // wrap object if it doesn't have epObject ifc
        if (!($o instanceof epObject)) {
            if (!($o = new epObject($o, $cm))) {
                throw new epExceptionManagerBase('Cannot wrap object of [' . $class . ']');
                return self::$null;
            }
        }

        // cache it in the array of uncommited objects (only when auto_flush is on)
        if ($caching /** && $this->getConfigOption("auto_flush")**/ ) {
            $this->objects_uc[$o->epGetUId()] = & $o;
        }

        // event: onCreate, onPostCreate
        if ($dispatch_event) {
            $this->_dispatchEvent(array('onCreate', 'onPostCreate'), $o);
        }

        return $o;
    }

    /**
     * Converts an array to an epObject
     * @param string $class
     * @param array $array
     * @param boolean $dispatch_event whether to dispatch event
     * @param boolean $clean whether to clean default values
     * @param boolean $commitable whether object is committable
     * @return null|epObject (error message if string)
     * @throws epExceptionManagerBase
     */
    public function &createFromArray($class, $array, $dispatch_event = true, $clean = false) {

        // ask epManager to create an object (true: cache it)
        if (!($o = & $this->_create($class, true, $dispatch_event))) {
            return self::$null;
        }

        // do we need a clean object?
        if ($clean) {
            // reset every var to null
            foreach($o as $var => $val) {
                // skip oid
                if ($var != 'oid') {
                    $o[$var] = null;
                }
            }
        }

        // create object from array
        return $this->_fromArray($o, $array, $dispatch_event, $clean);
    }

    /**
     * Use an array to update an epObject
     *
     * @param epObject $o
     * @param array $array
     * @param boolean $dispatch_event whether to dispatch events
     * @return null|epObject (error message if string)
     * @throws epExceptionManagerBase
     */
    public function &updateFromArray(epObject &$o, $array, $dispatch_event = true) {
        return $this->_fromArray($o, $array, $dispatch_event);
    }

    /**
     * Combines the same functionality that {@link createFromArray()} and
     * {@link updateFromArray} have.
     *
     * @param epObject $o
     * @param array $array
     * @param boolean $dispatch_event whether to dispatch events
     * @param boolean $clean
     * @return null|epObject|string (error message if string)
     * @throws epExceptionManagerBase
     */
    protected function &_fromArray(&$o, $array, $dispatch_event, $clean = true) {

        // get class from object
        $class = $o->epGetClass();

        // copy array values to object
        foreach($array as $var => $val) {

            // skip oid
            if ($var == 'oid') {
                $o->epSetObjectId($val);
                continue;
            }

            // check if var exists in class
            if (!$o->epIsVar($var)) {
                // unrecognized var
                throw new epExceptionManagerBase('Variable ['.$var.'] does not exist in class ['.$class.']');
                return self::$null;
            }

            //
            // a primitive var?
            //
            if ($o->epIsPrimitive($var)) {

                // primitive must be a scalar
                if (!is_scalar($val)) {
                    throw new epExceptionManagerBase('Value is not scalar for primitive var ['.$class.'::'.$var.']');
                    return self::$null;
                }

                // assign value to var in object
                $o[$var] = $val;

                continue;
            }

            //
            // a relationship var
            //

            // get class of var
            $class = $o->epGetClassOfVar($var);

            //
            // a single-valued var
            //
            if ($o->epIsSingle($var)) {

                // recursion if value is an array
                if (is_array($val)) {
                    // recursion:  object to var in object
                    $o->epSet($var, $this->createFromArray($class, $val, $dispatch_event, $clean));
                    continue;
                }

                // if value is int, it might be an oid of an existing object
                if (is_integer($val) || ctype_digit($val)) {
                    // get the corresponding object
                    if ($sub =& $this->get($class, $val)) {
                        // yep, it exists
                        $o->epSet($var, $sub);
                        continue;
                    }
                }

                // if it is an object, it might be an existing object
                if ($val instanceof epObject) {
                    $o->epSet($var, $val);
                    continue;
                }

                throw new epExceptionManagerBase('Value is not array or object id for relationship var ['.$class.'::'.$var.']');
                continue;
            }

            //
            // a many-valued var
            //

            // value should be either an integer (oid) or an array.
            $vals = is_array($val) ? $val : array($val);

            // check if value is multiple. arrayize it if not.
            $vals = isset($val[0]) ? $val : array($val);

            // create objects for many-valued field
            $os = array();
            foreach($vals as $val) {

                // recursion:  object to var in object
                if (is_array($val)) {
                    $os[] = & $this->createFromArray($class, $val, $dispatch_event, $clean);
                    continue;
                }

                // if value is int, it might be an oid of an existing object
                if (is_integer($val) || ctype_digit($val)) {
                    if ($sub = & $this->get($class, $val)) {
                        // yep, it exists
                        $os[] = & $sub;
                        continue;
                    }
                }

                // if it is an object, it might be an existing object
                if ($val instanceof epObject) {
                    $os[] = & $val;
                    continue;
                }

                throw new epExceptionManagerBase('Value is not array or object id for relationship var ['.$class.'::'.$var.']');
                continue;
            }

            $o->epSet($var, $os);
        }

        return $o;
    }

    /**
     * Explicitly cache an object instance
     * @param epObject $o object
     * @param bool $force force cached to be replaced if not the same instance
     * @return bool
     * @access protected
     * @static
     */
    public function cache(&$o, $force_replace = false) {

        // cannot cache object without id
        if (!($oid = $o->epGetObjectId())) {
            return false;
        }

        // get the class name
        if (!($class = $this->_getClass($o))) {
            return false;
        }

        // do nothing if not fored to replace and obj is cached
        if (!$force_replace && isset($this->objects_c[$class][$oid])) {
            return true;
        }

        // insert instance into cache (important to have & so
        // we can delete object explicitly)
        $this->objects_c[$class][$oid] = & $o;

        return true;
    }

    /**
     * Refresh an instance to make consistent with data in datastore
     * @param object
     * @return bool (false if instance not refreshable)
     * @access public
     */
    public function refresh(&$o) {
        return $this->_refresh($o);
    }

    /**
     * Low level refresh (called by {@link refresh()})
     * @param object
     * @param boolean $dispatch_event whether to dispatch events
     * @return bool (false if instance not refreshable)
     * @access protected
     */
    protected function _refresh(&$o, $dispatch_event = true) {

        // only refresh object with valid oid
        if (!($oid = $o->epGetObjectId())) {
            return false;
        }

        // get class name
        if (!($class = $this->_getClass($o))) {
            return false;
        }

        // get class map for class
        if (!($cm = & $this->_getMap($class))) {
            return false;
        }

        // get db for the class
        if (!($db = & $this->_getDb($cm))) {
            return false;
        }

        // event: onPreRefresh
        if ($dispatch_event) {
            $this->_dispatchEvent(array('onPreRefresh'), $o);
        }

        // fetch by id
        $os = & $db->fetch($cm, $o);
        if (count($os) != 1) {
            return false;
        }

        // replace object with the refreshed one
        $o->epCopyVars($os[0]);
        $o->epSetDirty(false);

        // remove it from uncommited cache
        unset($this->objects_uc[$o->epGetUid()]);

        // cache it
        $this->cache($o);

        // event: onRefresh and onPostRefresh
        if ($dispatch_event) {
            $this->_dispatchEvent(array('onRefresh', 'onPostRefresh'), $o);
        }

        return true;
    }

    /**
     * Called by {@link commit()} and {@link flush()}
     *
     * A commit can be partial, called a 'simple' commit which only
     * commits the primtive vars and leaves the relationship vars
     * uncommitted.
     *
     * @param epObject &$o The object to be committed
     * @param bool $simple Whether this is a simple commit
     * @return boolean
     * @access public
     */
    protected function _commit_o(&$o, $simple = false) {

        // get class map for class
        if (!($cm = & $o->epGetClassMap())) {
            return false;
        }

        // get class name
        if (!($class = $cm->getName())) {
            return false;
        }

        // get db for the class
        if (!($db = & $this->_getDb($cm))) {
            return false;
        }

        $modified_rvars = $o->epGetModifiedVars(epObject::VAR_RELATIONSHIP);

        // insert if no id assigned
        if (!$o->epGetObjectId()) {

            // event: onPreInsert
            $this->_dispatchEvent(array('onPreInsert'), $o);

            // insert now
            if (!$db->insert($cm, $o)) {
                return false;
            }

            // set object id
            $o->epSetObjectId($db->lastInsertId($cm->getOidColumn()));

            // reset dirty flag
            $o->epSetDirty(false);
            if ($simple) {
                $o->epSetModifiedVars($modified_rvars);
            }

            // remove it from uncommited cache
            unset($this->objects_uc[$o->epGetUid()]);

            // cache it
            $this->cache($o);

            // now set object committable
            $o->epSetCommittable(true);

            // event: onInsert onPostInsert
            $this->_dispatchEvent(array('onInsert', 'onPostInsert'), $o);

            return true;
        }

        // event: onPreUpdate
        $this->_dispatchEvent(array('onPreUpdate'), $o);

        // update db row
        if (!$db->update($cm, $o)) {
            return false;
        }

        // reset dirty flag after successful update
        $o->epSetDirty(false);
        if ($simple) {
            $o->epSetModifiedVars($modified_rvars);
        }

        // also set object committable
        $o->epSetCommittable(true);

        // event: onUpdate and onPostUpdate
        $this->_dispatchEvent(array('onUpdate', 'onPostUpdate'), $o);

        return true;
    }

    /**
     * Delete all stored objects for a class.
     *
     * This method offers a faster way to delete a table than deleting
     * all objects one by one. Since this method empty the whole table,
     * use with extreme _caution_.
     *
     * @param string class
     * @return bool
     * @access public
     */
    public function deleteAll($class) {

        // get class map for class
        $cm = & $this->_getMap($class);
        if (!$cm) {
            return false;
        }

        // get db for the class
        $db = & $this->_getDb($cm);
        if (!$db) {
            return false;
        }

        // event: onPreDeleteAll
        $this->_dispatchEvent(array('onPreDeleteAll'), $class);

        // call db to truncate the table
        if (!$db->truncate($cm)) {
            return false;
        }

        // delete objects from cache
        if (isset($this->objects_c[$class])) {
            unset($this->objects_c[$class]);
        }

        // also delete cached uncommited objects
        foreach($this->objects_uc as $uid => &$o) {

            if ($o && $class == $this->_getClass($o)) {

                // set that object is deleted
                $o->epSetDeleted(true);

                // remove from cache
                unset($this->objects_uc[$uid]);
                $this->objects_uc[$uid] = null;
            }
        }

        // event: onDeleteAll, onPostDeleteAll
        $this->_dispatchEvent(array('onDeleteAll', 'onPostDeleteAll'), $class);

        return true;
    }

    /**
     * Delete an object and its datastore copy
     * @param object
     * @return bool
     * @access public
     */
    public function delete(&$o = null) {

        if (!$o) {
            return false;
        }

        // no need to delete db row for unknown oid
        if (!($oid = $o->epGetObjectId()) ) {

            // event: onPreDelete
            $this->_dispatchEvent(array('onPreDelete'), $o);

            // delete it from uncommited cache
            $uid = $o->epGetUid();
            if (isset($this->objects_uc[$uid])) {
                $this->objects_uc[$uid] = null;
                unset($this->objects_uc[$uid]);
            }

            // fix bug 97: set that object is deleted
            $o->epSetDeleted(true);

            // event: onDelete, onPostDelete
            $this->_dispatchEvent(array('onDelete', 'onPostDelete'), $o);

            return true;
        }

        // get class name
        if (!($class = $this->_getClass($o))) {
            return false;
        }

        // get class map for class
        if (!($cm = & $this->_getMap($class))) {
            return false;
        }

        // get db for the class
        if (!($db = & $this->_getDb($cm))) {
            return false;
        }

        // event: onPreDelete
        $this->_dispatchEvent(array('onPreDelete'), $o);

        // delete db row
        if (!$db->delete($cm, $o)) {
            return false;
        }

        // set that object is deleted
        $o->epSetDeleted(true);

        // event: onDelete, onPostDelete
        $this->_dispatchEvent(array('onDelete', 'onPostDelete'), $o);

        // delete object from cache
        if (isset($this->objects_c[$class][$oid])) {
            // explicitly delete object
            $this->objects_c[$class][$oid] = null;
            unset($this->objects_c[$class][$oid]);
        }

        return true;
    }

    /**
     * Evict all instances of a class
     * @param string $class class name
     * @return bool
     * @access public
     */
    public function evictAll($class) {
        return $this->_evictAll($class);
    }

    /**
     * Low level evictAll. Called by {@link evictAll()}
     * @param string $class class name
     * @param boolean $dispatch_event whether to dispatch event
     * @return bool
     * @access public
     */
    protected function _evictAll($class = false, $dispatch_event = true) {

        // class name cannot be empty
        if (!$class) {
            return false;
        }

        // any object cached for class?
        if ($class && !isset($this->objects_c[$class])) {
            return true;
        }

        // event: onPreEvictAll
        if ($dispatch_event) {
            $this->_dispatchEvent(array('onPreEvictAll'), $class);
        }

        // if class unspecified, evict all classes
        if (!$class) {
            // set each object to null to explicitly delete
            foreach($this->objects_c as $class => &$os) {
                // set each object to null to explicitly delete
                foreach($os as $oid => &$o) {
                    $o = null;
                }
                // unset cache for class
                unset($this->objects_c[$class]);
            }
        } else {
            // set each object to null to explicitly delete
            foreach($this->objects_c[$class] as $oid => &$o) {
                $o = null;
            }
            // unset cache for class
            unset($this->objects_c[$class]);
        }

        // event: onEvictAll, onPostEvictAll
        if ($dispatch_event) {
            $this->_dispatchEvent(array('onEvictAll', 'onPostEvictAll'), $class);
        }

        return true;
    }

    /**
     * Evicts an instance from the cache. Eviction forces the object
     * be retrieved (same as {@link refresh()}  from datastore the
     * next time you access it. If the parameter $o is set to null,
     * all cached instances are evicted.
     * @param epObject object
     * @return bool
     * @access public
     */
    public function evict(&$o = null) {

        // if no object, evict all
        if (!$o) {
            $status = true;
            foreach($this->objects_c as $class => &$objects) {
                $status &= $this->evictAll($class);
            }
            return $status;
        }

        // no need to evict object with unknown oid
        if (!($oid = $o->epGetObjectId())) {
            return true;
        }

        // get class name
        if (!($class = $this->_getClass($o))) {
            return false;
        }

        // check if instance is cached
        if (!isset($this->objects_c[$class][$oid])) {
            return false;
        }

        // event: onPreEvict
        $this->_dispatchEvent(array('onPreEvict'), $o);

        // evict the instance from cache (needed to explicitly delete object)
        $this->objects_c[$class][$oid] = null;

        // unset this oid
        unset($this->objects_c[$class][$oid]);

        // event: onEvict and onPostEvict
        $this->_dispatchEvent(array('onEvict', 'onPostEvict'), $o);

        return true;
    }

    /**
     * Flush all objects into db (same as {@link flush()} with no parameters)
     * @return void
     */
    public function flushAll() {
        $this->flush();
    }

    /**
     * Flushes all dirty or new instances to the datastore
     * @param string $class
     * @param bool $commit_all (if true, commit uncommited)
     * @return bool
     * @access public
     */
    public function flush($class = false, $commit_all = true) {

        $status = true;

        if ($commit_all) {

            // flush uncommited objects
            foreach($this->objects_uc as $uid => &$o) {
                // weed out non-commitable uncommitted objects (eg example objects)
                if ($o && (!$class || $class == $this->_getClass($o)) && $o->epIsCommittable()) {
                    // commit to get object id
                    $status &= $this->_commit_o($o, true); // true: simple commit
                }
            }
        }

        // flush cached objects (commited)
        foreach($this->objects_c as $class_ => &$objects) {

            // flush only a class?
            if ($class && $class != $class_) {
                // skip if not required to commit
                continue;
            }

            // flush
            foreach($objects as $oid => &$o) {
                if ($o) {
                    $status &= $this->commit($o);
                }
            }
        }

        return $status;
    }

    /**
     * The public version of {@ink _getMap())
     * @param string $class the class name of the object
     * @param epClassMap $cm the class map info
     */
    public function &getMap($class) {
        return $this->_getMap($class);
    }

    /**
     * Returns an array of cached object that matches a given example object
     * @param epObject $o the example object
     * @return false|array
     */
    protected function _findInCache($o) {

        if (!$o) {
            return false;
        }

        // get class name
        if (!($class = $this->_getClass($o))) {
            return false;
        }

        // check if any cached object for the class
        if (!isset($this->objects_c[$class])) {
            return false;
        }

        // check each cached object
        $matched = array();
        foreach($this->objects_c[$class] as $oid => &$cached_o) {
            if ($cached_o) {
                if ($cached_o->epMatches($o, true)) {
                    $matched[] = & $cached_o;
                }
            }
        }

        return $matched;
    }

    /**
     * Get class name of an object. If wrapped get the class name
     * of the wrapped object
     * @param epObject $o the object
     * @return string
     * @access public
     */
    public function getClass($o) {
        return $this->_getClass($o);
    }

    /**
     * Get the class map of a class
     * @param object|string $class class name
     * @return epClassMap
     * @access public
     */
    public function &getClassMap($class) {

        // get class name if argument is an object
        if (is_object($class)) {
            $class = $this->_getClass($class);
        }

        return $this->_getMap($class);
    }

    /**
     * Get class name of an object. If wrapped get the class name
     * of the wrapped object
     * @param epObject $o the object
     * @return string
     * @access protected
     */
    protected function _getClass($o) {

        // sanity check
        if (!$o || !is_object($o)) {
            return false;
        }

        // get class name
        $class = get_class($o);
        if ($o instanceof epObject) {
            $class = get_class($o->getForeignObject());
        }

        return $class;
    }

    /**
     * Gets class map for a class
     * @param string $class the class name of the object
     * @param epClassMap $cm the class map info
     * @return epClassMap
     */
    protected function &_getMap($class) {

        // have we initialized class map factory yet?
        if (!$this->cmf) {
            $this->initialize(true);
        }

        // get class map for class
        return $this->cmf->track($class);
    }

    /**
     * Get the db connection for a class
     * @param string $class the class name
     * @param epClassMap $cm the class map
     * @return false|epDb
     */
    public function &getDb($cm) {
        return $this->_getDb($cm);
    }

    /**
     * (Low level) Get the db connection for a class
     * @param string $class the class name
     * @param epClassMap $cm the class map
     * @return false|epDb
     */
    protected function &_getDb($cm) {

        // get class name
        $class = $cm->getName();

        // check if db conx for the class has been cached
        if (isset($this->dbs[$class])) {
            return $this->dbs[$class];
        }

        // get dsn from class map
        $dsn = $cm->getDsn();
        if (!$dsn) {
            throw new epExceptionManagerBase('Cannot find DSN for class [' . $class . ']');
            return self::$false;
        }

        // have we initialized db factory yet?
        if (!$this->dbf) {
            $this->initialize(true);
        }

        // get the db connection from db factory
        $db = $this->dbf->make($dsn);
        if (!$db) {
            throw new epExceptionManagerBase('Cannot establish database connection for class [' . $class . ']');
            return self::$false;
        }

        // set check_table_exists options to db
        $db->setCheckTableExists($this->getConfigOption('check_table_exists'));

        // check if in transition. if so add db to watch
        if ($this->t) {
            $this->t->addDb($db);
        }

        // log queries if set
        $db->logQueries($this->getConfigOption('log_queries'));

        // create table if not exist
        if (!$db->create($cm)) {
            throw new epExceptionManagerBase('Cannot create table for class [' . $class . ']');
            return self::$false;
        }

        // cache db for class
        return $this->dbs[$class] = & $db;
    }

    /**
     * Loads compiled info into manager
     *
     * According to compile options, 'force_compile' and 'auto_compile',
     * the method decides whether to recompile all or some of the class
     * files.
     *
     * @return boolean
     */
    protected function _loadCompiled() {

        // get the compiled info
        if (!($rcmf = $this->getConfigOption('compiled_file'))) {
            throw new epExceptionManagerBase('Compiled file not specified');
            return false;
        }

        // get the dir that holds the class map file
        if ($compiled_dir = $this->getConfigOption('compiled_dir')) {
            // if compiled dir is a relative path, make is absolute
            $compiled_dir = $this->getAbsolutePath($compiled_dir);
            $rcmf =  $compiled_dir . '/' . $rcmf;
        }

        // check if force_compile is set
        if ($this->getConfigOption('force_compile')) {
            // if so, delete the compiled file to force compile
            if (file_exists($rcmf)) {
                @unlink($rcmf);
            }

            // recompiled?... check if we want to auto update schemas
            if ($this->getConfigOption('auto_update')){
                // touch updater to fire its execution later
                include_once (EP_SRC_DB.'/epDbUpdate.php');
                $this->su = epDbUpdate::instance();
            }
        }

        // get the contetns of the runtime config map file
        $compiled_info = false;
        if (file_exists($rcmf)) {
            $compiled_info = file_get_contents($rcmf);
        }

        // unserializing compiled info into class map factory
        include_once(EP_SRC_ORM.'/epClassMap.php');
        if ($compiled_info) {

            // unserialize class map info
            $this->cmf = & epClassMapFactory::unserialize($compiled_info);
            if (!$this->cmf) {
                throw new epExceptionManagerBase('Cannot unserialize compiled info');
                return false;
            }

            // only when auto compile do we check config file mtime
            if ($this->getConfigOption('auto_compile')) {
                // if config file is newer than compiled info
                if (filemtime($this->getConfigSource()) > filemtime($rcmf)) {
                    // remove all classes to force recompile
                    $this->cmf->removeAll();
                }
            }

        }
        // if no compiled class map file found
        else {

            // simply get the class map factory instance
            $this->cmf = & epClassMapFactory::instance();

            // remove all classes now
            $this->cmf->removeAll();
        }

        // check if auto_compile is enabled
        if ($this->getConfigOption('auto_compile')) {
            $this->_compileAll();

            // recompiled?... check if we want to auto update schemas
            if ($this->getConfigOption('auto_update')){
                // touch updater to fire its execution later
                include_once (EP_SRC_DB.'/epDbUpdate.php');
                $this->su = epDbUpdate::instance();
            }
        }

        return true;
    }

    /**
     * Get the compiler
     * @return bool
     * @throws epExceptionManagerBase
     */
    protected function _getCompiler() {

        // check if class compiler is instantiated
        if (!$this->cc) {
            if (!($this->cc = new epClassCompiler($this->getConfig()))) {
                throw new epExceptionManagerBase('Cannot instantiate class compiler');
                return false;
            }
        }

        return true;
    }

    /**
     * Auto-compile a class
     * @param string $class
     * @return false|epClassMap
     * @throws epExceptionManagerBase
     */
    protected function &_compileClass($class) {

        // sanity check
        if (!$class) {
            return self::$false;
        }

        // check if class compiler is instantiated
        if (!$this->_getCompiler()) {
            return self::$false;
        }

        // call class compiler to compile the class
        if (!$this->cc->compile($class)) {
            throw new epExceptionManagerBase('Failed in compiling class ' . $class);
            return self::$false;
        }

        // if so, get the class map after compiling the class
        return $this->_getMap($class);
    }

    /**
     * Auto-compile all source files
     * @return bool
     * @throws epExceptionManagerBase
     */
    protected function _compileAll() {

        // check if class compiler is instantiated
        if (!$this->_getCompiler()) {
            return false;
        }

        // call class compiler to compile the class
        if (!$this->cc->compile()) {
            throw new epExceptionManagerBase('Failed in compiling classes in configured directory');
            return false;
        }

        return true;
    }

    /**
     * Returns the current transaction
     * @return false|epTransaction
     */
    public function get_t() {
        return $this->t;
    }

    /**
     * Start a transaction
     * @return false|epTransaction
     */
    public function start_t() {

        // are we in transition already?
        if ($this->t) {
            return false;
        }

        // backup current object states and dbs
        include_once(EP_SRC_RUNTIME . '/epTransaction.php');
        if (!($this->t = new epTransaction(array_values($this->dbs)))) {
            throw new epExceptionManagerBase('Cannot start transaction');
            return false;
        }

        return $this->t;
    }

    /**
     * Commit the current transaction.
     * @param bool $rollback (true by default)
     * @return bool
     */
    public function commit_t($rollback = true) {

        // check if in transition
        if (!$this->t) {
            throw new epExceptionManagerBase('Transaction not started');
            return false;
        }

        try {
            // commit all changed objects since the start of transaction
            $commited = $this->t->commitObjects();
        } catch (epExceptionDb $e) {
            // fails to commit
            $commited = false;
        }

        if (!$commited) {

            // rollback if asked for
            if ($rollback) {
                $this->rollback_t();
            }
            // if not rollback
            else {
                // throw exception
                throw new epExceptionManagerBase('Committing objects failed');
            }

            // reset transaction
            $this->t = false;

            return false;
        }

        // commit sql statements in all dbs
        if (!$this->t->commitDbs()) {

            // rollback if asked for
            if ($rollback) {
                $this->rollback_t();
            }
            // if not rollback
            else {
                // throw exception
                throw new epExceptionManagerBase('Committing dbs failed');
            }

            // reset transaction
            $this->t = false;

            return false;
        }

        // reset transaction
        $this->t = false;

        return true;
    }

    /**
     * Rollback the current transction
     * @return bool
     */
    public function rollback_t() {

        if (!$this->t) {
            throw new epExceptionManagerBase('Transaction not started');
            return false;
        }

        // rollback all dbs
        $status = $this->t->rollbackDbs();

        // bug #217 non transactional mysql need to delete objects by hand
        if(! $status){
            // if not rolled back in db, get already commited objects
            $commited = &$this->t->getCommitedObjects();
            /*
             *  last chance to rollback, at least delete commited objects
             *  TODO optimize this operation, maybe deleteAll needs support
             *  for delete collections, delete all object collection at once
             */
            foreach ($commited as $co) {
                $co->delete();
            }
        }

        // restore object states
        $status = $this->t->rollbackObjects();

        // reset transaction
        $this->t = false;

        return $status;
    }

    /**
     * Add object for the current transition to watch
     * @param epObject
     * @return bool
     */
    protected function addObject_t(epObject  &$o) {

        // are we in transition?
        if (!$this->t) {
            // quit now if not
            return false;
        }

        // get class name
        if (!($class = $this->_getClass($o))) {
            return false;
        }

        // get class map for class
        if (!($cm = & $this->_getMap($class))) {
            return false;
        }

        // get db for the class
        if (!($db = & $this->_getDb($cm))) {
            return false;
        }

        // add object to watch
        $this->t->addObject($o, $db);

        return true;
    }

    /**
     * Returns the class map factory
     * @return epClassMapFactory
     * @access public
     */
    public function getClassMapFactory() {

        // if class map factory does not exist yet
        if (!$this->cmf) {
            // initialize
            $this->initialize(true);
        }

        return $this->cmf;
    }

    /**
     * Return the updater
     * @return epDbUpdate
     * @access public
     */
    public function getUpdater(){
        return $this->su;
    }

    /**
     * Returns an array of queries conducted (key'ed by database name)
     * @return false|array
     */
    public function getQueries() {

        // check if db factory is init'ed
        if (!$this->dbf) {
            return false;
        }

        // get dbs
        if (!($dbs = $this->dbf->allMade())) {
            return false;
        }

        // go through each db
        $queries = array();
        foreach($dbs as $db) {
            $queries[$db->connection()->dsn()] = $db->getQueries();
        }

        return $queries;
    }

    /**
     * Register an event listener
     *
     * For local listeners, only class names can be used to register.
     * Whilst for global listeners, both class name and object (listener
     * instances) are allowed to register.
     *
     * @param object|string $l listener object or class
     * @return boolean
     * @throws epExceptionManagerBase
     */
    public function register($l) {

        // sanity check
        if (!$l) {
            throw new epExceptionManagerBase('Listener cannot be null.');
            return false;
        }

        // if $l is a string
        if (is_string($l)) {

            // and it is -not- a class compiled (to be persisted)
            if (!$this->_getMap($l)) {

                // does listener class exist?
                if (!class_exists($l)) {
                    throw new epExceptionManagerBase('Listener class [' . $l . '] does not exist.');
                    return false;
                }

                // instantiate listener
                if (!($l = new $l())) {
                    throw new epExceptionManagerBase('Cannot instantiate listener [' . $l . '].');
                    return false;
                }
            }

            // otherwise its a local listener
            else {

                // add it to local listeners if not already in
                if (!isset($this->listeners_l[$l]) || !$this->listeners_l[$l]) {
                    $this->listeners_l[$l] = true;
                }

                // done
                return true;
            }

        }

        // listener must be an object
        if (!is_object($l)) {
            throw new epExceptionManagerBase('Listener must be an object.');
            return false;
        }

        // is it an epObject (local listener)?
        if ($l instanceof epObject) {
            throw new epExceptionManagerBase('Cannot register persistent object as local listener. Only persistent class name is allowed to register.');
            return false;
        }

        // check if $l is a valid listener
        if (!($callbacks = $this->_inspectListenser($l))) {
            throw new epExceptionManagerBase('No callback (event handler) is defined in listener.');
            return false;
        }

        // add listener to global listeners array
        $this->listeners_g[] = $l;

        return true;
    }

    /**
     * Remove (unregiester) a listener
     * @param string|object $l
     * @return false|integer (number of listener unregistered)
     */
    public function unregister($l) {

        // sanity check
        if (!$l || (!is_string($l) && !is_object($l))) {
            return false;
        }

        // is it a persistent class?
        if (is_string($l) && $this->_getMap($l)) {
            unset($this->listeners_l[$l]);
            return 1;
        }

        // go through all listeners
        $unregistered = 0;
        foreach($this->listeners_g as $k => $listener) {

            $is_listener = false;

            // $l is listener class
            if (is_string($l)) {
                if (get_class($listener) == $l) {
                    $is_listener = true;
                }
            }

            // $l is listener object
            else {
                if ($listener === $l) {
                    $is_listener = true;
                }
            }

            // remove matched listener
            if ($is_listener) {
                unset($this->listeners_g[$k]);
                $unregistered ++;
            }

        }

        return ($unregistered == 0) ? false : $unregistered;
    }

    /**
     * Dispatch an event to registered listeners
     * @param string|array $events one or an array of the allowed events
     * @param epObject|string $obj_or_class (either an object or a class involved in the event)
     * @param mixed $params extra parameters for the event
     * @return void
     */
    protected function _dispatchEvent($events, $obj_or_class, $params = array()) {

        // check if it is configured to dispatch events
        if (!$this->getConfigOption('dispatch_events')) {
            return;
        }

        // make event array
        if (is_string($events)) {
            $events = array($events);
        }

        // dispatch events one by one to local and global listeners
        foreach($events as $event) {

            // dispatch event to local listener
            $this->_dispatchEvent_l($event, $obj_or_class, $params);

            // dispatch event to global listener
            $this->_dispatchEvent_g($event, $obj_or_class, $params);
        }
    }

    /**
     * Dispatch an event to registered local listeners
     * @param string $event (one of the allowed callback names, @link system_callbacks)
     * @param epObject|string $obj_or_class (either an object or a class involved in the event)
     * @param mixed $params extra parameters for the event
     * @return void
     */
    protected function _dispatchEvent_l($event, $obj_or_class, $params = array()) {

        // must be either epObject or a string
        if (!$obj_or_class ||
            (!is_string($obj_or_class) && !($obj_or_class instanceof epObject))) {
            return;
        }

        // get the class involved in the event
        if (is_object($obj_or_class)) {
            $class = $this->_getClass($obj_or_class);
        } else {
            $class = $obj_or_class;
        }

        // check if class is registered
        if (!isset($this->listeners_l[$class]) || !$this->listeners_l[$class]) {
            // if not do nothing
            return;
        }

        // if involved is an object
        if (is_object($obj_or_class)) {
            // check if method exists in object
            if ($obj_or_class->epMethodExists($event)) {
                // static method
                if (epUtils::epIsMethodStatic($class, $event)) {
                    //$class::$event($params);
                    call_user_func_array(array($class, $event), $params);
                }
                // non-static method
                else {
                    $obj_or_class->$event($params);
                }
            }
        }
        // or if involved is a class
        else {
            // only static method can be called for a class event
            if (epUtils::epIsMethodStatic($class, $event)) {
                //$class::$event($params);
                call_user_func_array(array($class, $event), $params);
            }
        }
    }

    /**
     * Dispatch an event to registered listeners
     * @param string $event (one of the allowed callback names, @link system_callbacks)
     * @param epObject|string $obj_or_class (either an object or a class involved in the event)
     * @param mixed $params extra parameters for the event
     * @return void
     */
    protected function _dispatchEvent_g($event, $obj_or_class, $params = array()) {

        // go through all global event listeners
        foreach($this->listeners_g as $listener) {

            // check if listener can process event
            if (method_exists($listener, $event)) {

                // call the listen to process event
                $listener->$event($obj_or_class, $params);
            }
        }
    }

    /**
     * Inspect a (global) listener to see whether it is valid. A valid listener
     * needs to have at least one callback method. Returns false if invalid or
     * array of callbacks.
     *
     * @param object $l
     * @return false|array (of string (callback methods))
     */
    protected function _inspectListenser($l) {

        // sanity check
        if (!$l || !is_object($l) || !($class = get_class($l))) {
            return false;
        }

        // get all methods in listener
        if (!($methods = get_class_methods($class))) {
            return false;
        }

        // get intersection of methods in listener and system callbacks
        if (!($callbacks = array_intersect($methods, self::$events))) {
            return false;
        }

        return $callbacks;
    }

    /**
     * Notify object changes
     *
     * This method is used by an object {@epObject} to notify manage one or more
     * than one its vars will be or have been changed.
     *
     * @param epObject $o
     * @param string $event (either 'onPreChange' or 'onPostChange');
     * @param array $vars (vars that will be changed or have been changed)
     *
     * @return bool
     */
    public function notifyChange(&$o, $event, $vars) {

        switch ($event) {

            case 'onPreChange':

                // add this object into transaction
                $this->addObject_t($o);

                // dispatch event: onPreChange
                $this->_dispatchEvent(array('onPreChange'), $o, $vars);

                break;

            case 'onChange':
            case 'onPostChange':

                // dispatch events: onChange, onPostChange
                $this->_dispatchEvent(array('onChange', 'onPostChange'), $o, $vars);

                break;

            default:
                throw new epExceptionManagerBase('Unrecognized event');
        }

        return true;
    }

}
