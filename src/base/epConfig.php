<?php

/**
 * $Id: epConfig.php 969 2006-05-19 12:20:19Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 969 $ $Date: 2006-05-19 08:20:19 -0400 (Fri, 19 May 2006) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */ 

/**#@+
 * need epBase and epUtils
 */
include_once(EP_SRC_BASE.'/epBase.php');
include_once(EP_SRC_BASE.'/epUtils.php');
/**#@-*/

/**
 * Exception class for {@link epConfig}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 969 $ $Date: 2006-05-19 08:20:19 -0400 (Fri, 19 May 2006) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epExceptionConfig extends epException {
}

/**
 * The ezpdo config class
 * 
 * An object of this class stores configuration data. 
 * To construct an epConfig object, you can either load
 * config data from an XML file or an associative array. 
 * 
 * This class is inspired by Harry Feuck's aritle, 
 * http://www.sitepoint.com/article/xml-php-pear-xml_serializer
 * 
 * A configuration file looks like this. 
 * 
 * <pre>
 * &lt;?xml version="1.0" encoding="ISO-8859-1"?&gt; 
 * &lt;options _type="array"&gt; 
 *   &lt;domain _type="string"&gt;site.com&lt;/domain&gt; 
 *   &lt;email _type="string"&gt;info@site.com&lt;/email&gt; 
 *   &lt;docroot _type="string"&gt;/www/path&lt;/docroot&gt; 
 *   &lt;tmp _type="string"&gt;/tmp/path&lt;/tmp&gt; 
 *   &lt;db _type="array"&gt; 
 *     &lt;db_host _type="string"&gt;db.somecompany.com&lt;/db_host&gt; 
 *     &lt;db_user _type="string"&gt;dba&lt;/db_user&gt; 
 *     &lt;db_pass _type="string"&gt;secret&lt;/db_pass&gt; 
 *     &lt;db_name _type="string"&gt;sitedb&lt;/db_name&gt; 
 *   &lt;/db&gt; 
 * &lt;/options&gt; 
 * </pre>
 * 
 * Neither tag <options> nor type-hinting attribute "_type" 
 * is mandatory. If no type hint is present, the value of 
 * the option is treated as string.
 * 
 * Now say the above xml file is stored in config.xml. To load
 * it into memory, simply do the following.
 * <pre>
 *   $cfg = & epConfig::load('config.xml'); 
 * </pre>
 * 
 * Or you can also use an array to build an epConfig object.
 * For the same information as in the XML file, you would have
 * the following array. 
 * 
 * <pre>
 * $options = array(
 *   'domain' => 'site.com',
 *   'email' => 'info@site.com',
 *   'docroot' => '/tmp/path',
 *   'db' => array(
 *      'db_host' => 'db.somecompany.com',
 *      'db_user' => 'dba',
 *      'db_pass' => 'secret',
 *      'db_name' => 'sitedb'
 *    )
 * );
 * </pre>
 * 
 * Similarly the following line loads the array into an epConfig 
 * object.
 * 
 * <pre>
 *   $cfg = & epConfig::load($options); 
 * </pre>
 * 
 * It is easy to use this class once you load it into memory. 
 * Here is an example. Note you can also write the object back
 * to file as shown in the example.
 * 
 * <pre>
 * // load xml config into an epConfig instance 
 * $cfg = epConfig::load('config.xml');
 * 
 * // get values for options
 * $domain = $cfg->get('domain');
 * $email = $cfg->get('email');
 * // ...
 * 
 * // change or add options
 * $cfg->set('email', $corrected_email);
 * $cfg->set('second_domain', $second_domain);
 * // ...
 * 
 * // write config into xml file
 * $cfg->store('config2.xml');
 * // ...
 * </pre>
 * 
 * During the lifetime of an epConfig object, you can always 
 * alter configuration data. You can use {@link set()} to alter
 * values of individual options or do config merge {@link merge()} 
 * if you want to merge in new config data from another epConfig 
 * object or array. The merge behavior is explained in 
 * {@link epArrayMergeRecursive()}.
 * 
 * One thing worth note about using {@link get()}/{@link set()} 
 * to get and set option values is that the parameter, the 
 * option name, can be given in a namespace ('a.b.c.d') or xpath
 * ('a/b/c/d') format so the caller can reach any level of 
 * option hierarchy. For example, 
 * <pre>
 * // ...
 * 
 * // namespace-like option name
 * $log_file_name = $cfg->get('log.file.name');
 * 
 * // xpath-like option name
 * $cfg->get('log/db/name', $log_db_name);
 * 
 * // ...
 * </pre>
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 969 $ $Date: 2006-05-19 08:20:19 -0400 (Fri, 19 May 2006) $
 * @package ezpdo
 * @subpackage ezpdo.base
 */
class epConfig {

    /**
     * const: the default xml config file is config.xml
     * in the current directory 
     */
    const DEF_CONFIG_FILE = 'config.xml';

    /**#@+
     * Used for return value to avoid reference notice in 5.0.x and up
     * @var bool
     */
    static public $false = false;
    static public $true = true;
    static public $null = null;
    /**#@-*/

    /**
     * Array of configuration options
     * @var array
     * @access protected
     */
    protected $options = array();

    /**
     * The source file for this config
     * @var string 
     * @access protected
     */
    protected $source = false;

    /**
     * Constructor
     * @param mixed string xml config file name or array
     */
    public function __construct($cfg = '') { 
        
        if (is_string($cfg)) {
            
            // argument is xml cfg file name
            $cfg_file = $cfg;
            
            // if cfg file not empty, load it
            if (!empty($cfg_file)) {
                // true: load xml file into array
                $options = & $this->load($cfg_file, true);
                if (!empty($options)) {
                    // set loaded array 
                    include_once(EP_SRC_BASE.'/epUtils.php');
                    $this->options = epArrayStr2Bool($options);
                    $this->setSource($cfg_file);
                }
            }
        } else {

            // argument is an array of options
            $this->merge($cfg);

        }
    }
    
    /**
     * Get all options
     * @access public
     * @return array
     */
    public function &options() {
        return $this->options;
    }
    
    /**
     * Returns the value of a configuration option, if found.
     * 
     * If the option's value is an array, it returns an array by default. 
     * It can also return an epConfig wrapper if $ret_wrapper is set to true. 
     * 
     * The name of an option can be given in a namespace ('a.b.c.d') or xpath 
     * ('a/b/c/d') format so the caller can reach any level of the options. 
     * See {@link epArrayGet()}.
     * 
     * @param string name of option
     * @param bool whether to return an epConfig wrapper
     * @return mixed value if found or null if not
     * @access public 
     */
    public function get($name, $ret_wrapper = false) {
        
        // get option value
        $value = epArrayGet($this->options, $name);
        
        // return if non-array value or no epConfig wrapper required
        if (!is_array($value) || !$ret_wrapper) {
            return $value;
        }

        // otherwise wrap array value into epConfig
        $cfg = new epConfig($value);
        if ($cfg) {
            $cfg->setSource($this->getSource());
        }

        return $cfg;
    }
    
    /**
     * Sets (changes) value to a configuration option
     * 
     * The name of an option can be given in a namespace ('a.b.c.d') 
     * or xpath ('a/b/c/d') format so the caller can reach any level 
     * of the option hierarchy. See {@link epArraySet()}.
     * 
     * @param string name of option
     * @param mixed value of option
     * @return bool
     * @access public
     */
    public function set($name, $value) {
        
        // sanity check
        if (!$name) {
            return false;
        }
        
        // if $value is an instance of epConfig, get its option 
        // array so to internally maintain options in an array
        if ($value instanceof epConfig) {
            $value = $value->options();
        }
        
        // set name->value in array
        if (!($this->options = epArraySet($this->options, $name, $value))) {
            return false;
        }
        
        return true;
    }

    /**
     * Remove a configuration option
     * @param string name of option
     * @return void
     * @access public
     */
    public function remove($name) {
        if (isset($this->options[$name])) {
            unset($this->options[$name]);
        }
    }

    /**
     * Resets all configuration options
     * @return void
     * @access public
     */
    public function removeAll() {
        $this->options = array();
    }

    /**
     * Merges options from another epConfig instance or array
     * Same behavior as {@link epArrayMergeRecursive()}
     * @param mixed epConfig or array
     * @return bool
     * @throws epExceptionConfig
     * @access public
     */
    public function merge($config_or_options) {

        // if input config is invalid, do nothing
        if (!$config_or_options) {
            return false;
        }

        // get options in input config
        $options = null;
        $source = false;
        if ($config_or_options instanceof epConfig) {
            $options = & $config_or_options->options();
            $source = $config_or_options->getSource();
        } else if (is_array($config_or_options)) {
            $options = & $config_or_options;
        } else {
            throw new epExceptionConfig('Argument unrecognized');
            return false;
        }

        // check options 
        if (empty($options)) {
            return false;
        }
        
        // merge options
        $this->options = epArrayMergeRecursive($this->options, epArrayStr2Bool($options));

        // set source file
        $this->setSource($source);

        return true;
    }

    /**
     * Loads options from an XML file (see class description for XML format)
     * Returns either an array of options (if $ret_array is set to true) or epConfiginstance
     * @param string xml config file name 
     * @param bool return array if true 
     * @return mixed array or epConfig
     * @throws epExceptionConfig
     * @access protected
     * @static
     */
    public static function &load($cfg_file = DEF_CONFIG_FILE, $ret_array = false) {
        
        // if empty file, return either an empty array or config object
        $cfg_file = trim($cfg_file);
        if (!$cfg_file) {
            if ($ret_array) {
                return array();
            } else {
                $cfg = new epConfig;
                return $cfg;
            }
        }

        // load xml config file
        switch(epFileExtension($cfg_file)) {
            case 'xml': 
                if (false === ($options = epConfig::_loadXml($cfg_file))) {
                    return false;
                }
                break;
            case 'ini':
                if (false === ($options = epConfig::_loadIni($cfg_file))) {
                    return false;
                }
                break;
            default: 
                throw new epExceptionConfig('Unrecognized config file (should be either .ini or .xml)');
                return self::$false;
        }
        
        // return array if required
        if ($ret_array) {
            return $options;
        }
        
        // return epConfig
        $config = new epConfig;
        $config->merge($options);
        $config->setSource($cfg_file);

        return $config;
    }

    /**
     * Loads options from an XML file (see class description for XML format) 
     * into an array. Called by {@link epConfig::load()}
     * @param string file name 
     * @return false|array
     * @throws epExceptionConfig
     */
    static protected function _loadXml($cfg_file) {

        // if cfg file does not exist
        if (!$cfg_file || !file_exists($cfg_file)) {
            // return an empty array
            return array();
        }
        
        // unserialize xml config using SimpleXml
        if (false === ($options = epXml2Array($cfg_file))) {
            throw new epExceptionConfig('Parsing config file failed');
            return self::$false;
        }
        
        // locate options
        if (isset($options['ezpdo'])) {
            $options = $options['ezpdo'];
        } else if (isset($options['options'])) {
            $options = $options['options'];
        } 

        return $options;
    }
 
    /**
     * Loads options from an .ini file into an array. 
     * Called by {@link epConfig::load()}
     * @param string file name 
     * @return false|array
     * @throws epExceptionConfig
     */
    static protected function _loadIni($cfg_file) {

        // if cfg file does not exist
        if (!$cfg_file || !file_exists($cfg_file)) {
            // return an empty array
            return array();
        } 
        
        // parse .ini file
        $options = parse_ini_file($cfg_file, true);
        
        // locate options
        if (isset($options['ezpdo'])) {
            $options = $options['ezpdo'];
        } else if (isset($options['options'])) {
            $options = $options['options'];
        } 

        return $options;
    }
 
    /**
     * Stores (serializes) the config instance to an XML file
     * @param string xml config file name 
     * @return bool true if successful; false otherwise
     * @throws epExceptionConfig
     * @access public
     */
    public function store($cfg_file = DEF_CONFIG_FILE) {

        if (!$data = epValue2Xml($this->options())) {
            throw new epExceptionConfig("Cannot unserialize options into XML"); 
            return false;
        }

        $fp = fopen($cfg_file, 'wb');
        if (!$fp) {
            throw new epExceptionConfig('Cannot open ' . $cfg_file); 
            return false;
        }
        
        if (!fwrite($fp, $data, strlen($data))) {
            throw new epExceptionConfig('Cannot write to ' . $cfg_file); 
            fclose($fp);
            return false;
        } 
        
        fclose($fp);
        return true;
    }
    
    /**
     * Get the source file for the config
     * @return string $source
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * Set the source file for the config
     * Note the file must have an absolute path
     * @param string $source
     * @return void
     */
    public function setSource($source = '') {
        $this->source = $source;
    }

}

?>
