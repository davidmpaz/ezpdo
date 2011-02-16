<?php

/**
 * $Id: epTestManager.php 1019 2006-11-29 06:26:43Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1019 $ $Date: 2006-11-29 01:26:43 -0500 (Wed, 29 Nov 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/epTestRuntime.php');

/**
 * The unit test class for {@link epManager}  
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1019 $ $Date: 2006-11-29 01:26:43 -0500 (Wed, 29 Nov 2006) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.runtime
 */
class epTestManager extends epTestRuntime {
    
    /**
     * The cached manager
     * @var epManager
     */
    protected $m = false;

    /**
     * Maximum number of items to insert to a table
     */
    const MAX_ITEMS = 10;
    
    /**
     * Maximum number of items to insert to a table
     */
    const HALF_ITEMS = 10;

    /**
     * remove output dir in teardown
     */
    function tearDown() {
        epRmDir(dirname(__FILE__) . '/output');
    }

    /**
     * test {@link epManager} with single object
     * create, set/get vars, refresh, commit, delete
     */
    function _testSingleObject() {
        
        $this->assertTrue($m = & $this->m);
        
        // use manager to create one object
        include_once(EP_TESTS.'/classes/bookstore/src/eptBook.php');
        
        // delete all books
        $m->deleteAll('eptBook');

        // test object creation
        $title = md5('eptBook');
        $this->assertTrue($o = $m->create('eptBook', $title));
        $this->assertFalse($o->epIsDirty());
        $this->assertTrue($o->title === $title);
		$this->assertFalse($dirty = $o->epIsDirty());
		
		// call method doNothing should not change dirty flag
		$o->doNothing();
        $this->assertTrue($o->epIsDirty() == $dirty);
        
        // test commit 
        $this->assertTrue($m->commit($o)); 
        
        // check object id
        $this->assertTrue(($oid = $o->epGetObjectId()));
        
        // test setter/getter
        $pages = rand(1, 1000);
        $this->assertFalse(($pages0 = $o->pages) === $pages);
        $o->setPages($pages);
        $this->assertTrue($o->pages === $pages);
        $this->assertTrue($dirty = $o->epIsDirty());

		// call method doNothing should not change dirty flag
		$o->doNothing();
        $this->assertTrue($o->epIsDirty() == $dirty);
        
        // call refresh so $pages will be replace with old value $page0
        $this->assertTrue($m->refresh($o));

        $this->assertFalse($dirty = $o->epIsDirty());
        $this->assertTrue($o->pages === $pages0);
        
		// call method doNothing should not change dirty flag
		$o->doNothing();
        $this->assertTrue($o->epIsDirty() == $dirty);
        
        // set pages again and commit
        $this->assertFalse(($pages0 = $o->pages) === $pages);
        $o->setPages($pages);
        $this->assertTrue($o->pages === $pages);
        $this->assertTrue($o->epIsDirty());
        $this->assertTrue($m->commit($o));
        // insert id should not change
        $this->assertTrue($oid == $o->epGetObjectId());
        
        // now refresh again
        $this->assertTrue($o->pages !== $pages0);
        $this->assertTrue($o->pages === $pages);
        
        // try get (should get the cached)
        $this->assertTrue($o2 = & $m->get('eptBook', $oid));
        $this->assertTrue($o2 === $o);
        
        // test delete
        $this->assertTrue($m->delete($o));
        
        // cannot get obj for the oid
        $this->assertFalse($m->get('eptBook', $oid));

        // delete all books
        $m->deleteAll('eptBook');
    }

    /**
     * test {@link epManager}: 
     * {@link epManager::getAll()} 
     * {@link epManager::flush()} 
     */
    function _testMultiObjects() {
        
        // configure epManager
        $this->assertTrue($m = & $this->m);
        
        // use manager to create one object
        include_once(EP_TESTS.'/classes/bookstore/src/eptBook.php');
        
        // empty db
        $m->deleteAll('eptBook');
        
        // create self::MAX_ITEMS eptBook's
        $oids = array();
        $all_pages = array();
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            
            $title = "title" . md5($i);
            $this->assertTrue($o = & $m->create('eptBook', $title));
            
            // check title
            $this->assertFalse($dirty = $o->epIsDirty());
			
			// call method doNothing should not change dirty flag
			$o->doNothing();
			$this->assertTrue($o->epIsDirty() == $dirty );

            $this->assertTrue($o->title === $title);
            
            // set pages
            $pages = rand(1, 1000);
            $this->assertTrue($o->pages = $pages);
            $this->assertTrue($o->pages === $pages);
            
            // chech dirty flag
            $this->assertTrue($o->epIsDirty());
            
            // commit
            $this->assertTrue($m->commit($o));
            
            // make sure oid are valid
            $this->assertTrue(($oid = $o->epGetObjectId()));
            
            // keep track of oids and pages
            $oids[] = $oid;
            $all_pages[] = $pages;
        }
        
        // change page number for the first self::HALF_ITEMS books
        for($i = 0; $i < self::HALF_ITEMS; $i ++) {

            // get book by oid
            $this->assertTrue($o = & $m->get('eptBook', $oids[$i]));

            // check dirty flag (false)
            $this->assertFalse($dirty = $o->epIsDirty());

			// call method doNothing should not change dirty flag
			$o->doNothing();
			$this->assertTrue($o->epIsDirty() == $dirty);

            // chanage pages
            $this->assertTrue(($pages = $o->pages) === $all_pages[$i]);
            $this->assertTrue($o->pages = $pages + 1);

            // check dirty flag (true)
            $this->assertTrue($o->epIsDirty());
        }

        // now use epManager::getAll(): should get nothing since everything has been cached
        $this->assertTrue($os = $m->getAll('eptBook'));
        $this->assertTrue(count($os) == self::MAX_ITEMS);
        $this->assertTrue($m->count('eptBook') == self::MAX_ITEMS);
        
        // check: the first self::HALF_ITEMS should be retrieved from cache, page number +1 (dirty))
        for($i = 0; $i < self::HALF_ITEMS; $i ++) {

            // get book by oid
            $this->assertTrue($o = & $m->get('eptBook', $oids[$i]));

            // check dirty flag (true)
            $this->assertTrue($o->epIsDirty());

            // chanage pages
            $this->assertTrue($o->pages === $all_pages[$i] + 1);
        }

        // check: the second self::HALF_ITEMS should be retrieved from db, same page number (no dirty)
        for($i = self::HALF_ITEMS; $i < self::MAX_ITEMS; $i ++) {

            // get book by oid
            $this->assertTrue($o = & $m->get('eptBook', $oids[$i]));

            // check dirty flag (false)
            $this->assertFalse($dirty = $o->epIsDirty());

			// call method doNothing should not change dirty flag
			$o->doNothing();
			$this->assertTrue($o->epIsDirty() == $dirty);

            // chanage pages
            $this->assertTrue($o->pages === $all_pages[$i]);
        }
        
        // test flush
        $this->assertTrue($m->flush());

        // before refresh, change values in cached objects
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            
            // get book by oid
            $this->assertTrue($o = & $m->get('eptBook', $oids[$i]));

            // check dirty flag (false)
            $this->assertFalse($dirty = $o->epIsDirty());

			// call method doNothing should not change dirty flag
			$o->doNothing();
			$this->assertTrue($o->epIsDirty() == $dirty);

            // chanage pages
            $pages = $o->pages + rand(1, 1000);
            $this->assertTrue($o->pages = $pages);
            $all_pages[$i] = $pages;

            // check dirty flag (true)
            $this->assertTrue($o->epIsDirty());
        }
        
        // now refresh so get value from db that should all differ from memory
        // before refresh, change values in cached objects
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            
            // get book by oid
            $this->assertTrue($o = & $m->get('eptBook', $oids[$i]));

            // check dirty flag (false)
            $this->assertTrue($o->epIsDirty());

            // refresh
            $this->assertTrue($m->refresh($o));
            
            // check that pages are different from those stored in memory
            $this->assertTrue($o->pages !== $all_pages[$i]);
            
            // check dirty flag (true)
            $this->assertFalse($dirty = $o->epIsDirty());

			// call method doNothing should not change dirty flag
			$o->doNothing();
			$this->assertTrue($o->epIsDirty() == $dirty);
        }
        
        // delete all objects
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {

            // get book by oid
            $this->assertTrue($o = & $m->get('eptBook', $oids[$i]));

            // delete
            $this->assertTrue($m->delete($o));
        }

        // make sure we have deleted all
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            // get book by oid
            $this->assertFalse($o = & $m->get('eptBook', $oids[$i]));
        }

    }

    /**
     * test createFromArray and updateFromArray
     */
    function _testCreateFromArray() {
        
        $this->assertTrue($m = & $this->m);
        
        // use manager to create one object
        include_once(EP_TESTS.'/classes/bookstore/src/eptAuthor.php');
        include_once(EP_TESTS.'/classes/bookstore/src/eptBook.php');
        
        // delete all books
        $m->deleteAll('eptAuthor');
        $m->deleteAll('eptBook');

        // array to be used for object creation
        $author = array(
            'name' => "author-".md5('author'),
            'id' => rand(1, 1000),
            'age' => rand(1, 120),
            'books' => array(
                'title' => 'title-'.md5('book'),
                'pages' => rand(1, 1000),
                )
            );

        // create object from array
        $a = $m->createFromArray('eptAuthor', $author);
        
        // check if all vars are correct
        $this->assertTrue($a->name == $author['name']);
        $this->assertTrue($a->id == $author['id']);
        $this->assertTrue($a->age == $author['age']);
        $this->assertNotNull($a->books);
        $this->assertNotNull($a->books[0]->title == $author['books']['title']);
        $this->assertNotNull($a->books[0]->pages == $author['books']['pages']);

        // array to be used for object update
        $author_diff = array(
            'name' => "author-".md5('author'),
            'age' => rand(1, 120),
            'books' => $a->books
            );
        
        // update object from array
        $a_ = $m->updateFromArray($a, $author_diff);
        
        // check if all vars are correct
        $this->assertTrue($a_ === $a);
        $this->assertTrue($a->name == $author_diff['name']);
        $this->assertTrue($a->id == $author['id']);
        $this->assertTrue($a->age == $author_diff['age']);
        $this->assertNotNull($a->books);
        $this->assertNotNull($a->books[0]->title == $author['books']['title']);
        $this->assertNotNull($a->books[0]->pages == $author_diff['books']['pages']);
        
        // array to be used for object creation
        $author2 = array(
            'name' => "author-".md5('author2'),
            'id' => rand(1, 1000),
            'age' => rand(1, 120),
            'contact' => array(
                'phone' => '1234567', 
                'zipcode' => '8901', 
                ),
            'books' => array(
                array(
                    'title' => 'title-'.md5('book1'),
                    'pages' => rand(1, 1000),
                    ), 
                array(
                    'title' => 'title-'.md5('book2'),
                    'pages' => rand(1, 1000),
                    ), 
                ), 
            );

        // create object from array
        $a2 = $m->createFromArray('eptAuthor', $author2);
        
        // check if all vars are correct
        $this->assertTrue($a2->name == $author2['name']);
        $this->assertTrue($a2->id == $author2['id']);
        $this->assertTrue($a2->age == $author2['age']);
        $this->assertNotNull($a2->contact);
        $this->assertNotNull($a2->contact->phone = $author2['contact']['phone']);
        $this->assertNotNull($a2->contact->zipcode = $author2['contact']['zipcode']);
        $this->assertNotNull($a2->books);
        $this->assertNotNull($a2->books[0]->title == $author2['books'][0]['title']);
        $this->assertNotNull($a2->books[0]->pages == $author2['books'][0]['pages']);
        $this->assertNotNull($a2->books[1]->title == $author2['books'][1]['title']);
        $this->assertNotNull($a2->books[1]->pages == $author2['books'][1]['pages']);

        // flush all
        $m->flush();

        // delete all books
        $m->deleteAll('eptAuthor');
        $m->deleteAll('eptBook');
    }

    /**
     * test {@link epArray}: orderby 
     * {@link epArray::orderBy()} 
     */
    function _testArraySortBy() {
        
        // configure epManager
        $this->assertTrue($m = & $this->m);
        
        include_once(EP_TESTS.'/classes/bookstore/src/eptAuthor.php');
        include_once(EP_TESTS.'/classes/bookstore/src/eptBook.php');
        
        // empty db
        $m->deleteAll('eptAuthor');
        $m->deleteAll('eptBook');
        
        // create an author
        $this->assertTrue($author = $m->create('eptAuthor'));

        // add books into author
        $book_oids = array();
        $book_titles = array();
        $book_pages = array();
        $book_title_page_oids = array();
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            
            // create a book
            $this->assertTrue($book = $m->create('eptBook'));

            // set book title
            $title = "title" . md5('whatever');
            $book->title = $title;
            $this->assertTrue($title === $book->title);
            $book_titles[] = $title;
            
            // set book pages
            $pages = rand(1, 1000);
            $book->pages = $pages;
            $this->assertTrue($pages === $book->pages);
            $book_pages[] = $pages;

            // commit it
            $this->assertTrue($book->commit());
            $this->assertTrue($book->oid > 0);
            $book_oids[] = $book->oid;

            // add book into author
            $author->books[] = $book;
            $this->assertTrue($author->books->count() == $i + 1);

            $book_title_page_oids[] = array(
                'title' => $title,
                'pages' => $pages,
                'oid' => $book->oid
                );
        }

        // sort by title asc
        $this->assertTrue($author->books->sortBy('title', SORT_ASC));
        sort($book_titles);
        $i = 0;
        foreach($author->books as $book) {
            $this->assertTrue($book_titles[$i] === $book->title);
            $i ++;
        }

        // sort by title desc
        $this->assertTrue($author->books->sortBy('title', SORT_DESC));
        rsort($book_titles);
        $i = 0;
        foreach($author->books as $book) {
            $this->assertTrue($book_titles[$i] === $book->title);
            $i ++;
        }

        // sort by pages asc
        $this->assertTrue($author->books->sortBy('pages', SORT_ASC));
        sort($book_pages);
        $i = 0;
        foreach($author->books as $book) {
            $this->assertTrue($book_pages[$i] === $book->pages);
            $i ++;
        }

        // sort by pages desc
        $this->assertTrue($author->books->sortBy('pages', SORT_DESC));
        rsort($book_pages);
        $i = 0;
        foreach($author->books as $book) {
            $this->assertTrue($book_pages[$i] === $book->pages);
            $i ++;
        }

        // sort by oid asc
        $this->assertTrue($author->books->sortBy('oid', SORT_ASC));
        sort($book_oids);
        $i = 0;
        foreach($author->books as $book) {
            $this->assertTrue($book_oids[$i] === $book->oid);
            $i ++;
        }

        // sort by pages desc
        $this->assertTrue($author->books->sortBy('oid', SORT_DESC));
        rsort($book_oids);
        $i = 0;
        foreach($author->books as $book) {
            $this->assertTrue($book_oids[$i] === $book->oid);
            $i ++;
        }
    
        // multisort
        $this->assertTrue($author->books->sortBy('title', SORT_DESC, 'pages', SORT_ASC, 'oid', SORT_DESC));
        
        foreach ($book_title_page_oids as $key => $row) {
            $_titles[$key] = $row['title'];
            $_pages[$key]  = $row['pages'];
            $_oids[$key] = $row['oid'];
        }
        array_multisort($_titles, SORT_DESC, $_pages, SORT_ASC, $_oids, SORT_DESC, $book_title_page_oids);
        
        $i = 0;
        foreach($author->books as $book) {
            $this->assertTrue($book_title_page_oids[$i]['title'] === $book->title);
            $this->assertTrue($book_title_page_oids[$i]['pages'] === $book->pages);
            $this->assertTrue($book_title_page_oids[$i]['oid'] === $book->oid);
            $i ++;
        }
    }

    /**
     * test datatypes 
     */
    function _testDataTypes() {
        
        // configure epManager
        $this->assertTrue($m = & $this->m);

        // use manager to create one object
        include_once(EP_TESTS.'/classes/bookstore/src/eptBook.php');
        
        // empty db book
        $m->deleteAll('eptBook');
        
        $title = "title" . md5('whatever');
        $this->assertTrue($b = $m->create('eptBook', $title));
        
        // a new object cannot be dirty
        $this->assertFalse($b->epIsDirty());

        //
        // do all the setting here
        // 

        // set title (char)
        $this->assertTrue($b->title === $title);
        $this->assertTrue(is_string($b->title));

        // set pages (integer)
        $pages = rand(1, 1000);
        $this->assertTrue($b->pages = $pages);
        $this->assertTrue($b->pages === $pages);
        $this->assertTrue(is_integer($b->pages));
        
        // set recommended (boolean)
        $recommended = true;
        $this->assertTrue($b->recommended = $recommended);
        $this->assertTrue($b->recommended === $recommended);
        $this->assertTrue(is_bool($b->recommended));

        // set excerpt (clob(8192))
        $excerpt = str_repeat("excerpt", (integer)(8192/7));
        $this->assertTrue($b->excerpt = $excerpt);
        $this->assertTrue($b->excerpt === $excerpt);
        $this->assertTrue(is_string($b->excerpt));

        // set coverimg (blob(8192))
        $part = file_get_contents(dirname(__FILE__) . '/input/mysql_logo.gif');
        $coverimg = str_repeat($part, (integer)(8192/strlen($part)));
        $this->assertTrue($b->coverimg = $coverimg);
        $this->assertTrue($b->coverimg === $coverimg);
        $this->assertTrue(is_string($b->coverimg));

        // set pubdate (date)
        $pubdate = time();
        $this->assertTrue($b->pubdate = $pubdate);
        $this->assertTrue($b->pubdate === $pubdate);
        $this->assertTrue(is_integer($b->pubdate));

        // chech dirty flag
        $this->assertTrue($b->epIsDirty());

        // commit book
        $this->assertTrue($m->commit($b));
        $this->assertTrue($oid = $b->epGetObjectId());
    
        // now evict the book from memory
        $this->assertTrue($m->evictAll('eptBook'));
        $this->assertFalse($b);

        // read it from db
        $this->assertTrue($b = & $m->get('eptBook', $oid));
        
        //
        // do all the re-checking here
        // 

        // check title (char)
        $this->assertTrue($b->title == $title);
        $this->assertTrue(is_string($b->title));

        // check pages (integer)
        $this->assertTrue($b->pages == $pages);
        $this->assertTrue(is_integer($b->pages));
        
        // check recommended (boolean)
        $this->assertTrue($b->recommended == $recommended);
        $this->assertTrue(is_bool($b->recommended));

        // check excerpt (clob(8192))
        $this->assertTrue($b->excerpt == $excerpt);
        $this->assertTrue(is_string($b->excerpt));

        // check coverimg (blob(8192))
        $this->assertTrue($b->coverimg == $coverimg);
        $this->assertTrue(is_string($b->coverimg));

        // set pubdate (date)
        $this->assertTrue($b->pubdate == $pubdate);
        $this->assertTrue(is_integer($b->pubdate));

        // cleanup
        $m->deleteAll('eptBook');
    }

    /**
     * test {@link epManager}: find 
     * {@link epManager::find()} 
     */
    function _testObjectFind() {
        
        // configure epManager
        $this->assertTrue($m = & $this->m);
        
        // use manager to create one object
        include_once(EP_TESTS.'/classes/bookstore/src/eptAuthor.php');
        
        // empty db
        $m->deleteAll('eptAuthor');
        $m->deleteAll('eptBook');
        
        // create self::MAX_ITEMS eptBook's
        $oids = array(); // object ids
        $names = array(); // author names
        $ages = array(); // author ages
        $ids = array(); // author id
        $objs = array(); // all objects
        
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            
            $name = "author-" . md5($i);
            $this->assertTrue($o = & $m->create('eptAuthor', $name));
            
            // check title
            $this->assertFalse($o->epIsDirty());
            $this->assertTrue($o->name === $name);
            
            // set id
            $id = rand(1, 1000);
            //$this->assertTrue($o->setId($id));
            $this->assertTrue($o->id = $id);
            $this->assertTrue($o->id === $id);
            
            // set ages
            $age = rand(1, 120);
            $this->assertTrue($o->age = $age);
            $this->assertTrue($o->age === $age);
            
            // chech dirty flag
            $this->assertTrue($o->epIsDirty());
            
            // commit
            $this->assertTrue($m->commit($o));
            
            // make sure oid are valid
            $this->assertTrue(($oid = $o->epGetObjectId()));
            
            // keep track of oids and pages
            $names[] = $name;
            $ages[] = $age;
            $oids[] = $oid;
            $ids[] = $id;
            $objs[] = & $o;
        }
        
        // test find - all objects are in cache
        $eo = & $m->create('eptAuthor'); // example object
        
        // set null to all vars
        $eo->uuid = ''; 
        $eo->name = '';
        $eo->id = null;
        $eo->age = null;
        
        // find objects in cache only
        $this->assertTrue($os = $m->find($eo, EP_GET_FROM_CACHE));
        $this->assertTrue(count($os) == self::MAX_ITEMS);
        
        // evict and find in cache
        $this->assertTrue($m->evictAll('eptAuthor'));
        $this->assertFalse($os = $m->find($eo, EP_GET_FROM_CACHE));
        
        // after eviction, the objects themselves are deleted (set to null) 
        $this->assertFalse($o); // test the last one first
        
        // now check everyone (should all be deleted (ie null))
        foreach($objs as &$o) {
            $this->assertFalse($o); 
        }
        
        // find objects from db 
        $this->assertTrue($os = $m->find($eo, EP_GET_FROM_DB));
        $this->assertTrue(count($os) == self::MAX_ITEMS);
        
        // delete all objects
        $m->deleteAll('eptAuthor');
        
        // make sure we have deleted all
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            // get book by oid
            $this->assertFalse($o = & $m->get('eptAuthor', $oids[$i]));
        }
    }

    /**
     * test {@link epManager}: find 
     * {@link epManager::find()} 
     */
    function _testObjectFindByChild() {
        
        // configure epManager
        $this->assertTrue($m = & $this->m);
        
        // use manager to create one object
        include_once(EP_TESTS.'/classes/bookstore/src/eptAuthor.php');
        include_once(EP_TESTS.'/classes/bookstore/src/eptBook.php');
        include_once(EP_TESTS.'/classes/bookstore/src/eptContact.php');
        include_once(EP_TESTS.'/classes/bookstore/src/eptBookstore.php');
        
        // empty db
        $m->deleteAll('eptAuthor');
        $m->deleteAll('eptBook');
        $m->deleteAll('eptContact');
        $m->deleteAll('eptBookstore');
        
        // create self::MAX_ITEMS eptBook's
        $oids = array(); // object ids
        $names = array(); // author names
        $ages = array(); // author ages
        $ids = array(); // author ids
        $phones = array(); // author.contact phones
        $zipcodes = array(); // author.contact zipcodes
        $titles = array(); // book titles
        $pages = array(); // book pages
        $objs = array(); // all objects
        
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            
            $name = "author-" . md5($i);
            $this->assertTrue($o = & $m->create('eptAuthor', $name));
            
            // check title
            $this->assertFalse($dirty = $o->epIsDirty());
			
			// call method doNothing should not change dirty flag
			$o->doNothing();
			$this->assertTrue($o->epIsDirty() == $dirty);

            // set name
			$this->assertTrue($o->name === $name);
            
            // set id
            $id = rand(1, 1000);
            //$this->assertTrue($o->setId($id));
            $this->assertTrue($o->id = $id);
            $this->assertTrue($o->id === $id);
            
            // set ages
            $age = rand(1, 120);
            $this->assertTrue($o->age = $age);
            $this->assertTrue($o->age === $age);
            
            // check dirty flag
            $this->assertTrue($o->epIsDirty());

            // add the contact
            $this->assertTrue($o->contact = $m->create('eptContact'));
            $this->assertFalse($o->contact->epIsDirty());

            // set phone number
            $phone = rand(100, 999)."-456-".rand(1000, 9999);
            $this->assertTrue($o->contact->phone = $phone);
            $this->assertTrue($o->contact->phone === $phone);

            // set the zipcode
            $zipcode = rand(10000, 99999);
            $this->assertTrue($o->contact->zipcode = $zipcode);
            $this->assertTrue($o->contact->zipcode === $zipcode);

            // check dirty flag
            $this->assertTrue($o->contact->epIsDirty());

            // 2 books for each author
            for ($j = 0; $j < 2; $j++) {
                // add the book
                $title = 'title-'.$i.'-'.$j;
                $this->assertTrue($o->books[$j] = $m->create('eptBook', $title));
                $this->assertFalse($o->books[$j]->epIsDirty());
                $this->assertTrue($o->books[$j]->title === $title); 
                
                // set author
                $this->assertTrue($o->books[$j]->author = $o);
                $this->assertTrue($o->books[$j]->author === $o);

                // set amount of pages
                $page = rand(1, 1000);
                $this->assertTrue($o->books[$j]->pages = $page);
                $this->assertTrue($o->books[$j]->pages === $page);
                
                $titles[$i][] = $title;
                $pages[$i][] = $page;
            }

            // commit
            $this->assertTrue($m->commit($o));

            // make sure oid are valid
            $this->assertTrue(($oid = $o->epGetObjectId()));
            
            // keep track of oids and pages
            $names[] = $name;
            $ages[] = $age;
            $oids[] = $oid;
            $ids[] = $id;
            $phones[] = $phone;
            $zipcodes[] = $zipcode;
            $objs[] = & $o;
        }
        
        // test find - all objects are in cache
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            // find by book title of author
            $eo = & $m->create('eptAuthor'); // example object
            
            // set null to all vars
            $eo->uuid = ''; 
            $eo->name = '';
            $eo->id = null;
            $eo->age = null;
            $eo->books[0] = $m->create('eptBook');
            $eo->books[0]->title = $titles[$i][0];
            $eo->books[0]->uuid = null;
            $eo->books[0]->pages = null;
            $eo->books[0]->recommended = null;
            $eo->books[0]->pubdate = null;
            $eo->books[0]->coverimg = null;
            $eo->books[0]->excerpt = null;
            // match its author up again
            $eo->books[0]->author = $eo;

            // find each author
            $this->assertTrue($os = $m->find($eo, EP_GET_FROM_CACHE));
            // example object should be uncommittable
            $this->assertFalse($eo->books[0]->epIsCommittable());
            $this->assertTrue(count($os) == 1);
            $this->assertTrue($os[0]->name === $names[$i]);

            // find book by author's contact
            for($j = 0; $j < 2; $j++) {
                $eb = & $m->create('eptBook');
                $eb->uuid = null;
                $eb->pages = null;
                $eb->recommended = null;
                $eb->pubdate = null;
                $eb->coverimg = null;
                $eb->excerpt = null;

                $eb->author = $m->create('eptAuthor');
                $eb->author->uuid = ''; 
                $eb->author->name = '';
                $eb->author->id = null;
                $eb->author->age = null;
                $eb->author->contact = $m->create('eptContact');
                $eb->author->contact->phone = $phones[$i];
                $eb->author->contact->zipcode = null;
                $eb->author->contact->uuid = null;

                // find the book
                $this->assertTrue($os = $m->find($eb, EP_GET_FROM_CACHE));
                $this->assertFalse($eb->author->epIsCommittable());
                $this->assertFalse($eb->author->contact->epIsCommittable());
                $this->assertTrue(count($os) == 2);
                $this->assertTrue($os[$j]->title === $titles[$i][$j]);
                $this->assertTrue($os[$j]->author->name === $names[$i]);
            }

        }
        
        // evict and find in cache
        $this->assertTrue($m->evictAll('eptAuthor'));
        $this->assertTrue($m->evictAll('eptBook'));
        $this->assertTrue($m->evictAll('eptContact'));
        $this->assertFalse($os = $m->find($eo, EP_GET_FROM_CACHE));
        
        // test find - all objects are in cache
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            // find by book title of author
            $eo = & $m->create('eptAuthor'); // example object
            
            // set null to all vars
            $eo->uuid = ''; 
            $eo->name = '';
            $eo->id = null;
            $eo->age = null;
            $eo->books[0] = $m->create('eptBook');
            $eo->books[0]->title = $titles[$i][0];
            $eo->books[0]->uuid = null;
            $eo->books[0]->pages = null;
            $eo->books[0]->recommended = null;
            $eo->books[0]->pubdate = null;
            $eo->books[0]->coverimg = null;
            $eo->books[0]->excerpt = null;
            // match its author up again
            $eo->books[0]->author = $eo;

            // find each author
            $this->assertTrue($os = $m->find($eo, EP_GET_FROM_DB));
            // example object should be uncommittable
            $this->assertFalse($eo->books[0]->epIsCommittable());
            $this->assertTrue(count($os) == 1);
            $this->assertTrue($os[0]->name === $names[$i]);

            // find book by author's contact
            for($j = 0; $j < 2; $j++) {
                $eb = & $m->create('eptBook');
                $eb->uuid = null;
                $eb->pages = null;
                $eb->recommended = null;
                $eb->pubdate = null;
                $eb->coverimg = null;
                $eb->excerpt = null;

                $eb->author = $m->create('eptAuthor');
                $eb->author->uuid = ''; 
                $eb->author->name = '';
                $eb->author->id = null;
                $eb->author->age = null;
                $eb->author->contact = $m->create('eptContact');
                $eb->author->contact->phone = $phones[$i];
                $eb->author->contact->zipcode = null;
                $eb->author->contact->uuid = null;

                // find the book
                $this->assertTrue($os = $m->find($eb, EP_GET_FROM_DB));
                $this->assertFalse($eb->author->epIsCommittable());
                $this->assertFalse($eb->author->contact->epIsCommittable());
                $this->assertTrue(count($os) == 2);
                // there is no way to know for certain what order the data comes
                // back out.  But both of them must come out
                if ($os[$j]->title === $titles[$i][$j]) {
                    $this->assertTrue($os[$j]->title === $titles[$i][$j]);
                } else {
                    $this->assertTrue($os[$j]->title === $titles[$i][$j?0:1]);
                }
                $this->assertTrue($os[$j]->author->name === $names[$i]);
            }
        }
        
        // delete all objects
        $m->deleteAll('eptAuthor');
        $m->deleteAll('eptBook');
        $m->deleteAll('eptContact');
        $m->deleteAll('eptBookstore');
        
        // make sure we have deleted all
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            // get book by oid
            $this->assertFalse($o = & $m->get('eptAuthor', $oids[$i]));
        }
    }

    /**
     * test {@link epQuery}: query/find
     * More complete tests are moved to tests/query/epTestQueryRuntime.php
     * !!!Add new test cases there!!!
     * {@link epManager::find()} 
     */
    function _testObjectQueryPrimitive() {
        
        $this->assertTrue($m = & $this->m);
        
        // use manager to create one object
        include_once(EP_TESTS.'/classes/bookstore/src/eptBook.php');
        
        // empty db
        $m->deleteAll('eptBook');
        
        // create eptBook's (self::MAX_ITEMS in total)
        $bk_oids = array();
        $bk_pages = array();
        $bk_titles = array();
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            
            $title = "title" . md5($i);
            $this->assertTrue($b = & $m->create('eptBook', $title));

            // check title
            $this->assertFalse($b->epIsDirty());
            $this->assertTrue($b->title === $title);
            
            // set pages
            $pages = rand(1, 1000);
            $this->assertTrue($b->pages = $pages);
            $this->assertTrue($b->pages === $pages);
            
            // chech dirty flag
            $this->assertTrue($b->epIsDirty());
            
            // commit book
            $this->assertTrue($m->commit($b));
            
            // make sure oid are valid
            $this->assertTrue(($oid = $b->epGetObjectId()));
            
            // keep track of oids and pages
            $bk_oids[] = $oid;
            $bk_pages[] = $pages;
            $bk_titles[] = $title;
        }
        
        // remove all objects from memory
        $this->assertTrue($m->evictAll('eptBook'));

        // -----------------
        // test epQuery here
        // -----------------
        
        // test simple expression
        $this->assertTrue($os = $m->query("from eptBook as book where book.pages > ?", 0));
        $this->assertTrue(count($os) == self::MAX_ITEMS);
        $this->assertTrue($m->count('eptBook') == self::MAX_ITEMS);

        // test like
        $this->assertTrue($os = $m->query("from eptBook as book where book.title LIKE 'title%'"));
        $this->assertTrue(count($os) == self::MAX_ITEMS);
        $this->assertTrue($m->count('eptBook') == self::MAX_ITEMS);

        // count array values
        $value_counts = array_count_values($bk_pages);

        // search book by book title
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {

            // find book by title
            $this->assertTrue($os = $m->query("from eptBook as book where book.title = ?", $bk_titles[$i]));
            $this->assertTrue(count($os) == 1);
            $this->assertTrue($os[0]->pages == $bk_pages[$i]);

            // find book by pages
            $this->assertTrue($os = $m->query("from eptBook as book where book.pages = ?", $bk_pages[$i]));
            $this->assertTrue(count($os) == $value_counts[$bk_pages[$i]]);
        }

        // clean up 
        $m->deleteAll('eptBook');
        $m->deleteAll('eptAuthor');
    }

    /**
     * test {@link epQuery}: query/find (with relationship fields)
     * More complete tests are moved to tests/query/epTestQueryRuntime.php
     * !!!Add new test cases there!!!
     * {@link epManager::find()} 
     */
    function _testObjectQueryRelationship() {
        
        $this->assertTrue($m = & $this->m);
        
        // use manager to create one object
        include_once(EP_TESTS.'/classes/bookstore/src/eptAuthor.php');
        include_once(EP_TESTS.'/classes/bookstore/src/eptBook.php');
        
        // empty db
        $m->deleteAll('eptAuthor');
        $m->deleteAll('eptBook');
        
        // -----------------------------------------------------
        
        // create one author
        $name = "author-test";
        $this->assertTrue($a = $m->create('eptAuthor', $name));

        // check title
        $this->assertFalse($a->epIsDirty());
        $this->assertTrue($a->name === $name);

        // set id
        $id = rand(1, 1000);
        $this->assertTrue($a->id = $id);
        $this->assertTrue($a->id === $id);

        // set ages
        $age = rand(1, 120);
        $this->assertTrue($a->age = $age);
        $this->assertTrue($a->age === $age);

        // chech dirty flag
        $this->assertTrue($a->epIsDirty());

        // commit
        $this->assertTrue($m->commit($a));

        // make sure oid are valid
        $this->assertTrue(($oid = $a->epGetObjectId()));
        
        // -----------------------------------------------------
        
        // create eptBook's (self::MAX_ITEMS in total)
        $bk_oids = array();
        $bk_pages = array();
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            
            $title = "title" . md5($i);
            $this->assertTrue($b = $m->create('eptBook', $title));
            
            // check title
            $this->assertFalse($b->epIsDirty());
            $this->assertTrue($b->title === $title);
            
            // set pages
            $pages = rand(1, 1000);
            $this->assertTrue($b->pages = $pages);
            $this->assertTrue($b->pages === $pages);
            
            // chech dirty flag
            $this->assertTrue($b->epIsDirty());
            
            // set author to book 
            $this->assertTrue($b->author = $a);
            
            // add book into author
            if (!($books = $a->books)) {
                $books = array();
            }
            $books[] = $b;
            
            // assign books to author's books
            $this->assertTrue($a->books = $books);
            $this->assertTrue(count($a->books) == count($books));
            $this->assertTrue($b->epIsDirty());
            
            // commit book
            $this->assertTrue($m->commit($b));
            
            // make sure oid are valid
            $this->assertTrue(($oid = $b->epGetObjectId()));
            
            // keep track of oids and pages
            $bk_oids[] = $oid;
            $bk_pages[] = $pages;
        }
        
        // make sure the author has the right number of books
        if (version_compare(phpversion(), "5.1.0", "<")) {
            $this->assertTrue($a->books->count() == self::MAX_ITEMS);
        } else {
            $this->assertTrue(count($a->books) == self::MAX_ITEMS);
        }
        
        // -----------------------------------------------------

        // remove all objects from memory
        $this->assertTrue($m->evictAll('eptAuthor'));
        $this->assertTrue($m->evictAll('eptBook'));
        
        // make sure author is gone
        $this->assertFalse($a);

        // test 1
        $this->assertTrue($os = $m->query("from eptAuthor as a where a.books.contains(book) and book.pages > ?", 0));
        $this->assertTrue(count($os) == $m->count('eptAuthor'));

        // test 2
        $this->assertTrue($os = $m->query("from eptAuthor as a where a.books.contains(book) and book.title LIKE 'title%'"));
        $this->assertTrue(count($os) == $m->count('eptAuthor'));

        // test 3
        $this->assertTrue($os = $m->query("from eptAuthor as a where a.books.contains(b) and b.author.name like 'author-test%'"));
        $this->assertTrue(count($os) == $m->count('eptAuthor'));

        // test 4
        $this->assertTrue($os = $m->query("from eptAuthor as a where a.books.contains(b) and b.author.books.contains(b2) and b2.title like 'title%'"));
        $this->assertTrue(count($os) == $m->count('eptAuthor'));

        // clean up 
        $m->deleteAll('eptBook');
        $m->deleteAll('eptAuthor');
    }

    /**
     * test {@link epManager}
     * {@link epManager::find()} 
     */
    function _testObjectRelation() {
        
        // get the manager
        $this->assertTrue($m = & $this->m);

        // use manager to create one object
        include_once(EP_TESTS.'/classes/bookstore/src/eptAuthor.php');
        include_once(EP_TESTS.'/classes/bookstore/src/eptBook.php');
        include_once(EP_TESTS.'/classes/bookstore/src/eptBookstore.php');
        
        // empty db
        $m->deleteAll('eptAuthor');
        $m->deleteAll('eptBook');
        
        // -----------------------------------------------------
        
        // create one author
        $name = "author-test";
        $this->assertTrue($a = $m->create('eptAuthor', $name));

        // check title
        $this->assertFalse($a->epIsDirty());
        $this->assertTrue($a->name === $name);

        // set id
        $id = rand(1, 1000);
        $this->assertTrue($a->id = $id);
        $this->assertTrue($a->id === $id);

        // set ages
        $age = rand(1, 120);
        $this->assertTrue($a->age = $age);
        $this->assertTrue($a->age === $age);

        // chech dirty flag
        $this->assertTrue($a->epIsDirty());

        // commit
        $this->assertTrue($m->commit($a));

        // make sure oid are valid
        $this->assertTrue(($a_oid = $a->epGetObjectId()));
        
        // -----------------------------------------------------

        $storename = 'store';
        $this->assertTrue($s = $m->create('eptBookstore', $storename));

        // check title
        $this->assertFalse($s->epIsDirty());
        $this->assertTrue($s->name === $storename);

        // commit
        $this->assertTrue($m->commit($s));

        // make sure oid are valid
        $this->assertTrue(($s_oid = $s->epGetObjectId()));

        // -----------------------------------------------------
        
        // create eptBook's (self::MAX_ITEMS in total)
        $bk_oids = array();
        $bk_pages = array();
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            
            $title = "title" . md5($i);
            $this->assertTrue($b = $m->create('eptBook', $title));
            
            // check title
            $this->assertFalse($b->epIsDirty());
            $this->assertTrue($b->title === $title);
            
            // set pages
            $pages = rand(1, 1000);
            $this->assertTrue($b->pages = $pages);
            $this->assertTrue($b->pages === $pages);

            // check dirty flag
            $this->assertTrue($b->epIsDirty());

            // check modified vars (both)
            $this->assertTrue($modified_vars = $b->epGetModifiedVars());
            $this->assertTrue(count($modified_vars) == 1);
            $this->assertTrue($modified_vars['pages'] == $pages);
            
            // check modified vars (primitive)
            $this->assertTrue($modified_vars = $b->epGetModifiedVars(epObject::VAR_PRIMITIVE));
            $this->assertTrue(count($modified_vars) == 1);
            $this->assertTrue($modified_vars['pages'] == $pages);

            // check modified vars (relationship)
            $this->assertFalse($modified_vars = $b->epGetModifiedVars(epObject::VAR_RELATIONSHIP));

            // set author to book 
            $this->assertTrue($b->author = $a);
            $this->assertTrue($b->bookstore = $s);
            
            // check modified vars
            $this->assertTrue($modified_vars = $b->epGetModifiedVars());
            $this->assertTrue(count($modified_vars) == 3);
            $this->assertTrue($modified_vars['pages'] == $pages);
            $this->assertTrue($modified_vars['author'] == true);
            
            
            // check modified vars (primitive)
            $this->assertTrue($modified_vars = $b->epGetModifiedVars(epObject::VAR_PRIMITIVE));
            $this->assertTrue(count($modified_vars) == 1);
            $this->assertTrue($modified_vars['pages'] == $pages);

            // check modified vars (relationship)
            $this->assertTrue($modified_vars = $b->epGetModifiedVars(epObject::VAR_RELATIONSHIP));
            $this->assertTrue(count($modified_vars) == 2);
            $this->assertTrue($modified_vars['author'] == true);
            $this->assertTrue($modified_vars['bookstore'] == true);

            // add book into author
            if (!($books = $a->books)) {
                $books = array();
            }
            $books[] = $b;
            
            // assign books to author's books
            $this->assertTrue($a->books = $books);
            $this->assertTrue(count($a->books) == count($books));

            // add book into bookstore
            if (!($books = $s->books)) {
                $books = array();
            }
            $books[] = $b;
            
            // assign books to author's books
            $this->assertTrue($s->books = $books);
            $this->assertTrue(count($s->books) == count($books));

            $this->assertTrue($b->epIsDirty());
            
            // commit book
            $this->assertTrue($m->commit($b));
            
            // make sure oid are valid
            $this->assertTrue(($oid = $b->epGetObjectId()));
            
            // keep track of oids and pages
            $bk_oids[] = $oid;
            $bk_pages[] = $pages;
        }
        
        // make sure the author has the right number of books
        if (version_compare(phpversion(), "5.1.0", "<")) {
            $this->assertTrue($a->books->count() == self::MAX_ITEMS);
        } else {
            $this->assertTrue(count($a->books) == self::MAX_ITEMS);
        }

        // ----------------------------------------------------- 

        // remove all objects from memory
        $this->assertTrue($m->evictAll('eptAuthor'));
        $this->assertTrue($m->evictAll('eptBook'));
        
        // make sure author is gone
        $this->assertFalse($a);

        // retrieve author, change a value and recommit
        // other values should not have changed
        $this->assertTrue($a =& $m->get('eptAuthor', $a_oid));

        // check old id, change id
        $this->assertFalse($a->epIsDirty());
        $this->assertTrue($a->id === $id);

        $id = rand(1, 1000);
        $this->assertTrue($a->id = $id);
        $this->assertTrue($a->id === $id);

        // chech dirty flag
        $this->assertTrue($a->epIsDirty());

        // commit
        $this->assertTrue($m->commit($a));

        // make sure oid are valid
        $this->assertTrue(($a_oid = $a->epGetObjectId()));

        // remove all objects from memory
        $this->assertTrue($m->evictAll('eptAuthor'));
        $this->assertTrue($m->evictAll('eptBook'));
        
        // make sure author is gone
        $this->assertFalse($a);

        // all values should be good
        $this->assertTrue($a =& $m->get('eptAuthor', $a_oid));

        // check title
        $this->assertFalse($a->epIsDirty());
        $this->assertTrue($a->name === $name);

        // set id
        $this->assertTrue($a->id === $id);

        // set ages
        $this->assertTrue($a->age === $age);
        
        // make sure the author has the right number of books
        if (version_compare(phpversion(), "5.1.0", "<")) {
            $this->assertTrue($a->books->count() == self::MAX_ITEMS);
        } else {
            $this->assertTrue(count($a->books) == self::MAX_ITEMS);
        }
        
        // -----------------------------------------------------

        // remove all objects from memory
        $this->assertTrue($m->evictAll('eptAuthor'));
        $this->assertTrue($m->evictAll('eptBook'));
        
        // make sure author is gone
        $this->assertFalse($a);
        
        // retrieve books one by one and check its author
        $a0 = false;
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            
            // get book by oid
            $this->assertTrue($b = $m->get('eptBook', $bk_oids[$i]));
            
            // check pages
            $this->assertTrue($b->pages == $bk_pages[$i]);
            
            // check author
            $this->assertNotNull($a = $b->author);
            
            // same author (ref)
            if ($a0 && $a) {
                $this->assertTrue($a0 === $a);
            }
            
            // does it have the right author
            $this->assertTrue($a->name === $name); 
            
            // does it have the right age
            $this->assertTrue($a->age == $age);
            
            // does it have the right id
            $this->assertTrue($a->id == $id);
            
            // check books the author has is self::MAX_ITEMS
            if ($a->books instanceof epArray) {
                if (version_compare(phpversion(), "5.1.0", "<")) {
                    $this->assertTrue($a->books->count() == self::MAX_ITEMS);
                } else {
                    $this->assertTrue(count($a->books) == self::MAX_ITEMS);
                }
            } else {
                $this->assertTrue(count($a->books) == self::MAX_ITEMS);
            }
            
            $a0 = & $a;
        }

        // remove author from book
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            
            // get book by oid
            $this->assertTrue($b = $m->get('eptBook', $bk_oids[$i]));
            
            // check pages
            $this->assertTrue($b->pages == $bk_pages[$i]);
            
            // remove author
            $b->author = null;
            
            // commit
            $m->commit($b);
            
            $this->assertFalse($b->author);
        }

        // remove all objects from memory
        $this->assertTrue($m->evictAll('eptAuthor'));
        $this->assertTrue($m->evictAll('eptBook'));
        
        // check if author is removed from each book
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            
            // get book by oid
            $this->assertTrue($b = $m->get('eptBook', $bk_oids[$i]));
            
            // check pages
            $this->assertTrue($b->pages == $bk_pages[$i]);
            
            // author must have been removed
            $this->assertFalse($b->author);
        }
        
        // check if each book is removed from the author
        $this->assertTrue($a = $m->get('eptAuthor', $a_oid));
        if ($a->books instanceof epArray) {
            if (version_compare(phpversion(), "5.1.0", "<")) {
                $this->assertTrue($a->books->count() == self::MAX_ITEMS);
            } else {
                $this->assertTrue(count($a->books) == self::MAX_ITEMS);
            }
        } else {
            $this->assertTrue(count($a->books) == self::MAX_ITEMS);
        }

        foreach ($a->books as $k => $b) {
            // redundant check
            $this->assertTrue($a->books->inArray($b));

            // remove the book
            $a->books[$k] = null;
            
            // commit the changes
            $this->assertTrue($m->commit($a));

            // make sure the book has been removed
            $this->assertFalse($a->books->inArray($b));
        }

        // remove all objects from memory
        $this->assertTrue($m->evictAll('eptAuthor'));
        $this->assertTrue($m->evictAll('eptBook'));

        $this->assertTrue($a = $m->get('eptAuthor', $a_oid));

        if ($a->books instanceof epArray) {
            if (version_compare(phpversion(), "5.1.0", "<")) {
                $this->assertTrue($a->books->count() == 0);
            } else {
                $this->assertTrue(count($a->books) == 0);
            }
        } else {
            $this->assertTrue(count($a->books) == 0);
        }

        // -----------------------------------------------------

        // check for bug #142
        // reconnect the authors and books

        // remove all objects from memory
        $this->assertTrue($m->evictAll('eptAuthor'));
        $this->assertTrue($m->evictAll('eptBook'));
        $this->assertTrue($m->evictAll('eptBookstore'));
        
        $this->assertTrue($a =& $m->get('eptAuthor', $a_oid));

        $this->assertTrue($s =& $m->get('eptBookstore', $s_oid));

        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            
            // get book by oid
            $this->assertTrue($b = $m->get('eptBook', $bk_oids[$i]));
            
            // check pages
            $this->assertTrue($b->pages == $bk_pages[$i]);

            // check bookstore
            $this->assertTrue($b->bookstore === $s);
            
            $this->assertTrue($b->author = $a);
            
            // add book into author
            if (!($books = $a->books)) {
                $books = array();
            }
            $books[] = $b;
            
            // assign books to author's books
            $this->assertTrue($a->books = $books);
            $this->assertTrue(count($a->books) == count($books));

            $this->assertTrue($b->epIsDirty());
            
            // commit book
            $this->assertTrue($m->commit($b));
            
            // make sure oid are valid
            $this->assertTrue(($oid = $b->epGetObjectId()));
            
        }

        // now delete each book and check to see if both
        // the publisher's and author's book counts go down
        for($i = 0; $i < self::MAX_ITEMS; $i ++) {
            
            // get book by oid
            $this->assertTrue($b =& $m->get('eptBook', $bk_oids[$i]));
            
            // check pages
            $this->assertTrue($b->pages == $bk_pages[$i]);

            $m->delete($b);

            // we want to check that it is out of memory and database
            // but this is impossible because the reference in store::books 
            // is different than the one that is being deleted
            // for some reason, though, it doesn't have this problem with
            // authors::books
            $this->assertTrue($m->evictAll('eptAuthor'));
            $this->assertTrue($m->evictAll('eptBook'));
            $this->assertTrue($m->evictAll('eptBookstore'));
            
            $this->assertTrue($a =& $m->get('eptAuthor', $a_oid));
            $this->assertTrue($s =& $m->get('eptBookstore', $s_oid));

            if ($a->books instanceof epArray) {
                if (version_compare(phpversion(), "5.1.0", "<")) {
                    $this->assertTrue($a->books->count() == self::MAX_ITEMS - $i - 1);
                } else {
                    $this->assertTrue(count($a->books) == self::MAX_ITEMS - $i - 1);
                }
            } else {
                $this->assertTrue(count($a->books) == self::MAX_ITEMS - $i - 1);
            }

            if ($s->books instanceof epArray) {
                if (version_compare(phpversion(), "5.1.0", "<")) {
                    $this->assertTrue($s->books->count() == self::MAX_ITEMS - $i - 1);
                } else {
                    $this->assertTrue(count($s->books) == self::MAX_ITEMS - $i - 1);
                }
            } else {
                $this->assertTrue(count($s->books) == self::MAX_ITEMS - $i - 1);
            }
            
        }

        
        // -----------------------------------------------------

        // clean up 
        $m->deleteAll('eptAuthor');
        $m->deleteAll('eptBook');
    }
    
    /**
     * test {@link epManager}: deletion of composed_of fields
     * {@link epManager::delete()} 
     */
    function _testComposedOfDelete() {
        
        // get the manager
        $this->assertTrue($m = & $this->m);
        
        // use manager to create one object
        include_once(EP_TESTS.'/classes/bookstore/src/eptAuthor.php');
        
        // empty db: author and contact
        $m->deleteAll('eptAuthor');
        $m->deleteAll('eptContact');
        
        // -----------------------------------------------------
        
        // create an author
        $name = "author-test";
        $this->assertTrue($a = $m->create('eptAuthor', $name));

        // check title
        $this->assertFalse($a->epIsDirty());
        $this->assertTrue($a->name === $name);

        // set id
        $id = rand(1, 1000);
        $this->assertTrue($a->id = $id);
        $this->assertTrue($a->id === $id);

        // set ages
        $age = rand(1, 120);
        $this->assertTrue($a->age = $age);
        $this->assertTrue($a->age === $age);

        // commit
        $this->assertTrue($a->epIsDirty());
        $this->assertTrue($m->commit($a));
        $this->assertTrue(($oid = $a->epGetObjectId()));
        
        // get author oid to be used later
        $this->assertTrue($a_oid = $a->epGetObjectId());
        
        // -----------------------------------------------------
        
        // create a contact
        $this->assertTrue($c = $m->create('eptContact', $name));
        
        // set phone
        $phone = '123-456-789';
        $this->assertTrue($c->phone = $phone);
        $this->assertTrue($c->phone === $phone);

        // set zipcode
        $zipcode = '54321';
        $this->assertTrue($c->zipcode = $zipcode);
        $this->assertTrue($c->zipcode === $zipcode);

        // commit
        $this->assertTrue($c->epIsDirty());
        $this->assertTrue($m->commit($c));
        $this->assertFalse($c->epIsDirty());
        
        // get contact oid to be used later
        $this->assertTrue($c_oid = $c->epGetObjectId());
        
        // -----------------------------------------------------
        
        // assign contact to author and commit
        $this->assertTrue($a->contact = $c);
        $this->assertTrue($a->epIsDirty());
        $this->assertTrue($m->commit($a));
        $this->assertFalse($a->epIsDirty());
        
        // -----------------------------------------------------
        
        // remove author and contact from memory
        $this->assertTrue($m->evictAll('eptAuthor'));
        $this->assertTrue($m->evictAll('eptContact'));
        
        // make sure author is gone
        $this->assertFalse($a);
        $this->assertFalse($c);
        
        // read author back
        $this->assertTrue($a = & $m->get('eptAuthor', $a_oid));
        
        // check its primitive fields
        $this->assertTrue($a->name === $name);
        $this->assertTrue($a->id === $id);
        $this->assertTrue($a->age === $age);
        
        // check fields in the contact 
        $this->assertTrue($a->contact->phone === $phone);  
        $this->assertTrue($a->contact->zipcode === $zipcode);
        
        // -----------------------------------------------------
        
        // test delete author
        //$c = & $a->getContact();
        $this->assertTrue($m->delete($a));
        $this->assertFalse($a);
        //$this->assertFalse($c);
        
        // make sure we can't find author and contact any more
        $this->assertFalse($a = $m->get('eptAuthor', $a_oid));
        $this->assertFalse($a = $m->get('eptContact', $c_oid));
        
        // -----------------------------------------------------
        
        // clean up 
        $m->deleteAll('eptAuthor');
        $m->deleteAll('eptContact');
    }

    /**
     * Test epObject::epStartTransaction() and epEndTransaction() 
     */
    function _testTransactionObject() {
        
        $this->assertTrue($m = & $this->m);
        
        // use manager to create one object
        include_once(EP_TESTS.'/classes/bookstore/src/eptBook.php');
        
        // delete all books
        $m->deleteAll('eptBook');

        //
        // 1. create object (no rollback)
        // 

        // test object creation
        $title = md5('eptBook');
        $this->assertTrue($o = $m->create('eptBook', $title));
        $this->assertFalse($o->epIsDirty());
        $this->assertTrue($o->title === $title);
        $this->assertFalse($o->epIsDirty());
        
        // signal object the start of transaction 
        $this->assertTrue($o->epStartTransaction());
        
        // signal object the start of transaction (without rollback)
        $this->assertTrue($o->epEndTransaction());

        // everything remains the same
        $this->assertTrue($o->title === $title);
        $this->assertFalse($o->epIsDirty());
        $this->assertFalse($o->epGetObjectId());

        //
        // 2. change object (with rollback)
        // 

        // signal object the start of transaction 
        $this->assertTrue($o->epStartTransaction());
        $this->assertTrue($o->epInTransaction());
        
        $title_0 = $title;
        $title = md5($title_0);
        $o->title = $title;

        // signal object the start of transaction (true: with rollback)
        $this->assertTrue($o->epEndTransaction(true));
        $this->assertFalse($o->epInTransaction());

        $this->assertTrue($o->title === $title_0);
        $this->assertFalse($o->epIsDirty());
        $this->assertFalse($o->epGetObjectId());

        //
        // 3. change object (without rollback)
        // 

        // signal object the start of transaction 
        $this->assertTrue($o->epStartTransaction());
        $this->assertTrue($o->epInTransaction());
        
        $title_0 = $title;
        $title = md5($title_0);
        $o->title = $title;

        // signal object the start of transaction (false (default): with rollback)
        $this->assertTrue($o->epEndTransaction());
        $this->assertFalse($o->epInTransaction());

        $this->assertTrue($o->title == $title); // new value
        $this->assertTrue($o->epIsDirty()); // now dirty
        $this->assertFalse($o->epGetObjectId()); // still no oid

        //
        // 4. commit object (with rollback)
        // 
        
        // signal object the start of transaction 
        $this->assertTrue($o->epStartTransaction());
        $this->assertTrue($o->epInTransaction());

        // commit (save) object so we get valid oid
        $this->assertTrue($o->commit());
        $this->assertTrue($o->epGetObjectId()); // valid oid

        // signal object the start of transaction (false (default): with rollback)
        $this->assertTrue($o->epEndTransaction(true));
        $this->assertFalse($o->epInTransaction());

        // after rollback still no object id
        $this->assertFalse($o->epGetObjectId()); // no oid

        // delete all books
        $m->deleteAll('eptBook');
    }

    /**
     * Test transaction: start and commit
     */
    function _testTransactionStartCommit() {
        
        $this->assertTrue($m = & $this->m);
        
        // use manager to create one object
        include_once(EP_TESTS.'/classes/bookstore/src/eptBook.php');
        
        // delete all books
        $m->deleteAll('eptBook');

        // start transaction 
        $this->assertTrue($m->start_t());

        // test object creation
        $title = md5('eptBook');
        $this->assertTrue($o = $m->create('eptBook', $title));
        $this->assertFalse($o->epIsDirty());
        $this->assertTrue($o->title === $title);
        $this->assertFalse($o->epIsDirty());
        $this->assertFalse(($oid = $o->epGetObjectId()));
        
        // commit transaction 
        $this->assertTrue($m->commit_t());
        
        // check object id
        $this->assertTrue(($oid = $o->epGetObjectId()));

        // delete all books
        $m->deleteAll('eptBook');
    }

    /**
     * Transaction: start and rollback
     */
    function _testTransactionStartRollback() {
        
        $this->assertTrue($m = & $this->m);
        
        // use manager to create one object
        include_once(EP_TESTS.'/classes/bookstore/src/eptBook.php');
        
        // delete all books
        $m->deleteAll('eptBook');

        // 
        // start | new object | change object | rollback
        // 

        // start transaction 
        $this->assertTrue($m->start_t());

        // test object creation
        $title = md5('eptBook');
        $this->assertTrue($o = $m->create('eptBook', $title));
        $this->assertTrue($o->title === $title);
        $this->assertFalse($o->epIsDirty());
        $this->assertFalse($o->epGetObjectId());
        
        // rollback transaction 
        $this->assertTrue($m->rollback_t());
        
        // check object 
        $this->assertFalse($o->epGetObjectId());
        $this->assertTrue($o->title == $title);

        // 
        // 2. change object | rollback
        // 

        // start transaction 
        $this->assertTrue($m->start_t());

        $title_0 = $title;
        $title = md5($title_0);
        $o->title = $title;

        // rollback transaction 
        $this->assertTrue($m->rollback_t()); 

        // check object
        $this->assertTrue($o->title == $title_0);
        $this->assertFalse($o->epGetObjectId());

        // delete all books
        $m->deleteAll('eptBook');
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

        echo "  single object..";
        $this->_testSingleObject();
        echo "done " . epNewLine();

        echo "  multiple objects..";
        $this->_testMultiObjects();
        echo "done " . epNewLine();

        echo "  array sort by..";
        $this->_testArraySortBy();
        echo "done " . epNewLine();

        echo "  array to object..";
        $this->_testCreateFromArray();
        echo "done " . epNewLine();
        
        echo "  data typess..";
        $this->_testDataTypes();
        echo "done " . epNewLine();

        echo "  find objects..";
        $this->_testObjectFind();
        echo "done " . epNewLine();

        echo "  find objects by child..";
        $this->_testObjectFindByChild();
        echo "done " . epNewLine();

        echo "  object query primitive..";
        $this->_testObjectQueryPrimitive();
        echo "done " . epNewLine();

        echo "  object query relationship..";
        $this->_testObjectQueryRelationship();
        echo "done " . epNewLine();

        echo "  object relationship..";
        $this->_testObjectRelation();
        echo "done " . epNewLine();

        echo "  composed delete..";
        $this->_testComposedOfDelete();
        echo "done " . epNewLine();

        echo "  object transaction..";
        $this->_testTransactionObject();
        echo "done " . epNewLine();

        echo "  transaction: commit..";
        $this->_testTransactionStartCommit();
        echo "done " . epNewLine();
        
        echo "  transaction: rollback..";
        $this->_testTransactionStartRollback();
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
    
    $t = new epTestManager;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
    
    $elapsed = microtime(true) - $tm;
    
    echo epNewLine() . 'Time elapsed: ' . $elapsed . ' seconds';
}

?>
