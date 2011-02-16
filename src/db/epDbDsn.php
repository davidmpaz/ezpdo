<?php

/**
 * $Id: epDbDsn.php 857 2006-03-13 13:27:36Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 857 $ $Date: 2006-03-13 08:27:36 -0500 (Mon, 13 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */

/**
 * need epBase
 */
include_once(EP_SRC_BASE.'/epBase.php');

/**
 * Exception class for {@link epDbDsn}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 857 $ $Date: 2006-03-13 08:27:36 -0500 (Mon, 13 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.db 
 */
class epExceptionDbDsn extends epException {
}

/**
 * Class to parse data soure name (DSN)
 * 
 * See more on PEAR DSN at {@link 
 * http://pear.php.net/manual/en/package.database.db.intro-dsn.php}.
 * 
 * The class implements the SPL ArrayAccess and IteratorAggregate 
 * interfaces, which means you can access parsed DSN components just 
 * like an ordinary array. For example, use $dsn['username'] to 
 * get the parsed username from the DSN string, or 
 * foreach($dsn as $k => $v) to iterate through all the components.
 * 
 * The following is a list of components in a DSN.
 * + dsn: The original DSN string
 * + phptype:  Database backend used in PHP (mysql, odbc etc.)
 * + dbsyntax: Database used with regards to SQL syntax etc.
 * + protocol: Communication protocol to use (tcp, unix etc.)
 * + hostspec: Host specification (hostname[:port])
 * + database: Database to use on the DBMS server
 * + username: User name for login
 * + password: Password for login
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 857 $ $Date: 2006-03-13 08:27:36 -0500 (Mon, 13 Mar 2006) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbDsn extends epBase implements IteratorAggregate, ArrayAccess {
    
    /**
     * The original DSN string
     * @var string
     */
    protected $dsn = false;

    /**
     * Array to keep parsed DSN components
     *  + phptype:  Database backend used in PHP (mysql, odbc etc.)
     *  + dbsyntax: Database used with regards to SQL syntax etc.
     *  + protocol: Communication protocol to use (tcp, unix etc.)
     *  + hostspec: Host specification (hostname[:port])
     *  + database: Database to use on the DBMS server
     *  + username: User name for login
     *  + password: Password for login
     * @var array
     */
    protected $_parsed = array(
        'phptype'  => false,
        'dbsyntax' => false,
        'username' => false,
        'password' => false,
        'protocol' => false,
        'hostspec' => false,
        'port'     => false,
        'socket'   => false,
        'database' => false,
    );

    /**
     * Constructor
     * @param string $dsn
     * @throws epExceptionDbDsn
     */
    public function __construct($dsn = '') {
        $this->setDsn($dsn);
    }

    /**
     * Returns the original DSN string
     * @return string
     */
    public function getDsn() {
        return $this->_dsn;
    }
    
    /**
     * Set the DSN string and parse the string
     * @param string $dsn
     * @return bool
     */
    public function setDsn($dsn) {
        
        // keep a record of the DSN string
        $this->_dsn = $dsn;
        
        // parse non-empty DSN string
        $this->_parsed = self::parsePearDsn($this->_dsn);

        return true;
    }

    /**
     * Converts PEAR DSN into PDO DSN
     * 
     * The method returns a string of PDO DSN string and put username/password
     * into arguments (reference)
     * 
     * @param string $username
     * @param string $password
     * @return false|string
     * @see http://us3.php.net/manual/en/ref.pdo-sqlite.connection.php
     * @see 
     */
    public function toPdoDsn(&$username, &$password) {
        
        // check if DSN is valid (driver)
        if (!isset($this->_parsed['phptype'])) {
            throw new epExceptionDbDsn('Invalid DSN: phptype missing.');
            return false;
        }

        // check if DSN is valid (database)
        if (!isset($this->_parsed['database'])) {
            throw new epExceptionDbDsn('Invalid DSN: database missing.');
            return false;
        }

        // convert PEAR DSN into PDO DSN
        switch ($this->_parsed['phptype']) {
            
            // special format for sqlite
            case 'sqlite':
                $pdo_dsn = 'sqlite:' . $this->_parsed['database'];
                break;

            default:
                // string to hold pdo dsn
                $pdo_dsn = $this->_parsed['phptype'] . ':';

                // add dbname
                $pdo_dsn .= 'dbname=' . $this->_parsed['database'];

                // add host if exists
                if ($this->_parsed['hostspec']) {
                    $pdo_dsn .= ';host=' . $this->_parsed['hostspec'];
                }
        }
        

        // set username and password
        $username = '';
        if ($this->_parsed['username']) {
            $username = $this->_parsed['username'];
        }

        $password = '';
        if ($this->_parsed['password']) {
            $password = $this->_parsed['password'];
        }

        // return the PDO DSN 
        return $pdo_dsn;
    }

    /**
     * This method is copied from PEAR DB::parseDSN().
     * 
     * Parse a data source name
     *
     * Additional keys can be added by appending a URI query string to the
     * end of the DSN.
     *
     * The format of the supplied DSN is in its fullest form:
     * <code>
     *  phptype(dbsyntax)://username:password@protocol+hostspec/database?option=8&another=true
     * </code>
     *
     * Most variations are allowed:
     * <code>
     *  phptype://username:password@protocol+hostspec:110//usr/db_file.db?mode=0644
     *  phptype://username:password@hostspec/database_name
     *  phptype://username:password@hostspec
     *  phptype://username@hostspec
     *  phptype://hostspec/database
     *  phptype://hostspec
     *  phptype(dbsyntax)
     *  phptype
     * </code>
     *
     * @param string $dsn Data Source Name to be parsed
     *
     * @return array an associative array with the following keys:
     *  + phptype:  Database backend used in PHP (mysql, odbc etc.)
     *  + dbsyntax: Database used with regards to SQL syntax etc.
     *  + protocol: Communication protocol to use (tcp, unix etc.)
     *  + hostspec: Host specification (hostname[:port])
     *  + database: Database to use on the DBMS server
     *  + username: User name for login
     *  + password: Password for login
     */
    static public function parsePearDsn($dsn) {
        
        $parsed = array(
            'phptype'  => false,
            'dbsyntax' => false,
            'username' => false,
            'password' => false,
            'protocol' => false,
            'hostspec' => false,
            'port'     => false,
            'socket'   => false,
            'database' => false,
        );

        if (is_array($dsn)) {
            $dsn = array_merge($parsed, $dsn);
            if (!$dsn['dbsyntax']) {
                $dsn['dbsyntax'] = $dsn['phptype'];
            }
            return $dsn;
        }

        // Find phptype and dbsyntax
        if (($pos = strpos($dsn, '://')) !== false) {
            $str = substr($dsn, 0, $pos);
            $dsn = substr($dsn, $pos + 3);
        } else {
            $str = $dsn;
            $dsn = null;
        }

        // Get phptype and dbsyntax
        // $str => phptype(dbsyntax)
        if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
            $parsed['phptype']  = $arr[1];
            $parsed['dbsyntax'] = !$arr[2] ? $arr[1] : $arr[2];
        } else {
            $parsed['phptype']  = $str;
            $parsed['dbsyntax'] = $str;
        }

        if (!count($dsn)) {
            return $parsed;
        }

        // Get (if found): username and password
        // $dsn => username:password@protocol+hostspec/database
        if (($at = strrpos($dsn,'@')) !== false) {
            $str = substr($dsn, 0, $at);
            $dsn = substr($dsn, $at + 1);
            if (($pos = strpos($str, ':')) !== false) {
                $parsed['username'] = rawurldecode(substr($str, 0, $pos));
                $parsed['password'] = rawurldecode(substr($str, $pos + 1));
            } else {
                $parsed['username'] = rawurldecode($str);
            }
        }

        // Find protocol and hostspec
        if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {

            // $dsn => proto(proto_opts)/database
            $proto       = $match[1];
            $proto_opts  = $match[2] ? $match[2] : false;
            $dsn         = $match[3];

        } else {
            // $dsn => protocol+hostspec/database (old format)
            if (strpos($dsn, '+') !== false) {
                list($proto, $dsn) = explode('+', $dsn, 2);
            }
            if (strpos($dsn, '/') !== false) {
                list($proto_opts, $dsn) = explode('/', $dsn, 2);
            } else {
                $proto_opts = $dsn;
                $dsn = null;
            }
        }

        // process the different protocol options
        $parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
        $proto_opts = rawurldecode($proto_opts);
        if ($parsed['protocol'] == 'tcp') {
            if (strpos($proto_opts, ':') !== false) {
                list($parsed['hostspec'],
                     $parsed['port']) = explode(':', $proto_opts);
            } else {
                $parsed['hostspec'] = $proto_opts;
            }
        } elseif ($parsed['protocol'] == 'unix') {
            $parsed['socket'] = $proto_opts;
        }

        // Get database if any
        // $dsn => database
        if ($dsn) {
            if (($pos = strpos($dsn, '?')) === false) {
                // /database
                $parsed['database'] = rawurldecode($dsn);
            } else {
                // /database?param1=value1&param2=value2
                $parsed['database'] = rawurldecode(substr($dsn, 0, $pos));
                $dsn = substr($dsn, $pos + 1);
                if (strpos($dsn, '&') !== false) {
                    $opts = explode('&', $dsn);
                } else { // database?param1=value1
                    $opts = array($dsn);
                }
                foreach ($opts as $opt) {
                    list($key, $value) = explode('=', $opt);
                    if (!isset($parsed[$key])) {
                        // don't allow params overwrite
                        $parsed[$key] = rawurldecode($value);
                    }
                }
            }
        }

        return $parsed;
    }

    /**
     * Implements IteratorAggregate::getIterator()
     * Returns the iterator which is an ArrayIterator object connected to the array
     * @return ArrayIterator
     */
    public function getIterator() {
        
        // put 'dsn' into components
        $components = $this->_parsed;
        $components['dsn'] = $this->_dsn;

        // return the array iterator
        return new ArrayIterator($components);
    }
     
    /**
     * Implements ArrayAccess::offsetExists()
     * @return boolean
     */
    public function offsetExists($index) {
        return $index == 'dsn' || isset($this->_parsed[$index]);
    }
    
    /**
     * Implements ArrayAccess::offsetGet()
     * @return mixed
     * @throws epExceptionDbDsn
     */
    public function offsetGet($index) {
        
        // dsn string?
        if ($index == 'dsn') {
            return $this->_dsn;
        }

        // dsn component set?
        if (isset($this->_parsed[$index])) {
            return $this->_parsed[$index];
        }

        throw new epExceptionDbDsn('[' . $index . '] is not a valid component in DSN');
    }
     
    /**
     * Implements ArrayAccess::offsetSet()
     * @return void
     */
    public function offsetSet($index, $newval) {
        
        // dsn string?
        if ($index == 'dsn') {
            $this->setDsn($newval);
            return;
        }

        // dsn component?
        if (array_key_exists($index)) {
            $this->_parsed[$index] = $newval;
        }
        
        throw new epExceptionDbDsn('[' . $index . '] is not a valid component in DSN');
    }

    /**
     * Implements ArrayAccess::offsetUnset()
     * @return mixed
     */
     public function offsetUnset($index) {

         // dsn string?
         if ($index == 'dsn') {
             $this->setDsn('');
             return;
         }

         // dsn component?
         if (array_key_exists($index)) {
             $this->_parsed[$index] = $newval;
         }

         throw new epExceptionDbDsn('[' . $index . '] is not a valid component in DSN');
     }
}

?>