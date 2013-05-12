<?php

/**
 * $Id: epTagParser.php 1013 2006-09-27 01:55:43Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler.tag
 */
namespace ezpdo\compiler\tag;

use ezpdo\base\epUtils;
use ezpdo\orm\epFieldMap;
use ezpdo\base\parser\epLexer as epLexer;
use ezpdo\base\parser\epParser as epParser;

/**
 * The parser for ORM tag value
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler.tag
 */
class epTagParser extends epParser {

    /**
     * The current field map params
     * @var array
     */
    protected $map = array();

    /**
     * Constructor
     * @param string $s
     * @param boolean $v(verbose)
     */
    public function __construct($s = '', $verbose = false) {
        $this->verbose($verbose);
        $this->errors = array();
        $this->_lexer = new epTagLexer($s);
    }

    /**
     * Parses the stream and return the root node
     * @param bool is_class Whether parsing a class map or not
     * @return false
     */
    public function parse($is_class = false) {
        $this->message(__METHOD__);

        // is query empty?
        if ($this->peek() === false) {
            $this->error('Empty input');
            $this->message('parsing done');
            return false;
        }

        // parse and build field map
        $status = $this->fieldmap();
        $this->message('parsing done');
        if (!$status) {
            return false;
        }

        // return the parsed result
        return $this->map;
    }

    /**
     * Parse the tag statement
     * @return bool
     */
    protected function fieldmap() {

        $this->message(__METHOD__);

        // column name?
        if (($t = $this->peek()) == EPL_T_IDENTIFIER) {
            $this->map['name'] = $this->p->value;
            $this->next();
            $t = $this->peek();
        }

        // primitve data type?
        if ($t == EPL_T_DATA_TYPE) {
            return $this->primitive();
        }

        // has or composed_of?
        if ($t == EPL_T_HAS || $t == EPL_T_COMPOSED_OF) {
            return $this->relationship();
        }

        // error o.w.
        $this->syntax_error("Invalid var @orm tag: " . $this->getInput());

        return false;
    }

    /**
     * Parse primitive definition
     * @return bool
     */
    protected function primitive() {

        $this->message(__METHOD__);

        // expect an identifier
        if (EPL_T_DATA_TYPE != ($t = $this->peek())) {
            $this->syntax_error("Supported data type expected");
            return false;
        }

        // consume token
        $this->next();

        // create a primitive field map
        $this->map['type'] = $this->t->value;

        // check if '(' follows
        if ($this->peek() == '(') {
            if (false === ($params = $this->params())) {
                $this->syntax_error("Invalid parameters for [".$this->t->value."]");
                return false;
            }
            $this->map['params'] = join(',', $params);
        }

        // index or unique?
        $t = $this->peek();
        if ($t == EPL_T_INDEX || $t == EPL_T_UNIQUE) {

            // consume index or unique
            $this->next();

            // get key type
            $this->map['keytype'] = $keytype = $this->t->value;

            // get key name in ()
            if ($this->peek() == '(') {
                $params = $this->params();
                if (!$params || count($params) != 1) {
                    $this->syntax_error("Invalid parameter for " . $keytype);
                    return false;
                }
                $this->map['keyname'] = $params[0];
            }
            // nothing should exist beyond 'unique/index()'?
            else if ($this->peek() !== false) {
                $this->syntax_error("Invalid var @orm tag: " . $this->getInput());
                return false;
            }
        }

        return true;
    }

    /**
     * Parse a relatinship definition
     */
    protected function relationship() {

        $this->message(__METHOD__);

        $t = $this->peek();
        if ($t != EPL_T_HAS && $t != EPL_T_COMPOSED_OF) {
            $this->syntax_error("'has' or 'composed_of' is expected");
            return false;
        }
        $this->next();

        // fix bug 179: allow date type keywords to be class names
        $this->_lexer->toggleDataTypeTokens();

        // get type
        $type = epFieldMap::DT_HAS;
        if ($t == EPL_T_COMPOSED_OF) {
            $type = epFieldMap::DT_COMPOSED_OF;
        }

        // create a relationship field map
        $this->map['type'] = $type;
        $this->map['params'] = array();

        // one?
        $this->map['params']['is_many'] = false;
        if ($this->peek() == EPL_T_ONE) {
            $this->next();
        }
        // many?
        else if ($this->peek() == EPL_T_MANY) {
            $this->next();
            $this->map['params']['is_many'] = true;
        }

        // class
        $this->map['params']['class'] = false;
        if ($this->peek() == EPL_T_IDENTIFIER) {
            $this->next();
            $this->map['params']['class'] = $this->t->value;
        } else {
            $this->syntax_error("Class name is expected");
            return false;
        }

        // toggle data types back
        $this->_lexer->toggleDataTypeTokens();

        // inverse
        $this->map['params']['inverse'] = false;
        if ($this->peek() == EPL_T_INVERSE) {

            // consume inverse
            $this->next();

            // get inverse parameters
            $params = $this->params();
            if (!$params || count($params) != 1) {
                $this->syntax_error("Invalid parameters for inverse");
                return false;
            }
            $this->map['params']['inverse'] = $params[0];
        }

        return true;
    }

    /**
     * Reads params within parenthesis
     * @return array
     */
    protected function params() {

        $this->message(__METHOD__);

        if ('(' != $this->peek()) {
            $this->syntax_error("'(' expected");
            return false;
        }
        $t = $this->next();

        // get all params
        $params = array();
        do {

            // eat ','
            if ($t == ',') {
                $this->next();
            }

            // expect an identifier or a number
            $t = $this->peek();
            if ($t != EPL_T_IDENTIFIER && $t != EPL_T_FLOAT && $t != EPL_T_INTEGER) {
                $this->syntax_error("String or number is expected");
                return false;
            }

            // consume token
            $this->next();

            // get this param (string)
            $params[] = $this->t->value;

        } while (($t = $this->peek()) == ',');

        // consume the closing ')'
        if ($t == ')') {
            $this->next();
        }

        return $params;
    }

}
