<?php

/**
 * $Id: epDbObject.php 1044 2007-03-08 02:25:07Z nauhygon $
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

use ezpdo\base\epUtils;

use ezpdo\orm\epFieldMap;

use ezpdo\runtime\epArray;
use ezpdo\runtime\epObject;
use ezpdo\runtime\epManager;

use ezpdo\db\exception\epExceptionDbObject;

/**
 * Class for object operations with databases
 *
 * This class provides a layer between the database access (i.e.
 * {@link epDb}) and the persisent objects.
 *
 * It translates persistence-related operations into SQL statements
 * and executes them by calling {@link epDb::execute()}.
 *
 * It implements the two-way conversions, from database rows to
 * persistent objects, and vice versa.
 *
 * It also supports table-level operations for mapped classes -
 * table creation, table dropping, and emptying.
 *
 * Objects of this class is managed by the db factory, {@link
 * epDbFactory}.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbObject {

    /**#@+
     * Used for return value to avoid reference notice in 5.0.x and up
     * @var bool
     */
    static public $false = false;
    static public $true = true;
    static public $null = null;
    /**#@-*/

    /**
     * The reference to the epDb object that connects to the database
     * @var epDb
     */
    protected $db;

    /**
     * Last inserted table
     * @var string
     */
    protected $table_last_inserted = false;

    /**
     * Whether to check if table exists before db operation
     * @var boolean
     */
    protected $check_table_exists = true;

    /**
     * The cached manager
     * @var epManager
     */
    protected $ep_m = false;

    /**
     * The 'order by's used for sorting
     * @var array
     */
    protected $orderbys = array();

    /**
     * Constructor
     * @param epDb
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Destructor
     * Close db connection
     */
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }

    /**
     * Returns whether to check a table exists before any db operation
     * @param boolean $v (default to true)
     */
    public function getCheckTableExists() {
        return $this->check_table_exists;
    }

    /**
     * Sets whether to check a table exists before any db operation
     * @param boolean $v (default to true)
     */
    public function setCheckTableExists($v = true) {
        $this->check_table_exists = $v;
    }

    /**
     * Return the database type defined in {@link epDb}
     * @return string
     */
    public function dbType() {
        return $this->db->dbType();
    }

    /**
     * Return the db connection (epDb)
     * @return epDb
     */
    public function &connection() {
        return $this->db;
    }

    /**
     * Fetchs objects using the variable values specified in epObject
     * If the object is null, get all objects in table.
     * @param array $cms an array of epClassMap
     * @param array $sql_stmts an array of SQL statements
     * @param string $aggr_func
     * @param string $orderby
     * @param string $limit
     * @return false|array
     */
    public function &query($cms, $sql_stmts, $orderby = false, $limit = false, $aggr_func = false) {
        if ($aggr_func) {
            $result = $this->_queryAggrFunc($sql_stmts, $aggr_func);
            return $result;
        }
        $os = $this->_queryObjects($sql_stmts, $cms, $orderby, $limit);
        return $os;
    }

    /**
     * Fetchs objects using the variable values specified in epObject
     * If the object is null, get all objects in table.
     * @param string $sql
     * @param array $cms an array of epClassMap
     * @param string $orderbys
     * @param string $limit
     * @return false|array
     * @throws epExceptionDbObject
     */
    protected function _queryObjects($sql_stmts, $cms, $orderbys, $limit) {

        $result = array();
        foreach ($sql_stmts as $index => $sql_stmt) {

            // stmt preproc
            $sql_stmt = $this->_queryPreproc($sql_stmt);

            // execute sql stmt
            if (!$this->_execute($sql_stmt)) {
                return self::$false;
            }

            // result conversion
            if ($r = $this->_rs2obj($cms[$index])) {
                $result = array_merge($result, $r);
            }
        }

        // sortby
        if ($orderbys) {

            // random orderby
            if (count($orderbys) && $orderbys[0]['dir'] == 'random') {
                shuffle($result);
            }
            // asc|desc orderbys
            else {
                $this->orderbys = $orderbys;
                usort($result, array($this, '__sort'));
            }
        }

        // limit (string)
        if ($limit) {
            $limit = trim(substr(trim($limit), strlen('limit')));
            $parts = explode(' OFFSET ', $limit);
            if (count($parts) == 2) {
                $amount = $parts[0];
                $offset = $parts[1];
            } else {
                $amount = $parts[0];
                $offset = 0;
            }
            $result = array_slice($result, $offset, $amount);
        }

        return $result;
    }

    /**
     * Preprocesses a SQL statement before query
     * @return string
     */
    private function _queryPreproc($sql_stmt) {

        if (false !== strpos($sql_stmt, 'RANDOM()')) {

            // replace RANDOM
            $sql_stmt = str_replace('RANDOM()', epObj2Sql::sqlRandom($this).'()', $sql_stmt);

            // kludge: SELECT DISTINCT does not work with ORDER BY RANDOM()
            if ($this->db->dbType() == 'Postgres') {
                $sql_stmt = str_replace('SELECT DISTINCT', 'SELECT', $sql_stmt);
            }
        }

        return $sql_stmt;
    }

    /**
     * Sorts two objects
     * @param epObject $a
     * @param epObject $b
     * @throws epExceptionDbObject
     */
    private function __sort($a, $b) {

        // tie if no orderbys
        if (!$this->orderbys) {
            return 0;
        }

        // go through each orderby
        foreach($this->orderbys as $orderby) {

            // sign by direction
            $sign = $orderby['dir'] == 'desc' ? -1 : + 1;

            // get values from a and b
            $path = $orderby['path'];
            $va = epUtils::epArrayGet($a, $path);
            $vb = epUtils::epArrayGet($b, $path);

            // boolean or numeric
            if (is_bool($va) || is_numeric($va)) {
                // a < b
                if ($va < $vb) {
                    return -1 * $sign;
                }
                // a > b
                else if ($va > $vb) {
                    return +1 * $sign;
                }
                continue;
            }

            // string
            if (is_string($va)) {
                // a < b
                if (($r = strcmp($va, $vb)) < 0) {
                    return -1 * $sign;
                }
                // a > b
                else if ($r > 0) {
                    return +1 * $sign;
                }
                continue;
            }

            // invalid orderby value
            throw new epExceptionDbObject('Invalid ORDER BY [' . $path . '] value');
        }

        // tie
        return 0;
    }

    /**
     * Execute queries with aggregate functions
     * @param array $cms an array of class maps (epClassMap)
     * @param array $sql_stmts an array of SQL statements
     * @param string $aggr_func
     * @return false|array
     */
    protected function _queryAggrFunc($sql_stmts, $aggr_func)    {

        // it is a single sql stmt?
        if (1 == count($sql_stmts)) {
            return $this->_queryAggrFunc1($sql_stmts[0], $aggr_func);
        }

        // special treatment for average func
        if (0 === stripos($aggr_func, 'AVG(')) {
            // aggreate function: AVG()
            return $this->_queryAggrFuncAverage($sql_stmts, $aggr_func);
        }

        // simple aggregate functions: COUNT, MAX, MIN, SUM
        return $this->_queryAggrFuncSimple($sql_stmts, $aggr_func);
    }

    /**
     * Execute a single SQL stmt with aggregate function
     * @param array $cms an array of class maps (epClassMap)
     * @param array $sql_stmts an array of SQL statements
     * @param string $aggr_func
     * @return false|array
     */
    protected function _queryAggrFunc1($sql_stmt, $aggr_func) {

        // execute sql
        if (!$this->_execute($sql_stmt)) {
            return self::$false;
        }

        // are we dealing with an aggregation function
        return $this->_rs2aggr($aggr_func);
    }

    /**
     * Execute queries with simple aggregate functions (COUNT, MIN, MAX, SUM)
     * @param array $cms an array of class maps (epClassMap)
     * @param array $sql_stmts an array of SQL statements
     * @param string $aggr_func
     * @return false|array
     */
    protected function _queryAggrFuncSimple($sql_stmts, $aggr_func) {

        $result = null;
        foreach ($sql_stmts as $index => $sql_stmt) {

            // execute single sql stmt with aggregate func
            try {
                $r = $this->_queryAggrFunc1($sql_stmt, $aggr_func);
            }
            catch(Exception $e) {
                $r = null;
            }
            if (is_null($r)) {
                continue;
            }


            // collect results according to aggregate function
            if (0 === stripos($aggr_func, 'COUNT') || 0 === stripos($aggr_func, 'SUM')) {
                $result = is_null($result) ? $r : ($result + $r);
            }
            else if (0 === stripos($aggr_func, 'MIN')) {
                $result = is_null($result) ? $r : min($result, $r);
            }
            else if (0 === stripos($aggr_func, 'MAX')) {
                $result = is_null($result) ? $r : max($result, $r);
            }
        }

        return $result;
    }

    /**
     * Execute queries with aggregate function AVG (special treatment)
     * @param array $sql_stmts an array of SQL statements
     * @param string $aggr_func
     * @return false|array
     */
    protected function _queryAggrFuncAverage($sql_stmts, $aggr_func) {

        // sum stmts
        $sum_func = str_ireplace('AVG(', 'SUM(', $aggr_func);
        $sql_stmts_sum = array();
        foreach($sql_stmts as $sql_stmt) {
            $sql_stmts_sum[] = str_replace($aggr_func, $sum_func, $sql_stmt);
        }
        $sum = $this->_queryAggrFuncSimple($sql_stmts_sum, $sum_func);

        // count stmts
        $count_func = 'COUNT(*)';
        $sql_stmts_count = array();
        foreach($sql_stmts as $sql_stmt) {
            $sql_stmts_count[] = str_replace($aggr_func, $count_func, $sql_stmt);
        }
        $count = $this->_queryAggrFuncSimple($sql_stmts_count, $count_func);

        return $sum / $count;
    }

    /**
     * Returns the total number of stored object in a class
     * @param epClassMap
     * @return false|integer
     */
    public function count($cm) {

        // check if class is abstract
        if ($cm->isAbstract()) {
            throw new epExceptionDbObject('Class [' . $cm->getName() . '] is abstract');
            return false;
        }

        // preapre sql statement
        if (!($sql = epObj2Sql::sqlCount($this, $cm))) {
            return false;
        }

        // execute sql
        if (($r = $this->_execute($sql)) === false) {
            return false;
        }

        // check query result
        $this->db->rsRestart();
        $count = $this->db->rsGetCol('COUNT(' . $this->quoteId($cm->getOidColumn()) . ')', 'count');
        if (!is_numeric($count)) {
            return false;
        }

        // return the number of rows found in the class table
        return $count;
    }

    /**
     * Fetchs objects using the variable values specified in epObject
     * If the object is null, get all objects in table.
     * @param epObject $o
     * @param epClassMap
     * @param array (of integer) $oids_ex object ids to be excluded
     * @param array (of integer) $oids_in object ids to be included
     * @param bool $objs convert the rows into objects or leave as uoids
     * @return false|array
     */
    public function &fetch($cm, $o = null, $oids_ex = null, $oids_in = null, $objs = true) {

        // check if class is abstract
        if ($cm->isAbstract()) {
            throw new epExceptionDbObject('Class [' . $cm->getName() . '] is abstract');
            return self::$false;
        }

        // make sure the table is created
        if (!$this->create($cm, false)) {
            return self::$false;
        }

        // preapre sql statement
        if (!($sql = epObj2Sql::sqlSelect($this, $cm, $o, $oids_ex, $oids_in))) {
            return self::$false;
        }

        // execute sql
        if (!$this->_execute($sql)) {
            return self::$false;
        }

        if ($objs) {
            // result conversion
            $r = $this->_rs2obj($cm, $oids_ex);
        } else {
            $r = $this->_rs2uoid($cm, $oids_ex);
        }

        return $r;
    }

    /**
     * Fetchs records using the variable values specified in epObject
     * @param epObject $o
     * @param epClassMap
     * @return bool
     */
    public function insert($cm, $o) {

        // check if class is abstract
        if ($cm->isAbstract()) {
            throw new epExceptionDbObject('Class [' . $cm->getName() . '] is abstract');
            return false;
        }

        // make sure the table is created
        if (!$this->create($cm, false)) {
            return false;
        }

        // update table for last insertion
        $this->table_last_inserted = $cm->getTable();

        // preapre sql statement
        if (!($sql = epObj2Sql::sqlInsert($this, $cm, $o))) {
            return false;
        }

        // execute sql
        return ($r = $this->_execute($sql));
    }

    /**
     * Fetchs records using the variable values specified in epObject
     * @param epObject $o
     * @param epClassMap
     * @return bool
     */
    public function delete($cm, $o) {

        // check if class is abstract
        if ($cm->isAbstract()) {
            throw new epExceptionDbObject('Class [' . $cm->getName() . '] is abstract');
            return false;
        }

        // preapre sql statement
        $sql = epObj2Sql::sqlDelete($this, $cm, $o);
        if (!$sql) {
            return false;
        }

        // execute sql
        return ($r = $this->_execute($sql));
    }

    /**
     * Fetchs records using the variable values specified in epObject
     * @param epObject $o
     * @param epClassMap
     */
    public function update($cm, $o) {

        // check if class is abstract
        if ($cm->isAbstract()) {
            throw new epExceptionDbObject('Class [' . $cm->getName() . '] is abstract');
            return false;
        }

        // preapre sql statement
        $sql = epObj2Sql::sqlUpdate($this, $cm, $o);
        if (!$sql) {
            // no need to update
            return true;
        }

        // execute sql
        return ($r = $this->_execute($sql));
    }

    /**
     * Create a table specified in class map if not exists
     * @param epClassMap $cm The class map
     * @param bool $force Whether to force creating table
     * @return bool
     */
    public function create($cm, $force = false) {

        // check if class is abstract
        if ($cm->isAbstract()) {
            // if so, no need to actually create
            return true;
        }

        // check if table exists
        if (!$force && $this->_tableExists($cm->getTable())) {
            return true;
        }

        // preapre sql statement
        $sql = epObj2Sql::sqlCreate($this, $cm);
        if (!$sql) {
            return false;
        }

        // execute sql
        return ($r = $this->_execute($sql));
    }

    /**
     * Alter a table specified in class map.
     *
     * Depending of config options it run/log/ignore queries.
     * The modification for which it takes care are relative to fields and/or
     * dropping the table
     *
     * @param epClassMap $cm The class map
     * @param array $rtables relation table information for renaming
     * @param boolean $update run queries or log them
     * @param boolean $force Whether to force schema update WARNING!!!
     * @return false|array  of sql to execute and result of operations.
     * @author David Moises Paz <davidmpaz@gmail.com>
     * @version 1.1.6
     */
    public function alter($cm, $rtables = array(), $update = false, $force = false) {

        // we will return array of queries
        $result = array('executed' => array(), 'ignored' => array(), 'sucess' => false);

        //to drop?
        $droping = $cm->getTag(epDbUpdate::SCHEMA_OP_TAG) == epDbUpdate::OP_DROP;

        // force to drop?
        if($force && $droping){
            //marked table for drop, WARN HERE!!
            if(! $sql = epObj2Sql::sqlDrop($this, $cm)){
                return false;
            }

            // @todo check for relations from/to this class

            // return result of executed query or sql generated
            $result['executed'][] = $sql;
            $result['sucess'] = $update ? $this->_execute($sql) : true;
            return $result;
        }

        //not forced, report it!!
        if(!$force && $droping){
            if(! $sql = epObj2Sql::sqlDrop($this, $cm)){
                return false;
            }
            $result['ignored'][] = $sql;
            $result['sucess'] = true;
            return $result;
        }

        // we can continue so prepare sql statement
        $sql = epObj2Sql::sqlAlter($this, $cm, $force);
        if(!$sql || (
            empty($sql[epDbUpdate::OP_ADD]) &&
            empty($sql[epDbUpdate::OP_ALTER]) &&
            empty($sql[epDbUpdate::OP_DROP]) &&
            empty($sql[epDbUpdate::OP_IGNORE]) &&
            empty($sql[epDbUpdate::OP_TABLE]) &&
            empty($sql["index"])) ) {
            return false;
        }

        // rename relationship classes
        foreach ($rtables as $t) {
            $queries = epObj2Sql::sqlRenameRelationshipClass($this,
                $t['old_table'], $t['new_table'],
                $t['old_class'], $t['new_class'] );

            // merge first relation modifications, lastly table renaming
            $sql[epDbUpdate::OP_TABLE] =
                array_merge($queries, $sql[epDbUpdate::OP_TABLE]);
        }

        // eliminate possibles duplicates
        $sql[epDbUpdate::OP_TABLE] = array_unique($sql[epDbUpdate::OP_TABLE]);

        // get the sqls
        $queries = array();
        $queries = empty($sql[epDbUpdate::OP_DROP])
            ? array() : array_merge($queries, $sql[epDbUpdate::OP_DROP]);
        $queries = empty($sql[epDbUpdate::OP_IGNORE])
            ? $queries : array_merge($queries, $sql[epDbUpdate::OP_IGNORE]);

        // here the queries that need to be forced to execute
        $forced = $queries;

        // reinitialize for queries to be executed without forcing
        $queries = array();
        $queries = empty($sql[epDbUpdate::OP_ADD])
            ? array() : array_merge($queries, $sql[epDbUpdate::OP_ADD]);
        $queries = empty($sql[epDbUpdate::OP_ALTER])
            ? $queries : array_merge($queries, $sql[epDbUpdate::OP_ALTER]);
        $queries = empty($sql["index"])
            ? $queries : array_merge($queries, $sql["index"]);
        $queries = empty($sql[epDbUpdate::OP_TABLE])
            ? $queries : array_merge($queries, $sql[epDbUpdate::OP_TABLE]);

        // if not force but $forced queries not empty
        if(!($force || empty($forced))){
            $result['ignored'] = $forced;
        }

        // force and forced queries not empty
        if($force && !empty($forced)){
            //WARN HERE, could be really destructive
            $queries = array_merge($forced, $queries);
            $result['ignored'] = array();
        }

        // report queries
        $result['executed'] = $queries;

        // do we want to run queries or log them
        if(! $update){
            $result['sucess'] = true;
            return $result;
        }

        // do changes atomically
        if(!($in_transact = $this->db->inTransaction())){
            $this->db->beginTransaction();
        }
        try {
            if( !empty($queries) && ($result['sucess'] = $this->_execute( $queries ))){
                // if was in transaction dont commit
                if(! $in_transact){
                    $result['sucess'] = $this->db->commit();
                }
                return $result;
            }

            return $result;

        }catch (Exception $e){
            if(! $in_transact){
                $this->db->rollback();
            }
            throw $e;
            return false;
        }
    }

    /**
     * Drop a table specified in class map if exists
     * @param epClassMap $cm The class map
     * @return bool
     */
    public function drop($cm) {

        // check if class is abstract
        if ($cm->isAbstract()) {
            // if so, no need to actually create
            return true;
        }

        // preapre sql statement
        $sql = epObj2Sql::sqlDrop($this, $cm);
        if (!$sql) {
            return false;
        }

        $this->db->clearTableExists($cm->getTable());

        // execute sql
        // if the table doesn't exist, it will throw an
        // exception which is ok
        try {
            $result = ($r = $this->_execute($sql));
        } catch (Exception $e) {
            return true;
        }

        return $result;
    }

    /**
     * Create indexes and uniques specified in class map
     * @param epClassMap $cm The class map
     * @param bool Whether to force to create table or not
     * @return bool
     */
    public function index($cm, $create = false) {

        // check if class is abstract
        if ($cm->isAbstract()) {
            // if so, no need to actually create
            return true;
        }

        // tabe not exists?
        if (!$this->_tableExists($cm->getTable())) {

            // done if -not- forced to create
            if (!$create) {
                return true;
            }

            // create (includes index creation. done.)
            return $this->create($cm, true);
        }

        // check if index exists already
        if (!($curIndexes = $this->checkIndex($cm))) {
            return false;
        }

        // preapre sql statement for creating index
        $sqls = epObj2Sql::sqlCreateIndex($this, $cm, $curIndexes);

        if (!$sqls) {
            return false;
        }

        // execute sql
        return ($r = $this->_execute($sqls));
    }

    /**
     * Retrieves the current indexes in a given table
     * @param epClassMap $cm The class map
     * @return bool
     */
    protected function checkIndex($cm) {

        // get the portable
        if (!($dbp = & epObj2Sql::getPortable($this->dbType()))) {
            return false;
        }

        // call portable to check index
        return $dbp->checkIndex($cm, $this->db);
    }

    /**
     * Empty a table specified in class map
     * @param epClassMap
     * @return bool
     */
    public function truncate($cm) {

        // check if class is abstract
        if ($cm->isAbstract()) {
            throw new epExceptionDbObject('Class [' . $cm->getName() . '] is abstract');
            return false;
        }

        // preapre sql statement
        $sql = epObj2Sql::sqlTruncate($this, $cm);
        if (!$sql) {
            return false;
        }

        // execute sql
        return ($r = $this->_execute($sql));
    }

    /**
     * Updates relationships for a relationship var
     * @param epClassMap $cm the class map for epObjectRelationship
     * @param string $base_a name of base class a
     * @param string $class_a name of class a
     * @param integer $oid_a object id of the class a object
     * @param integer $var_a the relational field of object a
     * @param string $base_b name of base b
     * @param array $oids_b oids of the class b object related to the class a object
     * @return bool
     */
    public function updateRelationship($cm, $class_a, $var_a, $oid_a, $base_b, $oids_b_new, $oids_b_old) {

        // make sure the table is created
        if (!$this->create($cm, false)) {
            return false;
        }

        // make sql for relationship update
        $sql = epObj2Sql::sqlUpdateRelationship($this, $cm->getTable(), $class_a, $var_a, $oid_a, $base_b, $oids_b_new, $oids_b_old);
        if ($sql === false) {
            return false;
        }

        // execute sql
        $this->_execute($sql);

        return true;
    }

    /**
     * Deletes relationships for an object or a class in relationship
     * @param epClassMap $cm the class map for epObjectRelationship
     * @param string $base_a name of base class a
     * @param integer $oid
     */
    public function deleteRelationship($cm, $class, $oid = null) {

        // make sure the table is created
        if (!$this->create($cm, false)) {
            return false;
        }

        // make sql for relationship update
        $sql = epObj2Sql::sqlDeleteRelationship($this, $cm->getTable(), $class, $oid);
        if (!$sql) {
            return false;
        }

        // execute sql
        $this->_execute($sql);

        return true;
    }

    /**
     * Rename relationships var name for a relationship class map or
     * produce the sql for it.
     *
     * @param epClassMap $cm
     * @param string $rtable
     * @param string $ofname
     * @param string $nfname
     * @author David Moises Paz <davidmpaz@gmail.com>
     * @since 1.1.6
     */
    public function renameRelationship($cm, $rtable, $ofname, $nfname, $force = false) {

        // make sure the table is created
        if (!$this->create($cm, false)) {
            return false;
        }

        // make sql for relationship update
        $sql = epObj2Sql::sqlRenameRelationshipName($this, $rtable, $ofname, $nfname);
        if (!$sql) {
            return false;
        }

        // run query if force, return sql in other case
        return $force ? $this->_execute($sql) : $sql;
    }

    /**
     * Returns the last insert id
     * @param string $oid the oid column
     * @return integer
     * @access public
     */
    public function lastInsertId($oid = 'oid') {
        return $this->db->lastInsertId($this->table_last_inserted, $oid);
    }

    /**
     * Formats input so it can be safely used as a literal
     * Wraps around {@link epDb::quote()}
     * @param mixed $v
     * @param epFieldMap
     * @return mixed
     */
    public function quote($v, $fm = null) {

        // special treatment for blob
        if ($fm) {

            switch ($fm->getType()) {

            case epFieldMap::DT_BOOL:
            case epFieldMap::DT_BOOLEAN:
            case epFieldMap::DT_BIT:
                $v = $v ? 1 : 0;

            case epFieldMap::DT_INT:
            case epFieldMap::DT_INTEGER:
            // date, time, datetime treated as integer
            case epFieldMap::DT_DATE:
            case epFieldMap::DT_TIME:
            case epFieldMap::DT_DATETIME:
                return (integer)$v;

            case epFieldMap::DT_FLOAT:
            case epFieldMap::DT_REAL:
               $v = empty($v) ? '0.0' : $v;
               break;

            case epFieldMap::DT_BLOB:
            //case epFieldMap::DT_TEXT:
                //$v = epStr2Hex($v);
                // fix postgresql issue when storing binary strings into
                // bytea records until a method is added for dealing with it,
                // lets use this
                // @TODO remove this when support for binary string comes in
                return $this->db->quoteBlob($v);
                break;
            }
        }

        return $this->db->quote($v);
    }

    /**
     * Wraps around {@link epDb::quoteId()}
     * Formats a string so it can be safely used as an identifier (e.g. table, column names)
     * @param string $id
     * @return mixed
     */
    public function quoteId($id) {
        return $this->db->quoteId($id);
    }

    /**
     * Set whether to log queries
     * @param boolean $log_queries
     * @return boolean
     */
    public function logQueries($log_queries = true) {
        return $this->db->logQueries($log_queries);
    }

    /**
     * Returns queries logged
     * @return array
     */
    public function getQueries() {
        return $this->db->getQueries();
    }

    /**
     * Calls underlying db to check if table exists. Always returns
     * true if options check_table_exists is set to false
     * @param string $table
     * @return boolean
     */
    protected function _tableExists($table) {

        // if no checking of table existence
        if (!$this->check_table_exists) {
            // always assume table exists
            return true;
        }

        return $this->db->tableExists($table);
    }

    /**
     * Executes multiple db queries
     * @param string|array $sql either a single sql statement or an array of statemetns
     * @return mixed
     */
    protected function _execute($sql) {

        // make sql into an array
        if (!is_array($sql)) {
            $sql = array($sql);
        }

        $r = true;

        // execute sql stmt one by one
        foreach($sql as $sql_) {
            // skip empty queries
            if (!$sql_) {
                continue;
            }

            $r = $this->db->execute($sql_);
        }

        return $r;
    }

    /**
     * Returns the aggregate function result
     * @return integer|float
     * @throws epExceptionDb
     */
    protected function _rs2aggr($aggr_func) {

        // get ready to ready query result
        $this->db->rsRestart();

        // return aggregation 'column'
        $aggr_alt = substr($aggr_func, 0, strpos($aggr_func, '('));
        return $this->db->rsGetCol($aggr_func, $aggr_alt);
    }

    /**
     * Converts the last record set into epObject object(s) with class map
     * @param epClassMap $cm the class map for the conversion
     * @param array (of integers) object ids to be excluded
     * @return false|array (of epObject)
     * @throws epExceptionDbObject
     */
    protected function _rs2obj($cm, $oids_ex = null) {

        // !!!important!!! with a large db, the list of oid to be excluded
        // $oids_ex can grown really large and can significantly slow down
        // queries. so it is suppressed in the select statement and moved
        // to this method to process.

        // get epManager instance and cache it
        if (!$this->ep_m) {
            $this->ep_m = & epManager::instance();
        }

        // get the class name
        $class = $cm->getName();

        // get all mapped vars
        if (!($fms = $cm->getAllFields())) {
            return self::$false;
        }

        // reset counter and return value
        $ret = array();

        // go through reach record
        $okay = $this->db->rsRestart();

        while ($okay) {

            // get oid column
            $oid = $this->db->rsGetCol($cn=$cm->getOidColumn(), $class.'.'.$cn);

            // exclude it?
            if ($oids_ex && in_array($oid, $oids_ex)) {

                // next row
                $okay = $this->db->rsNext();

                // exclude it
                continue;
            }

            // call epManager to create an instance (false: no caching; false: no event dispatching)
            if (!($o = & $this->ep_m->_create($class, false, false))) {
                // next row
                $okay = $this->db->rsNext();
                continue;
            }

            // go through each field
            foreach($fms as $fname => $fm) {

                // skip non-primivite field
                if (!$fm->isPrimitive()) {
                    continue;
                }

                // get var value and set to object
                $val = $this->db->rsGetCol($cn=$fm->getColumnName(),$class.'.'.$cn);

                // set value to var (true: no dirty flag change)
                $o->epSet($fm->getName(), $this->_castType($val, $fm->getType()), true);
            }

            // set oid
            $o->epSetObjectId($oid);

            // collect return result
            $ret[] = $o;

            // next row
            $okay = $this->db->rsNext();
        }

        return $ret;
    }

    /**
     * Converts the last record set into uoids
     * @param epClassMap $cm the class map for the conversion
     * @param array (of integers) object ids to be excluded
     * @return false|array (of uoids)
     * @throws epExceptionDbObject
     */
    protected function _rs2uoid($cm, $oids_ex = null) {

        // !!!important!!! with a large db, the list of oid to be excluded
        // $oids_ex can grown really large and can significantly slow down
        // queries. so it is suppressed in the select statement and moved
        // to this method to process.

        // get the class name
        $class = $cm->getName();

        // reset counter and return value
        $ret = array();

        // go through reach record
        $okay = $this->db->rsRestart();

        while ($okay) {

            // get oid column
            $oid = $this->db->rsGetCol($cn=$cm->getOidColumn(), $class.'.'.$cn);

            // exclude it?
            if ($oids_ex && in_array($oid, $oids_ex)) {

                // next row
                $okay = $this->db->rsNext();

                // exclude it
                continue;
            }

            // get class_b
            $class_b = $this->db->rsGetCol('class_b',$class.'.'.'class_b');

            // get oid_b
            $oid_b = $this->db->rsGetCol('oid_b',$class.'.'.'oid_b');

            // collect return result
            $ret[] = $class_b . ':' . $oid_b;

            // next row
            $okay = $this->db->rsNext();
        }

        return $ret;
    }

    /**
     * Cast type according to field type
     * @param mixed $val
     * @param string $ftype
     * @return mixed (casted value)
     * @access protected
     */
    protected function _castType(&$val, $ftype) {

        switch($ftype) {

            case epFieldMap::DT_BOOL:
            case epFieldMap::DT_BOOLEAN:
            case epFieldMap::DT_BIT:
                $val = (boolean)$val;
                break;

            case epFieldMap::DT_DECIMAL:
            case epFieldMap::DT_CHAR:
            case epFieldMap::DT_CLOB:
                $val = (string)$val;
                break;

            case epFieldMap::DT_BLOB:
            //case epFieldMap::DT_TEXT:
                //$val = (string)epHex2Str($val);
                $val = $this->db->castBlob($val);
                break;

            case epFieldMap::DT_INT:
            case epFieldMap::DT_INTEGER:
                $val = (integer)$val;
                break;

            case epFieldMap::DT_FLOAT:
            case epFieldMap::DT_REAL:
                $val = (float)$val;
                break;

            case epFieldMap::DT_DATE:
            case epFieldMap::DT_TIME:
            case epFieldMap::DT_DATETIME:
                $val = (integer)$val;
                break;
        }

        return $val;
    }
}
