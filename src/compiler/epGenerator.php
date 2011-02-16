<?php

/**
 * $Id: epGenerator.php 561 2005-10-14 01:33:52Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 561 $ $Date: 2005-10-13 21:33:52 -0400 (Thu, 13 Oct 2005) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */

/** 
 * Need {@link epConfigurableWithLog} as the superclass 
 */
include_once(EP_SRC_BASE.'/epConfigurableWithLog.php');

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

/**
 * Class to generate runtime persistence configuaration 
 * 
 * This class use PHP object serialization to persist runtime 
 * class mapping information. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 561 $ $Date: 2005-10-13 21:33:52 -0400 (Thu, 13 Oct 2005) $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
class epGenRuntimeConfig extends epGenerator {
    
    /**
     * Constructor
     * @param epConfig|array 
     * @access public
     * @see epConfig
     */
    public function __construct($config = null) {
        parent::__construct($config);
    }
    
    /**
     * Implement abstract method {@link epConfigurable::defConfig()}
     * @access public
     */
    public function defConfig() {
        // collect parent's default config 
        $cfg = new epConfig(parent::defConfig());
        if ($cfg) {
            $cfg->set('compiled_file', 'compiled.ezpdo');
        }
        return $cfg;
    }
    
    /**
     * Implement abstract method {@link epGenerator::generate()}
     * @param boolean $validate whether to validate before writing runtime config
     * @param boolean $backup whether to backup old compiled file or not
     * @return bool
     */
    public function generate($validate = true, $backup = true) {
        
        // check if we have class map factory (which should have set in constructor)
        if (!$this->cmf) {
            $this->log('Cannot find class mapping factory', epLog::LOG_CRIT);
            return false;
        }
        
        // validate the class maps
        if ($validate) {
            if (($err_msg = $this->cmf->isValid(false)) !== true) {
                $this->log("Class mapping info is invalid (\n" . implode("\n", $err_msg) . "\n)", epLog::LOG_ERR);
                return false;
            }
        }
        
        // serialize class map factory for later retrieval
        $scmf = $this->cmf->serialize();
        if (!$scmf) {
            $this->log('Empty class mapping info', epLog::LOG_ERR);
            return false;
        }
        
        // write serialized config into file
        $file = $this->compiled_dir . '/' . $this->getConfigOption('compiled_file');
        
        // check if file exist
        if ($backup && file_exists($file)) {
            // backup if exists
            $file_bkup = $file . '.backup.' . date('ymdHis');
            if (!copy($file, $file_bkup)) {
                $this->log('Cannot back up old class map file [' . $file . '] to [' . $file_bkup . ']', epLog::LOG_ERR);
                return false;
            }
        }
        
        if (!file_put_contents($file, $scmf)) {
            $this->log('Cannot write class map file into [' . $file . ']', epLog::LOG_WARN);
            return false;
        }
        
        return true;
    }
    
}

?>
