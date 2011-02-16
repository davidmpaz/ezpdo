<?php

/**
 * $Id: epTestQueryPath.php 969 2006-05-19 12:20:19Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 969 $ $Date: 2006-05-19 08:20:19 -0400 (Fri, 19 May 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.query
 */

/**#@+
 * need runtime testcase (under ../runtime) and epQueryBuilder 
 */
include_once(dirname(__FILE__).'/../runtime/epTestRuntime.php');
include_once(EP_SRC_QUERY.'/epQueryPath.php');
/**#@-*/

/**
 * The unit test class for {@link epQueryPathManager}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 969 $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.query
 */
class epTestQueryPath extends epTestRuntime {
    
    // Setup for tests
    function setUp() {
        
        // call parent setup
        parent::setUp();
        
        // runtime setup
        $this->_setup('adodb', 'mysql');
        $this->assertTrue($this->m);
    }
    
    // test {@link epQueryAliasManager}
    function _testQueryBuilderAlias() {
        
        // get alias generator
        $this->assertTrue($am = new epQueryAliasManager());

        // check alias generation for classes
        $class = "TestClass";
        $this->assertTrue($alias1 = $am->getClassAlias($class, true)); // true: create
        $this->assertTrue($alias2 = $am->getClassAlias($class, true)); // true: create
        $this->assertFalse($alias1 == $alias2); // unique aliases
        $this->assertTrue($class == $am->getClass($alias1));
        $this->assertTrue($class == $am->getClass($alias2));
        $this->assertTrue(2 == count($am->getClassAliases($class)));
        
        // check alias generation for tables
        $table = "TestTable";
        $this->assertTrue($alias3 = $am->getTableAlias($table, true)); // true: create
        $this->assertTrue($alias4 = $am->getTableAlias($table, true)); // true: create
        $this->assertFalse($alias3 == $alias4); // unique aliases
        $this->assertTrue($table == $am->getTable($alias3));
        $this->assertTrue($table == $am->getTable($alias4));
        $this->assertTrue(2 == count($am->getTableAliases($table)));

        // alias uniqueness
        $this->assertFalse($alias1 == $alias3); // unique aliases
        $this->assertFalse($alias1 == $alias4); // unique aliases
        $this->assertFalse($alias2 == $alias3); // unique aliases
        $this->assertFalse($alias2 == $alias4); // unique aliases
    }

    // test {@link epQueryPathRoot}
    function _testQueryPathNodeRoot() {
        
        // get class map of eptAuthor
        $this->assertTrue($cm = $this->m->getClassMap('eptAuthor'));
        
        // create alias generator
        $this->assertTrue($am = new epQueryAliasManager());
        
        // create a root node
        $this->assertTrue($root = new epQueryPathRoot($cm, $alias = false, epQueryPathRoot::PRIMARY, $am));
        
        // check if class map is set
        $this->assertTrue($cm === $root->getMap());
        
        // check if alias is set
        $this->assertTrue($alias = $am->getClassAlias('eptAuthor'));
        $this->assertTrue($alias == $root->getAlias());

        // --- 1 ---

        // test find last node on path .contact.zipcode
        $this->assertFalse($node1 = $root->findNode('.contact.zipcode'));
        
        // insert the path to root
        $this->assertTrue($node1 = $root->insertPath('.contact.zipcode'));
        
        // returned node should be epQueryPathField
        $this->assertTrue($node1 instanceof epQueryPathField);
        $this->assertTrue($node1->getName() == 'zipcode');
        $this->assertTrue($node1->getMap()->getName() == 'zipcode');
        $this->assertTrue($node1->getParent()->getName() == 'contact');
        $this->assertTrue($node1->getParent()->getMap()->getName() == 'contact');
        
        // test findNode again
        $this->assertTrue($node1 = $root->findNode('.contact.zipcode'));
        $this->assertTrue($node1 instanceof epQueryPathField);
        $this->assertTrue($node1->getName() == 'zipcode');
        $this->assertTrue($node1->getMap()->getName() == 'zipcode');
        $this->assertTrue($node1->getParent()->getName() == 'contact');
        $this->assertTrue($node1->getParent()->getMap()->getName() == 'contact');

        // --- 2 ---

        // test find last node on path .contact.phone
        $this->assertFalse($node2 = $root->findNode('.contact.phone'));
        
        // insert the path to root
        $this->assertTrue($node2 = $root->insertPath('.contact.phone'));
        
        // returned node should be epQueryPathField
        $this->assertTrue($node2 instanceof epQueryPathField);
        $this->assertTrue($node2->getName() == 'phone');
        $this->assertTrue($node2->getMap()->getName() == 'phone');
        $this->assertTrue($node2->getParent()->getName() == 'contact');
        $this->assertTrue($node2->getParent()->getMap()->getName() == 'contact');
        
        // test findNode again
        $this->assertTrue($node2 = $root->findNode('.contact.phone'));
        $this->assertTrue($node2 instanceof epQueryPathField);
        $this->assertTrue($node2->getName() == 'phone');
        $this->assertTrue($node2->getMap()->getName() == 'phone');
        $this->assertTrue($node2->getParent()->getName() == 'contact');
        $this->assertTrue($node2->getParent()->getMap()->getName() == 'contact');

        // the two paths should share .contact
        $this->assertTrue(($parent = & $node1->getParent()) === $node2->getParent());
        $this->assertTrue(2 == count($parent->getChildren()));
        $this->assertTrue($node1 === $parent->getChild('zipcode'));
        $this->assertTrue($node2 === $parent->getChild('phone'));
    }

    // test {@link epQueryPathManager}: one super root only
    function _testQueryBuilderPath_1() {

        // create an instance of epQueryPathManager
        $this->assertTrue($p = new epQueryPathManager);

        // add the super root
        $this->assertTrue($p->addPrimaryRoot('eptAuthor', $alias));

        // insert path 
        $this->assertTrue($p->insertPath($alias . '.contact.zipcode'));

        // generate sql parts
        $this->assertTrue($p->generateSql());

        // get sql part for the primary root
        $this->assertTrue($sql_parts = $p->getRootSql());

        // check primary alias
        $this->assertTrue(isset($sql_parts[$alias]));

        // check joins
        $this->assertEqual(
            $sql = trim($sql_parts[$alias]['eptAuthor']), 
            "LEFT JOIN `_ez_relation_eptauthor_eptcontact` AS `_2` ON " 
            . "`_2`.var_a = 'contact' " 
            . "AND (" 
            . "`_2`.class_a = 'eptAuthor' " 
            . "AND `_2`.oid_a = `_1`.`eoid`" 
            . ") " 
            . "LEFT JOIN `eptContact` AS `_3` ON " 
            . "`_2`.base_b = 'eptContact' "
            . "AND `_2`.class_b = 'eptContact' " 
            . "AND `_2`.oid_b = `_3`.`eoid`"
            );
        //echo "\n\n$sql\n\n";
    }

    // test {@link epQueryPathManager}: one super root & one secondary
    function _testQueryBuilderPath_2() {

        // create an instance of epQueryPathManager
        $this->assertTrue($p = new epQueryPathManager);

        // add the super root
        $this->assertTrue($p->addPrimaryRoot('eptAuthor', $alias));

        // insert path 
        $this->assertTrue($p->insertPath($alias . '.contact.zipcode'));

        // add a secondary root
        $this->assertTrue($p->addSecondaryRoot('eptBook', 'bk'));
        
        // insert path to the secondary root
        $this->assertTrue($p->insertPath('bk.authors'));

        // generate sql parts
        $this->assertTrue($p->generateSql());

        // get sql part for the primary root
        $this->assertTrue($sql_parts = $p->getRootSql());

        // check primary root
        $this->assertTrue(isset($sql_parts[$alias]));

        // check primary statement
        $this->assertEqual(
            $sql_parts[$alias]['eptAuthor'],
            "LEFT JOIN `_ez_relation_eptauthor_eptcontact` AS `_2` ON " 
            . "`_2`.var_a = 'contact' " 
            . "AND (" 
            . "`_2`.class_a = 'eptAuthor' " 
            . "AND `_2`.oid_a = `_1`.`eoid`" 
            . ") " 
            . "LEFT JOIN `eptContact` AS `_3` ON " 
            . "`_2`.base_b = 'eptContact' " 
            . "AND `_2`.class_b = 'eptContact' " 
            . "AND `_2`.oid_b = `_3`.`eoid` " 
            );
        
        // check secondary root
        $this->assertTrue(isset($sql_parts['bk']));

        // check secondary statement
        $this->assertEqual(
            $sql_parts['bk']['eptBook'],
            "LEFT JOIN `_ez_relation_eptauthor_eptbook` AS `_4` ON " 
            . "`_4`.var_a = 'authors' " 
            . "AND (" 
            . "`_4`.class_a = 'eptBook' " 
            . "AND `_4`.oid_a = `bk`.`eoid`" 
            . ") " 
            . "LEFT JOIN `eptAuthor` AS `_5` ON " 
            . "`_4`.base_b = 'eptAuthor' " 
            . "AND `_4`.class_b = 'eptAuthor' " 
            . "AND `_4`.oid_b = `_5`.`eoid` "
            );
    }

    // test {@link epQueryPathManager}: one super root only
    function _testQueryBuilderPath_3() {

        // create an instance of epQueryPathManager
        $this->assertTrue($p = new epQueryPathManager);

        // insert super root
        $this->assertTrue($p->addPrimaryRoot('eptBook', $alias));

        // insert path 
        $path = $alias . '.authors';
        $this->assertTRue($p->insertPath($path));

        // generate sql parts
        $this->assertTrue($p->generateSql());

        // get sql part for the primary root
        $this->assertTrue($sql_parts = $p->getRootSql());

        // check primary root
        $this->assertTrue(isset($sql_parts[$alias]));

        // check primary statement
        $this->assertEqual(
            $sql_parts[$alias]['eptBook'],
            "LEFT JOIN `_ez_relation_eptauthor_eptbook` AS `_2` ON " 
            . "`_2`.var_a = 'authors' " 
            . "AND (" 
            . "`_2`.class_a = 'eptBook' " 
            . "AND `_2`.oid_a = `_1`.`eoid`" 
            . ") " 
            . "LEFT JOIN `eptAuthor` AS `_3` ON " 
            . "`_2`.base_b = 'eptAuthor' "
            . "AND `_2`.class_b = 'eptAuthor' "
            . "AND `_2`.oid_b = `_3`.`eoid` "
            );
    }

    // test {@link epQueryPathManager}: one super root and one contained root
    function testQueryBuilderPath_4() {

        // create an instance of epQueryPathManager
        $this->assertTrue($p = new epQueryPathManager);

        // insert super root
        $this->assertTrue($p->addPrimaryRoot('eptBook', $alias));

        // insert path 
        $path = $alias . '.authors';
        $this->assertTRue($p->insertPath($path));

        // add contained node
        $this->assertTrue($p->addContainedRoot($path, $a = 'a1'));

        // insert path to the contained node
        $this->assertTRue($p->insertPath('a1.contact'));

        // generate sql parts
        $this->assertTrue($p->generateSql());

        // get sql part for the primary root
        $this->assertTrue($sql_parts = $p->getRootSql());

        // check primary root
        $this->assertTrue(isset($sql_parts[$alias]));

        // check primary statement
        $this->assertEqual(
            $sql_parts[$alias]['eptBook'],
            "LEFT JOIN `_ez_relation_eptauthor_eptbook` AS `_4` ON " 
            . "`_4`.var_a = 'authors' " 
            . "AND (" 
            . "`_4`.class_a = 'eptBook' " 
            . "AND `_4`.oid_a = `_1`.`eoid`" 
            . ") " 
            . "LEFT JOIN `eptAuthor` AS `_3a1` ON " 
            . "`_4`.base_b = 'eptAuthor' " 
            . "AND `_4`.class_b = 'eptAuthor' " 
            . "AND `_4`.oid_b = `_3a1`.`eoid` "
            . "LEFT JOIN `_ez_relation_eptauthor_eptcontact` AS `_5` ON " 
            . "`_5`.var_a = 'contact' " 
            . "AND (" 
            . "`_5`.class_a = 'eptAuthor' " 
            . "AND `_5`.oid_a = `_3a1`.`eoid`" 
            . ") " 
            . "LEFT JOIN `eptContact` AS `_6` ON " 
            . "`_5`.base_b = 'eptContact' " 
            . "AND `_5`.class_b = 'eptContact' " 
            . "AND `_5`.oid_b = `_6`.`eoid` "
            );
    }
}

if (!defined('EP_GROUP_TEST')) {
    $tm = microtime(true);
    $t = new epTestQueryPath;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
    $elapsed = microtime(true) - $tm;
    echo epNewLine() . 'Time elapsed: ' . $elapsed . ' seconds' . "\n";
}

?>
