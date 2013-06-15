<?php

/**
 * $Id: epGenerator.php 561 2005-10-14 01:33:52Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision: 561 $ $Date: 2005-10-13 21:33:52 -0400 (Thu, 13 Oct 2005) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
namespace ezpdo\compiler;

use ezpdo\base\epConfig;
use ezpdo\base\epConfigurableWithLog;

use ezpdo\orm\epClassMapFactory;

/**
 * Class of ezpdo generator
 *
 * A generator is a configurable object that creates new files
 * etc. into an intended place. This is an abstract class that
 * subclasses should implement method {@link generate()}.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 561 $ $Date: 2005-10-13 21:33:52 -0400 (Thu, 13 Oct 2005) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
abstract class epGenerator extends epConfigurableWithLog {

    /**
     * Cached output dir as it's used very often
     * @var string (transient)
     */
    protected $compiled_dir = '.'; // default to pwd

    /**
     * Cached class map factory
     * @var epClassMapFactory
     */
    protected $cmf;

    /**
     * Constructor
     * @param epConfig|array
     * @access public
     * @see epConfig
     */
    public function __construct($config = null) {
        parent::__construct($config);
        $this->cmf = epClassMapFactory::instance();
    }

    /**
     * Implement abstract method {@link epConfigurable::defConfig()}
     * @access public
     */
    public function defConfig() {

        return array(
            "compiled_dir" => ".", // default to pwd
            "indent" => "  ", // default indent: 2 spaces
            );
    }

    /**
     * Override {@link epConfigurable::setConfig}
     * @param array|epConfig
     * @return void
     */
    public function setConfig($config) {

        // call parent to set config
        if (!parent::setConfig($config)) {
            return false;
        }

        // make sure cached compiled_dir is consistent with newly set config
        $this->compiled_dir = $this->getConfigOption('compiled_dir');

        // if output dir is a relative path, make is absolute
        $this->compiled_dir = $this->getAbsolutePath($this->compiled_dir);
    }

    /**
     * The main task of the generator
     * @return bool
     */
    abstract public function generate();

}
