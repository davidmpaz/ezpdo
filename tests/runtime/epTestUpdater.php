<?php

/**
 * $Id: epTestUpdater.php 1019 2006-11-29 06:26:43Z davidmpaz $
 *
 * Copyright(c) 2011 by David Paz. All rights reserved.
 *
 * @author David Paz <davidmpazphp@gmail.com>
 * @version $Revision: 1019 $ $Date: 2011-05-25 01:26:43 -0500 (Fri, 25 Mar 2011) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 * @since 1.1.6
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/epTestRuntime.php');

/**
 * The unit test class for {@link epDbUpdate}
 *
 * @author David Paz <davidmpazphp@gmail.com>
 * @version $Revision: 1019 $ $Date: 2011-05-25 01:26:43 -0500 (Fri, 25 Mar 2011) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 * @since 1.1.6
 */
class epTestUpdater extends epTestRuntime {

    /**
     * The cached manager
     * @var epManager
     */
    protected $m = false;

    /**
     * The cached updater, not using from manager
     * @var epDbUpdate
     */
    protected $u = false;

    /**
     * remove output dir in teardown
     */
    function tearDown() {
        epRmDir(dirname(__FILE__) . '/output');
        $this->u = null;
    }

    /**
     * test {@link epDbUpdate} constructor
     *
     */
    function _testCreateUpdater() {

        $this->assertTrue($m = & $this->m);

        // set up strategy, not specify backup file, we are in tests
        $m->setConfigOption('update_strategy', 'drop');

        // return everything for log instead execute in db
        $m->setConfigOption('auto_update', false);

        // force everything
        $m->setConfigOption('force_update', false);

        include_once EP_SRC_DB . '/epDbUpdate.php';

        // created
        $this->assertTrue($updater = new epDbUpdate($m->getConfig()));
        $this->assertEqual($updater->getStrategy(), 'drop');
        $this->assertFalse($updater->getClassMapFactory());
        $this->u = $updater;
    }

    /**
     * test {@link epDbUpdate::processClassMaps()}
     */
    function _testProcessClassMaps() {

        // create a classmap to find
        $cm = new epClassMap('Author');
        $cm->setTag('uuid', '001');

        $fm1 = new epFieldMapPrimitive('name', 'char', 50, $cm);
        $fm1->setTag('uuid', '011');

        $fm2 = new epFieldMapPrimitive('age', 'integer', 16, $cm);
        $fm2->setTag('uuid', '021');

        $fm3 = new epFieldMapRelationship('books', 'has', 'Book', true, false, $cm);
        $fm3->setTag('uuid', '022');
        // add the fields
        $cm->addField($fm1);
        $cm->addField($fm2);
        $cm->addField($fm3);


        $cm2 = new epClassMap('Person');
        $cm2->setTag('uuid', '001');

        $fm21 = new epFieldMapPrimitive('id', 'integer', 16, $cm2);
        $fm21->setTag('uuid', '031');

        $fm22 = new epFieldMapPrimitive('name', 'char', 50, $cm2);
        $fm22->setTag('uuid', '011');

        $fm23 = new epFieldMapRelationship('magazines', 'has', 'Book', true, false, $cm2);
        $fm23->setTag('uuid', '022');
        // add the fields
        $cm2->addField($fm21);
        $cm2->addField($fm22);
        $cm2->addField($fm23);

        $this->assertTrue($anncm = $this->u->processClassMaps($cm, $cm2));

        // it was renamed the class
        $this->assertEqual('Author', $anncm->getTag('named'));
        $this->assertEqual('Person', $anncm->getName());

        $fields = $anncm->getAllFields();
        $this->assertEqual(4, count($fields));
        $this->assertTrue(isset($fields['name']));
        $this->assertTrue(isset($fields['id']));
        $this->assertTrue(isset($fields['age']));

        // this doesn't changed
        $this->assertFalse($fields['name']->getTag(epDbUpdate::SCHEMA_NAMED_TAG));
        $this->assertFalse($fields['name']->getTag(epDbUpdate::SCHEMA_OP_TAG));

        // name of this was changed
        $this->assertEqual($fields['magazines']->getTag(epDbUpdate::SCHEMA_OP_TAG), 'alter');
        $this->assertEqual($fields['magazines']->getTag(epDbUpdate::SCHEMA_NAMED_TAG), 'books');

        // this one was added
        $this->assertEqual($fields['id']->getTag(epDbUpdate::SCHEMA_OP_TAG), 'add');

        // this one was dropped
        $this->assertEqual($fields['age']->getTag(epDbUpdate::SCHEMA_OP_TAG), 'drop');

    }

    /**
     * test {@link epDbUpdate::updateSchema()}
     */
    function _testUpdateSchema() {

        // no query executed, dry run only
        $this->m->setConfigOption('update_strategy', 'sim');
        $this->m->setConfigOption('auto_update', false);
        $this->m->setConfigOption('force_update', false);

        // create a classmap to find (partially reproduce the eptAuthor)
        $cm = $this->m->getClassMapFactory()->make('Author');
        $cm->setTag('uuid', '001');
        $cm->setOidColumn("eoid");
        // dsn to create DDL based on it (mysql, pg, sqlite)
        $cm->setDsn($this->m->getConfigOption("default_dsn"));

        $fm1 = new epFieldMapPrimitive('name', 'char', 30, $cm);
        $fm1->setTag('uuid', '0012');

        $fm2 = new epFieldMapPrimitive('age', 'integer', 3, $cm);
        $fm2->setTag('uuid', '0013');

        $fm3 = new epFieldMapPrimitive('id', 'integer', 16, $cm);
        $fm3->setTag('uuid', '0011');

        $fm4 = new epFieldMapPrimitive('address', 'char', 255, $cm);
        $fm4->setTag('uuid', '0011');

        $fm5 = new epFieldMapRelationship('books', 'eptBook', 'eptBook', true, false, $cm);
        $fm5->setTag('uuid', '0015');

        $fm6 = new epFieldMapRelationship('business_contact', 'eptContact', 'eptContact', false, false, $cm);
        $fm6->setTag('uuid', '0016');

        // add the fields
        $cm->addField($fm1); $cm->addField($fm2);
        $cm->addField($fm3); $cm->addField($fm4);
        $cm->addField($fm5); $cm->addField($fm6);

        // set up the class map factory from manager
        $this->u->setClassMapFactory($this->m->getClassMapFactory());

        if( ($dbtpye = $this->m->getDb($cm)->dbType()) == epDb::EP_DBT_SQLITE){
            try{
                // generate update DDL based in annotated class map
                $queries = $this->u->updateSchema($cm);
            }catch (epExceptionDbObject $e){
                $this->assertEqual($e->getMessage(),
                    "Unsuported operation [ALTER] for this database type");
                return true;
            }
        }
        else $queries = $this->u->updateSchema($cm);

        $this->assertTrue($queries['sucess']);
        $this->assertEqual(11, count($queries['executed']));
        $this->assertEqual(2, count($queries['ignored']));

        $method = "_assert". $dbtpye ."UpdateSchema";
        $this->$method($queries);
    }

    protected function _assertMysqlUpdateSchema($queries){

        $this->assertEqual("ALTER TABLE `eptAuthor` ADD COLUMN `address` varchar(255)",
            $queries['executed'][0]);
        $this->assertEqual("UPDATE `_ez_relation_eptauthor_eptcontact` SET `var_a` = 'business_contact' WHERE `var_a` = 'contact'",
            $queries['executed'][1]);
        $this->assertEqual("UPDATE `_ez_relation_eptauthor_eptcontact` SET `class_a` = 'Author' WHERE `class_a` = 'eptAuthor'",
            $queries['executed'][2]);
        $this->assertEqual("UPDATE `_ez_relation_eptauthor_eptcontact` SET `class_b` = 'Author' WHERE `class_b` = 'eptAuthor'",
            $queries['executed'][3]);
        $this->assertEqual("UPDATE `_ez_relation_eptauthor_eptcontact` SET `base_b` = 'Author' WHERE `base_b` = 'eptAuthor'",
            $queries['executed'][4]);
        $this->assertEqual("ALTER TABLE `_ez_relation_eptauthor_eptcontact` RENAME TO `_ez_relation_author_eptcontact`",
            $queries['executed'][5]);
        $this->assertEqual("UPDATE `_ez_relation_eptauthor_eptbook` SET `class_a` = 'Author' WHERE `class_a` = 'eptAuthor'",
            $queries['executed'][6]);
        $this->assertEqual("UPDATE `_ez_relation_eptauthor_eptbook` SET `class_b` = 'Author' WHERE `class_b` = 'eptAuthor'",
            $queries['executed'][7]);
        $this->assertEqual("UPDATE `_ez_relation_eptauthor_eptbook` SET `base_b` = 'Author' WHERE `base_b` = 'eptAuthor'",
            $queries['executed'][8]);
        $this->assertEqual("ALTER TABLE `_ez_relation_eptauthor_eptbook` RENAME TO `_ez_relation_author_eptbook`",
            $queries['executed'][9]);
        $this->assertEqual("ALTER TABLE `eptAuthor` RENAME TO `Author`",
            $queries['executed'][10]);

        $this->assertEqual("ALTER TABLE `eptAuthor` DROP COLUMN `uuid`",
            $queries['ignored'][0]);
        $this->assertEqual("ALTER TABLE `eptAuthor` DROP COLUMN `is_elite`",
            $queries['ignored'][1]);
    }

protected function _assertPostgresUpdateSchema($queries){

        $this->assertEqual('ALTER TABLE "eptAuthor" ADD COLUMN "address" varchar(255)',
            $queries['executed'][0]);
        $this->assertEqual('UPDATE "_ez_relation_eptauthor_eptcontact" SET "var_a" = \'business_contact\' WHERE "var_a" = \'contact\'',
            $queries['executed'][1]);
        $this->assertEqual('UPDATE "_ez_relation_eptauthor_eptcontact" SET "class_a" = \'Author\' WHERE "class_a" = \'eptAuthor\'',
            $queries['executed'][2]);
        $this->assertEqual('UPDATE "_ez_relation_eptauthor_eptcontact" SET "class_b" = \'Author\' WHERE "class_b" = \'eptAuthor\'',
            $queries['executed'][3]);
        $this->assertEqual('UPDATE "_ez_relation_eptauthor_eptcontact" SET "base_b" = \'Author\' WHERE "base_b" = \'eptAuthor\'',
            $queries['executed'][4]);
        $this->assertEqual('ALTER TABLE "_ez_relation_eptauthor_eptcontact" RENAME TO "_ez_relation_author_eptcontact"',
            $queries['executed'][5]);
        $this->assertEqual('UPDATE "_ez_relation_eptauthor_eptbook" SET "class_a" = \'Author\' WHERE "class_a" = \'eptAuthor\'',
            $queries['executed'][6]);
        $this->assertEqual('UPDATE "_ez_relation_eptauthor_eptbook" SET "class_b" = \'Author\' WHERE "class_b" = \'eptAuthor\'',
            $queries['executed'][7]);
        $this->assertEqual('UPDATE "_ez_relation_eptauthor_eptbook" SET "base_b" = \'Author\' WHERE "base_b" = \'eptAuthor\'',
            $queries['executed'][8]);
        $this->assertEqual('ALTER TABLE "_ez_relation_eptauthor_eptbook" RENAME TO "_ez_relation_author_eptbook"',
            $queries['executed'][9]);
        $this->assertEqual('ALTER TABLE "eptAuthor" RENAME TO "Author"',
            $queries['executed'][10]);

        $this->assertEqual('ALTER TABLE "eptAuthor" DROP COLUMN "uuid"',
            $queries['ignored'][0]);
        $this->assertEqual('ALTER TABLE "eptAuthor" DROP COLUMN "is_elite"',
            $queries['ignored'][1]);
    }

    /**
     * Run all tests
     * @param string $dbal (adodb, peardb)
     * @param string $dbtype (mysql, sqlite)
     */
    function _allTests($dbal, $dbtype) {

        echo "tests for $dbal/$dbtype started.. " . epNewLine();

        echo "  setup..";
        $this->_setUp($dbal, $dbtype);
        echo "done " . epNewLine();

        echo "  create updater..";
        $this->_testCreateUpdater();
        echo "done " . epNewLine();

        echo "  process class map..";
        $this->_testProcessClassMaps();
        echo "done " . epNewLine();

        echo "  update schema..";
        $this->_testUpdateSchema();
        echo "done " . epNewLine();

        echo "  complete!" . epNewLine();
    }

    /**
     * Test adodb & mysql
     */
    function testAdodbMysql() {

        // skip testing adodb + mysql if not allowed
        if (!$this->canTestAdodb() || !$this->canTestMysql()) {
            return;
        }

        $this->_allTests('adodb', 'mysql');
    }

    /**
     * Test peardb & mysql
     */
    function testPearMysql() {

        // skip testing peardb + mysql if not allowed
        if (!$this->canTestPeardb() || !$this->canTestMysql()) {
            return;
        }

        $this->_allTests('peardb', 'mysql');
    }

    /**
     * Test pdo & mysql
     */
    function testPdoMysql() {

        // skip testing pdo + mysql if not allowed
        if (!$this->canTestPdo('mysql') || !$this->canTestMysql()) {
            return;
        }

        $this->_allTests('pdo', 'mysql');
    }

    /**
     * Test adodb & pgsql
     */
    function testAdodbPgsql() {

        // skip testing adodb + pgsql if not allowed
        if (!$this->canTestAdodb() || !$this->canTestPgsql()) {
            return;
        }

        $this->_allTests('adodb', 'pgsql');
    }

    /**
     * Test peardb & pgsql
     */
    function testPearPgsql() {

        // skip testing peardb + mysql if not allowed
        if (!$this->canTestPeardb() || !$this->canTestPgsql()) {
            return;
        }

        $this->_allTests('peardb', 'pgsql');
    }

    /**
     * Test pdo & pgsql
     */
    function testPdoPgsql() {

        // skip testing pgsql if not allowed
        if (!$this->canTestPdo('pgsql') || !$this->canTestPgsql()) {
            return;
        }

        $this->_allTests('pdo', 'pgsql');
    }

    /**
     * Test adodb & sqlite
     */
    function testAdodbSqlite() {

        // skip testing sqlite if not allowed
        if (!$this->canTestAdodb() || !$this->canTestSqlite()) {
            return;
        }

        $this->_allTests('adodb', 'sqlite');
    }

    /**
     * Test peardb & mysql
     */
    function testPearSqlite() {

        // skip testing sqlite if not allowed
        if (!$this->canTestPeardb() || !$this->canTestSqlite()) {
            return;
        }

        $this->_allTests('peardb', 'sqlite');
    }


    /**
     * Test pdo & sqlite
     */
    function testPdoSqlite() {

        // skip testing sqlite if not allowed
        if (!$this->canTestPdo('sqlite') || !$this->canTestSqlite()) {
            return;
        }

        $this->_allTests('pdo', 'sqlite');
    }
}

if (!defined('EP_GROUP_TEST')) {

    $tm = microtime(true);

    $t = new epTestUpdater();
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }

    $elapsed = microtime(true) - $tm;

    echo epNewLine() . 'Time elapsed: ' . $elapsed . ' seconds' . epNewLine();
}

?>
