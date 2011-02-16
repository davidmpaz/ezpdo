<?php

/**
 * $Id: epDb.php 1044 2007-03-08 02:25:07Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */

/**
 * need epBase
 */
include_once(EP_SRC_BASE.'/epBase.php');

/**
 * Exception class for {@link epDb}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db 
 */
class epExceptionDb extends epException {
}

/**
 * Class for a database connection
 * 
 * This abstract class provides a unified layer for using different 
 * database abstract libraries. Subclasses should implement abstract
 * method defined here. 
 * 
 * Note that this layer does not concern any object persistence 
 * issues, all of which are taken care of by {@link epDbObject} (a 
 * wrapper around this class). Also note that we DO NOT deal with 
 * portability issues in this class. Portabilty issues are dealt 
 * with in {@link epDbPort}.
 * 
 * This class provides a generic interface only for establishing/
 * tearing down connection, executing standard SQL queries, tracking 
 * insert ids, quoting values/identifiers, etc. 
 * 
 * For now, we support both ADODB ({@link epDbAdodb}) and PEAR::DB 
 * ({@link epDbPeardb}). Experimentally, we now also support PDO 
 * ({@link http://www.php.net/manual/en/ref.pdo.php}) either through 
 * the PDO driver in ADODB (see {@link epDbAdodbPdo}) or directly 
 * (see {@link epDbPdo}). 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1044 $ $Date: 2007-03-07 21:25:07 -0500 (Wed, 07 Mar 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
abstract class epDb {
    
    /**#@+
     * Types of databases 
     */
    const EP_DBT_ACCESS   = 'Access';
    const EP_DBT_DB2      = 'DB2';
    const EP_DBT_FIREBIRD = 'Firebird';
    const EP_DBT_IBASE    = 'Ibase';
    const EP_DBT_INFORMIX = 'Informix';
    const EP_DBT_MSSQL    = 'Mssql';
    const EP_DBT_MYSQL    = 'Mysql';
    const EP_DBT_OCI8     = 'Oci8';
    const EP_DBT_POSTGRES = 'Postgres';
    const EP_DBT_SAPDB    = 'Sapdb';
    const EP_DBT_SQLITE   = 'Sqlite';
    const EP_DBT_SYBASE   = 'Sybase';
    /**#@-*/

    /**
     * Name quotes for different db types
     * @var array
     */
    static protected $nameQuotes = array(
        epDb::EP_DBT_MYSQL    => '`', 
        epDb::EP_DBT_POSTGRES => '"', 
        epDb::EP_DBT_SQLITE   => '"', 
        );
    
    /**
     * The PEAR-style DSN for the database 
     * @var string 
     * @see http://pear.php.net/manual/en/package.database.db.intro-dsn.php
     */
    protected $dsn = false;

    /**
     * The db connection that the underlying db lib supports
     * @var mixed
     */
    public $db = false;

    /**
     * Flag that db is currently in transaction
     * @var bool
     */
    public $in_transaction = false;
    
    /**
     * Whether to debug db or not
     * If true, queries will be collected
     * @var boolean
     */
    protected $log_queries = false;

    /**
     * Whether to debug db or not
     * @var boolean
     */
    protected $queries = array();
    
    /**
     * The db type (EP_DBT_XXX consts below)
     * @var string
     */
    public $dbtype = false;
    
    /**
     * The name quote for the current db type
     */
    protected $nameQuote = false;
    
    /**
     * Array of tables exist
     */
    protected $tables_exist = array();

    /**
     * Constructor 
     * @param string $dsn the foreign class name
     * @param boolean $log_queries whether to log queries (for debugging purpose)
     * @see epOverload::__construct()
     */
    public function __construct($dsn, $log_queries = false) {
        
        // check dsn
        if (empty($dsn)) {
            throw new epExceptionDb('DSN empty');
        }
        
        $this->dsn = $dsn;
        $this->log_queries = $log_queries;
    }
    
    /**
     * Returns the database type for this connection
     * @return string
     * @throws epExceptionDb
     */
    public function dbType() {
        
        if (!$this->dbtype) {
            
            // use epDbDsn to parse PEAR DSN 
            include_once(EP_SRC_DB . '/epDbDsn.php');
            if (!($d = new epDbDsn($this->dsn))) {
                throw new epExceptionDb('Error in parsing DSN');
            }

            // set db type
            $this->_setDbType($d['phptype'] . ':' . $d['dbsyntax']);
        }
        
        return $this->dbtype;
    }

    /**
     * Set the database type from driver/phptype string
     * @param string $t (the driver/phptype string)
     * @return bool
     */
    protected function _setDbType($t) {
        
        if (stristr($t, 'access')) {
            $this->dbtype = self::EP_DBT_ACCESS;
        } else if (stristr($t, 'db2')) {
            $this->dbtype = self::EP_DBT_DB2;
        } else if (stristr($t, 'firebird')) {
            $this->dbtype = self::EP_DBT_FIREBIRD;
        } else if (stristr($t, 'ibase')) {
            $this->dbtype = self::EP_DBT_IBASE;
        } else if (stristr($t, 'informix') || stristr($t, 'ifx')) {
            $this->dbtype = self::EP_DBT_INFORMIX;
        } else if (stristr($t, 'mssql')) {
            $this->dbtype = self::EP_DBT_MSSQL;
        } else if (stristr($t, 'mysql')) {
            $this->dbtype = self::EP_DBT_MYSQL;
        } else if (stristr($t, 'oci8')) {
            $this->dbtype = self::EP_DBT_OCI8;
        } else if (stristr($t, 'pgsql')) {
            $this->dbtype = self::EP_DBT_POSTGRES;
        } else if (stristr($t, 'sqlite')) {
            $this->dbtype = self::EP_DBT_SQLITE;
        } else if (stristr($t, 'sybase')) {
            $this->dbtype = self::EP_DBT_SYBASE;
        } else {
            return false;
        }
        
        return true;
    }

    /**
     * Establishes a DB connection
     * @access public
     * @return bool
     */
    abstract public function open();

    /**
     * Closes the DB connection
     * Subclass must override this method
     * @return void
     */
    abstract public function close();
    
    /**
     * Check if a table exists
     * @param string $table
     * @return bool
     */
    abstract public function tableExists($table);

    /**
     * Clears table from table_exists cache
     * @param string $table
     * @return void
     */
    public function clearTableExists($table) {
        unset($this->tables_exist[$table]);
    }

    /**
     * Set whether to log queries
     * @param boolean $log_queries
     * @return boolean
     */
    public function logQueries($log_queries = true) {
        return $this->log_queries = $log_queries;
    }
    
    /**
     * Returns queries logged. If reset flag is set 
     * (default), empty logged queries.
     * @param boolean $reset
     * @return array
     */
    public function getQueries($reset = true) {
        $queries = $this->queries;
        if ($reset) {
            $this->queries = array();
        }
        return $queries;
    }

    /**
     * Returns whether db is in transaction
     * @return bool
     */
    public function inTransaction() {
        return $this->in_transaction;
    }

    /**
     * Turns off autocommit mode. While autocommit mode is turned off, 
     * changes made to the database are not committed until you end 
     * the transaction by calling either {@link commit()}.
     * @return bool
     */
    public function beginTransaction() {
        if ($this->in_transaction) {
            return false;
        }
        $status = $this->_beginTransaction();
        $this->in_transaction = true;
        return $status;
    }

    /**
     * Acutally start a transaction by calling the underlying 
     * database connection
     * @return bool
     */
    abstract protected function _beginTransaction();

    /**
     * Commits a transaction, returning the database connection to 
     * autocommit mode until the next call to {@link beginTransaction()}
     * starts a new transaction.
     * @return bool
     */
    public function commit() {
        if (!$this->in_transaction) {
            return false;
        }
        $status = $this->_commit();
        $this->in_transaction = false;
        return (boolean)$status;
    }
    
    /**
     * Actually commit the current transaction by calling the underlying 
     * database connection
     * @return bool
     */
    abstract protected function _commit();

    /**
     * Rolls back the current transaction, as initiated by 
     * {@link beginTransaction()}. It is an error to call this method if no 
     * transaction is active. If the database was set to autocommit mode, 
     * this function will restore autocommit mode after it has rolled 
     * back the transaction. 
     * @return bool
     */
    public function rollback() {
        if (!$this->in_transaction) {
            return false;
        }
        $status = $this->_rollback();
        $this->in_transaction = false;
        return $status;
    }

    /**
     * Acutally rollback the current transaction by calling the underlying 
     * database connection
     * @return bol
     */
    abstract protected function _rollback();
    
    /**
     * Executes SQL command
     * Subclass must override this method
     * @param string query string
     * @return mixed
     * @access public
     */
    public function execute($query) {
        
        if ($this->log_queries) {
            $t = microtime(true);
        }

        $r = $this->_execute($query);
        
        if ($this->log_queries) {
            $t = microtime(true) - $t;
            $this->queries[] = '[' . $t . '] ' .  $query;
        }

        return $r;
    }
    
    /**
     * Executes SQL command (only called by execute())
     * Subclass must override this method
     * @param string query string
     * @return mixed
     * @access public
     */
    abstract protected function _execute($query);

    /**
     * Returns the last insert id
     * @param string $oid the oid column
     * @return integer
     * @access public
     */
    abstract public function lastInsertId($table, $oid = 'oid');

    /**
     * Returns the number of records in last result set
     * @param mixed $rs the result set
     * @return integer
     */
    abstract public function rsRows();
    
    /**
     * Rewinds to the first row in the last result
     * @return bool
     */
    abstract public function rsRestart();
    
    /**
     * Moves to the next row in the last result set
     * @return bool
     */
    abstract public function rsNext();
    
    /**
     * Get the value for a column in the current row in the last result set
     * @param string $col the name of the column 
     * @param string $col_alt the alternative name of the column (used only when $col fails)
     * @return false|mixed
     */
    abstract public function rsGetCol($col, $col_alt = false);
    
    /**
     * Formats input so it can be safely used as a literal
     * @param mixed $input
     * @return mixed
     */
    abstract public function quote($input);

    /**
     * Formats a string so it can be safely used as an identifier (e.g. table, column names)
     * @param mixed $input
     * @return mixed
     */
    public function quoteId($input) {

        // [kludge] sqlite bug - doesn't like "xxx"."*"
        if (strpos($input, '.*') && $this->dbType() == self::EP_DBT_SQLITE) {
            return $input;
        }
        
        // the quoting symbol
        $q = $this->_getNameQuote();
        
        // split input into items (xx.yy.zz)
        $quoted = array();
        foreach(explode('.', $input) as $item) {
            if ($item != '*') {
                $quoted[] = $q . $item . $q;
            } else {
                $quoted[] = $item;
            }
        }
        
        // quote them into ('xx'.'yy'.'zz')
        return implode('.', $quoted);
    }

    /**
     * Gets the data source name (DSN) of the database connection 
     * @return string 
     * @access public
     */
    public function dsn() {
        return $this->dsn;
    }
    
    /**
     * Returns the db connection - an instance of db connection 
     * that the underlying db library supports. Not supposed to 
     * be called directly. Provided for testing only.
     * @return mixed
     */
    public function &connection() {
        return $this->db;
    }

    /**
     * Returns the name quote char for the curent db type
     * @return string
     */
    protected function _getNameQuote() {

        // have we gotten the name quote yet?
        if ($this->nameQuote === false) {
            $this->nameQuote = ''; // default to empty string
            if (isset(self::$nameQuotes[$dbtype = $this->dbType()])) {
                $this->nameQuote = self::$nameQuotes[$dbtype];
            }
        }
        
        // return name quote
        return $this->nameQuote;
    }

}

?>
