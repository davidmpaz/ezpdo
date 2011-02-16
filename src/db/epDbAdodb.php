<?php

/**
 * $Id: epDbAdodb.php 1030 2007-01-19 10:38:55Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1030 $ $Date: 2007-01-19 05:38:55 -0500 (Fri, 19 Jan 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */

/**
 * need base class epDb
 */
include_once(EP_SRC_DB.'/epDb.php');

/**
 * Exception class for {@link epDbAdodb}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1030 $ $Date: 2007-01-19 05:38:55 -0500 (Fri, 19 Jan 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epExceptionDbAdodb extends epExceptionDb {
}

/**
 * A wrapper class of ADODB
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1030 $ $Date: 2007-01-19 05:38:55 -0500 (Fri, 19 Jan 2007) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbAdodb extends epDb {
    
    /**
     * The last record set
     * @var mixed
     */
    public $last_rs = false;
    
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
        
        // need adodb and exceptions
        include_once(EP_LIBS_ADODB.'/adodb-exceptions.inc.php');
        include_once(EP_LIBS_ADODB.'/adodb.inc.php');
        
        // connect db now
        try {
            $this->db = & ADONewConnection($this->dsn);
        } 
        catch (ADODB_Exception $e) {
            throw new epExceptionDbAdodb('Cannot connect db: ' . $e->getMessage());
            return false;
        }

        // set fetch mode to assoc 
        $this->db->SetFetchMode(ADODB_FETCH_ASSOC);
        
        return true;
    }

    /**
     * Closes the DB connection
     * @return void
     */
    public function close() {
        if ($this->db) {
            $this->db->Close();
        }
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
        
        // test if table exists 
        try {
            // execute a select statement on the table 
            $rs = $this->db->Execute('SELECT COUNT(*) FROM ' . $this->quoteId($table) . ' WHERE 1=1'); 
        } 
        catch (Exception $e) {
            // table does not exist if exception 
            return false;
        }
        
        // cache to tables_exist if exists
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

        // call adodb to start a transaction
        return $this->db->BeginTrans();
    }

    /**
     * Implements {@link epDb::_commit()}
     * @return bool
     */
    public function _commit() {
        // call adodb to commit
        return $this->db->CommitTrans();
    }

    /**
     * Implements {@link epDb::_rollback()}
     * @return bool
     */
    public function _rollback() {
        // call adodb to roll back
        return $this->db->RollbackTrans();
    }

    /**
     * Executes SQL command
     * @param string query string
     * @access public
     * @return mixed
     * @throws epExceptionDbAdodb
     */
    protected function _execute($query) {
        
        // check db connection
        if (!$this->open()) {
            return false;
        }
        
        // set fetch mode to assoc 
        $this->db->SetFetchMode(ADODB_FETCH_ASSOC);
        
        // execute query and cache last record set
        try {
            $this->last_rs = $this->db->Execute($query);
        }
        catch (ADODB_Exception $e) {
            throw new epExceptionDbAdodb('Cannot execute query: ' . $e->getMessage());
            return false;
        }
    
        return $this->last_rs;
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
        return $this->db->Insert_ID($this->quoteId($table), $oid);
    }

    /**
     * Returns the number of records in last result set
     * @return integer
     */
    public function rsRows() {
        if (!$this->last_rs) {
            return 0;
        }
        return $this->last_rs->RecordCount();
    }
    
    /**
     * Rewinds to the first row in the last result
     * @return void
     */
    public function rsRestart() {
        if (!$this->last_rs) {
            return false;
        }
        $this->last_rs->MoveFirst();
        return (!$this->last_rs->EOF);
    }
    
    /**
     * Moves to the next row in the last result set
     * @return bool
     */
    public function rsNext() {
        if (!$this->last_rs) {
            return false;
        }
        $this->last_rs->MoveNext();
        return (!$this->last_rs->EOF);
    }
    
    /**
     * Get the value for a column in the current row in the last result set
     * @param string the name of the column 
     * @return false|mixed
     */
    public function rsGetCol($col, $col_alt = false) {
        
        if (!$this->last_rs) {
            throw new epExceptionDbAdodb('No last query result found');
            return false;
        }
        
        // try $col first
        if (array_key_exists($col, $this->last_rs->fields)) {
            return $this->last_rs->fields[$col];
        }

        // now try $col_alt
        if ($col_alt && array_key_exists($col_alt, $this->last_rs->fields)) {
            return $this->last_rs->fields[$col_alt];
        }

        // last resort: partial match
        foreach($this->last_rs->fields as $col_ => $value) {
            $pieces = explode('.', $col_);
            $field = (count($pieces) == 1) ? $pieces[0] : $pieces[1];
            $field = strtolower($field);
            if ($field == strtolower($col) || $field == strtolower($col_alt)) {
                return $value;
            }
        }

        // no matching column found
        throw new epExceptionDbAdodb('Column [' . $col . '] not found');
        return false;
    }
    
    /**
     * Formats input so it can be safely used as a literal
     * @param mixed $input
     * @return mixed
     */
    public function quote($input) {
        
        // open connection if not already 
        if (!$this->open()) {
            return false;
        }

        return $this->db->qstr($input);
    } 

}

?>
