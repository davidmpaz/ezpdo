<?php

/**
 * $Id: epManager.php 1044 2007-03-08 02:25:07Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */

/**
 * Need {@link epConfigurableWithLog} as the super class 
 */
include_once(EP_SRC_BASE.'/epConfigurableWithLog.php');

/**
 * need epObject
 */
include_once(EP_SRC_RUNTIME.'/epObject.php');

/**
 * need epUtils
 */
include_once(EP_SRC_BASE.'/epUtils.php');

/**#@+
 * Defines where to get objects
 */
define('EP_GET_FROM_DB',    1);
define('EP_GET_FROM_CACHE', 2);
define('EP_GET_FROM_BOTH',  3);
/**#@-*/

/**
 * Exception class for {@link epManagerBase}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epExceptionManagerBase extends epExceptionConfigurableWithLog {
}

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
            if (!($o = & epNewObject($class, $args))) {
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
            include_once(EP_SRC_COMPILER.'/epCompiler.php'); 
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

        // commit all changed objects since the start of transaction
        if (!$this->t->commitObjects()) {

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
    protected function _dispatchEvent($events, $obj_or_class, $params = null) {
        
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
                if (epIsMethodStatic($class, $event)) {
                    //$class::$event($params);
                    call_user_func_array(array($class, $method), $params);
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
            if (epIsMethodStatic($class, $event)) {
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
    protected function _dispatchEvent_g($event, $obj_or_class, $params = null) {

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

/**
 * Exception class for {@link epManager}
 * 
 * Child of epExceptionManagerBase.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epExceptionManager extends epExceptionManagerBase {
}

/**
 * Class of ezpdo persistence manager
 * 
 * This is a subclass of {@link epManagerBase}. {@link epManagerBase}
 * has dealt with the persistence of primitive data types. This class 
 * addresses issues related to object relationships. 
 * 
 * This class also implements the {@link epSingleton} interface. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epManager extends epManagerBase implements epSingleton {
    
    /**
     * The cached example object for epObjectRelation
     * @var epObjectRelation
     */
    protected $eo_obj_rel = false;

    /**
     * The cached relationship class map
     * @var epClassMap
     */
    protected $cm_obj_rel = false;

    /**
     * The cached prefix of relation table name
     * @var string
     */
    protected $rel_tbl_prefix = '';

    /**
     * Whether to split relation table
     * @var boolean (default to false)
     */
    protected $rel_tbl_split = false;

    /**
     * Constructor
     * @param epConfig|array 
     * @access public
     */
    public function __construct($config = null) {
        parent::__construct($config);
    }
    
    /**
     * Overrides {@link epManagerBase::initialize()}
     * @param bool $force whether to force initialization
     * @return bool
     * @throws epExceptionManager
     */
    protected function initialize($force = false) {
        
        // call parent epManagerBase to initialize
        $status = parent::initialize($force);
        
        // check if init'ed before
        if (!$force && $this->cm_obj_rel) {
            return true;
        }

        // check if epObjectRelation has been compiled
        include_once(EP_SRC_ORM . '/epObjectRelation.php');
        if (!($this->cm_obj_rel = & $this->_getMap('epObjectRelation'))) {
            if (!($this->cm_obj_rel = & $this->_compileClass('epObjectRelation'))) {
                throw new epExceptionManager('Failed in compiling class epObjectRelation');
                return false;
            }
        }
        
        // append overall table prefix
        $this->rel_tbl_prefix = $this->getConfigOption('relation_table');
        if ($prefix = $this->getConfigOption('table_prefix')) {
            $this->rel_tbl_prefix = epUnquote($prefix) . $this->rel_tbl_prefix;
        }

        // set relation table name
        $this->cm_obj_rel->setTable($this->rel_tbl_prefix);

        // set default dsn to relation table
        $this->cm_obj_rel->setDsn($this->getConfigOption('default_dsn'));
        
        // cache relation table splitting flag
        $this->rel_tbl_split = $this->getConfigOption('split_relation_table');
        
        return $status;
    }
    
    /**
     * Commits an object
     * @param object $o
     * @return bool $force (ignored - treated as false!)
     * @access public
     */
    public function commit(&$o, $force = false) {
        
        // make sure we are dealing with an epObject
        if (!($o instanceof epObject)) {
            return false;
        }

        // check if the object should be commited
        if (!$force && !$o->epNeedsCommit()) {
            return true;
        }
        
        // get class map for class 
        if (!($cm = & $o->epGetClassMap())) {
            return false;
        }
        
        // get class name
        if (!($class = $cm->getName())) {
            return false;
        }
        
        // check if class has any non-primitive fields
        if (!($npfs = $cm->getNonPrimitive())) {
            return $this->_commit_o($o);
        }

        // set object is being commited
        $o->epSetCommitting(true);

        // get all modified relationship vars
        $modified_rvars = $o->epGetModifiedVars(epObject::VAR_RELATIONSHIP);

        // array to keep track of 1-to-many relations
        $relations = array(); 
        
        // go through each non-primitive field
        $status = true;    
        foreach($npfs as $name => $fm) {

            // initialize arrays to hold relation oids
            if (isset($modified_rvars[$name])) {
                $relations[$name] = array();
                $relations[$name][$fm->getBase_b()] = array('new' => array(), 'old' => null);
            }

            // var field value
            if (!($v = & $o->epGet($name))) {
                continue;
            }
            
            // check if value is array
            if (is_array($v) || $v instanceof epArray) {
                
                // check if it is a "many" field
                if (!$fm->isMany()) {
                    throw new epExceptionManager('Value (array) of variable [' . $cm->getName() . "::" . $fm->getName() . '] and its field map mismatch');
                    continue;
                }

                // no convertion from oid to object
                if ($v instanceof epArray) {
                    $convert_oid_0 = $v->getConvertOid();
                    $v->setConvertOid(false);
                }
                
                // go through each value in $v
                $oids = array();
                foreach($v as &$w) {
                    
                    if (is_string($w)) {
                        
                        $oids[] = $w;
                        
                    } else if (is_object($w) && ($w instanceof epObject)) {

                        // ignore deleted object
                        if ($w->epIsDeleted()) {
                            continue;
                        }
                        
                        // commit the value (object)
                        if ($w->epIsCommitting()) {
                            
                            if (!$w->epGetObjectId()) {
                                // if the object is to be commited, do a simple commit to get the oid
                                $status &= $this->_commit_o($w, true); // true: simple commit
                            }
                            
                        } else {

                            // for not in to-be-committed queue
                            $status &= $this->commit($w);
                        }
                        
                        if ($w->epIsCommittable()) {
                            // collect oid
                            $oids[] = $this->encodeUoid($w);
                        }
                    } 

                }

                // set convert id flag back 
                if ($v instanceof epArray) {
                    $v->setConvertOid($convert_oid_0);
                }
                
                // put oids into the relation array
                if (isset($modified_rvars[$name])) {
                    $new = $oids;
                    $old = null;
                    if ($v instanceof epArray) {
                        $old = array();
                        $new = array();

                        $origArray = $v->getOrigArray();

                        // figure out how many of each object is in the relationships
                        $oidscount = array();
                        $oidsduplicate = array();
                        foreach ($oids as $oid) {
                            if (!isset($oidscount[$oid])) {
                                $oidscount[$oid] = 0;
                            }
                            $oidscount[$oid]++;
                            if ($oidscount[$oid] > 1) {
                                // keep track of the duplicate ones
                                $oidsduplicate[$oid] = $oidscount[$oid];
                            }
                        }
                        $origcount = array();
                        $origduplicate = array();
                        foreach ($origArray as $oid) {
                            if (!isset($origcount[$oid])) {
                                $origcount[$oid] = 0;
                            }
                            $origcount[$oid]++;
                            if ($origcount[$oid] > 1) {
                                // keep track of the duplicate ones
                                $origduplicate[$oid] = $origcount[$oid];
                            }
                        }

                        // grab the differences between the original and the new one
                        $new = array_diff($oids, $origArray);
                        $old = array_diff($origArray, $oids);

                        // check for duplicates because array_diff doesn't care about them
                        if (count($origduplicate) > 0 || count($oidsduplicate) > 0) {
                            // find out if there are any duplicate counts
                            // This is done by going through all the original duplicates
                            // and checking:
                            // 1. Are they even in the new oids
                            //   a. If not, the array_diff already took care of it
                            //   b. If so, check the count to see if there is a difference
                            //     i. If not, leave it alone
                            //     ii. If so, check if greater or lesser
                            //        I. orig is greater: delete them all and add them back to new
                            //        II. new is greater: add the difference to new
                            // 
                            // Also, delete the oidsduplicates that we see so we can check if we have
                            // any new duplicates.
                            // Just add one less than the count to the new for the new duplicates. This
                            // is because if they are brand new, $new will already have on entry, and if
                            // there are not new, the relationship already contains one of them.
                            foreach ($origduplicate as $oid => $count) {
                                if (isset($oidscount[$oid])) {
                                    if ($oidscount[$oid] > $count) {
                                        $new = array_merge($new, array_fill(0, $oidscount[$oid] - $count, $oid));
                                    } elseif ($oidscount[$oid] < $count) {
                                        $new = array_merge($new, array_fill(0, $oidscount[$oid], $oid));
                                        $old[] = $oid;
                                    }
                                    unset($oidsduplicate[$oid]);
                                }
                            }

                            foreach ($oidsduplicate as $oid => $count) {
                                $new = array_merge($new, array_fill(0, $count - 1, $oid));
                            }
                        }

                        // set the new original Array
                        $v->setOrigArray($oids);
                    }
                    $relations[$name][$fm->getBase_b()]['new'] = $new;
                    $relations[$name][$fm->getBase_b()]['old'] = $old;
                }
                
            } else {
                
                $oid = false;
                
                if (is_string($v)) {
                    
                    $oid = $v;
                    
                } else if (is_object($v) && ($v instanceof epObject)) {

                    // check if it is a "One" field map 
                    if (!$fm->isSingle()) {
                        throw new epExceptionManager('Variable value (array) and field map (One) mismatch');
                        continue;
                    }
                    
                    // ignore deleted object
                    if ($v->epIsDeleted()) {
                        continue;
                    }

                    // commit the value (object)
                    if ($v->epIsCommitting()) {
                        
                        if (!$v->epGetObjectId()) {
                            // if the object is to be commited, do simple commit to get the oid
                            $status &= $this->_commit_o($v, true); // true: simple commit
                        }
                        
                    } else {
                        
                        // object not in to-be-committed queue. force commit
                        $status &= $this->commit($v);

                    }
                    
                    if ($v->epIsCommittable()) {
                        // collect oid
                        $oid = $this->encodeUoid($v);
                    }
                }
                
                // put oid into the relation array
                if ($oid !== false) {
                    if (isset($modified_rvars[$name])) {
                        $relations[$name][$fm->getBase_b()]['new'] = array($oid);
                        $relations[$name][$fm->getBase_b()]['old'] = null;
                    }
                }
            }
        }

        // make object committable
        $o->epSetCommitting(false);
        if ($o->epNeedsCommit()) {
            $status &= $this->_commit_o($o);
        }
        
        // update object relation for has_many or composed_of_many fields 
        foreach($relations as $var_a => $relation) {
            $base_a = $cm->getField($var_a)->getBase_a();
            foreach($relation as $base_b => $b_oids) {
                $status &= $this->_updateRelations($base_a, $class, $o->epGetObjectId(), $var_a, $base_b, $b_oids['new'], $b_oids['old']);
            }
        }
        
        return $status;
    }
    
    /**
     * Overrides {@link epManagerBase::delete()} to deal with object relationships
     * Delete all objects in a class and all its subclasses
     * @param string class 
     * @return bool
     * @access public
     */
    public function deleteAll($class) { 
        
        // get class map for class 
        if (!($cm = & $this->_getMap($class))) {
            return false;
        }

        // get all subclasses (true: recursive)
        $cms = $cm->getChildren(true);

        // add this class 
        $cms[] = $cm;

        // go through each class 
        $status = true;
        foreach($cms as $cm) {
            $status &= $this->_deleteAll($cm);
        }

        return $status;
    }

    /**
     * Delete all objects in one class BUT NOT in its subclasses 
     * object relationships
     * @param epClassMap $cm
     * @return bool
     * @access protected
     */
    protected function _deleteAll(epClassMap $cm) { 
        
        // get class name
        $class = $cm->getName();

        // check if class has any non-primitive fields
        if (!$cm->hasNonPrimitive()) {
            // if not, simply call parent to delete all 
            return parent::deleteAll($class);
        }
        
        // check if class has any composed_of fields
        if (!($cofs = $cm->getComposedOf())) {
            
            // call parent to delete all
            $status = parent::deleteAll($class);
            
            // remove relations from and to the class
            $status &= $this->_deleteRelations($class);
            return $status;
        }
        
        // otherwise, we need to go through each object and delete the composed_of fields
        // this is an expensive operation. should be optimized.
        
        // get all objects of the class
        if (!($os = $this->getAll($class))) {
            return true;
        }
        
        // delete every object 
        $status = true;
        foreach($os as $o) {
            $status &= $this->delete($o);
        }
        
        return $status;
    }
    
    /**
     * Overrides {@link epManagerBase::delete()} to deal with object relationships
     * @param object
     * @return bool 
     * @access public
     */
    public function delete(&$o = null) { 
        
        // get class name
        if (!($class = $this->_getClass($o))) {
            return false;
        }
        
        // get class map for class 
        if (!($cm = & $this->_getMap($class))) {
            return false;
        }

        // get oid before it's deleted
        $oid = $o->epGetObjectId();

        // remove inverse relationships from memory
        $o->epUpdateInverse(epObject::INVERSE_REMOVE);

        // check if class has any composed_of fields
        if (!($cofs = $cm->getComposedOf())) {
            
            // if not, delete all relations from and to the object 
            $status = parent::delete($o);
            
            // remove relations from and to the class
            $status &= $this->_deleteRelations($class, $oid);
            
            return $status;
        }
        
        // otherwise, go through composed_of fields and delete each object composed of
        $status  = true;
        foreach($cofs as $cof) {
            
            // get the value for the composed_of field
            if (!($val = $o->epGet($cof->getName()))) {
                continue;
            }
            
            // is the field value an array? if so, delete recursively
            if (!is_array($val) && !($val instanceof epArray) ) {
                $status &= $this->delete($val);
            } else {
                foreach($val as $val_) {
                    $status &= $this->delete($val_);
                }
            }
        }
        
        $status &= parent::delete($o);
        $status &= $this->_deleteRelations($class, $oid);
        
        return $status;
    }
    
    /**
     * Returns the relation ids for the variable specified of the object
     * @param epObject $o the object
     * @param epFieldMap $fm the field map of the variable
     * @param epClassMap $cm the class map of the object
     * @return false|string|array
     */
    public function getRelationIds(&$o, $fm, $cm) {
        
        // make sure we are dealing with valid object and non-primitive field
        if (!$o || !$fm  || !$cm) {
            return false;
        }
        
        // object needs to have a valid id and has to be non-primitive
        if (!($oid_a = $o->epGetObjectId()) /* || $fm->isPrimitive() */) {
            return false;
        }
        
        // get class_a, var_a, and the related class 
        $base_a = $fm->getBase_a();
        $class_a = $cm->getName();
        $var_a = $fm->getName();
        $base_b = $fm->getBase_b();
        if (!$base_a ||!$class_a || !$var_a || !$base_b) {
            throw new epExceptionManager('Cannot find related class for var [' . $class_a . '::' . $var_a . ']');
            return false;
        }

        // switch relations table
        $this->_setRelationTable($base_a, $base_b);

        // make an example relation objects 
        if (!($eo = & $this->_relationExample($class_a, $oid_a, $var_a, $base_b))) {
            return false;
        }
        
        // find all relation objects using the example object
        // (find from db only, false: no cache, false: don't convert to objects)
        $rs = & parent::find($eo, EP_GET_FROM_DB, false, false); 
        
        // convert result into oids
        $oids_b = null;
        if ($fm->isSingle()) {
            
            if (is_array($rs) && count($rs) > 1) {
                throw new epExceptionManager('Field ' . $fm->getName() . ' mapped as composed_of_/has_one but is associated with > 1 objects');
                return false;
            }
            
            if ($rs) {
                $oids_b = $rs[0];
            }
            
        } else if ($fm->isMany()) {
            
            $oids_b = array();
            if ($rs) {
                $oids_b = $rs;
            }
            
        }
        
        // always return unique oids
		return is_array($oids_b) ? array_unique($oids_b) : $oids_b;
    }
    
    /**
     * Delete one-to-many relations between class_a and class_b. 
     * @param string $class name of class
     * @param integer|null $oid object id 
     * @return bool
     */
    protected function _deleteRelations($class, $oid = null) {
        
        // split relation table?
        if (!$this->rel_tbl_split) {
            // delete with no split
            return $this->_deleteRelationsNoSplit($class, $oid);
        } 

        // delete relation with split
        return $this->_deleteRelationsSplit($class, $oid);
    }
    
    /**
     * Delete one-to-many relations between class_a and class_b
     * under split_relation_table mode
     * @param string $class name of class
     * @param integer|null $oid object id 
     * @return bool
     */
    protected function _deleteRelationsSplit($class, $oid = null) {
        
        $status = true;

        foreach($this->_getRelationPairs($class) as $base_a_b) {
            
            // split base a and b
            list($base_a, $base_b) = split(' ', $base_a_b);

            // switch relation table
            $this->_setRelationTable($base_a, $base_b);

            // delete relation in the current table
            $status &= $this->_deleteRelationsNoSplit($class, $oid, null, null);
        }

        return $status;
    }

    /**
     * Delete one-to-many relations between class_a and class_b
     * under no split_reltion_table mode
     * @param string $class name of class
     * @param integer|null $oid object id 
     * @return bool
     */
    protected function _deleteRelationsNoSplit($class, $oid = null) {
        
        // get db for object relationship
        if (!($db = & $this->_getDb($this->cm_obj_rel))) {
            return false;
        }

        // call db to update relationship
        return $db->deleteRelationship($this->cm_obj_rel, $class, $oid);
    }

    /**
     * Updates one-to-many relation between two classes 
     * 
     * Relations from $class_a stored that are not in array $oids_b are deleted and 
     * new relations to $class_b objects in $oids_b are added.
     * 
     * @param string $base_a name of base class a
     * @param string $class_a name of class a
     * @param integer $oid_a object id of the class a object
     * @param integer $var_a the relational field of object a
     * @param string $base_b name of base b
     * @param array $oids_b_new oids of the class b object related to the class a object that are new
     * @param array $oids_b_old oids of the class b object related to the class a object that are old (null if all old ones should be deleted)
     * @return bool
     */
    protected function _updateRelations($base_a, $class_a, $oid_a, $var_a, $base_b, $oids_b_new = array(), $oids_b_old = null) {
        
        // need to have a non-empty name
        if (!$base_a || !$class_a || !$oid_a || !$var_a|| !$base_b) {
            throw new epExceptionManager('Incorrect parameters to update relations');
            return false;
        }

        // switch relations table
        $this->_setRelationTable($base_a, $base_b);

        // get db for object relationship
        if (!($db = & $this->_getDb($this->cm_obj_rel))) {
            return false;
        }

        // decode oids_b
        $oids_b_new_decoded = array();
        foreach($oids_b_new as $oid_b) {
            $this->decodeUoid($oid_b, $class, $oid);
            $oids_b_new_decoded[] = array('class' => $class, 'oid' => $oid);
        }

        if (is_array($oids_b_old)) {
            $oids_b_old_decoded = array();
            foreach($oids_b_old as $oid_b) {
                $this->decodeUoid($oid_b, $class, $oid);
                $oids_b_old_decoded[] = $oid;
            }
        } else {
            $oids_b_old_decoded = $oids_b_old;
        }

        // call db to update relationship
        return $db->updateRelationship($this->cm_obj_rel, $class_a, $var_a, $oid_a, $base_b, $oids_b_new_decoded, $oids_b_old_decoded);
    }

    /**
     * Returns the example object of {@link epObjectRelation}
     * 
     * The example object will only be created once and used later by changing the
     * values of its vars. This saves memory and a little execution time.
     * 
     * @param string $class_a name of class a
     * @param integer $oid_a object id of the class a object
     * @param integer $var_a the relational field of object a
     * @param string $base_b name of base b
     * @param string $class_b name of class b
     * @param integer $oid_b oid of the class b object
     * @return false|epObjectRelation
     */
    protected function &_relationExample($class_a = null, $oid_a = null, $var_a = null, $base_b = null, $class_b = null, $oid_b = null) {
        
        // check if the example object has been created
        if ($this->eo_obj_rel) {
            
            // set values to vars
            $this->eo_obj_rel->class_a = $class_a;
            $this->eo_obj_rel->oid_a   = $oid_a;
            $this->eo_obj_rel->var_a   = $var_a;
            $this->eo_obj_rel->base_b  = $base_b;
            $this->eo_obj_rel->class_b = $class_b;
            $this->eo_obj_rel->oid_b   = $oid_b;

            return $this->eo_obj_rel;
        }

        // need to create example object (false: no caching, false: no event dispatching)
        $this->eo_obj_rel = & parent::_create('epObjectRelation', false, false, array($class_a, $oid_a, $var_a, $base_b, $class_b, $oid_b));
        if (!$this->eo_obj_rel) {
            throw new epExceptionManager('Cannot create relation object');
            return false;
        }

        return $this->eo_obj_rel;
    }

    /**
     * Get relation pairs for a given class
     * 
     * Calls class map factory to get all relation field maps for the class
     * and sorts out redundant pairs
     * 
     * @return array
     */
    protected function _getRelationPairs($class) {
        
        // return value (array)
        $pairs = array();

        // call class map factory to get all relation fields that involves the given class
        if (!($fms = $this->cmf->getRelationFields($class))) {
            return $pairs;
        }

        // go through one by one
        $pairs = array(); 
        foreach($fms as $fm) {
            
            // get base_a and base_b
            $base_a = $fm->getBase_a();
            $base_b = $fm->getBase_b();
            
            // swap base_a and base_b if a > b 
            if ($base_a > $base_b) {
                $t = $base_a;
                $base_a = $base_b;
                $base_b = $t;
            }
            $base_a_b = $base_a . ' ' . $base_b;
            
            // check if pair has been seen
            if (in_array($base_a_b, $pairs)) {
                continue;
            }
            
            $pairs[] = $base_a_b;
        }

        // return the pairs
        return $pairs;
    }

    /**
     * Returns the relation table accroding to current settting
     * @param string $base_a
     * @param string $base_b
     * @return string 
     */
    public function getRelationTable($base_a, $base_b) {
        
        // are we in split mode?
        if (!$this->rel_tbl_split) {
            // return the single relation table name
            return $this->rel_tbl_prefix;
        }

        // make relation table postfix
        if ($base_a < $base_b) {
            $postfix = strtolower($base_a . '_' .  $base_b);
        } else {
            $postfix = strtolower($base_b . '_' .  $base_a);
        }
        
        // append postfix
        $table = $this->rel_tbl_prefix;
        if ($table[strlen($table) - 1] != '_') {
            $table .= '_';
        }
        $table .= $postfix;

        return $table;
    }

    /**
     * Switches table name for the object relations class 
     * ({@link epObjectRelation}). The public method of 
     * {@link _setRelationTable()}.
     * 
     * If the second parameter is default to false, the first is the 
     * table name. Otherwise the two parameters are a pair of base 
     * classes for a relationship.
     * 
     * @param string $table_or_base_a
     * @param string $base_b
     * @return void
     */
    public function setRelationTable($table_or_base_a, $base_b = false) {
        $this->_setRelationTable($table_or_base_a, $base_b);
    }

    /**
     * Changes table name for object relations class 
     * 
     * If the second parameter is default to false, the first is the 
     * table name. Otherwise the two parameters are a pair of base 
     * classes for a relationship.
     * 
     * @param string $table_or_base_a
     * @param string $base_b
     * @return void
     */
    protected function _setRelationTable($table_or_base_a, $base_b = false) {
        
        // get relation table name (w/o prefix)
        $table = $table_or_base_a;
        if ($base_b) {
            $table = $this->getRelationTable($table_or_base_a, $base_b);
        }

        // fix bug 175: make DSN always the same as class_a or class_b
        if ($base_b) {
            
            // set dsn to relation table
            $this->cm_obj_rel->setDsn($this->_getRelationDsn($table_or_base_a, $base_b));
            
            // remove cached db for relation table
            if (isset($this->dbs[$this->cm_obj_rel->getName()])) {
                unset($this->dbs[$this->cm_obj_rel->getName()]);
            }
        }

        // set table name for relationship
        $this->cm_obj_rel->setTable($table);
    }

    /**
     * Returns the DSN for relationship table of two classes. Also checks 
     * if the two classes are in the same DSN.
     * @param string $class_a
     * @return false|string
     * @throws epExceptionManager
     */
    protected function _getRelationDsn($class_a, $class_b) {

        // get class map a
        if (!($cm_a = $this->_getMap($class_a))) {
            throw new epExceptionManager('Cannot get class map for class [' . $class_a . ']');
            return false;
        }

        // get dsn for class a 
        if (!($dsn_a = $cm_a->getDsn())) {
            throw new epExceptionManager('Cannot get DSN for class [' . $class_a . ']');
            return false;
        }

        // get class map b
        if (!($cm_b = $this->_getMap($class_b))) {
            throw new epExceptionManager('Cannot get class map for class [' . $class_b . ']');
            return false;
        }

        // get dsn for class b
        if (!($dsn_b = $cm_b->getDsn())) {
            throw new epExceptionManager('Cannot get DSN for class [' . $base_b . ']');
            return false;
        }

        if ($dsn_a != $dsn_b) {
            throw new epExceptionManager('DSNs of classes ['. $dsn_a .', ' . $dsn_b . '] mismatch');
            return false;
        }

        return $dsn_a;
    }

    /**
     * Get object by universal oid
     * @param string $uoid The universal object id
     * @return false|epObject
     */
    public function getByUoid($uoid) {
        
        // string: encoded objected id
        if (!is_string($uoid)) {
            return false;
        }
        
        // ask manager to decode uoid
        if (!$this->decodeUoid($uoid, $class, $oid)) {
            return false;
        }
        
        // let manager find object with class and oid
        return $this->get($class, $oid);
    }

    /**
     * Get object by universal oids
     * @param array $uoids The universal object oids
     * @return false|array of epObject (with same index as $uoids)
     */
    public function getByUoids($uoids) {
        
        // string: encoded objected id
        if (!is_array($uoids)) {
            return false;
        }

        $oids_class = array();
        foreach ($uoids as $uoid) {
            // ask manager to decode universal oid
            if (!$this->decodeUoid($uoid, $class, $oid)) {
                return false;
            }
            $oids_class[$class][] = $oid;
        }

        $objects = array();

        // get all the objects for the different classes
        foreach ($oids_class as $class => $oids) {
            $objects = array_merge($objects, $this->getAll($class, EP_GET_FROM_BOTH, $oids));
        }
        
        return $objects;
    }
    
    /**
     * Generate a string that can be used to uniquely identify an object.
     * False is returned if invalid object or object does not have oid yet.
     * @param epObject $o
     * @return false|string
     */
    public function encodeUoid(epObject $o) {
        if (!$o || !($oid = $o->epGetObjectId())) {
            return false;
        }
        // put class name and id into simple format: "<class>:<id>"
        return $this->_encodeUoid($o->epGetClass(), $oid);
    }
    
    /**
     * Lower level method called by epManager::encodeUoid()
     * @param string $class
     * @param string $oid
     * @return false|string
     */
    protected function _encodeUoid($class, $oid) {
        return $class . ':' . $oid;
    }
    
    /**
     * The reverse of epManager::encodeUoid()
     * @param string $s (the encoded object id)
     * @param string $class (to hold class name)
     * @param string $oid (to hold object id)
     * @return boolean
     */
    public function decodeUoid($s, &$class, &$oid) {
        
        if (!$s || strpos($s,':') <= 0) {
            return false;
        }
        
        // split by ':'
        list($class, $oid) = split(':', $s);
        $oid = (integer)$oid;
        
        // class and oid should not be null
        return ($class && $oid);
    }
    
    /**
     * Changes DSN for classes at runtime. 
     * 
     * Working assumption: the class hierarchy of the input classes 
     * of which you want to change DSN, all their related classes
     * and their relationship tables should be confined in one 
     * database (i.e. one DSN) to insure data integrity and queries
     * to work.
     * 
     * If no class name is specified, all compiled classes will 
     * change their DSN to the new one. 
     * 
     * @param string $dsn The targeted DSN
     * @param string ... Names of classes to change to the target DSN 
     * @return boolean
     * @todo Needs to check if class hiearchies and relations is in one db.
     */
    public function setDsn($dsn) {
        
        // DSN should not be empty
        if (!$dsn) {
            return false;
        }

        // need to initialize if not yet done (no forcing)
        $this->initialize();

        // get all classes
        $classes = func_get_args();
        array_shift($classes); 

        // call class map factory to switch dsn
        if (!($cms = $this->cmf->setDsn($dsn, $classes))) {
            return false;
        }

        // reset cached dbs
        $this->dbs = array();

        return true;
    }

    /**
     * Create all tables for classes mapped so far
     * 
     * This method may be useful if you want to create tables all at once.
     * 
     * @return false|array (of classes for which tables are created)
     * @access public
     */
    public function createTables() {
        
        // if class map factory does not exist yet 
        if (!$this->cmf || !$this->cm_obj_rel) {
            // initialize
            $this->initialize(true);
        }
        
        // done if no class at all
        if (!($cms = $this->cmf->allMade())) {
            return array();
        }

        // array to hold classes/relation tables done
        $classes_done = array();

        // reset dbs cache so they will be created
        $this->dbs = array();
        
        // go through all classes mapped
        foreach($cms as $cm) {
            
            // get class name
            $class = $cm->getName();

            // relation table dealt with for each class
            if ($class == $this->cm_obj_rel->getName()) {
                continue;
            }

            // create table by calling db
            if ($db = $this->_getDb($cm)) {
                $classes_done[] = $class; 
            }

            // create relation tables
            foreach($this->_getRelationPairs($class) as $base_a_b) {

                // don't try to create indexes twice
                if (in_array($base_a_b, $classes_done)) {
                    continue;
                }

                // split base a and b
                list($base_a, $base_b) = split(' ', $base_a_b);

                // switch relation table
                $this->_setRelationTable($base_a, $base_b);

                // call _getDb to create table
                if ($this->_getDb($this->cm_obj_rel)) {
                    $classes_done[] = $base_a_b;
                }
            }
        }
        
        return $classes_done;
    }

    /**
     * Drops all tables for classes mapped so far
     * 
     * This method may be useful if you want to drop tables all at once.
     * 
     * @return false|array (of classes for which tables are drops)
     * @access public
     */
    public function dropTables() {
        
        // if class map factory does not exist yet 
        if (!$this->cmf || !$this->cm_obj_rel) {
            // initialize
            $this->initialize(true);
        }
        
        // done if no class at all
        if (!($cms = $this->cmf->allMade())) {
            return array();
        }

        // array to hold classes/relation tables done
        $classes_done = array();
        
        // go through all classes mapped
        foreach($cms as $cm) {
            
            // get class name
            $class = $cm->getName();

            // relation table dealt with for each class
            if ($class == $this->cm_obj_rel->getName()) {
                continue;
            }

            if ($db = $this->_getDb($cm)) {
                $db->drop($cm);
                $classes_done[] = $class; 
            }

            // create relation tables
            foreach($this->_getRelationPairs($class) as $base_a_b) {

                // don't try to create indexes twice
                if (in_array($base_a_b, $classes_done)) {
                    continue;
                }

                // split base a and b
                list($base_a, $base_b) = split(' ', $base_a_b);

                // switch relation table
                $this->_setRelationTable($base_a, $base_b);

                if ($this->_getDb($this->cm_obj_rel)) {
                    $db->drop($this->cm_obj_rel);
                    $classes_done[] = $base_a_b;
                }
            }
        }
        
        return $classes_done;
    }

    /**
     * Create indexes all at once
     * 
     * This method may be useful if you want to create indexes all at once 
     * or create indexes for already created tables.
     * 
     * @return array (The array of classes and relationship tables with indexes created) 
     * @access public
     */
    public function createIndexes() {

        // initialize tables
        if (!$this->cmf || !$this->cm_obj_rel) {
            $this->initialize();
        }

        // done if no classes at all
        if (!($cms = $this->cmf->allMade())) {
            return array();
        }
        
        // array to hold classes/tables with indexes created
        $classes_done = array();

        // go through all classes mapped
        foreach($cms as $cm) {

            // get class name
            $class = $cm->getName();

            // relation table dealt with for each class
            if ($class == $this->cm_obj_rel->getName()) {
                continue;
            }

            // don't try to create indexes twice
            if (!in_array($class, $classes_done)) {
                // create indexes on the class
                if ($db =& $this->_getDb($cm)) {
                    $db->index($cm);
                    $classes_done[] = $class; 
                }
            }

            // create indexes on the object relations for the class
            foreach($this->_getRelationPairs($class) as $base_a_b) {

                // don't try to create indexes twice
                if (in_array($base_a_b, $classes_done)) {
                    continue;
                }

                // split base a and b
                list($base_a, $base_b) = split(' ', $base_a_b);

                // switch relation table
                $this->_setRelationTable($base_a, $base_b);

                // call db to create index
                if ($db =& $this->_getDb($this->cm_obj_rel)) {
                    $db->index($this->cm_obj_rel);
                    $classes_done[] = $base_a_b; 
                }
            }
        }

        return $classes_done;
    }   

    /**
     * Implements {@link epSingleton} interface
     * @return epBase (instance)
     * @access public
     * @static
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
     * self instance
     * @var epManagerBase
     * @static
     */
    static protected $instance; 
}

?>
