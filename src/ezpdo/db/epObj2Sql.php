<?php

/**
 * $Id: epObj2Sql.php 1044 2007-03-08 02:25:07Z nauhygon $
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

use ezpdo\orm\epFieldMap;

use ezpdo\runtime\epArray;
use ezpdo\runtime\epObject;
use ezpdo\runtime\epManager;

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
     * Makes a SQL alter table statement for a class map
     *
     * @param epDbObject $db the db connection
     * @param epClassMap $ncm the new class map for the object
     * @param boolean $force whether to include queries to update schema ignoring information lost. BE AWARE OF THIS!!
     * @return false|array Array of queries to execute
     * @author David Moises Paz <davidmpaz@gmail.com>
     * @version 1.1.6
     */
    static public function sqlAlter($db, $ncm, $force = false) {

        // get the portable
        if (!($dbp = & epObj2Sql::getPortable($db->dbType()))) {
            return self::$false;
        }

        // array to hold sql stmts
        $sqls = array();
        // call portability object to produce
        $sqls = $dbp->alterTable($ncm, $db, $force);

        // build the CREATE INDEX queries as well
        $indexes = $dbp->createIndex($ncm, $db);

        // build the CREATE UNIQUE INDEX queries as well
        $uniques = $dbp->createUnique($ncm, $db);

        // merge all sql statements
        $sqls["index"] = array_merge(
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
        if (!$rtable || !$class || $oid === false) {
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
     * Rename relationships classes for a relationship
     *
     * Whenever a class name is changed, every relationship
     * with reference to old class name must be updated.
     *
     * @param string $rtable the name of the old relationship table
     * @param string $nrtable the name of the new relationship table
     * @param string $oclass name of the old class name
     * @param string $nclass name of the new class name
     * @return false|array
     */
    static public function sqlRenameRelationshipClass($db, $rtable, $nrtable, $oclass, $nclass) {

        // make sure we have params
        if (!($rtable && $oclass && $nclass) ) {
            return false;
        }

        // quoted ids
        $class_a_q = $db->quoteId('class_a');
        $class_b_q = $db->quoteId('class_b');
        $base_b_q = $db->quoteId('base_b');

        // rename all existing relationships for the table
        $sql = array();
        $sql[] = sprintf('UPDATE %s SET %s = %s WHERE %s = %s',
            $db->quoteId($rtable), $class_a_q, $db->quote($nclass),
            $class_a_q, $db->quote($oclass));
        $sql[] = sprintf('UPDATE %s SET %s = %s WHERE %s = %s',
            $db->quoteId($rtable), $class_b_q, $db->quote($nclass),
            $class_b_q, $db->quote($oclass));
        $sql[] = sprintf('UPDATE %s SET %s = %s WHERE %s = %s',
            $db->quoteId($rtable), $base_b_q, $db->quote($nclass),
            $base_b_q, $db->quote($oclass));
        // rename relationship table itself
        $sql[] = sprintf('ALTER TABLE %s RENAME TO %s',
            $db->quoteId($rtable), $db->quoteId($nrtable));

        return $sql;
    }

    /**
     * Rename relationships var name for a relationship
     *
     * Whenever a var name is changed, every relationship
     * with reference to old var name must be updated.
     *
     * @param string $rtable the name of the relationship table
     * @param string $oclass name of the old class name
     * @param string $nclass name of the new class name
     * @return false|array
     */
    static public function sqlRenameRelationshipName($db, $rtable, $ofname, $nfname) {

        // make sure we have params
        if (!($rtable && $ofname && $nfname) ) {
            return false;
        }

        // quoted ids
        $var_a = $db->quoteId('var_a');

        // rename all existing relationships var name for the table
        $sql = sprintf('UPDATE %s SET %s = %s WHERE %s = %s', $db->quoteId($rtable),
            $var_a, $db->quote($nfname), $var_a, $db->quote($ofname));

        return array($sql);
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
