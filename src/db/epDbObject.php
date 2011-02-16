<?php

/**
 * $Id: epDbObject.php 1044 2007-03-08 02:25:07Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author Trevan Richins <developer@ckiweb.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */

/**
 * need epClassMap class
 */
include_once(EP_SRC_ORM.'/epClassMap.php');

/**
 * Class of SQL statement generator
 * 
 * This class is responsible for converting class map info 
 * ({@link epClassMap}) into SQL statements
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epObj2Sql {

    /**#@+
     * Used for return value to avoid reference notice in 5.0.x and up
     * @var bool
     */
    static public $false = false;
    static public $true = true;
    static public $null = null;
    /**#@-*/

    /**
     * The cached db portability factory
     * @var epDbPortableFactory
     */
    static public $dbpf = false;
    
    /**
     * Get the portable object
     * @param string $dbtype
     * @return false|epDbPortable
     */
    static public function &getPortable($dbtype) {
        
        // check if we have portability factory cached already
        if (!epObj2Sql::$dbpf) {
            include_once(EP_SRC_DB."/epDbPortable.php");
            epObj2Sql::$dbpf = epDbPortFactory::instance();
        }
        
        // get the portability object for the db
        if (!($dbp = & epObj2Sql::$dbpf->make($dbtype))) {
            return self::$false;
        }

        return $dbp;
    }

    /**
     * Makes a SQL create index and unique statement for a class map
     * @param epDbObject $db the db connection 
     * @param epClassMap the class map for the object
     * @return false|string|array
     */
    static public function sqlCreateIndex($db, $cm, $curIndexes) {
        
        // get the portable
        if (!($dbp = & epObj2Sql::getPortable($db->dbType()))) {
            return false;
        }

        $sqls = array();

        // build the CREATE INDEX queries as well
        $indexes = $dbp->createIndex($cm, $db, $curIndexes[1]);

        // build the CREATE UNIQUE INDEX queries as well
        $uniques = $dbp->createUnique($cm, $db, $curIndexes[0]);

        $sqls = array_merge(
            $sqls, 
            $indexes['drop'], $uniques['drop'], 
            $indexes['create'], $uniques['create']
            );

        return $sqls;
    }

    /**
     * Makes a SQL create table statement for a class map
     * @param epDbObject $db the db connection 
     * @param epClassMap the class map for the object
     * @return false|string|array
     */
    static public function sqlCreate($db, $cm, $indent = '  ') {
        
        // get the portable
        if (!($dbp = & epObj2Sql::getPortable($db->dbType()))) {
            return false;
        }

        // array to hold sql stmts
        $sqls = array();

        // call portability object to produce 
        $sqls[] = $dbp->createTable($cm, $db);

        // build the CREATE INDEX queries as well
        $indexes = $dbp->createIndex($cm, $db);
        
        // build the CREATE UNIQUE INDEX queries as well
        $uniques = $dbp->createUnique($cm, $db);

        // merge all sql statements
        $sqls = array_merge(
            $sqls,
            $indexes['drop'], $uniques['drop'],
            $indexes['create'], $uniques['create']
            );

        return $sqls;
    }
    
    /**
     * Makes a SQL drop table if exists statement for a class map
     * @param epDbObject $db the db connection  
     * @param epClassMap the class map for the object
     * @return string
     */
    static public function sqlDrop($db, $cm) {
        
        // get the portable
        if (!($dbp = & epObj2Sql::getPortable($db->dbType()))) {
            return false;
        }
        
        // call portability object to produce 
        return $dbp->dropTable($cm->getTable(), $db);
    }
    
    /**
     * Makes a SQL truncate table if exists statement for a class map
     * @param epDbObject $db the db connection 
     * @param epClassMap the class map for the object
     * @return string
     */
    static public function sqlTruncate($db, $cm) {
        
        // get the portable
        if (!($dbp = & epObj2Sql::getPortable($db->dbType()))) {
            return false;
        }

        // call portability object to produce 
        return $dbp->truncateTable($cm->getTable(), $db);
    }
    
    /**
     * Makes a SQL count statement to get the total number rows in table 
     * @param epDbObject $db the db connection  
     * @param epClassMap the class map for the object
     * @return string
     */
    static public function sqlCount($db, $cm) {
        return 'SELECT COUNT(' . $db->quoteId($cm->getOidColumn()) . 
            ') FROM ' . $db->quoteId($cm->getTable());
    }

    /**
     * Makes a SQL select statement from object variables
     * If the object is null, select all from table
     * @param epDbObject $db the db connection  
     * @param epObject the object for query
     * @param epClassMap the class map for the object
     * @param array (of integers) $oids_ex object ids to be excluded
     * @param array (of integers) $oids_in object ids to be included
     * @return false|string
     * @author Oak Nauhygon <ezpdo4php@gmail.com>
     * @author Trevan Richins <developer@ckiweb.com>
     */
    static public function sqlSelect($db, $cm, $o = null, $oids_ex = null, $oids_in = null) {
        
        // !!!important!!! with a large db, the list of oid to be excluded
        // $oids_ex can grown really large and can significantly slow down 
        // queries. so it is suppressed and moved to epDbObject::_rs2obj() 
        // to process.
        $oids_ex = null;

        // arrays to collect 'from' and 'where' parts
        $from = array();
        $where = array();
        
        // add table for the object in 'from'
        $from[] = $db->quoteId($cm->getTable());
        
        // if object is specified, recursively collect 'from' and 'where' 
        // for the select statement 
        if ($o) {
            $from_where = epObj2Sql::sqlSelectChildren($db, $o, 1, $cm->getTable());
            $from = array_merge($from, $from_where['from']);
            $where = array_merge($where, $from_where['where']);
        }
        
        // any oids to exclude?
        if ($oids_ex) {
            // add oids to be excluded (shouldn't get here. see comments above.)
            foreach($oids_ex as $oid) {
                $where[] = $db->quoteId($cm->getOidColumn()) . ' <> ' . $db->quote($oid);
            }
        }
        
        // add oids to be included
        if ($oids_in) {
            $_oids_in = array();
            $oid_column = $db->quoteId($cm->getOidColumn());
            foreach($oids_in as $oid) {
                $_oids_in[] = $oid_column . ' = ' . $db->quote($oid);
            }
            $where[] = '(' . join(' OR ', $_oids_in) . ')';
        }
        
        // columns to be selected (*: all of them)
        $columns = $db->quoteId($cm->getTable() . '.*');
        
        // assemble the select statement
        return epObj2Sql::_sqlSelectAssemble($columns, $from, $where);
    }

    /**
     * Assemble a select statement from parts. 
     * Note identifiers and values in all parts should have been properly quoted.
     * @param string|array $columns 
     * @param array $from from expressions
     * @param array $where where expressions ('1=1' if empty) 
     * @return string 
     */
    static protected function _sqlSelectAssemble($columns, $from, $where = array()) {
        
        // the columns clause
        $columns = is_array($columns) ? implode(' ', $columns) : $columns;
        
        // the from caluse
        $from = implode(', ', $from);
        
        // the where clause
        $where = $where ? implode(' AND ', $where) : '1=1';
        
        // put them together
        return 'SELECT '.$columns.' FROM '.$from.' WHERE '.$where;
    }

    /**
     * Make where part of a SQL select to get children values
     * @param epDbObject $db the db connection  
     * @param epObject $o the child object for query
     * @param int $depth how many children down we are
     * @param string $parent the parent of this child
     * @return array('from', 'where')
     * @author Oak Nauhygon <ezpdo4php@gmail.com>
     * @author Trevan Richins <developer@ckiweb.com>
     */
    static public function sqlSelectChildren($db, $o, $depth, $parent) {
        
        // array to keep new tables in 'from'
        $from = array();

        // array to keep new expression in 'where'
        $where = array();
        
        // get the class map for the child object
        $cm = $o->epGetClassMap();
        
        // get all vars in the object
        $vars = $o->epGetVars();
        
        // if object has oid, select use oid
        if ($oid = $o->epGetObjectId()) {
            $where[] = $db->quoteId($cm->getOidColumn()) . ' = ' . $oid; 
            return array('from'=>$from, 'where'=>$where);
        }
        
        // mark child object under search (to avoid loops)
        $o->epSetSearching(true);

        // total number of vars (primitive or non-primitive) collected
        $n = 0;

        // new depth
        $depth ++;

        // number of non-primitive (relationship) fields collected 
        $nprim_id = 0;

        // loop through vars
        while (list($var, $val) = each($vars)) { 
            
            // get field map
            if (!($fm = & $cm->getField($var))) {
                // should not happen
                continue;
            }
            
            // exclude null values (including empty strings)
            if (is_null($val) || (!$val && $fm->getType() == epFieldMap::DT_CHAR)) {
                continue;
            }
            
            // is it a primitive var?
            if ($fm->isPrimitive()) {
                $where[] = $db->quoteId($parent) . '.' . $db->quoteId($fm->getColumnName()) . ' = ' . $db->quote($val, $fm); 
                // done for this var
                $n ++;
                continue;
            }

            // okay we are dealing with a non-primitive (relationship) var
            if ($val instanceof epArray) {

                foreach ($val as $obj) {

                    // skip object that is under searching
                    if (!$obj || $obj->epIsSearching()) {
                        continue;
                    }
                    
                    // get 'where' and 'from' from relationship 
                    $from_where = epObj2Sql::sqlSelectRelations(
                        $db, $fm, $cm, $obj->epGetClassMap()->getTable(), 
                        $depth.$nprim_id, $parent
                        );
                    $where = array_merge($where, $from_where['where']);
                    $from = array_merge($from, $from_where['from']);

                    // get 'where' and 'from' from relationship 
                    $from_where = epObj2Sql::sqlSelectChildren($db, $obj, $depth, '_'.$depth.$nprim_id);
                    $where = array_merge($where, $from_where['where']);
                    $from = array_merge($from, $from_where['from']);
                    
                    $nprim_id++;
                }

            } else if ($val instanceof epObject && !$val->epIsSearching()) {
                
                // get 'where' and 'from' from relationship 
                $from_where = epObj2Sql::sqlSelectRelations(
                    $db, $fm, $cm, $val->epGetClassMap()->getTable(), 
                    $depth.$nprim_id, $parent
                    );
                $where = array_merge($where, $from_where['where']);
                $from = array_merge($from, $from_where['from']);

                // get 'where' and 'from' from relationship 
                $from_where = epObj2Sql::sqlSelectChildren($db, $val, $depth, '_'.$depth.$nprim_id);
                $where = array_merge($where, $from_where['where']);
                $from = array_merge($from, $from_where['from']);

                $nprim_id++;
            }

            $n ++;
        } 
        
        // reset search flag on child object
        $o->epSetSearching(false);

        return array('from' => $from, 'where' => $where);
    }

    /**
     * Make where part of a SQL select for relationship fields
     * @param epDbObject $db the db connection  
     * @param epFieldMap $fm the field map
     * @param epClassMap $cm the child object for query
     * @param string $alias the alias of this table in the previous part
     * @return array('from', 'where')
     * @author Oak Nauhygon <ezpdo4php@gmail.com>
     * @author Trevan Richins <developer@ckiweb.com>
     */
    static public function sqlSelectRelations($db, $fm, $cm, $table, $alias, $parentTable) {

        $base_a = $fm->getBase_a();
        $class_a = $cm->getName();
        $var_a = $fm->getName();
        $base_b = $fm->getBase_b();

        // call manager to get relation table for base class a and b
        $rt = epManager::instance()->getRelationTable($base_a, $base_b);
        
        // the alias of the table we are dealing with right now
        $tbAlias = '_'.$alias;
        $rtAlias = 'rt'.$alias;
        
        // quoted aliases (avoid repeating)
        $tbAlias_q = $db->quoteId($tbAlias);
        $rtAlias_q = $db->quoteId($rtAlias);
        
        // compute 'from' parts: tables with aliases
        $from = array();
        $from[] = $db->quoteId($table) . ' AS '.$tbAlias_q;
        $from[] = $db->quoteId($rt) . ' AS '.$rtAlias_q;

        // compute expressions 'where'
        $where = array();
        
        // rt.class_a = 
        $where[] = $rtAlias_q.'.'.$db->quoteId('class_a').' = '.$db->quote($class_a);
        
        // rt.var_a = 
        $where[] = $rtAlias_q.'.'.$db->quoteId('var_a').' = '.$db->quote($var_a);
        
        // rt.base_b =
        $where[] = $rtAlias_q.'.'.$db->quoteId('base_b').' = '.$db->quote($base_b);
        
        // rt.class_b =  TODO: doesn't look like it is used
        //$where .= 'rt.'.$db->quoteId('class_b') . ' = ' . $db->quote($val->getClass());
        
        // A.oid = rt.oid_a
        $where[] = $db->quoteId($parentTable).'.'.$db->quoteId($cm->getOidColumn()).' = ' . $rtAlias_q.'.'.$db->quoteId('oid_a');
        
        // Child.oid = rt.oid_b
        $where[] = $tbAlias_q.'.'.$db->quoteId($fm->getClassMap()->getOidColumn()).' = ' . $rtAlias_q.'.'.$db->quoteId('oid_b');
        
        return array('from' => $from, 'where' => $where);
    }
    
    /**
     * Make a SQL insert statement from object variables
     * @param epDbObject $db the db connection 
     * @param epObject the object for query
     * @param epClassMap the class map for the object
     * @return false|string
     */
    static public function sqlInsert($db, $cm, $o) {
        
        // get all vars
        if (!($vars = $o->epGetVars())) {
            return false;
        }
        
        // make select statement
        $sql = 'INSERT INTO ' . $db->quoteId($cm->getTable()) . ' (' ; 

        // get column names
        $i = 0; 
        foreach ($vars as $var => $val) {
            
            // exclude 'oid'
            if ($var == 'oid') {
                continue;
            }

            // shouldn't happen
            if (!($fm = $cm->getField($var))) {
                continue;
            }
            
            // exclude non-primitive fields
            if (!$fm->isPrimitive()) {
                continue;
            }
            
            $sql .= $db->quoteId($fm->getColumnName()) . ', '; 
            
            $i ++;
        } 
        
        // no need to insert if we don't have any var to insert
        if ($i == 0) {
            $sql .= $db->quoteId($cm->getOidColumn()) . ') VALUES (' . $db->quote('', $fm) . ');';
            return $sql;
        }
        
        // remove the last ', '
        if ($i > 0) {
            $sql = substr($sql, 0, strlen($sql) - 2);
        } 
        
        $sql .= ') VALUES ('; 
        
        // get values
        $i = 0;
        foreach ($vars as $var => $val) {
            
            // exclude 'oid'
            if ($var == 'oid') {
                continue;
            }

            if (!($fm = & $cm->getField($var))) {
                continue;
            }
            
            // exclude non-primitive fields
            if (!$fm->isPrimitive()) {
                continue;
            }
            
            // get quoted field value
            $sql .= $db->quote($val, $fm) . ', '; 
            
            ++ $i;
        }   
        
        // remove the last ', '
        if ($i > 0) {
            $sql = substr($sql, 0, strlen($sql) - 2);
        }
        
        // end of statement
        $sql .= ');'; 
        
        return $sql;
    }
    
    /**
     * Make a SQL delete statement from object variables
     * @param epDbObject $db the db connection 
     * @param epObject the object for query
     * @param epClassMap the class map for the object
     * @return false|string
     */
    static public function sqlDelete($db, $cm, $o) {
        
        // get all vars
        $vars = $o->epGetVars();
        if (!$vars) {
            return false;
        }
        
        // delete row with the object id
        $sql = 'DELETE FROM ' . $db->quoteId($cm->getTable()) . ' WHERE ' . $db->quoteId($cm->getOidColumn()) . ' = ' . $o->epGetObjectId();
        
        return $sql;
    }
    
    /**
     * Make a SQL update statement from object variables
     * @param epObject the object for query
     * @param epClassMap the class map for the object
     * @return false|string
     */
    static public function sqlUpdate($db, $cm, $o) {
        
        // get the modified vars
        $vars = $o->epGetModifiedVars(epObject::VAR_PRIMITIVE);
        if (!$vars) {
            return false;
        }

        $sql = 'UPDATE ' . $db->quoteId($cm->getTable()) . ' SET '; 
        $i = 0; 
        while (list($var, $val) = each($vars)) { 
            
            // get field map
            if (!($fm = & $cm->getField($var))) {
                // should not happen
                continue;
            }
            
            // exclude 'oid'
            if ($fm->getName() == 'oid') {
                continue;
            }
            
            // exclude non-primitive fields
            if (!$fm->isPrimitive()) {
                continue;
            }
            
            // get column name
            $sql .= $db->quoteId($fm->getColumnName()) . '=' . $db->quote($val, $fm) . ', '; 
            
            $i ++;
        } 
        
        if ($i == 0) {
            return false;
        }
        
        // remove the last ', '
        if ($i > 0) {
            $sql = substr($sql, 0, strlen($sql) - 2);
        }
        
        $sql .= ' WHERE ' . $db->quoteId($cm->getOidColumn()) . ' = ' . $o->epGetObjectId(); 
        
        return $sql; 
    }

    /**
     * Update relationships for a relationship var
     * 
     * @param string $rtable the name of the relationship table
     * @param string $base_a name of base class a
     * @param string $class_a name of class a
     * @param integer $oid_a object id of the class a object
     * @param integer $var_a the relational field of object a
     * @param string $base_b name of base b
     * @param array $oids_b_new oids of the class b object related to the class a object that are new
     * @param array $oids_b_old oids of the class b object related to the class a object that are old
     * 
     * @return false|array
     */
    static public function sqlUpdateRelationship($db, $rtable, $class_a, $var_a, $oid_a, $base_b, $oids_b_new, $oids_b_old) {
        
        // make sure we have params (except $oids_b) not empty
        if (!$rtable || !$class_a || !$var_a|| !$oid_a || !$base_b) {
            return false;
        }

        $sql_del = '';
        if (is_null($oids_b_old) || (is_array($oids_b_old) && count($oids_b_old) > 0)) {
            // delete all existing relationships
            $sql_del = 'DELETE FROM ' . $db->quoteId($rtable) . ' WHERE ';
            $sql_del .= $db->quoteId('class_a') . '=' . $db->quote($class_a) . ' AND ';
            $sql_del .= $db->quoteId('var_a') . '=' . $db->quote($var_a) . ' AND ';
            $sql_del .= $db->quoteId('oid_a') . '=' . $db->quote($oid_a) . ' AND ';
            $sql_del .= $db->quoteId('base_b') . '=' . $db->quote($base_b);
            if (count($oids_b_old) > 0) {
                $sql_del .= ' AND ' . $db->quoteId('oid_b') . ' IN (' . join(',', $oids_b_old) . ')';
            }
        }

        // done if we don't have oids_b to insert
        if (!$oids_b_new) {
            return $sql_del;
        }

        // get the portable
        if (!($dbp = & epObj2Sql::getPortable($db->dbType()))) {
            return false;
        }

        // columns for a relationship
        $cols = array('class_a', 'var_a', 'oid_a', 'base_b', 'class_b', 'oid_b');

        // the common values for a row
        $common = array($class_a, $var_a, $oid_a, $base_b);

        // rows to be inserted
        $rows = array();
        foreach($oids_b_new as $oid_b) {
            $row = $common;
            $row[] = $oid_b['class'];
            $row[] = $oid_b['oid'];
            $rows[] = $row;
        }
        
        // call portability object to create sql insert stmt
        $sql_ins = $dbp->insertValues($rtable, $db, $cols, $rows);
        if (is_array($sql_ins)) {
            // prepend the delete stmt
            array_unshift($sql_ins, $sql_del);
            return $sql_ins;
        }
        
        return array($sql_del, $sql_ins);
    }
    
    /**
     * Update relationships for a relationship var
     * @param string $rtable the name of the relationship table
     * @param string $class name of class a
     * @param string $oid name of oid
     * @return false|array
     */
    static public function sqlDeleteRelationship($db, $rtable, $class, $oid) {
        
        // make sure we have params (except $oids_b) not empty
        if (!$rtable || !$class) {
            return false;
        }

        // quoted ids
        $class_a_q = $db->quoteId('class_a');
        $oid_a_q = $db->quoteId('oid_a');
        $class_b_q = $db->quoteId('class_b');
        $oid_b_q = $db->quoteId('oid_b');
        
        // delete all existing relationships
        $sql  = 'DELETE FROM ' . $db->quoteId($rtable) . ' WHERE (';
        $sql .= $class_a_q . '=' . $db->quote($class);
        if ($oid) {
            $sql .= ' AND ' . $oid_a_q . '=' . $db->quote($oid);
        }
        $sql .= ') OR (';
        $sql .= $class_b_q . '=' . $db->quote($class);
        if ($oid) {
            $sql .= ' AND ' . $oid_b_q . '=' . $db->quote($oid);
        }
        $sql .= ')';
        
        return $sql;
    }

    /**
     * Makes a SQL comment for a table (class map)
     * @param epClassMap the class map for the object
     * @return string
     */
    static public function sqlTableComments($db, $cm) {
        $sql = "\n";
        $sql .= "-- \n";
        $sql .= "-- Table for class " . $cm->getName() . "\n";
        $sql .= "-- Source file: " . $cm->getClassFile() . "\n";
        $sql .= "-- \n\n";
        return $sql;
    }
    
    /**
     * Returns the random function
     * @param epDbObject $db the db connection 
     * @return false|string
     */
    static public function sqlRandom($db) {
        
        // get the portable
        if (!($dbp = & epObj2Sql::getPortable($db->dbType()))) {
            return false;
        }

        // call portability object to get random function
        return $dbp->randomFunc();
    }
}

/**
 * Exception class for {@link epDbObject}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */
class epExceptionDbObject extends epException {
}

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
            $va = epArrayGet($a, $path);
            $vb = epArrayGet($b, $path);
            
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
    public function fetch($cm, $o = null, $oids_ex = null, $oids_in = null, $objs = true) {

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
            if (!$force) {
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
                $v = epStr2Hex($v);
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
                $val = (string)epHex2Str($val);
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

/**
 * Exception class for {@link epDbFactory}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db 
 */
class epExceptionDbFactory extends epException {
}

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
                include_once(EP_SRC_DB.'/epDbAdodb.php'); 
                $this->dbs[$dsn] = new epDbObject(new epDbAdodb($dsn));
                break;
        
            case self::DBL_ADODB_PDO:
                include_once(EP_SRC_DB.'/epDbAdodbPdo.php'); 
                $this->dbs[$dsn] = new epDbObject(new epDbAdodbPdo($dsn));
                break;
        
            case self::DBL_PEARDB:
                include_once(EP_SRC_DB.'/epDbPeardb.php');
                $this->dbs[$dsn] = new epDbObject(new epDbPeardb($dsn));
                break;

            case self::DBL_PDO:
                include_once(EP_SRC_DB.'/epDbPdo.php');
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

?>
