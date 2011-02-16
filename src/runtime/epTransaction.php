<?php

/**
 * $Id: epTransaction.php 501 2005-09-02 19:47:37Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 501 $ $Date: 2005-09-02 15:47:37 -0400 (Fri, 02 Sep 2005) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */

/**
 * Need {@link epBase} as the super class 
 */
include_once(EP_SRC_BASE.'/epBase.php');

/**
 * Need {@link epDb} 
 */
include_once(EP_SRC_DB.'/epDb.php');

/**
 * Exception class for {@link epTransaction}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 501 $ $Date: 2005-09-02 15:47:37 -0400 (Fri, 02 Sep 2005) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epExceptionTransaction extends epException {
}

/**
 * The transaction class
 * 
 * When a transaction starts (meaning a transaction boundary is declared), 
 * it starts to keep track of object changes. Any object with any changes
 * in its state (i.e. the values of its data members) will be committed 
 * once transaction ends (either by {@link epManager::commit_t()} or 
 * {@link epManager::rollback_t()}. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 501 $ $Date: 2005-09-02 15:47:37 -0400 (Fri, 02 Sep 2005) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epTransaction extends epBase {

    /**
     * Objects that are changed since transaction starts
     * @var array (keyed by object uid to guarentee uniqueness)
     * @see epObject::epGetUId()
     */
    protected $objects = array();

    /**
     * Dbs to keep track for transaction
     * @var array (keyed by dsn to guarentee uniqueness)
     */
    protected $dbs = array();
    
    /**
     * Add one object into objects and its associated db to watch
     * @param epObject $o 
     * @param epDbObject $db
     * @return bool
     */
    public function addObject($o, $db) {
        
        // validate input
        if (!($o instanceof epObject)) {
            return false;
        }

        // has object been kept?
        $uid = $o->epGetUId();
        if (!isset($this->objects[$uid])) {
            
            // check if object is in transaction. start if not
            if (!$o->epInTransaction()) {
                $o->epStartTransaction();
            }
            
            // add the associated db to watch
            $this->addDb($db);
            
            // add if not
            $this->objects[$uid] = $o;
        }

        return true;
    }

    /**
     * Add an array of objects into objects to watch
     * @param array
     * @return bool
     */
    public function addObjects($os) {
        $status = true;
        foreach($os as $o) {
            $status &= $this->addObject($o);
        }
        return $status;
    }

    /**
     * Commit all objects
     * @return bool
     * @throws epExceptionTransaction
     */
    public function commitObjects() {
        $status = true;
        foreach($this->objects as $o) {
            $status &= $o->commit();
            $status &= $o->epEndTransaction(false); // false: no rollback
        }
        return $status;
    }

    /**
     * Rollback all objects
     * @return bool
     * @throws epExceptionTransaction
     */
    public function rollbackObjects() {
        $status = true;
        foreach($this->objects as $o) {
            $status &= $o->epEndTransaction(true); // true: rollback
        }
        return $status;
    }

    /**
     * Add one db into dbs to watch
     * @param epDb $db
     * @return bool
     */
    public function addDb($db) {
        
        // get the real db connection 
        if ($db instanceof epDbObject) {
            $db = $db->connection();
        }

        // validate input
        if (!($db instanceof epDb)) {
            return false;
        }

        // has db been kept?
        if (!isset($this->dbs[$db->dsn()])) {

            // check if db is in transaction. start if not
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            // add if not
            $this->dbs[$db->dsn()] = $db;
        }

        return true;
    }

    /**
     * Add an array of dbs into dbs to watch
     * @param array
     * @return bool
     */
    public function addDbs($dbs) {
        $status = true;
        foreach($dbs as $db) {
            $status &= $this->addDb($db);
        }
        return $status;
    }

    /**
     * Commit all dbs
     * @return bool
     * @throws epExceptionTransaction
     */
    public function commitDbs() {
        
        $status = true;
        
        try {
            foreach($this->dbs as $db) {
                $status &= $db->commit();
            }
        }
        catch (Exception $e) {
            $status = false;
        }

        return $status;
    }

    /**
     * Rollback all dbs
     * @return bool
     * @throws epExceptionTransaction
     */
    public function rollbackDbs() {
        
        $status = true;
        
        try {
            foreach($this->dbs as $db) {
                $status &= $db->rollback();
            }
        }
        catch (Exception $e) {
            $status = false;
        }

        return $status;
    }

}

?>
