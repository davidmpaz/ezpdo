<?php

/**
 * $Id: epQuery.php 1035 2007-01-31 11:46:48Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1035 $ $Date: 2007-01-31 06:46:48 -0500 (Wed, 31 Jan 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */

/**
 * need query builder
 */
include_once(EP_SRC_QUERY.'/epQueryBuilder.php');

/**
 * Exception class for {@link epQuery}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1035 $ $Date: 2007-01-31 06:46:48 -0500 (Wed, 31 Jan 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epExceptionQuery extends epException {
}

/**
 * The EZPDO query class
 * 
 * This class interprets EZOQL (the EZPDO Object Query Language) query 
 * strings and outputs SQL statement. EZOQL is a simple object query 
 * lanaguage, a variant of standard SQL. 
 * 
 * The syntax EZOQL in the BNF (Backus Normal Form) can be found in 
 * src/query/bnf.txt. You can safely skip it if you know enough about 
 * the SQL SELECT statement. The syntax is very similar.  
 * 
 * During a query, both in-memory objects and those in database tables 
 * should be searched. The presence of in-memory objects actually presents 
 * a problem for us. We can safely pass the query string to the 
 * database to do the query if there is no loaded objects. When there are 
 * objects loaded and their variables have been altered, the database 
 * query won't be aware of the inconsistency and the results can be invalid. 
 * 
 * Two solutions to this:
 * <ol>
 * <li>
 * It would be ideal to do the same query on the in-memory objects, but
 * this apparently requires the "deep" parsing of the query string and 
 * applying the where clause on all objects. Potentially a lot of 
 * work on the parser. Plus, whether in-memory query can always outperform 
 * an all-database query is also very questionable in PHP.
 * </li>
 * <li>
 * A simple solution. Before the query, we <b>commit</b> all in-memory 
 * objects that are related to the database query. The overhead is the 
 * commiting before query. So it is important to commit only the objects 
 * of the <b>related</b> classes.
 * </li>
 * </ol>
 * 
 * Currently we implement the second option, for which we can simply 
 * process the string with class/table name replacement and pass it
 * to the database layer to execute the query. This is by any means a 
 * rudimentary implementation. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1035 $ $Date: 2007-01-31 06:46:48 -0500 (Wed, 31 Jan 2007) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epQuery {

	/**
	 * The query parser
	 * @var epQueryParser
	 */
	protected $p = false;

	/**
	 * The query builder
	 * @var epQueryBuilder
	 */
	protected $b = false;

	/**
	 * The cached syntax trees parsed for queries
	 * @var array (keyed by query)
	 */
	protected $parsed = array();

    /**
     * The last parsed EZOQL query
     * @var string
     */
    protected $oql_stmt = false;

    /**
     * Constructor
     * @param string $q (the EZOQL query string)
     * @param array $args arguments for the query
     */
    public function __construct($oql_stmt = '', $args = array()) {
        if ($oql_stmt) {
            $this->parse($oql_stmt, $args);
        }
    }
    
    /**
     * Return the EZOQL statement 
     * @return string
     */
    public function getOqlStatement() {
        return $this->oql_stmt;
    }

    /**
     * Returns the class maps involved in the query
     * @return array
     */
	public function getClassMaps() {
		return $this->b->getRootClassMaps();
	}

    /**
     * Returns whether the query has aggregate function
     * @return boolean
     */
    public function getAggregateFunction() {
        return $this->b->getAggregateFunction();
    }

    /**
     * Returns whether the query has a limit
     * @return boolean|string
     */
    public function getLimit() {
        return $this->b->getLimit();
    }

    /**
     * Returns whether the query has an order by
     * @return boolean|string
     */
    public function getOrderBy() {
        return $this->b->getOrderBy();
    }

    /**
     * Parse the EZOQL statement and translate it into equivalent 
     * SQL statement
     * 
     * @param string $q (the EZOQL query string)
     * @param array $args arguments for the query
     * @return false|string
     * @throws epExceptionQuery, epQueryExceptionBuilder
     */
    public function parse($oql_stmt, $args = array()) {
        
        // reset aggregation function
        $this->aggr_func = false;

        // get oql statement (query)
        if ($oql_stmt) {
            $this->oq_stmt = $oql_stmt;
        }

        // check if query empty
        if (!$this->oq_stmt) {
            throw new epExceptionQuery('Empty EZOQL query'); 
            return false;
        }

		// the root to the parsed syntax tree for oql stmt
		$root = null;

		// check if any cached parsed syntax tree
		if (!isset($this->parsed[$oql_stmt])) {
			
			// instantiate a query parser if not already
			if (!$this->p) {
				if (!($this->p = new epQueryParser())) {
					return false;
				}
			}

			// parse query and get syntax tree
			$this->parsed[$oql_stmt] = $this->p->parse($oql_stmt);
		}
		
		// get syntax tree from cache
		$root = $this->parsed[$oql_stmt];
		
        // check if there is any errors
        if (!$root || $errors = $this->p->errors()) {
            $emsg = 'EZOQL parsing error';
            if (isset($errors) && $errors) {
                $emsg .= ":\n";
                foreach($errors as $error) {
                    $emsg .= $error->__toString() . "\n";
                }
            } else {
                $emsg .= " (unknown)";
            }
            throw new epExceptionQuery($emsg); 
            return false;
        }

		// build the SQL query
		if (!$this->b) {
			// instantiate builder if not yet
			if (!($this->b = new epQueryBuilder($root, $this->oq_stmt, $args))) {
				// should not happen
				return false;
			}
		} else {
			// initialize builder
			$this->b->initialize($root, $this->oq_stmt, $args);
		}

        // build the SQL query from syntax tree
        return $this->b->build();
    }

}

?>