<?php

/**
 * $Id: epTestFieldMap.php 185 2011-03-09 16:54:21Z davidmpaz $
 *
 *
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision: 185 $ $Date: 2011-03-09 12:54:21 -0400 (Wed, 17 Mar 2011) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.orm
 */
namespace ezpdo\tests\orm;

use ezpdo\base\epUtils;
use ezpdo\tests\src\epTestCase;
use ezpdo\orm\epFieldMapFactory;

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/../src/epTestCase.php');

/**
 * need epUtils
 */
include_once(EP_SRC_BASE.'/epUtils.php');

/**
 * Unit test class for {@link epFieldMapFactory}
 *
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision: 185 $ $Date: 2011-03-09 12:54:21 -0400 (Wed, 17 Mar 2011) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.orm
 */
class epTestFieldMap extends epTestCase {

    /**
     * test basic functinos of field map factory
     */
    function testFieldMapFactoryBasic() {

        // need epClassMapFactory
        include_once(EP_SRC_ORM.'/epFieldMap.php');

        // create a field map
        $fm1 = & epFieldMapFactory::make('epFieldName', 'char', '40');
        $this->assertTrue(isset($fm1));

        // create field map
        $fm2 = &  epFieldMapFactory::make('epFieldName2', 'char', '40');
        $this->assertTrue(isset($fm1));

        // test compatibility from char to char
        $this->assertTrue($fm1->isTypeCompatible($fm2));

        // change types
        $fm1->setType('bit');
        $fm2->setType('bool');

        // test compatibility from bit to bool
        $this->assertTrue($fm1->isTypeCompatible($fm2));

        // change types
        $fm1->setType('date');
        $fm2->setType('int');

        // test compatibility from date to int
        $this->assertTrue($fm1->isTypeCompatible($fm2));

        // change types
        $fm1->setType('date');
        // date, time and datetime are mapped to int(16)
        $fm1->setTypeParams('16');
        $fm2->setType('int'); $fm2->setTypeParams('20');

        // test compatibility from date to int
        $this->assertTrue($fm2->isTypeCompatible($fm1));
        $this->assertFalse($fm1->isTypeCompatible($fm2));

        // change types
        $fm1->setType('decimal'); $fm1->setTypeParams(array('3', '5'));
        $fm2->setType('decimal'); $fm2->setTypeParams(array('5', '5'));

        // test compatibility from decimal to decimal
        $this->assertTrue($fm2->isTypeCompatible($fm1));

        // change presicion
        $fm1->setTypeParams(array('6', '5'));
        $fm2->setTypeParams(array('5', '5'));
        $this->assertFalse($fm2->isTypeCompatible($fm1));

    }
}

if (!defined('EP_GROUP_TEST')) {
    $t = new epTestFieldMap();
    if ( epUtils::epIsWebRun() ) {
        $t->run(new \HtmlReporter());
    } else {
        $t->run(new \TextReporter());
    }
}

?>
