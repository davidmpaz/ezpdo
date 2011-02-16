<?php

/**
 * $Id: epDbPortSqlite.php 1030 2007-01-19 10:38:55Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1030 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */

/**
 * Class to handle database portability for Sqlite
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1030 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbPortSqlite extends epDbPortable {

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
        $sql = "CREATE TABLE " . $db->quoteId($cm->getTable()) . " (\n";
        
        // the oid field
        $fstr = $this->_defineField($db->quoteId($cm->getOidColumn()), 'INTEGER', '12', false, true);
        $sql .= $indent . $fstr . ",\n";

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
        
        // remove the last ','
        $sql = substr($sql, 0, strlen($sql) - 2);

        // end of table creation
        $sql .= ");\n";
        
        return $sql;
    }

    /**
     * Override {@link epDbPort::checkIndex()}
     * @param epClassMap $cm
     * @param epDb $db
     * @return false|array
     */
    public function checkIndex($cm, $db) {

        // get index list
        $sql = 'PRAGMA index_list('.$db->quoteId($cm->getTable()).')';
        if (!$db->execute($sql)) {
            return false;
        }

        // reset counter and return value
        $ret = array(array(), array());
        $indexes = array();
        $uniques = array();

        // go through reach record
        $okay = $db->rsRestart();
        while ($okay) {

            // get index name
            $name = $db->rsGetCol('name');
            $unique = $db->rsGetCol('unique');
            // $unique is 1 if unique
            // $unique is 0 if index
            $unique = !$unique; // Invert the unique to match the other databases

            // store the index name for further information
            $indexes[] = $name;
            $uniques[$name] = $unique;

            $ret[$unique][$name] = array();

            // next row
            $okay = $db->rsNext();
        }

        // go through each index
        foreach ($indexes as $index) {
            
            $sql = 'PRAGMA index_info('.$db->quoteId($index).')';
            if (!$db->execute($sql)) {
                return false;
            }

            // go through reach record
            $okay = $db->rsRestart();
            
            while ($okay) {
                // get index name
                $column = $db->rsGetCol('name');
                $ret[$uniques[$index]][$index][] = $column;
                // next row
                $okay = $db->rsNext();
            }
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
        return 'DELETE FROM  ' . $db->quoteId($table) . " WHERE 1;\n";
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
     * Returns the insert SQL statement. 
     * 
     * Sqlite does not allow to insert multiple rows in one INSERT
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
            return $fname . ' INTEGER PRIMARY KEY';
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
     * Overrides epDbPortable::_indexName(). 
     * Returns the index name for CREATE INDEX statement
     * 
     * Note that SQLITE keeps all indices in a master table so the
     * table name to be indexed must be appended.
     * 
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
