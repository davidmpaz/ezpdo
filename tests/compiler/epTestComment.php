<?php

/**
 * $Id: epTestComment.php 974 2006-05-20 13:19:19Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 974 $ $Date: 2006-05-20 09:19:19 -0400 (Sat, 20 May 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.base
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/../src/epTestCase.php');

/**
 * need ezpdo utils
 */
include_once(EP_SRC_BASE.'/epUtils.php');

/**
 * need epClassCompiler to test
 */
include_once(EP_SRC_COMPILER.'/epComment.php');

/**
 * The unit test class for {@link epComment}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 974 $ $Date: 2006-05-20 09:19:19 -0400 (Sat, 20 May 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.compiler
 */
class epTestComment extends epTestCase {
    
    /**
     * Maximum number of repeats
     */
    const MAX_REPEATS = 25;
    
    /**
     * Return a random number of spaces
     * @return string
     */
    function randSpaces() {
        return str_repeat(' ', rand(1, self::MAX_REPEATS));
    }

    /**
     * Return a random number of spaces
     * @return string
     */
    function randStars() {
        return str_repeat('*', rand(1, self::MAX_REPEATS));
    }

    /**
     * Return a random number of new lines (starting with spaces followed by stars) 
     * @return string
     */
    function randStarLines() {
        $s = '';
        for($i = 0; $i < rand(1, self::MAX_REPEATS); $i ++) {
            $s .= $this->randSpaces() . $this->randStars() . "\n";    
        }
        return $s;
    }
    
    /**
     * Return a "wild" start of a docblock
     * @return string
     */
    function randDocBlockStart() {
        $s = $this->randSpaces() .  "/" . $this->randStars() . "\n";
        $s .= $this->randStarLines();
        return $s;
    }
    
    /**
     * Return a "wild" comment line with a tag pair value
     * @return string
     */
    function randTagValue($tag, $value) {
        
        // add @ to tag if needed
        $tag = trim($tag);
        if ($tag[0] != '@') {
            $tag = '@' . $tag;
        }
        
        $s = $this->randStarLines();
        $s .= $this->randSpaces() . $this->randStars() . $this->randSpaces();
        $s .= $tag . $this->randSpaces() . $value . $this->randSpaces() . "\n";
        $s .= $this->randStarLines();
        
        return $s;
    }
    
    /**
     * Return a "wild" end of a docblock
     * @return string
     */
    function randDocBlockEnd() {
        $s = $this->randStars() . "/";
        return $s;
    }
    
    /**
     * Test the comment parser
     */
    function testComment() {
        
        // make a random array of tag_value pairs
        $tag_values = array();
        for($i = 0; $i < self::MAX_REPEATS; $i ++) {
            $tag_values[md5(rand(1, self::MAX_REPEATS))] = (string)md5(md5(time()));
        }
        
        // make a comment block 
        $comment = $this->randDocBlockStart();
        foreach($tag_values as $tag => $value) {
            $comment .= $this->randTagValue($tag, $value);
        }
        $comment .= $this->randDocBlockEnd();
        
        // now parse the comment
        $this->assertTrue($c = new epComment($comment));
        $this->assertTrue($tags = $c->getTags());
        foreach($tags as $tag => $value) {
            $this->assertTrue($c->getTagValue($tag) == $tag_values[$tag]);
        }
    }
    
    /**
     * Test class orm tag
     */
    function testClassTag() {
        
        // dsn and table
        $dsn = 'mysql://dbuser:secret@localhost/ezpdo';
        $table = "mytable";
        $oid = md5(rand(0, 1000000));
        
        // make a "wild" docblock
        $comment = $this->randDocBlockStart();
        $comment .= $this->randTagValue('@orm', $table . $this->randSpaces() . $dsn . $this->randSpaces() . "oid($oid)");
        $comment .= $this->randDocBlockEnd();
        
        // parse orm tag parser 
        $c = new epComment($comment);
        $this->assertTrue($c);
        
        // get orm tag value
        $value = $c->getTagValue('orm');
        $this->assertTrue(stristr($value, $dsn) != false);
        $this->assertTrue(stristr($value, $table) != false);
        
        // parse tag value
        $this->assertTrue($t = new epClassTag);
        $this->assertTrue(true === $t->parse($value));
        
        // check dsn
        $this->assertTrue($t->get('dsn') == $dsn);
        $this->assertTrue($t->get('table') == $table);
        $this->assertTrue($t->get('oid') == $oid);
    }

    /**
     * Test var orm tag for has|composed_of
     */
    function testVarRelTag() {
        
        // get all supported column types
        include_once(EP_SRC_ORM.'/epFieldMap.php');
        
        $types = array('has', 'composed_of');
        $amounts = array('', 'one', 'many');
        $classes = array('epBook');
        $inverse_vars = array('', 'book');

        // data types are allowed (except has/composed_of to be classes
        $classes = array_merge($classes, epFieldMap::getSupportedTypes());

        foreach ($types as $type) {
            foreach ($amounts as $amount) {
                foreach ($classes as $class) {
                    foreach ($inverse_vars as $inverse_var) {
                        $this->_varRelTag($type, $amount, $class, $inverse_var);
                    }
                }
            }
        }
    }

    /**
     * Test var orm tag for has|composed_of
     */
    function _varRelTag($type, $amount, $class, $inverse_var) {
        $type_params = '';
        $type_params .= $type;
        $is_many = false;
        if ($amount) {
            if ($amount == 'many') {
                $is_many = true;
            }
            $type_params .= $this->randSpaces() . $amount;
        }
        $type_params .= $this->randSpaces() . $class;
        $inverse = false;
        if ($inverse_var) {
            $inverse = true;
            $type_params .= $this->randSpaces() . 'inverse' . $this->randSpaces() . '(' . $this->randSpaces() . $inverse_var . $this->randSpaces() . ')';
        }

        $comment = $this->randDocBlockStart();
        $comment .= $this->randTagValue('@orm', $type_params);
        $comment .= $this->randDocBlockEnd();

        // parse orm tag parser 
        $c = new epComment($comment);
        $this->assertTrue($c);

        // get orm tag value
        $value = $c->getTagValue('orm');
        $this->assertTrue(stristr($value, $type) !== false);
        if ($amount) {
            $this->assertTrue(stristr($value, $amount) !== false);
        }
        $this->assertTrue(stristr($value, $class) !== false);
        if ($inverse_var) {
            $this->assertTrue(stristr($value, $inverse_var) !== false);
        }

        // parse tag value
        $t = new epVarTag;
        $this->assertTrue($t);
        $this->assertTrue($t->parse($value) === true);

        // check parts
        $this->assertTrue($t->get('type') === $type);
        $this->assertTrue($params = $t->get('params'));
        $this->assertTrue($params['is_many'] === $is_many);
        $this->assertTrue($params['class'] === $class);
        if ($inverse_var) {
            $this->assertTrue($params['inverse'] === $inverse_var);
        } else {
            $this->assertTrue($params['inverse'] === false);
        }
    }

    /**
     * Test var orm tag
     */
    function testVarPrimTag() {
        
        // get all supported column types
        include_once(EP_SRC_ORM.'/epFieldMap.php');
        $alltypes = epFieldMap::getSupportedTypes();

        $keytypes = array('', 'unique', 'index');
        $keynames = array('', 'testingname');
        
        foreach($alltypes as $type) {

            // skip relationship types
            if ($type == epFieldMap::DT_HAS || $type == epFieldMap::DT_COMPOSED_OF) {
                continue;
            }
            
            $name = '_' . md5($type);
            $params = array();
            if ($type == epFieldMap::DT_CHAR || $type == epFieldMap::DT_TEXT || $type == epFieldMap::DT_BLOB) {
                $params[] = rand(1, self::MAX_REPEATS);
            } elseif ($type == epFieldMap::DT_DECIMAL) {
                $params[] = rand(1, self::MAX_REPEATS);
                $params[] = rand(1, self::MAX_REPEATS);
            }

            foreach ($keytypes as $keytype) {
                foreach ($keynames as $keyname) {
                    // skip the situations where we have no keytype but we have a keyname
                    if ($keytype == '' && $keyname != '') {
                        continue;
                    }
                    $this->_varPrimTag($name, $type, $params, $keytype, $keyname);
                }
            }
        }
    }

    /**
     * Test var orm tag for has|composed_of
     */
    function _varPrimTag($name, $type, $params, $keytype, $keyname) {
        $type_params = '';
        if ($name) {
            $type_params .= $name . $this->randSpaces();
        }

        $type_params .= $type;

        if (!empty($params)) {
            // store the string for a test
            $params_str = $this->randSpaces() . '(' . $this->randSpaces();
            $params_str .= join(',' . $this->randSpaces(), $params);
            $params_str .= $this->randSpaces() . ')';

            $type_params .= $params_str;
        }

        if ($keytype) {
            $type_params .= $this->randSpaces() . $keytype;
        }

        if ($keyname) {
            $type_params .= $this->randSpaces() . '(' . $this->randSpaces() . $keyname . $this->randSpaces() . ')';
        }

        $comment = $this->randDocBlockStart();
        $comment .= $this->randTagValue('@orm', $type_params);
        $comment .= $this->randDocBlockEnd();

        // parse orm tag parser 
        $c = new epComment($comment);
        $this->assertTrue($c);

        // get orm tag value
        $value = $c->getTagValue('orm');
        if ($name) {
            $this->assertTrue(stristr($value, $name) !== false);
        }
        $this->assertTrue(stristr($value, $type) !== false);
        if (!empty($params)) {
            $this->assertTrue(stristr($value, $params_str) !== false);
        }
        if ($keytype) {
            $this->assertTrue(stristr($value, $keytype) !== false);
        }
        if ($keyname) {
            $this->assertTrue(stristr($value, $keyname) !== false);
        }

        // parse tag value
        $t = new epVarTag;
        $this->assertTrue($t);
        $this->assertTrue($t->parse($value) === true);

        // check parts
        $this->assertTrue($t->get('type') === $type);

        if (!empty($params)) {
            $this->assertTrue($t->get('params') === join(',', $params));
        } else {
            $this->assertTrue($t->get('params') === null);
        }

        if ($keytype) {
            $this->assertTrue($t->get('keytype') === $keytype);
        } else {
            $this->assertTrue($t->get('keytype') === null);
        }

        if ($keyname) {
            $this->assertTrue($t->get('keyname') === $keyname);
        } else {
            $this->assertTrue($t->get('keyname') === null);
        }
    }

}

if (!defined('EP_GROUP_TEST')) {
    $t = new epTestComment;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
}

?>
