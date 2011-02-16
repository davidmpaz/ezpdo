<?php

/**
 * $Id: epDbPortPostgres.php 1051 2007-06-20 00:02:31Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1051 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */

/**
 * Class to handle database portability for Postgres
 * 
 * Initially contributed by sbogdan (http://www.ezpdo.net/forum/profile.php?id=34). 
 * Improved by rashid (Robert Janeczek) (http://www.ezpdo.net/forum/profile.php?id=27). 
 * 
 * @author Robert Janeczek <rashid@ds.pg.gda.pl>
 * @author sbogdan <http://www.ezpdo.net/forum/profile.php?id=34>
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * 
 * @version $Revision: 1051 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbPortPostgres extends epDbPortable {

    /**
     * Override {@link epDbPort::createTable()}
     *
     * Generate SQL code to create table
     *
     * @param epClassMap $cm
     * @param string $indent
     * @param epDb $db
     * @return string|array (of strings)
     */
    public function createTable($cm, $db, $indent = '  ') {
       
        // start create table
        $sql = "CREATE TABLE \"" . $cm->getTable() . "\" (";
       
        // the oid field
        $fstr = $this->_defineField($cm->getOidColumn(), 'Integer', '', false, true);
        $sql .= $indent . $fstr . ",";

        // write sql for each field
        foreach($cm->getAllFields() as $fname => $fm) {
            if ($fm->isPrimitive()) {
                // get the field definition
                $fstr = $this->_defineField(
                    $db->quoteId($fm->getColumnName()),
                    $fm->getType(),
                    $fm->getTypeParams(),
                    $fm->getDefaultValue(),
                    false
                    );
                $sql .= $indent . $fstr . ",\n";
            }
        }

        // write unique keys
        //$sql .= $this->_uniqueKeys($cm, $db, $indent);
        
        // write primary key
        $sql .= $indent . "CONSTRAINT ". $cm->getTable() . "_" .$cm->getOidColumn() . " PRIMARY KEY (" .$cm->getOidColumn() .")\n";
       
        // end of table creation 
        // WITH OIDS - see http://www.ezpdo.net/forum/viewtopic.php?pid=750#p750
        $sql .= ") WITH OIDS;\n";
       
        return $sql;
    }

    /**
     * Override {@link epDbPort::checkIndex()}
     * @param epClassMap $cm
     * @param epDb $db
     * @return false|array
     */
    public function checkIndex($cm, $db) {

        // reset counter and return value
        $ret = array(array(), array());

        // get all columns in the pg_attribute and pg_class table
        $sql = 'SELECT a.attname, a.attnum ' . 
            ' FROM pg_attribute a, pg_class c ' . 
            ' WHERE c.relname = ' . $db->quote($cm->getTable()) . 
            ' AND a.attrelid = c.oid AND a.attnum > 0' . 
            ' ORDER BY a.attnum';

        // execute the query
        if (!$db->execute($sql)) {
            return false;
        }

        // array to collect all columns
        $columns = array();
        
        // go through reach record
        $okay = $db->rsRestart();
        while ($okay) {
            $num = $db->rsGetCol('attnum');
            $name = $db->rsGetCol('attname');
            $columns[$num] = $name;
            $okay = $db->rsNext();
        }

        // get all the indexes in the table (indkey has a list, space separated)
        $sql = 'SELECT c2.relname AS indexname, i.indisprimary, i.indisunique, i.indkey AS indkey' .
            ' FROM pg_class c, pg_class c2, pg_index i' . 
            ' WHERE c.relname = '.$db->quote($cm->getTable()) . 
            ' AND c.oid = i.indrelid AND i.indexrelid = c2.oid';

        // execute above query
        if (!$db->execute($sql)) {
            return false;
        }

        // go through reach record
        $okay = $db->rsRestart();
        while ($okay) {
            
            // skip the primary index
            if ($db->rsGetCol('indisprimary') == 't') {
                // next row
                $okay = $db->rsNext();
                continue;
            }

            // get index name
            $name = $db->rsGetCol('indexname');
            $unique = $db->rsGetCol('indisunique');
            
            // $unique is t if unique
            // $unique is f if index
            $unique = ($unique == 't')? 0 : 1;
            $indexes = explode(' ', $db->rsGetCol('indkey'));
            foreach ($indexes as $index) {
                $ret[$unique][$name][] = $columns[$index];
            }
            
            // next row
            $okay = $db->rsNext();
        }
        
        return $ret;
    }

    /**
     * Overrides {@link epDbPort::dropIndex()}
     * Returns the SQL statement to drop an index
     * @param string $name The name of the index
     * @param string $table The name of the table
     * @param epDb $db The underlying database
     */
    public function dropIndex($name, $table, $db) {
        return "DROP INDEX " . $db->quoteId($name);
    }

    /**
     * Override {@link epDbPort::dropTable()}
     * SQL to drop table
     * @param string $table
     * @param epDb $db
     * @return string
     */
    public function dropTable($table, $db) {
        return 'DROP TABLE ' . $db->quoteId($table) . ";\n";
    }

    /**
     * SQL to truncate (empty) table
     * @param string $table
     * @param epDb $db
     * @return string
     */
    public function truncateTable($table, $db) {
        //return 'DELETE FROM ' . $table . " WHERE 1=1;\n";
        return 'TRUNCATE TABLE  ' . $db->quoteId($table) . ";\n";
    }
   
    /**
     * Returns the random function name
     * @return string
     */
    public function randomFunc() {
        return "RANDOM";
    }

    /**
     * Overrides {@link epDbPort::insertValues()}
     * Returns the insert SQL statement
     * 
     * Pgsql does not allow to insert multiple rows in one INSERT
     * statement. So we need to create multiple INSERT statements.
     * 
     * @param string $table
     * @param epDb $db
     * @param array $cols The names of the columns to be inserted 
     * @param array $rows The rows of values to be inserted
     * @return string|array
     */
    public function insertValues($table, $db, $cols, $rows) {
        
        // make insert sql stmt
        $sql_header = 'INSERT INTO ' . $db->quoteId($table) . ' (';

        // get all column names
        $cols_q = array();
        foreach($cols as $col) {
            $cols_q[] = $db->quoteId($col);
        }
        $sql_header .= implode(',',$cols_q) . ') VALUES ';

        // array to hold sql statements
        $sqls = array();

        // collect all sql statements
        foreach($rows as $row) {
            
            // collect all values
            $row_q = array();
            foreach($row as $col_value) {
                $row_q[] = $db->quote($col_value);
            }

            // make one sql statement
            $sqls[] = $sql_header . '(' . implode(',', $row_q) . ')';
        }

        return $sqls;
    }

    /**
     * Override {@link epDbPortable::_defineField}
     *
     * Return column/field definition in CREATE TABLE (called by
     * {@link createTable()})
     *
     * @param string $fname
     * @param string $type
     * @param string $params
     * @param string $default
     * @param bool $autoinc
     * @return false|string
     */
    protected function _defineField($fname, $type, $params = false, $default = false, $autoinc = false, $notnull = false) {
       
        // is it an auto-incremental?
        if ($autoinc) {
            //return $fname . ' INTEGER AUTOINCREMENT';
            return $fname . ' SERIAL NOT NULL ';
        }
        // get field name and type(params)
        $sql = $fname . ' ' . $this->_fieldType($type, $params);
       
        // does the field have default value?
        if ($default) {
            $sql .= ' DEFAULT ' . $default;
        }
       
        return $sql;
    }

    /**
     * Translate EZPDO datatype to the field type
     * @param string $ftype
     * @param string $params
     * @return false|string
     */
    protected function _fieldType($ftype, $params = false) {

        switch($ftype) {

            case epFieldMap::DT_BOOL:
            case epFieldMap::DT_BOOLEAN:
            case epFieldMap::DT_BIT:
                // to simplify things
                return 'numeric(1)';

            case epFieldMap::DT_CHAR:
                // as opposed to 'char' (which has the space-padding problem in pgsql)
                $ftype = 'varchar';
                break;

            case epFieldMap::DT_INT:
            case epFieldMap::DT_INTEGER:
                $ftype = 'numeric';
                if (!$params) {
                    $params = 10;
                }
                break;

            case epFieldMap::DT_FLOAT:
            case epFieldMap::DT_REAL:
            case epFieldMap::DT_DECIMAL:
                $ftype = 'numeric';
                if (!$params) {
                    $params = "10,5";
                }
                break;
            
            case epFieldMap::DT_CLOB:
            case epFieldMap::DT_TEXT:
                // http://www.postgresql.org/docs/8.0/interactive/datatype-character.html
                return 'text';

            case epFieldMap::DT_BLOB:
                // see http://www.postgresql.org/docs/8.0/interactive/datatype-binary.html
                return 'bytea';

            case epFieldMap::DT_DATE:
            case epFieldMap::DT_TIME:
            case epFieldMap::DT_DATETIME:
                // currently date/time/datetime are all mapped to integer
                // this should be changed once we work out unixDate() and
                // dbDate() (as in ADODB)
                return "numeric(16)"; //???

        }

        // concat params
        if ($params) {
            $ftype .= '(' . $params . ')';
        }

        return $ftype;
    }
   
    /**
     * Overrides epDbPortable::_indexName(). 
     * Returns the index name for CREATE INDEX statement
     * @param string $table The table name
     * @param string 
     * @return string 
     */
    protected function _indexName($index_name, $table = false) {
        $_t = ($table[0] == '_') ? '' : '_'; 
        $_i = ($index_name[0] == '_') ? '' : '_';
        return 'idx' . $_t . $table . $_i . $index_name;
    }

    /**
     * SQL to genreate one unique key (called by epDbPortable::_uniqueKeys())
     * @param string $name The name of the key (already quoted)
     * @param array $keys The columns for the key (already quoted) 
     * @return string
     */
    protected function _uniqueKey($name, $keys) {
        return 'CONSTRAINT ' . $name . ' UNIQUE (' . join(', ', $keys) . ')';
    }

}

?>
