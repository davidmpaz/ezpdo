<?php

/**
 * $Id: epLog.php 584 2005-10-19 02:22:30Z nauhygon $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 584 $ $Date: 2005-10-18 22:22:30 -0400 (Tue, 18 Oct 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */

/**
 * need epConfigurable, epUtils
 */
include_once(EP_SRC_BASE.'/epConfigurable.php');

/**
 * also need PEAR::Log (modified) 
 */
include_once(EP_LIBS_PEAR . '/Log.php');

/**
 * Exception class for {@link epLog}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 584 $ $Date: 2005-10-18 22:22:30 -0400 (Tue, 18 Oct 2005) $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epExceptionLog extends epException {
}

/**
 * Class of ezpdo logging facility
 * 
 * A wrapper around the composite log handler in PEAR::Log 
 * (see {@link http://www.indelible.org/pear/Log}).
 * 
 * The class extends the {@link epConfigurable} class. This 
 * means you can easily change the logger's configuration. 
 * 
 * The logger uses two log handlers (defined in PEAR::Log),
 * to which log messages are sent: console, file, or both. 
 * You can use any combination by setting config through 
 * the {@link epConfigurable} API that this class inherits.
 * 
 * Usage: 
 * <pre>
 *   //... 
 *   epLog::log('log msg # 1', epLog::LOG_EMERG);
 *   //...
 *   // to merge in new config to the logger
 *   epLog::config($new_cfg);
 *   //...
 *   epLog::log('log msg # 2', epLog::LOG_ALERT);
 *   epLog::log('log msg # 3'); // uses default level epLog::LOG_INFO
 *   //...
 * </pre>
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 584 $ $Date: 2005-10-18 22:22:30 -0400 (Tue, 18 Oct 2005) $
 * @package ezpdo
 * @subpackage ezpdo.base
 */
class epLog extends epConfigurable implements epSingleton {
    
    /**
     * log levels 
     */
    const LOG_EMERG   = PEAR_LOG_EMERG;     // System is unusable
    const LOG_ALERT   = PEAR_LOG_ALERT;     // Immediate action required
    const LOG_CRIT    = PEAR_LOG_CRIT;      // Critical conditions
    const LOG_ERR     = PEAR_LOG_ERR;       // Error conditions
    const LOG_WARN    = PEAR_LOG_WARNING;   // Warning conditions
    const LOG_NOTICE  = PEAR_LOG_NOTICE;    // Npdoal but significant
    const LOG_INFO    = PEAR_LOG_INFO;      // Infpdoational
    const LOG_DEBUG   = PEAR_LOG_DEBUG;     // Debug-level messages    
    
    /**
     * default log file
     */
    const DEF_LOG_FILE = 'ezpdo.log';
    
    /**
     * console handler
     */
    private $console_handler;
    
    /**
     * file handler
     */
    private $file_handler;
    
    /**
     * the composite handler that contains all availabe simple handlers
     */
    private $composite_handler;
    
    /**
     * Constructor
     * @param epConfig|array 
     * @access public
     */
    public function __construct($config = null) {
        parent::__construct($config);
    }
    
    /**
     * Implement abstract method {@link epConfigurable::defConfig()}
     * @access public
     */
    public function defConfig() {
        // by default, disable all
        return array(
            "log_console" => false, // by default no console log
            "log_file" => '', // by default no file log
            );
    }
    
    /**
     * Initialize handlers according to config
     * @return bool
     * @throws epExceptionLog
     * @access private
     */
    private function initialize() {
        
        // create the composite handler first 
        if (!$this->composite_handler) {
            // create the composite handler 
            $this->composite_handler = &epLib_Log::singleton('composite');
            if (!$this->composite_handler) {
                throw new epExceptionLog('Cannot instantiate composite handler');
                return false;
            }
        }
        
        // config the console hander (only on CLI)
        if (epIsCliRun()) {
            if ($this->getConfigOption('log_console')) {
                $this->console_handler = &epLib_Log::singleton('console', '', 'ident', array('stream' => STDOUT));
                if (!$this->console_handler) {
                    throw new epExceptionLog('Cannot instantiate log console handler');
                    return false;
                }
                $this->composite_handler->addChild($this->console_handler); 
            }
        }
        
        // config the file hander
        $log_file = trim($this->getConfigOption('log_file'));
        if (!empty($log_file)) {
            $log_file = $this->getAbsolutePath($log_file);
            $this->file_handler = &epLib_Log::singleton('file', $log_file, 'ident', array('mode' => 0644));
            if (!$this->file_handler) {
                throw new epExceptionLog('Cannot instantiate log file handler');
                return false;
            }
            $this->composite_handler->addChild($this->file_handler); 
        } 
        
        return true;
    }
    
    /**
     * Override {@link epConfigurable::setConfig}
     * @param mixed array or epConfig
     * @throws epExceptionLog
     * @return void
     */
    public function setConfig($config) {
        
        parent::setConfig($config);
        
        // force initialization
        return $this->initialize();
    }
    
    /**
     * Check if log has been initialized
     * @return bool
     * @access private
     */
    private function isInitialized() {
        return (isset($this->composite_handler));
    }
    
    /**
     * Log a message with severity level 
     * <b>Non-static</b> called by the static version {@link epLog::Log()}
     * @param string log message
     * @param integer log level
     * @return bool
     * @throws epExceptionLog
     * @access public
     */
    public function log($msg, $level = epLog::LOG_INFO) {
        
        // check if log initialized
        if (!$this->isInitialized()) {
            $this->initialize();
        }
        
        if (!$this->composite_handler) {
            throw new epExceptionLog('Cannot get logger');
            return false;
        }
        
        $this->composite_handler->log($msg, $level);
        return true;
    }
    
    /**
     * Set identity of the client calling this logger
     * @param string 
     * @return bool
     */
    public function setIdent($ident = '') {
        
        // check if log initialized
        if (!$this->isInitialized()) {
            $this->initialize();
        }
        
        // check composite handler
        if (!$this->composite_handler) {
            throw new epExceptionLog('Cannot get logger');
            return false;
        }
        
        $this->composite_handler->setIdent($ident);
        return true;
    }
    
    /**
     * Implements {@link epSingleton} interface
     * @return epBase (instance)
     * @access public
     * @static
     */
    static public function &instance() {
        if (!isset(epLog::$instance)) {
            self::$instance = new epLog;
        }
        return self::$instance;
    }
    
    /**
     * Implement {@link epSingleton} interface
     * Forcefully destroy old instance (only used for tests). 
     * After reset(), {@link instance()} returns a new instance.
     */
    static public function destroy() {
        self::$instance = null;
    }

    /**
     * epLog instance
     */
    static private $instance; 
    
} // end of class epLog 

?>
