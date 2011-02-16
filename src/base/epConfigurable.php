<?php

/**
 * $Id: epConfigurable.php 816 2006-02-14 03:03:02Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 816 $ $Date: 2006-02-13 22:03:02 -0500 (Mon, 13 Feb 2006) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */ 

/**
 * need epBase
 */
include_once(EP_SRC_BASE.'/epBase.php');

/**
 * need epConfig
 */
include_once(EP_SRC_BASE.'/epConfig.php');

/**
 * Class of configurable objects
 * 
 * A configurable object is one whose internal behaviors can be 
 * controlled by configuration parameters (or options). 
 * 
 * The object can have a set of default options. The default options 
 * are supplied by method {@link defConfig()}. If a 
 * subclass needs to have its own set of default config, you only need 
 * to change this method to return the desired options. 
 * 
 * If you want to change the config after the object is 
 * instantiated, simply call method {@link setConfig()} with the 
 * intended new config options as the parameter. The parameter 
 * can be either an associative array or an {@link epConfig} 
 * object. The new config will be merged to the old config. 
 * See {@link epConfig} and {@link epConfig::merge()} for more info. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 816 $ $Date: 2006-02-13 22:03:02 -0500 (Mon, 13 Feb 2006) $
 * @package ezpdo
 * @subpackage ezpdo.base
 * @see epConfig
 */
abstract class epConfigurable extends epBase {
    
    /**
     * Config for the object 
     * @var epConfig
     * @access private
     */
    private $config = null;
    
    /**
     * Constructor
     * @param epConfig|array|null
     */
    public function __construct($config = null) { 
        
        // set config with default config
        $this->config = $this->defConfig();
        // if returned is an array, make it epConfig 
        if (is_array($this->config)) {
            $this->config = new epConfig($this->config);
        }
        
        // merge if param is set 
        if ($config) {
            $this->setConfig($config);
        }
    }
    
    /**
     * Returns default config
     * Subclasses having default options should override 
     * this method to return the desired default config
     * @return mixed array or epConfig
     */
    abstract public function defConfig();
    
    /**
     * Get the object's current config
     * @return epConfig
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Set config to the object
     * This method perfpdos array_merge (overwrite) operatoins 
     * on options and can be called multiple times
     * @param mixed array or epConfig
     * @return bool
     */
    public function setConfig($config) {
        $this->config->merge($config);
        return true;
    }
    
    /**
     * Save config into a file
     * @param string file name 
     * @return bool
     */
    public function saveConfig($file) {
        return $this->config->store($file);
    }
    
    /**
     * Return the value of a config option
     * @param string option name 
     * @return mixed option value (null if not set)
     */
    public function getConfigOption($option) {
        return $this->config->get($option);
    }
    
    /**
     * Set the value of a config option
     * @param string option name
     * @param mixed option value 
     * @return void
     */
    public function setConfigOption($option, $value) {
        return $this->config->set($option, $value);
    }

    /**
     * Get the source file for the config
     * @return string
     */
    public function getConfigSource() {
        return $this->config->getSource();
    }

    /**
     * Get the absolute path for a given path 
     * The method checks if this is a relative path. If so, 
     * it makes the path absolute by adding the path of the 
     * config source file
     */
    public function getAbsolutePath($path) {
        
        // if no config source file is set
        if (!($source = $this->getConfigSource())) {
            // do nothing on the path
            return $path;
        }

        // check if path is absolute
        if (epIsAbsPath($path)) {
            return $path;
        }

        // make path absolute
        return dirname($source) . '/' . $path;
    }
}

?>