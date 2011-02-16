<?php

/**
 * $Id: epDbPdo.php 1030 2007-01-19 10:38:55Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1030 $ $Date: 2007-01-19 05:38:55 -0500 (Fri, 19 Jan 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */

/**
 * need epOverload
 */
include_once(EP_SRC_DB.'/epDb.php');

/**
 * Exception class for {@link epDbPdo}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1030 $ $Date: 2007-01-19 05:38:55 -0500 (Fri, 19 Jan 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epExceptionDbPdo extends epExceptionDb {
}

/**
 * A wrapper class of PHP PDO 
 * {@link http://us4.php.net/manual/en/ref.pdo.php}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1030 $ $Date: 2007-01-19 05:38:55 -0500 (Fri, 19 Jan 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbPdo extends epDb {
    
    /**
     * The last record set
     * @var mixed
     */
    public $last_rs = false;
    
    /**
     * The last row fetched in record set
     * @var record
     */
    protected $last_row = false;

    /**
     * Constructor
     * @param string $dsn the DSN to access the database
     * @param bool $is_persistent whether connection is persistent or not
     * @param string $fetch_mode the fetch mode
     */
    public function __construct($dsn) {
        parent::__construct($dsn);
    }
    
    /**
     * Establishes a DB connection
     * @access public
     * @return bool
     */
    public function open() {
        
        // check if connected already
        if ($this->db) {
            return true;
        }
        
        // convert PEAR DSN into PDO DSN
        $dsn_pdo = $this->_convertDsn($this->dsn, $username, $password, $phptype);

        // set db type
        $this->_setDbType($phptype);

        // connect db now
        try {
            $this->db = new PDO($dsn_pdo, $username, $password);
        } 
        catch (Exception $e) {
            throw new epExceptionDbPdo('Cannot connect db [' . $dsn_pdo . ']: ' . $e->getMessage());
            return false;
        }
        
        // double check if we have db connectcion
        if (!$this->db) {
            throw new epExceptionDbPdo('Cannot connect db [' . $dsn . ']');
            return false;
        }

        // set column name case to upper
        $this->db->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
        
        return true;
    }

    /**
     * Closes the DB connection
     * @return void
     */
    public function close() {
    }
    
    /**
     * Check if a table exists
     * @param string $table
     * @return bool
     */
    public function tableExists($table) {
        
        if (!$table) {
            return false;
        }
        
        // check cached
        if (isset($this->tables_exist[$table])) {
            return true;
        }

        // check db connection
        if (!$this->open()) {
            return false;
        }
        
        // check if table exists 
        try {
            
            // use a select stmt to test the existence of the table
            $sql = 'SELECT COUNT(*) FROM ' . $this->quoteId($table) . ' WHERE 1=1';
            
            // prepare statement
            if (!($stmt = $this->db->prepare($sql))) {
                return false;
            }
            
            // execute statement
            if (!$stmt->execute()) {
                return false;
            }
        }
        catch (Exception $e) {
            return false;
        }
        
        $this->tables_exist[$table] = $table;
        return true;
    }

    /**
     * Implements {@link epDb::_beginTransaction()}
     * @return bool
     */
    public function _beginTransaction() {
        
        // open connection if not already 
        if (!$this->open()) {
            return false;
        }

        // call pdo to start a transaction
        return $this->db->beginTransaction();
    }

    /**
     * Implements {@link epDb::_commit()}
     * @return bool
     */
    public function _commit() {
        // call pdo to commit
        return $this->db->commit();
    }

    /**
     * Implements {@link epDb::_rollback()}
     * @return bool
     */
    public function _rollback() {
        // call pdo to roll back
        return $this->db->rollBack();
    }

    /**
     * Executes SQL command
     * @param string query string
     * @access public
     * @return mixed
     */
    public function _execute($query) {
        
        // check db connection
        if (!$this->open()) {
            return false;
        }

        // execute query and cache last record set
        return ($this->last_rs = $this->db->query($query));
    }

    /**
     * Override {@link epDb::lastInsertId()}
     * Returns the last insert id
     * @param string table name (unquoted)
     * @param string $oid the oid column 
     * @return integer
     * @access public
     */
    public function lastInsertId($table, $oid = 'oid') {
        if (!$this->db) {
            return false;
        }
        return $this->db->lastInsertId($oid);
    }

    /**
     * Returns the number of records in last result set
     * @return integer
     */
    public function rsRows() {
        if (!$this->last_rs) {
            return 0;
        }
        return $this->last_rs->rowCount();
    }
    
    /**
     * Rewinds to the first row in the last result
     * @return void
     */
    public function rsRestart() {
        if (!$this->last_rs) {
            return false;
        }
        return $this->last_row = $this->last_rs->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT, 0);
    }
    
    /**
     * Moves to the next row in the last result set
     * @return bool
     */
    public function rsNext() {
        if (!$this->last_rs) {
            return false;
        }
        return $this->last_row = $this->last_rs->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get the value for a column in the current row in the last result set
     * @param string the name of the column 
     * @return false|mixed
     */
    public function rsGetCol($col, $col_alt = false) {
        
        if (!$this->last_rs) {
            throw new epExceptionDbPdo('No last query result found');
            return false;
        }
        
        // make columan capital (PDO::ATTR_CASE)
        $col = strtoupper($col);

        // try $col first
        if (array_key_exists($col, $this->last_row)) {
            return $this->last_row[$col];
        }
        
        // make columan capital (PDO::ATTR_CASE)
        $col_alt = strtoupper($col_alt);
        
        // now try $col_alt
        if ($col_alt && array_key_exists($col_alt, $this->last_row)) {
            return $this->last_row[$col_alt];
        }
        
        // last resort: partial match
        foreach($this->last_row as $col_ => $value) {
            $pieces = explode('.', $col_);
            $field = (count($pieces) == 1) ? $pieces[0] : $pieces[1];
            $field = strtolower($field);
            if ($field == strtolower($col) || $field == strtolower($col_alt)) {
                return $value;
            }
        }
        
        // no matching column found
        throw new epExceptionDbPdo('Column [' . $col . '] not found');
        return false;
    }

    /**
     * Formats input so it can be safely used as a literal
     * @param mixed $s
     * @return mixed
     */
    public function quote($s) {
        // open connection if not already 
        if (!$this->open()) {
            return false;
        }
        return $this->db->quote($s);
    } 

    /**
     * Convert the PEAR DSN into what PHP PDO can recognize
     * @param string $dsn the PEAR-styple DSN
     * @param string $username
     * @param string $password
     * @param string $phptype
     * @return false|string
     * @throws epExceptionDbAdodbPdo
     */
    protected function _convertDsn($dsn, &$username, &$password, &$phptype) {
        
        // use epDbDsn to parse PEAR DSN 
        include_once(EP_SRC_DB . '/epDbDsn.php');
        if (!($d = new epDbDsn($dsn))) {
            throw new epExceptionDbAdodbPdo('Error in converting PEAR DSN into PDO DSN');
            return false;
        }

        // set dbtype
        $phptype = $d['phptype'];

        // convert PEAR DSN to PDO DSN 
        return $d->toPdoDsn($username, $password);
    }
    
}

?>
