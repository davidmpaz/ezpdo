<?php

/**
 * $Id: epDbPortable.php 992 2006-06-01 11:04:15Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 992 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */

/**
 * Need field type definition
 */
include_once(EP_SRC_ORM . '/epClassMap.php');

/**
 * Class to handle database portability
 * 
 * This class takes care of the database portability issues. 
 * Databases of different vendors are notoriously known 
 * not to follow the standard ANSI SQL. This class provides 
 * methods to resolve differences among different SQLs. 
 * 
 * This class is by no means a panacea to all the portability
 * issues, but only those concern EZPDO. And the class is 
 * designed to be "static" and interaction with the database 
 * is limited to, for example, meta info retrievals. All 'actual' 
 * database interactions (queries) are done through {@link epDb}. 
 * 
 * Most ideas are from ADODb author John Lim's blog 
 * ({@link http://phplens.com/phpeverywhere/?q=node/view/177}) 
 * and the slides from PEAR:DB's author Daniel Convissor 
 * ({@link http://www.analysisandsolutions.com/presentations/portability/slides/toc.htm}). 
 * Credits are due to the two development teams. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 992 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbPortable {

    /**
     * Generate SQL code to create table
     * 
     * Sometimes extra procedure may be needed for some fields to work. 
     * For example, some database does not have a direct auto-incremental 
     * keyword and, to make it work, you need extra procedure after 
     * creating the table. Here is an example, for a table column in 
     * an Oracle database to be auto-incremental, we need to insert 
     * a sequence and a trigger (See this link for more info 
     * {@link http://webxadmin.free.fr/article.php?i=134}). If this is 
     * the case the subclass should override this method and return both
     * "create table" statement and the extra.
     * 
     * @param epClassMap $cm
     * @param epDb $db
     * @param string $indent
     * @return string|array (of strings)
     */
    public function createTable($cm, $db, $indent = '  ') {
        
        // start create table
        $sql = "CREATE TABLE " . $db->quoteId($cm->getTable()) . " (\n";
        
        // the oid field
        $fstr = $this->_defineField($db->quoteId($cm->getOidColumn()), 'integer', '12', false, true);
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
        
        // write primary key
        $sql .= $indent . "PRIMARY KEY (" . $db->quoteId($cm->getOidColumn()) . ")\n";
        
        // end of table creation
        $sql .= ");\n";
        
        return $sql;
    }

    /**
     * Checks index in database table
     * @param epClassMap $cm
     * @param epDb $db
     * @return false|array
     */
    public function checkIndex($cm, $db) {

        // show all keys in the table
        $sql = 'SHOW KEYS FROM '.$db->quoteId($cm->getTable());
        if (!$db->execute($sql)) {
            return false;
        }

        // reset return array
        $ret = array(array(), array());
        
        // go through reach record
        $okay = $db->rsRestart();
        while ($okay) {

            // get index name
            $name = $db->rsGetCol('Key_name');

            // skip the primary key
            if ($name == 'PRIMARY') {
                // next row
                $okay = $db->rsNext();
                continue;
            }

            // get the column name
            $column = $db->rsGetCol('Column_name');

            // get if it is unique or not
            $unique = $db->rsGetCol('Non_unique');
            // $unique is 1 if an index
            // $unique is 0 if unique

            // collect return result
            $ret[$unique][$name][] = $column;

            // next row
            $okay = $db->rsNext();
        }

        return $ret;
    }

    /**
     * SQL to create indexes
     * @param epClassMap $cm
     * @return string
     */
    public function createIndex($cm, $db, $curIndex = array()) {
        
        // reset return array (sql stmts to drop and create indexes)
        $sqls = array('drop' => array(), 'create' => array());

        foreach ($cm->getIndexKeys() as $name => $key) {

            $indexname = $this->_indexName($name, $cm->getTable());

            if (in_array($indexname, array_keys($curIndex))) {

                // check to see if the columns are the same
                if (count($key) == count($curIndex[$indexname]) 
                    && count(array_diff($key, $curIndex[$indexname]) == 0)) {
                    unset($curIndex[$indexname]);
                    continue;
                }

                unset($curIndex[$indexname]);

                // otherwise, we need to drop the index
                $sql = $this->dropIndex($indexname, $cm->getTable(), $db);

                $sqls['drop'][] = $sql;
            }
            
            // quote keys
            foreach($key as $k => $v) {
                $key[$k] = $db->quoteId($v);
            }

            // make CREATE INDEX stmt
            $sql  = "CREATE INDEX ";
            $sql .= $db->quoteId($indexname);
            $sql .= " ON "; 
            $sql .= $db->quoteId($cm->getTable()) . " (" . join(', ', $key) . ");";
            
            // collect stmt
            $sqls['create'][] = $sql;
        }

        // drop all the old ones that weren't messed with above
        foreach ($curIndex as $name => $key) {

            // otherwise, we need to drop the index
            $sql = $this->dropIndex($name, $cm->getTable(), $db);

            $sqls['drop'][] = $sql;
        }

        return $sqls;
    }

    /**
     * SQL to create uniques
     * @param epClassMap $cm
     * @return string
     */
    public function createUnique($cm, $db, $curIndex = array()) {

        $sqls = array('drop' => array(), 'create' => array());

        foreach ($cm->getUniqueKeys() as $name => $key) {

            $indexname = $this->_indexName($name, $cm->getTable());

            if (in_array($indexname, array_keys($curIndex))) {

                // check to see if the columns are the same
                if (count($key) == count($curIndex[$indexname]) 
                    && count(array_diff($key, $curIndex[$indexname]) == 0)) {
                    unset($curIndex[$indexname]);
                    continue;
                }

                unset($curIndex[$indexname]);

                // otherwise, we need to drop the index
                $sql = $this->dropIndex($indexname, $cm->getTable(), $db);

                $sqls['drop'][] = $sql;
            }
            
            // quote keys
            foreach($key as $k => $v) {
                $key[$k] = $db->quoteId($v);
            }

            // make CREATE UNIQUE INDEX stmt
            $sql  = "CREATE UNIQUE INDEX ";
            $sql .= $db->quoteId($indexname);
            $sql .= " ON "; 
            $sql .= $db->quoteId($cm->getTable()) . " (" . join(', ', $key) . ");";
            
            // collect stmt
            $sqls['create'][] = $sql;
        }

        // drop all the old ones
        // that weren't messed with above
        foreach ($curIndex as $name => $key) {

            // otherwise, we need to drop the index
            $sql = $this->dropIndex($name, $cm->getTable(), $db);

            $sqls['drop'][] = $sql;
        }

        return $sqls;
    }

    /**
     * Returns the SQL statement to drop an index
     * @param string $name The name of the index
     * @param string $table The name of the table
     * @param epDb $db The underlying database
     */
    public function dropIndex($indexname, $table, $db) {
        return "DROP INDEX " . $db->quoteId($indexname) . " ON " . $db->quoteId($table);
    }

    /**
     * SQL to drop table 
     * @param string $table
     * @param epDb $db
     * @return string
     */
    public function dropTable($table, $db) {
        return 'DROP TABLE IF EXISTS ' . $db->quoteId($table) . ";\n";
    }
    
    /**
     * SQL to truncate (empty) table 
     * @param string $table
     * @param epDb $db
     * @return string
     */
    public function truncateTable($table, $db) {
        return 'TRUNCATE TABLE  ' . $db->quoteId($table) . ";\n";
    }

    /**
     * Returns the random function name
     * @return string
     */
    public function randomFunc() {
        return "RAND";
    }

    /**
     * Returns the insert SQL statement
     * 
     * By default, we put multiple rows into one INSERT statements.
     * This is allowed by databases like MySQL, but not SQlite or
     * Pgsql.
     * 
     * @param string $table
     * @param epDb $db
     * @param array $cols The column names for insert values
     * @param array $values The 2-d array of insert values
     * @return string|array
     */
    public function insertValues($table, $db, $cols, $rows) {
        
        // make insert sql stmt
        $sql = 'INSERT INTO ' . $db->quoteId($table) . ' (';

        // get all column names
        $cols_q = array();
        foreach($cols as $col) {
            $cols_q[] = $db->quoteId($col);
        }
        $sql .= implode(',',$cols_q) . ') VALUES ';

        // qutoe all row values
        $rows_q = array();
        foreach($rows as $row) {
            $row_q = array();
            foreach($row as $col_value) {
                $row_q[] = $db->quote($col_value);
            }
            $rows_q[] = '(' . implode(',', $row_q) . ')';
        }

        // assemble all values
        $sql .= implode(',', $rows_q);
        
        return $sql;
    }

    /**
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
        
        // get field name and type(params)
        $sql = $fname . ' ' . $this->_fieldType($type, $params);
        
        // does the field have default value?
        if ($default) {
            $sql .= ' DEFAULT ' . $default;
        }
        
        // is it not null?
        if ($notnull || $autoinc) {
            $sql .= ' NOT NULL';
        }
        
        // is it an auto-incremental?
        if ($autoinc) {
            $sql .= ' AUTO_INCREMENT';
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
                // let's make it simple - one-byte integer
                // return "int(1)";
                return "boolean";

            case epFieldMap::DT_DATE:
            case epFieldMap::DT_TIME:
            case epFieldMap::DT_DATETIME:
                // currently date/time/datetime are all mapped to integer 
                // this should be changed once we work out unixDate() and 
                // dbDate() (as in ADODB)
                return "int(16)";

            case epFieldMap::DT_CHAR:
                // data type char is mapped to varchar for space saving 
                if (!$params) {
                    $params = '255';
                }
                return 'varchar(' . $params . ')';

            case epFieldMap::DT_CLOB:
                $ftype = epFieldMap::DT_TEXT;

            default:
                // concat params
                if ($params) {
                    $ftype .= '(' . $params . ')';
                }
        }

        return $ftype;
    }

    /**
     * SQL to create unique keys
     * @param epClassMap $cm
     * @param epDb $db
     * @param string $indent
     * @return string
     */
    protected function _uniqueKeys($cm, $db, $indent = '  ') {
        $sql = '';
        foreach ($cm->getUniqueKeys() as $name => $key) {
            // quote keys
            foreach($key as $k => $v) {
                $key[$k] = $db->quoteId($v);
            }
            // get stmt for this key
            $sql .= $indent . $this->_uniqueKey($db->quoteId($name), $key) . ",\n";
        }
        return $sql;
    }

    /**
     * SQL to genreate one unique key
     * @param string $name The name of the key (already quoted)
     * @param array $keys The columns for the key 
     * @return string
     */
    protected function _uniqueKey($name, $keys) {
        return 'UNIQUE ' . $name . ' (' . join(', ', $keys) . ')';
    }

    /**
     * Returns the index name for CREATE INDEX statement
     * @param string $index_name The name of the index
     * @param string $table The table name
     * @return string 
     */
    protected function _indexName($index_name, $table = false) {
        $_i = ($index_name[0] == '_') ? '' : '_';
        return 'idx' . $_i . $index_name;
    }

}

/**
 * Exception class for epDbPortFactory
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 992 $ $Date: 2006-06-01 07:04:15 -0400 (Thu, 01 Jun 2006) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epExceptionDbPortFactory extends epException {
}

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
            $dbp = new epDbPortable;
        } else {
            include_once($port_class_file);
            $dbp = new $port_class;
        }
        
        // check if portability object is created successfully
        if (!$dbp) {
            throw new epExceptionDbPortFactory('Cannot instantiate portability class for [' . $dbType . ']');
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

?>
