<?php

/**
 * $Id: epDbAdodbPdo.php 927 2006-04-28 17:15:44Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 927 $ $Date: 2006-04-28 13:15:44 -0400 (Fri, 28 Apr 2006) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */

/**
 * need base class epDbAdodb
 */
include_once(EP_SRC_DB.'/epDbAdodb.php');

/**
 * Exception class for {@link epDbAdodbPdo}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 927 $ $Date: 2006-04-28 13:15:44 -0400 (Fri, 28 Apr 2006) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epExceptionDbAdodbPdo extends epExceptionDbAdodb {
}

/**
 * A wrapper class to use PDO via ADODb
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 927 $ $Date: 2006-04-28 13:15:44 -0400 (Fri, 28 Apr 2006) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbAdodbPdo extends epDbAdodb {
    
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
            // $this->db = & ADONewConnection($this->dsn);
            $this->db = & NewADOConnection('pdo');

            // convert PEAR DSN into PDO DSN
            $dsn_pdo = $this->_convertDsn($this->dsn, $username, $password, $phptype);

            // set db type
            $this->_setDbType($phptype);

            // connect through PDO
            $this->db->Connect($dsn_pdo, $username, $password);
        } 
        catch (Exception $e) {
            throw new epExceptionDbAdodb('Cannot connect db: ' . $e->getMessage());
            return false;
        }

        // set fetch mode to assoc 
        $this->db->SetFetchMode(ADODB_FETCH_ASSOC);
        
        return true;
    }

    /**
     * Convert the PEAR DSN into what PHP PDO can recognize
     * @param string $dsn the PEAR-styple DSN
     * @param string $username
     * @param string $password
     * @return false|string
     * @throws epExceptionDbAdodbPdo
     */
    protected function _convertDsn($dsn, &$username, &$password, &$phptype) {
        
        // use epDbDsn to parse PEAR DSN 
        include_once(EP_SRC_DB . '/epDbDsn.php');
        if (!($d = new epDbDsn($dsn))) {
            throw new epExceptionDbAdodbPdo('Error in DSN parsing');
            return false;
        }

        // set dbtype
        $phptype = $d['phptype'];

        // convert PEAR DSN to PDO DSN 
        return $d->toPdoDsn($username, $password);
    }

    /**
     * Override epDbAdodb::quoteId()
     * Surpress (no) quoteId() due to a ADODB bug. Should be removed after it's fixed.
     * @param string $input
     * @return string
     */
    public function quoteId($input) {
        return $input;
    }

}

?>
