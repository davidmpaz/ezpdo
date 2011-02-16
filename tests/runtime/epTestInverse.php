<?php

/**
 * $Id: epTestInverse.php 1005 2006-06-23 09:40:05Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1005 $ $Date: 2006-06-23 05:40:05 -0400 (Fri, 23 Jun 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/epTestRuntime.php');

/**
 * The unit test class for inverses   
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1005 $ $Date: 2006-06-23 05:40:05 -0400 (Fri, 23 Jun 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 */
class epTestInverse extends epTestRuntime {
    
    /**
     * Test inverses
     */
    function testOneValuedInverses() {
        
        // setup manager
        $this->_setup('adodb', 'sqlite');
        $this->assertTrue($this->m);

        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneA.php');
        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneB.php');

        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a1 = $this->m->create('eptInvOneA'));
        $this->assertTrue($b1 = $this->m->create('eptInvOneB'));

        // set $a1 to $b1->a
        $b1->a = $a1;
        $this->assertTrue($a1->b === $b1);

        // remove $b1 from $a1
        $a1->b = null;
        $this->assertTrue(empty($b1->a));
    
        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a2 = $this->m->create('eptInvOneA'));
        $this->assertTrue($b2 = $this->m->create('eptInvOneB'));

        // set $b2 to $a2->b
        $a2->b = $b2;
        $this->assertTrue($b2->a === $a2);
        
        // remove $a2 from $b2
        $b2->a = null;
        $this->assertTrue(empty($a2->b));
    }

    /**
     * Test one-valued inverses with refresh (bug #118)
     */
    function testOneValuedInversesAfterRefresh() {
        
        // setup manager
        $this->_setup('adodb', 'sqlite');
        $this->assertTrue($this->m);

        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneA.php');
        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneB.php');

        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a1 = $this->m->create('eptInvOneA'));
        $this->assertTrue($b1 = $this->m->create('eptInvOneB'));

        // set $a1 to $b1->a
        $b1->a = $a1;

        //simulating reload (e.g. after a page refresh)
        $this->m->flush();
        $this->m->refresh($a1);
        $this->m->refresh($b1);
        
        $this->assertTrue($a1->b === $b1);
        //used to fail in ezpdo 1.1.0rc2 and ezpdo.2005.12.05/06
    }

    /**
     * Test one-valued inverses to self (bug #178)
     */
    function testOneValuedInversesSortedList() {

        // setup manager
        $this->_setup('adodb', 'sqlite');
        $this->assertTrue($this->m);

        include_once(EP_TESTS.'/classes/inverses/src/eptSortedList.php');

        // delete all items
        $this->m->deleteAll('eptSortedList');
        
        // create a sorted list
        $listItem1 = $this->m->create('eptSortedList');
        $listItem2 = $this->m->create('eptSortedList');
        $listItem3 = $this->m->create('eptSortedList');
        $listItem4 = $this->m->create('eptSortedList');
        
        // link the list
        $listItem1->predecessor = null;
        $listItem1->successor = $listItem2;
        $listItem1->entry = "_1_";
        
        $listItem2->predecessor = $listItem1;
        $listItem2->successor = $listItem3;
        $listItem2->entry = "_2_";
        
        $listItem3->predecessor = $listItem2;
        $listItem3->successor = $listItem4;
        $listItem3->entry = "_3_";
        
        $listItem4->predecessor = $listItem3;
        $listItem4->successor = null;
        $listItem4->entry = "_4_";
        

        // assert order is right
        $this->assertTrue(is_null($listItem1->predecessor));
        $this->assertTrue($listItem1->successor === $listItem2);

        $this->assertTrue($listItem2->predecessor === $listItem1);
        $this->assertTrue($listItem2->successor === $listItem3);

        $this->assertTrue($listItem3->predecessor === $listItem2);
        $this->assertTrue($listItem3->successor === $listItem4);

        $this->assertTrue($listItem4->predecessor === $listItem3);
        $this->assertTrue(is_null($listItem4->successor));

        // persist
        $this->m->flush();

        // move listItem3 one step up
        $listItem3->successor = $listItem2;
        $listItem3->predecessor = $listItem1;
        $listItem2->successor = $listItem4;

        // assert order is right
        $this->assertTrue(is_null($listItem1->predecessor));
        $this->assertTrue($listItem1->successor === $listItem3);

        $this->assertTrue($listItem2->predecessor === $listItem3);
        $this->assertTrue($listItem2->successor === $listItem4);

        $this->assertTrue($listItem3->predecessor === $listItem1);
        $this->assertTrue($listItem3->successor === $listItem2);

        $this->assertTrue($listItem4->predecessor === $listItem2);
        $this->assertTrue(is_null($listItem4->successor));

        // delete all items
        $this->m->deleteAll('eptSortedList');
    }

    /**
     * Test many-valued inverses
     */
    function testManyValuedInverses() {
        
        // setup manager
        $this->_setup('adodb', 'sqlite');
        $this->assertTrue($this->m);

        include_once(EP_TESTS.'/classes/inverses/src/eptInvManyA.php');
        include_once(EP_TESTS.'/classes/inverses/src/eptInvManyB.php');

        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a1 = $this->m->create('eptInvManyA'));
        $this->assertTrue($b1 = $this->m->create('eptInvManyB'));

        // set $a1 to $b1->a
        $b1->as[] = $a1;
        $this->assertTrue($b1->as->inArray($a1));
        $this->assertTrue($a1->bs->inArray($b1));
    
        // remove $b1 from $a1->bs
        $a1->bs->remove($b1);
        $this->assertFalse($b1->as->inArray($a1));
        $this->assertFalse($a1->bs->inArray($b1));
    
        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a2 = $this->m->create('eptInvManyA'));
        $this->assertTrue($b2 = $this->m->create('eptInvManyB'));

        // set $b2 to $a2->bs
        $a2->bs[] = $b2;
        $this->assertTrue($a2->bs->inArray($b2));
        $this->assertTrue($b2->as->inArray($a2));
        
        // remove $a2 from $b2->as
        $b2->as->remove($a2);
        $this->assertFalse($b2->as->inArray($a2));
        $this->assertFalse($a2->bs->inArray($b2));
    }
    
    /**
     * Test one-valued and many-valued inverses
     */
    function testOneManyValuedInverses() {
        
        // setup manager
        $this->_setup('adodb', 'sqlite');
        $this->assertTrue($this->m);

        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneManyA.php');
        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneManyB.php');

        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a1 = $this->m->create('eptInvOneManyA'));
        $this->assertTrue($b1 = $this->m->create('eptInvOneManyB'));

        // set $a to $b->a
        $b1->a = $a1;
        $this->assertTrue($b1->a === $a1);
        $this->assertTrue($a1->bs->inArray($b1));
    
        // remove $b1 from $a1->bs
        $a1->bs->remove($b1);
        $this->assertFalse($a1->bs->inArray($b1));
        $this->assertFalse($b1->a);

        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a2 = $this->m->create('eptInvOneManyA'));
        $this->assertTrue($b2 = $this->m->create('eptInvOneManyB'));

        // set $a to $b->a
        $a2->bs[] = $b2;
        $this->assertTrue($a2->bs->inArray($b2));
        $this->assertTrue($b2->a === $a2);
        
        // reset $b2->a
        $b2->a = null;
        $this->assertFalse($b2->a);
        $this->assertFalse($a2->bs->inArray($b2));
    }
    
    /**
     * Test one-valued inverses with non commitable item
     * Check for bug 188
     */
    function testOneNonCommittableInverses() {
        
        // setup manager
        $this->_setup('adodb', 'mysql');
        $this->assertTrue($this->m);

        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneA.php');
        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneB.php');

        // first check for brand new objects that are not committable
        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a1 = $this->m->create('eptInvOneA'));
        $this->assertTrue($b1 = $this->m->create('eptInvOneB'));
        $b1->epSetCommittable(false);

        // insert both to $a1->bs
        $a1->b = $b1;
        $this->assertTrue($b1->a === $a1);
        $this->assertTrue($a1->b === $b1);

        // flush to database
        $this->assertTrue($this->m->flush());
        $this->assertTrue($a1_oid = $a1->oid);
        // should have no oid as it was not committed
        $this->assertFalse($b1->oid);

        // remove from memory
        $this->assertTrue($this->m->evictAll('eptInvOneA'));
        $this->assertTrue($this->m->evictAll('eptInvOneB'));

        $a1 = $this->m->get('eptInvOneA', $a1_oid);
        $this->assertFalse($a1->b);

        // now check for objects that are old but are not committable
        // create the object and flush it
        $this->assertTrue($b2 = $this->m->create('eptInvOneB'));
        $this->assertTrue($this->m->flush());
        $this->assertTrue($b2_oid = $b2->oid);
        $this->assertTrue($this->m->evictAll('eptInvOneA'));
        $this->assertTrue($this->m->evictAll('eptInvOneB'));

        $a1 = $this->m->get('eptInvOneA', $a1_oid);
        $b2 = $this->m->get('eptInvOneB', $b2_oid);
        $b2->epSetCommittable(false);

        $a1->b = $b2;
        $this->assertTrue($b2->a === $a1);
        $this->assertTrue($a1->b === $b2);

        // flush it
        $this->assertTrue($this->m->flush());

        // remove from memory
        $this->assertTrue($this->m->evictAll('eptInvOneA'));
        $this->assertTrue($this->m->evictAll('eptInvOneB'));

        $a1 = $this->m->get('eptInvOneA', $a1_oid);
        $b2 = $this->m->get('eptInvOneB', $b2_oid);
        $this->assertFalse($b2->a);
        $this->assertFalse($a1->b);
    }
    
    /**
     * Test many-valued inverses with non commitable item
     * Check for bug 188
     */
    function testManyNonCommittableInverses() {
        
        // setup manager
        $this->_setup('adodb', 'mysql');
        $this->assertTrue($this->m);

        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneManyA.php');
        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneManyB.php');

        // first check for brand new objects that are not committable
        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a1 = $this->m->create('eptInvOneManyA'));
        $this->assertTrue($b1 = $this->m->create('eptInvOneManyB'));
        $this->assertTrue($b2 = $this->m->create('eptInvOneManyB'));
        $b1->epSetCommittable(false);

        // insert both to $a1->bs
        $a1->bs[] = $b1;
        $a1->bs[] = $b2;
        $this->assertTrue($b1->a === $a1);
        $this->assertTrue($b2->a === $a1);
        $this->assertTrue($a1->bs->inArray($b1));
        $this->assertTrue($a1->bs->inArray($b2));

        // flush to database
        $this->assertTrue($this->m->flush());
        $this->assertTrue($a1_oid = $a1->oid);
        $this->assertTrue($b2_oid = $b2->oid);
        // should have no oid as it was not committed
        $this->assertFalse($b1->oid);

        // remove from memory
        $this->assertTrue($this->m->evictAll('eptInvOneManyA'));
        $this->assertTrue($this->m->evictAll('eptInvOneManyB'));

        $a1 = $this->m->get('eptInvOneManyA', $a1_oid);
        $b2 = $this->m->get('eptInvOneManyB', $b2_oid);
        $this->assertTrue($b2->a === $a1);
        $this->assertTrue($a1->bs->inArray($b2));
        $this->assertTrue($a1->bs->count() == 1);

        // now check for objects that are old but are not committable
        // create the object and flush it
        $this->assertTrue($b3 = $this->m->create('eptInvOneManyB'));
        $this->assertTrue($this->m->flush());
        $this->assertTrue($b3_oid = $b3->oid);
        $this->assertTrue($this->m->evictAll('eptInvOneManyA'));
        $this->assertTrue($this->m->evictAll('eptInvOneManyB'));

        $a1 = $this->m->get('eptInvOneManyA', $a1_oid);
        $b3 = $this->m->get('eptInvOneManyB', $b3_oid);
        $b3->epSetCommittable(false);

        $a1->bs[] = $b3;
        $this->assertTrue($b3->a === $a1);
        $this->assertTrue($a1->bs->inArray($b3));

        // flush it
        $this->assertTrue($this->m->flush());

        // remove from memory
        $this->assertTrue($this->m->evictAll('eptInvOneManyA'));
        $this->assertTrue($this->m->evictAll('eptInvOneManyB'));

        $a1 = $this->m->get('eptInvOneManyA', $a1_oid);
        $b2 = $this->m->get('eptInvOneManyB', $b2_oid);
        $b3 = $this->m->get('eptInvOneManyB', $b3_oid);
        $this->assertTrue($b2->a === $a1);
        $this->assertFalse($b3->a);
        $this->assertTrue($a1->bs->inArray($b2));
        $this->assertFalse($a1->bs->inArray($b3));
        $this->assertTrue($a1->bs->count() == 1);
    }
    
    /**
     * Test many-valued inverses delete
     */
    function testOneManyValuedInversesDelete() {
         
        // setup manager
        $this->_setup('adodb', 'sqlite');
        $this->assertTrue($this->m);
        
        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneManyA.php');
        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneManyB.php');
        
        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a1 = $this->m->create('eptInvOneManyA'));
        $this->assertTrue($b1 = $this->m->create('eptInvOneManyB'));
        $this->assertTrue($b2 = $this->m->create('eptInvOneManyB'));
        
        // set $a to $b->a
        $b1->a = $a1;
        $this->assertTrue($b1->a === $a1);
        $this->assertTrue($a1->bs->inArray($b1));
        
        // bug #161
        // delete $b1 (which is bs[0])
        $a1->bs[0]->delete();
        $a1->bs[0] = $b2;
        $this->assertFalse($a1->bs->inArray($b1));
        $this->assertFalse($b1->a);
        $this->assertTrue($b2->a === $a1);
        $this->assertTrue($a1->bs->inArray($b2));
        
        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a3 = $this->m->create('eptInvOneManyA'));
        $this->assertTrue($b3 = $this->m->create('eptInvOneManyB'));
        $this->assertTrue($b4 = $this->m->create('eptInvOneManyB'));
        
        // set $a to $b->a
        $b3->a = $a3;
        $this->assertTrue($b3->a === $a3);
        $this->assertTrue($a3->bs->inArray($b3));
        
        // check that overwriting position 0
        // actually overwrites it
        $a3->bs[0] = $b4;
        $this->assertFalse($a3->bs->inArray($b3));
        $this->assertFalse($b3->a);
        $this->assertTrue($b4->a === $a3);
        $this->assertTrue($a3->bs->inArray($b4));

        // check that by changing the many relation
        // to null affects the inverse
        $a3->bs = null;
        $this->assertFalse($a3->bs->inArray($b4));
        $this->assertFalse($b4->a);
    }

    /**
     * Test many-valued inverses delete
     */
    function testManyValuedInversesDelete() {
         
        // setup manager
        $this->_setup('adodb', 'sqlite');
        $this->assertTrue($this->m);

        include_once(EP_TESTS.'/classes/inverses/src/eptInvManyA.php');
        include_once(EP_TESTS.'/classes/inverses/src/eptInvManyB.php');

        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a1 = $this->m->create('eptInvManyA'));
        $this->assertTrue($b1 = $this->m->create('eptInvManyB'));
        $this->assertTrue($b2 = $this->m->create('eptInvManyB'));

        // set $a1 to $b1->a
        $b1->as[] = $a1;
        $this->assertTrue($b1->as->inArray($a1));
        $this->assertTrue($a1->bs->inArray($b1));
        
        // bug #161
        // delete $b1 (which is bs[0])
        $a1->bs[0]->delete();
        $a1->bs[0] = $b2;
        $this->assertFalse($a1->bs->inArray($b1));
        $this->assertFalse($b1->as->inArray($a1));
        $this->assertTrue($b2->as->inArray($a1));
        $this->assertTrue($a1->bs->inArray($b2));
    }

    /**
     * Test to make sure duplicates in many fields
     * works correctly
     */
    function testDuplicateValuesInMany() {
         
        // setup manager
        $this->_setup('adodb', 'sqlite');
        $this->assertTrue($this->m);
        
        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneManyA.php');
        include_once(EP_TESTS.'/classes/inverses/src/eptInvOneManyB.php');
        
        // -------------------------------------------------
        // check 1-many relationship
    
        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a1 = $this->m->create('eptInvOneManyA'));
        $this->assertTrue($b1 = $this->m->create('eptInvOneManyB'));
        
        // set $a to $b->a
        $b1->a = $a1;
        $this->assertTrue($b1->a === $a1);
        $this->assertTrue($a1->bs->inArray($b1));
        $this->assertTrue($a1->bs->count() == 1);

        // flush to database
        $this->assertTrue($this->m->flush());
        $this->assertTrue($a1_oid = $a1->oid);
        $this->assertTrue($b1_oid = $b1->oid);

        $this->assertTrue($this->m->evictAll('eptInvOneManyA'));
        $this->assertTrue($this->m->evictAll('eptInvOneManyB'));

        // retrieve entries
        $a1 = $this->m->get('eptInvOneManyA', $a1_oid);
        $b1 = $this->m->get('eptInvOneManyB', $b1_oid);

        // put in a duplicate 
        // this should delete the old relationship
        // and rebuild it
        $b1->a = $a1;
        $this->assertTrue($b1->a === $a1);
        $this->assertTrue($a1->bs->inArray($b1));
        $this->assertTrue($a1->bs->count() == 1);

        // flush to database
        $this->assertTrue($this->m->flush());
        $this->assertTrue($a1_oid = $a1->oid);
        $this->assertTrue($b1_oid = $b1->oid);

        $this->assertTrue($this->m->evictAll('eptInvOneManyA'));
        $this->assertTrue($this->m->evictAll('eptInvOneManyB'));

        // retrieve entries
        $a1 = $this->m->get('eptInvOneManyA', $a1_oid);
        $b1 = $this->m->get('eptInvOneManyB', $b1_oid);
        
        // check duplicates
        $this->assertTrue($b1->a === $a1);
        $this->assertTrue($a1->bs->inArray($b1));
        $this->assertTrue($a1->bs->count() == 1);

        $this->assertTrue($this->m->evictAll('eptInvOneManyA'));
        $this->assertTrue($this->m->evictAll('eptInvOneManyB'));
        $this->assertTrue($this->m->deleteAll('eptInvOneManyA'));
        $this->assertTrue($this->m->deleteAll('eptInvOneManyB'));

        // -------------------------------------------------
        // check many-1 relationship
        
        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a1 = $this->m->create('eptInvOneManyA'));
        $this->assertTrue($b1 = $this->m->create('eptInvOneManyB'));
        
        // set $b1 to $a1->bs[]
        $a1->bs[] = $b1;
        $this->assertTrue($b1->a === $a1);
        $this->assertTrue($a1->bs->inArray($b1));
        $this->assertTrue($a1->bs->count() == 1);

        // flush to database
        $this->assertTrue($this->m->flush());
        $this->assertTrue($a1_oid = $a1->oid);
        $this->assertTrue($b1_oid = $b1->oid);

        $this->assertTrue($this->m->evictAll('eptInvOneManyA'));
        $this->assertTrue($this->m->evictAll('eptInvOneManyB'));

        // retrieve entries
        $a1 = $this->m->get('eptInvOneManyA', $a1_oid);
        $b1 = $this->m->get('eptInvOneManyB', $b1_oid);

        // put in a duplicate 
        // this should delete the old relationship
        // and rebuild it
        $a1->bs[] = $b1;
        $this->assertTrue($b1->a === $a1);
        $this->assertTrue($a1->bs->inArray($b1));
        $this->assertTrue($a1->bs->count() == 1);

        // flush to database
        $this->assertTrue($this->m->flush());
        $this->assertTrue($a1_oid = $a1->oid);
        $this->assertTrue($b1_oid = $b1->oid);

        $this->assertTrue($this->m->evictAll('eptInvOneManyA'));
        $this->assertTrue($this->m->evictAll('eptInvOneManyB'));

        // retrieve entries
        $a1 = $this->m->get('eptInvOneManyA', $a1_oid);
        $b1 = $this->m->get('eptInvOneManyB', $b1_oid);
        
        // check duplicates
        $this->assertTrue($b1->a === $a1);
        $this->assertTrue($a1->bs->inArray($b1));
        $this->assertTrue($a1->bs->count() == 1);

        $this->assertTrue($this->m->evictAll('eptInvOneManyA'));
        $this->assertTrue($this->m->evictAll('eptInvOneManyB'));
        $this->assertTrue($this->m->deleteAll('eptInvOneManyA'));
        $this->assertTrue($this->m->deleteAll('eptInvOneManyB'));

        // -------------------------------------------------
        // check many-many relationship
        
        include_once(EP_TESTS.'/classes/inverses/src/eptInvManyA.php');
        include_once(EP_TESTS.'/classes/inverses/src/eptInvManyB.php');

        // create eptInvOneA and eptInvOneB
        $this->assertTrue($a1 = $this->m->create('eptInvManyA'));
        $this->assertTrue($b1 = $this->m->create('eptInvManyB'));
        
        // set $b1 to $a1->bs[]
        $a1->bs[] = $b1;
        $this->assertTrue($b1->as->inArray($a1));
        $this->assertTrue($a1->bs->inArray($b1));
        $this->assertTrue($a1->bs->count() == 1);
        $this->assertTrue($b1->as->count() == 1);

        // flush to database
        $this->assertTrue($this->m->flush());
        $this->assertTrue($a1_oid = $a1->oid);
        $this->assertTrue($b1_oid = $b1->oid);

        $this->assertTrue($this->m->evictAll('eptInvManyA'));
        $this->assertTrue($this->m->evictAll('eptInvManyB'));
/*
        // retrieve entries
        $a1 = $this->m->get('eptInvManyA', $a1_oid);
        $b1 = $this->m->get('eptInvManyB', $b1_oid);

        // put in a duplicate 
        $a1->bs[] = $b1;
        $this->assertTrue($b1->as->inArray($a1));
        $this->assertTrue($a1->bs->inArray($b1));
        $this->assertTrue($a1->bs->count() == 2);
        $this->assertTrue($b1->as->count() == 2);

        // flush to database
        $this->assertTrue($this->m->flush());
        $this->assertTrue($a1_oid = $a1->oid);
        $this->assertTrue($b1_oid = $b1->oid);

        $this->assertTrue($this->m->evictAll('eptInvManyA'));
        $this->assertTrue($this->m->evictAll('eptInvManyB'));

        // retrieve entries
        $a1 = $this->m->get('eptInvManyA', $a1_oid);
        $b1 = $this->m->get('eptInvManyB', $b1_oid);
        
        // check duplicates
        $this->assertTrue($b1->as->inArray($a1));
        $this->assertTrue($a1->bs->inArray($b1));
        $this->assertTrue($a1->bs->count() == 2);
        $this->assertTrue($b1->as->count() == 2);

        // remove a duplicate (with remove())
        $a1->bs->remove($b1);
        // one should still be in there
        $this->assertTrue($b1->as->inArray($a1));
        $this->assertTrue($a1->bs->inArray($b1));
        $this->assertTrue($a1->bs->count() == 1);
        $this->assertTrue($b1->as->count() == 1);

        // flush to database
        $this->assertTrue($this->m->flush());
        $this->assertTrue($a1_oid = $a1->oid);
        $this->assertTrue($b1_oid = $b1->oid);

        $this->assertTrue($this->m->evictAll('eptInvManyA'));
        $this->assertTrue($this->m->evictAll('eptInvManyB'));

        // retrieve entries
        $a1 = $this->m->get('eptInvManyA', $a1_oid);
        $b1 = $this->m->get('eptInvManyB', $b1_oid);
        
        // one should still be in there
        $this->assertTrue($b1->as->inArray($a1));
        $this->assertTrue($a1->bs->inArray($b1));
        $this->assertTrue($a1->bs->count() == 1);
        $this->assertTrue($b1->as->count() == 1);

        // put in a duplicate again
        $a1->bs[] = $b1;
        $this->assertTrue($b1->as->inArray($a1));
        $this->assertTrue($a1->bs->inArray($b1));
        $this->assertTrue($a1->bs->count() == 2);
        $this->assertTrue($b1->as->count() == 2);

        // flush to database
        $this->assertTrue($this->m->flush());
        $this->assertTrue($a1_oid = $a1->oid);
        $this->assertTrue($b1_oid = $b1->oid);

        $this->assertTrue($this->m->evictAll('eptInvManyA'));
        $this->assertTrue($this->m->evictAll('eptInvManyB'));

        // retrieve entries
        $a1 = $this->m->get('eptInvManyA', $a1_oid);
        $b1 = $this->m->get('eptInvManyB', $b1_oid);
        
        // check duplicates
        $this->assertTrue($b1->as->inArray($a1));
        $this->assertTrue($a1->bs->inArray($b1));
        $this->assertTrue($a1->bs->count() == 2);
        $this->assertTrue($b1->as->count() == 2);
        
        foreach ($b1->as as $a) {
            $this->assertTrue($a->epMatches($a1));
        }

        foreach ($a1->bs as $b) {
            $this->assertTrue($b->epMatches($b1));
        }

        // remove a duplicate (with remove())
        $a1->delete();

        $this->assertFalse($b1->as->inArray($a1));
        $this->assertTrue($b1->as->count() == 0);

        $this->assertTrue($this->m->evictAll('eptInvManyA'));
        $this->assertTrue($this->m->evictAll('eptInvManyB'));

        // retrieve entries
        $a1 = $this->m->get('eptInvManyA', $a1_oid);
        $b1 = $this->m->get('eptInvManyB', $b1_oid);
        
        // one should still be in there
        $this->assertFalse($b1->as->inArray($a1));
        $this->assertFalse($a1);
        $this->assertTrue($b1->as->count() == 0);
*/
		
        $this->assertTrue($this->m->evictAll('eptInvManyA'));
        $this->assertTrue($this->m->evictAll('eptInvManyB'));

        $this->assertTrue($this->m->deleteAll('eptInvManyA'));
        $this->assertTrue($this->m->deleteAll('eptInvManyB'));

    }
    
}   

if (!defined('EP_GROUP_TEST')) {
    
    $tm = microtime(true);
    
    $t = new epTestInverse;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
    
    $elapsed = microtime(true) - $tm;
    
    echo epNewLine() . 'Time elapsed: ' . $elapsed . ' seconds' . "\n";
}

?>
