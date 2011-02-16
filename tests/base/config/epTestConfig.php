<?php

/**
 * $Id: epTestConfig.php 465 2005-08-30 01:41:28Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 465 $ $Date: 2005-08-29 21:41:28 -0400 (Mon, 29 Aug 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.base
 */

/**
 * need epTestCase
 */
include_once(dirname(__FILE__).'/../../src/epTestCase.php');

/**
 * need ezpdo utils
 */
include_once(EP_SRC_BASE.'/epUtils.php');

/**#@+
 * need config manager
 */
include_once(EP_SRC_BASE.'/epConfig.php');
include_once(EP_SRC_BASE.'/epConfigurable.php');
/**#@-*/

/**
 * Test class 1 for epConfigurable
 * 
 * defConfig() returns epConfig
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 465 $ $Date: 2005-08-29 21:41:28 -0400 (Mon, 29 Aug 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.base
 */
class epConfigurableTest1 extends epConfigurable {

    /**
     * Returns default config
     * @return mixed array or epConfig
     */
    public function defConfig() {
        
        $cfg = new epConfig;
        
        for($i = 0; $i < 10; $i ++) {
            // set an option-value pair
            $option = 'def_option' . $i;
            $value_set = 'def_value' . $i;
            $cfg->set($option, $value_set);
        }
        
        return $cfg;
    }

};

/**
 * Test class 2 for epConfigurable
 * 
 * defConfig() returns array
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 465 $ $Date: 2005-08-29 21:41:28 -0400 (Mon, 29 Aug 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.base
 */
class epConfigurableTest2 extends epConfigurable {
    
    /**
     * Returns default config
     * @return mixed array or epConfig
     */
    public function defConfig() {
        
        $options = array();
        
        for($i = 0; $i < 10; $i ++) {
            $option = 'def_option' . $i;
            $value_set = 'def_value' . $i;
            $options[$option] = $value_set; 
        }
        
        return $options;
    }

};

/**
 * The unit test class for {@link epConfig}  
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 465 $ $Date: 2005-08-29 21:41:28 -0400 (Mon, 29 Aug 2005) $
 * @package ezpdo.tests
 * @subpackage ezpdo.tests.base
 */
class epTestConfig extends epTestCase {
    
    /**
     * test epConfig creattion and set/get
     */
    function testConfigCreation() {
        
        // create config
        $cfg = new epConfig;
        $this->assertTrue($cfg != null);
        
        // set options
        for($i = 0; $i < 10; $i ++) {
            
            // set an option-value pair
            $option = 'option' . $i;
            $value_set = 'value' . $i;
            $cfg->set($option, $value_set);
            
            // get option value
            $value_get = $cfg->get($option);
            
            // value_get should equal value_set
            $this->assertTrue($value_set == $value_get);
        }
    }
    
    /**
     * test epConfig merge: same options
     */
    function testConfigMergeSame() {
        
        // create cfg1
        $cfg1 = new epConfig;
        $this->assertTrue($cfg1 != null);
        
        // create cfg2
        $cfg2 = new epConfig;
        $this->assertTrue($cfg2 != null);
        
        // set same options/values to cfg1 and cfg2
        for($i = 0; $i < 10; $i ++) {
            
            // set an option-value pair
            $option = 'option' . $i;
            $value_set = 'value' . $i;
            
            // cfg1 set/get option 
            $cfg1->set($option, $value_set);
            $value_get = $cfg1->get($option);
            $this->assertTrue($value_set == $value_get);
            
            // cfg2 set/get option 
            $cfg2->set($option, $value_set);
            $value_get = $cfg2->get($option);
            $this->assertTrue($value_set == $value_get);
        }
        
        // merge cfg2 to cfg1
        $cfg1->merge($cfg2);
        
        // since options in cfg2 are the same, cfg1 should not be changed
        // set same options/values to cfg1 and cfg2
        for($i = 0; $i < 10; $i ++) {
            
            // set an option-value pair
            $option = 'option' . $i;
            $value_set = 'value' . $i;
            
            // cfg1 set/get option 
            $value_get = $cfg1->get($option);
            $this->assertTrue($value_set == $value_get);
        }
    }

    /**
     * test epConfig merge: different options 
     * all options/values of the second config are different 
     */
    function testConfigMergeDiff() {
        
        // create cfg1
        $cfg1 = new epConfig;
        $this->assertTrue($cfg1 != null);
        
        // create cfg2
        $cfg2 = new epConfig;
        $this->assertTrue($cfg2 != null);
        
        // set same options/values to cfg1 and cfg2
        for($i = 0; $i < 10; $i ++) {
            
            // set an option-value pair
            $option = 'cfg1_option' . $i;
            $value_set = 'cfg1_value' . $i;
            
            // cfg1 set/get option 
            $cfg1->set($option, $value_set);
            $value_get = $cfg1->get($option);
            $this->assertTrue($value_set == $value_get);
            
            // set an option-value pair
            $option = 'cfg2_option' . $i;
            $value_set = 'cfg2_value' . $i;
            
            // cfg2 set/get option 
            $cfg2->set($option, $value_set);
            $value_get = $cfg2->get($option);
            $this->assertTrue($value_set == $value_get);
        }
        
        // merge cfg2 to cfg1
        $cfg1->merge($cfg2);
        
        // since options in cfg2 are the same, cfg1 should not be changed
        // set same options/values to cfg1 and cfg2
        for($i = 0; $i < 10; $i ++) {
            
            // check old options cfg1 had
            $option = 'cfg1_option' . $i;
            $value_set = 'cfg1_value' . $i;
            $value_get = $cfg1->get($option);
            $this->assertTrue($value_set == $value_get);
        
            // cfg1 now should have options from cfg2
            $option = 'cfg2_option' . $i;
            $value_set = 'cfg2_value' . $i;
            $value_get = $cfg1->get($option);
            $this->assertTrue($value_set == $value_get);
        }
    }

    /**
     * test epConfig merge: different options 
     * same options but different values 
     */
    function testConfigMergeDiff2() {
        
        // create cfg1
        $cfg1 = new epConfig;
        $this->assertTrue($cfg1 != null);
        
        // create cfg2
        $cfg2 = new epConfig;
        $this->assertTrue($cfg2 != null);
        
        // set same options/values to cfg1 and cfg2
        for($i = 0; $i < 10; $i ++) {
            
            // set an option-value pair
            $option = 'option' . $i;
            $value_set = 'cfg1_value' . $i;
            
            // cfg1 set/get option 
            $cfg1->set($option, $value_set);
            $value_get = $cfg1->get($option);
            $this->assertTrue($value_set == $value_get);
            
            $value_set = 'cfg2_value' . $i;
            
            // cfg2 set/get option 
            $cfg2->set($option, $value_set);
            $value_get = $cfg2->get($option);
            $this->assertTrue($value_set == $value_get);
        }
        
        // merge cfg2 to cfg1
        $cfg1->merge($cfg2);
        
        // since options in cfg2 are the same, cfg1 should not be changed
        // set same options/values to cfg1 and cfg2
        for($i = 0; $i < 10; $i ++) {
            // values from cfg2 now have overridden cfg1's
            $option = 'option' . $i;
            $value_set = 'cfg2_value' . $i;
            $value_get = $cfg1->get($option);
            $this->assertTrue($value_set == $value_get);
        }
    }

    /**
     * test epConfig serialization 
     * epConfig to be read from input/config.xml
     * and written to output/config.xml
     */
    function testConfigSerialization() {
        
        // start from input/config.xml
        $cfg =& epConfig::load(realpath(dirname(__FILE__)).'/input/config.xml');
        $this->assertTrue($cfg != null);
        
        // check options not empty
        $options = & $cfg->options();
        $this->assertTrue(!empty($options));
        $count0 = count($options);
        
        // add new options to cfg
        for($i = 0; $i < 10; $i ++) {
            $option = 'option' . $i;
            $value_set = 'value' . $i;
            $cfg->set($option, $value_set);
        }
        
        $options = & $cfg->options();
        $count1 = count($options);
        $this->assertTrue($count1 - $count0 == 10);
        
        // write to output/config.xml
        $status = $cfg->store('output/config.xml');
        $this->assertTrue($status);
        
        // done with cfg
        $cfg = null;
        
        // load output/config.xml into cfg2
        $cfg2 =& epConfig::load('output/config.xml');
        $this->assertTrue($cfg2 != null);
        
        // check new options in cfg2
        for($i = 0; $i < 10; $i ++) {
            $option = 'option' . $i;
            $value_set = 'value' . $i;
            $value_get = $cfg2->get($option);
            $this->assertTrue($value_set == $value_get);
        }
        
        // remove new options
        for($i = 0; $i < 10; $i ++) {
            $option = 'option' . $i;
            $value_get = $cfg2->remove($option);
        }
        
        // write to output/config.xml
        $status = $cfg2->store('output/config.xml');
        $this->assertTrue($status);
        
        // done with cfg2
        $cfg2 = null;
        
        // load output/config.xml into cfg3
        $cfg3 =& epConfig::load('output/config.xml');
        $this->assertTrue($cfg3 != null);
        
        // check if new options are removed in cfg3
        for($i = 0; $i < 10; $i ++) {
            $option = 'option' . $i;
            $value_get = $cfg3->get($option);
            $this->assertTrue(!isset($value_get));
        }
    }

    /**
     * test configurable (defConfig() retrurns epConfig)
     */
    function testConfigurable1() {
        
        $co = new epConfigurableTest1;
        $this->assertTrue($co != null);
        
        // check default options
        for($i = 0; $i < 10; $i ++) {
            // set an option-value pair
            $option = 'def_option' . $i;
            $value_set = 'def_value' . $i;
            $value_get = $co->getConfigOption($option);
            $this->assertTrue($value_get == $value_set);
        }
    
        // make a cfg (to be set to co)
        $cfg = new epConfig;
        for($i = 0; $i < 10; $i ++) {
            // set an option-value pair
            $option = 'cfg_option' . $i;
            $value_set = 'cfg_value' . $i;
            $cfg->set($option, $value_set);
        }
        
        // set cfg to co
        $co->setConfig($cfg);
        
        // check cfg options
        for($i = 0; $i < 10; $i ++) {
            // set an option-value pair
            $option = 'cfg_option' . $i;
            $value_set = 'cfg_value' . $i;
            $value_get = $co->getConfigOption($option);
            $this->assertTrue($value_get == $value_set);
        }
        
        // also check default options
        for($i = 0; $i < 10; $i ++) {
            // set an option-value pair
            $option = 'def_option' . $i;
            $value_set = 'def_value' . $i;
            $value_get = $co->getConfigOption($option);
            $this->assertTrue($value_get == $value_set);
        }
        
    }

    /**
     * test configurable (defConfig() retrurns array)
     */
    function testConfigurable2() {
        
        $co = new epConfigurableTest2;
        $this->assertTrue($co != null);
        
        // check default options
        for($i = 0; $i < 10; $i ++) {
            // set an option-value pair
            $option = 'def_option' . $i;
            $value_set = 'def_value' . $i;
            $value_get = $co->getConfigOption($option);
            $this->assertTrue($value_get == $value_set);
        }
        
        // make a cfg (to be set to co)
        $cfg = new epConfig;
        for($i = 0; $i < 10; $i ++) {
            // set an option-value pair
            $option = 'cfg_option' . $i;
            $value_set = 'cfg_value' . $i;
            $cfg->set($option, $value_set);
        }
        
        // set cfg to co
        $co->setConfig($cfg);
        
        // check cfg options
        for($i = 0; $i < 10; $i ++) {
            // set an option-value pair
            $option = 'cfg_option' . $i;
            $value_set = 'cfg_value' . $i;
            $value_get = $co->getConfigOption($option);
            $this->assertTrue($value_get == $value_set);
        }
        
        // also check default options
        for($i = 0; $i < 10; $i ++) {
            // set an option-value pair
            $option = 'def_option' . $i;
            $value_set = 'def_value' . $i;
            $value_get = $co->getConfigOption($option);
            $this->assertTrue($value_get == $value_set);
        }
        
    }

}

if (!defined('EP_GROUP_TEST')) {
    $t = new epTestConfig;
    if ( epIsWebRun() ) {
        $t->run(new HtmlReporter());
    } else {
        $t->run(new TextReporter());
    }
}

?>
