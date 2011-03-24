<?php

/**
 * $Id: epDbUpdate.php 118 2005-03-20 18:14:20Z shak $
 *
 * Copyright(c) 2011 by David Paz. All rights reserved.
 *
 * @author David Paz <davidmpaz@gmail.com>
 * @version 1.1.6 $Date: Feb 11, 2011 10:37:47 AM -0500 (Feb 11, 2011) $
 * @package ezpdo
 * @subpackage ezpdo.tools
 */

/**
 * Need {@link epConfigurableWithLog} as the superclass
 */
include_once(EP_SRC_BASE.'/epConfigurableWithLog.php');

/**
 * Need class map factory
 */
include_once(EP_SRC_ORM.'/epClassMap.php');

/**
 * Exception class for epDbUpdate
 *
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision$ $Date: Feb 13, 2011 10:53:43 AM -0500 (Feb 13, 2011) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epExceptionDbUpdate extends epException {
}

/**
 *
 * Class epDbUpdate used in {@link epManager}
 * <b>Incrementally</b> updates the schema
 *
 * This class is in charge applying changes and store them after changes are
 * applied to the database schema. This is done by the {@link updateSchema()} method.
 * <b>It can be used by others</b> for this purpose.
 *
 *
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision$ $Date: Feb 11, 2011 10:53:43 AM -0500 (Feb 11, 2011) $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbUpdate extends epConfigurableWithLog implements epSingleton {

    /**
     * Constants
     */
    const SCHEMA_OP_TAG = "schemaop";
    const SCHEMA_UI_TAG = "uuid";
    const SCHEMA_NAMED_TAG = "named";
    const SCHEMA_TNAMED_TAG = "tablename";

    const OP_TABLE      = "table";
    const OP_IGNORE     = "ignore";
    const OP_ALTER      = "alter";
    const OP_DROP       = "drop";
    const OP_ADD        = "add";

    /**
     * The outdated class map factory
     * @var false|epClassMapFactory
     */
    protected $ocmf = false;

    /**
     * The strategy to use
     * @var false|string
     */
    protected $strategy = false;

    /**
     * The cached manager
     * @var false|epManager
     */
    protected $ep_m = false;

    /**
     * A record of processed classes
     * @var array
     */
    protected $processed = array();

    /**
     * @return array the $processed
     */
    public function getProcessed() {
        return $this->processed;
    }

    /**
     * @return string the $strategy
     */
    public function getStrategy() {
        return $this->strategy;
    }

    /**
     * Constructor, also set outdated class map factory
     * @param epConfig|array
     * @access public
     * @see epConfig
     */
	public function __construct($config = null) {
        parent::__construct($config);

        // get manager
        $this->ep_m = epManager::instance();

        //grab config info
        $this->setConfig($this->ep_m->getConfig());

        // check config preconditions, no file specified
        // to get old class map factory
        if(!($this->getConfigOption('backup_compiled') || $this->getConfigOption('update_from')) &&
            ($this->getConfigOption('update_strategy') != 'drop')){
            throw new epExceptionDbUpdate(
                'Failed to create updater, backup or old class map needed to update.');
            return false;
        }

        // init strategy and setup class map to update
        $this->strategy = $this->getConfigOption('update_strategy');

        // get outdated class map
        if($file = $this->getConfigOption('update_from')){
            // load it from specified file
            $this->ocmf = epGetBackup($file);
        }else{
            // load it from compile dir
            $this->ocmf = epGetBackup($this->getConfigOption('compiled_dir'));
        }

        //strategy defined but no backup to work with
        if( ($strategy = $this->strategy) && $strategy != 'drop' && !$this->ocmf){
            // no backup file was found
            throw new epExceptionDbUpdate('Failing in loading backup class map.');
            return false;
        }
    }

    /**
     * Main task, incrementally updates the schema.
     *
     * This method is in charge of finding the matching class map
     * in old classes for the one passed and find differences between
     * both class maps.
     *
     * <i>Expected info</i>
     * Class map with a custom tag (class level) called
     * {@link epDbUpdate::SCHEMA_UI_TAG} which contain uuid
     * of the class just modified. This uuid is used for name
     * conflict resolution.
     *
     * For each field map in the modified classes there must be an identical
     * custom tag for all fields. Based on diffs found, the method
     * will annotate each field of the class map with the kind of operation
     * needed to apply to it (add,modify,drop)
     *
     * <b><i>Expected Result</i><b>
     * Only schema for primitives fields will be updated for now.
     * Only incremental changes will be applied automatically. This means
     *  that <b>only field addition</b> will be made and <b>modifications
     *  only when there won't be lost of data</b> from one field type to
     *  other field of another type
     * Changes of other kind will be ignored and the sql printed to log output.
     *  Here enters droping a table/column, change a column to an incompatible type
     *  A <b>force_update</b> config option could be used by those with destructive
     *  intentions :).
     *
     * @param epClassMap $ncm the class to alter table.
     * @param bool $update whether to execute (true) or log (false) the queries.
     * @param bool $force whether to force destructives queries to be executed.
     * @return boolean
     */
    public function updateSchema($ncm, $update = false, $force = false) {

        // sanity check
        if( !($this->ocmf || $ncm) ){
            return false;
        }

        $this->log('Updating schema for class [' . $ncm->getName() . '] - start',
            epLog::LOG_INFO);

        // generate/execute queries to alter tables
        $ret = $this->_updateSchema($this->ocmf->allMade(), $ncm, $update, $force);

        // array with executed and ignored queries and execution result
        if($ret['sucess']){
            if(!$update){
                $content = array();
                // print them in file
                array_push($content,
                    "--- Queries executed on " . $ncm->getTable() . "\n\n");
                $content = array_merge($content, $ret['executed']);
                array_push($content,
                    "\n\n--- Queries ignored on " . $ncm->getTable() . "\n");
                array_push($content,
                    "--- you are in charge of execute them if wanted.\n\n/*");
                $content = array_merge($content, $ret['ignored']);
                array_push($content, "\n*/");

                // write to file
                epWriteToFile($this->getConfigOption('compiled_dir').
                    '/' . $ncm->getName() . '.sql', $content);

                $this->log("Updating schema for class [" . $ncm->getName() . "] - end",
                    epLog::LOG_INFO);
                }

            // result cached
            $ret['class'] = $ncm->getName();

            // return same array, usefull for testing
            return $ret;
        }

        $this->log('Nothing to update in schema for class [' . $ncm->getName() . '] - end',
            epLog::LOG_INFO);

        return $ret['sucess'];
    }

    public function cleanupSchema($update, $force) {
        // sanity check
        if(!($this->processed && $this->ocmf)){
            return false;
        }

        // lets find surplus classes and drop them
        $outdated = $this->ocmf->allMade();
        if(!(count($outdated) > count($this->processed))){
            //nothing to do
            return false;
        }

        //if not found a match, drop it is requested!
        $this->log('DOING SCHEMA CLEANUP', epLog::LOG_INFO);
        foreach ($outdated as $ocm) {
            if(!($cm = $this->findMatch($this->processed, $ocm))){
                // annotate for drop the class
                $annClassMap = $this->processClassMaps($ocm, null);

                // get db object
                include_once(EP_SRC_DB.'/epDbObject.php');
                $dbo = epDbFactory::instance()->make($ocm->getDsn());
                if( !($dbo)){
                    throw new epExceptionDbUpdate("Can not create db object.");
                    continue;
                }
                // real alter table schema operation
                if(!$ret = $dbo->alter($annClassMap, $update, $force)){
                    $this->log(
                        "Schema for class [" . $annClassMap->getName() . "] was not droped.",
                        epLog::LOG_WARN);
                    continue;
                }
            }
        }
        $this->log('END OF SCHEMA CLEANUP', epLog::LOG_INFO);
    }

    /**
     * Takes two class maps, outdated and updated ones and produces a class map
     * properly annotated to produce sql for altering the table its refers to.
     *
     * If updated class map is not specified (null) it set up outdated class map
     * for being drop.
     *
     * @param epClassMap $outdCm
     * @param epClassMap $updCm
     * @return epClassMap the ready to produce sql class map
     */
    public function processClassMaps($outdCm, $updCm = null, $force = false){

        if(!$updCm){
            // WARN HERE, its being marked for drop
            $outdCm->setTag(epDbUpdate::SCHEMA_OP_TAG, epDbUpdate::OP_DROP);
            return $outdCm;
        }

        // annotate class name
        if($updCm->getName() != $outdCm->getName()){
            $updCm->setTag(epDbUpdate::SCHEMA_NAMED_TAG, $outdCm->getName());
            $updCm->setTag(epDbUpdate::SCHEMA_TNAMED_TAG, $outdCm->getTable());
        }
        else{
            $updCm->setTag(epDbUpdate::SCHEMA_NAMED_TAG, false);
        }
        // annotate fields
        $ofm = $outdCm->getAllFields();
        $cfm = $updCm->getAllFields();
        foreach ($cfm as $fname => &$ufm) {
            //no relation for now
            if(! $ufm->isPrimitive()) continue;

            // directly found field
            if( isset($ofm[$fname]) ){
                // no need of named tag
                $ufm->setTag(epDbUpdate::SCHEMA_NAMED_TAG, false);
                // check if equal fields
                if(! $ufm->equal($ofm[$fname])){
                    $ufm->setTag(epDbUpdate::SCHEMA_OP_TAG, epDbUpdate::OP_ALTER);
                }
                // ignored if not force and not compatible in types
                if(!($force || $ufm->isTypeCompatible($ofm[$fname]))){
                    $ufm->setTag(epDbUpdate::OP_IGNORE, true);
                }
                // remove current old field map to use the resting maps for drop later
                unset($ofm[$fname]);
                continue;
            }
            // look for uuid tag matching field
            $found = false; $name = null;
            reset($ofm); $fm = null;
            while(!$found && (list($name, $fm) = each($ofm))){
                $last_name = $name;
                $found = $ufm->getTag(epDbUpdate::SCHEMA_UI_TAG) == $fm->getTag(epDbUpdate::SCHEMA_UI_TAG);
            }

            if($found){
                // annotate field to modify
                $ufm->setTag(epDbUpdate::SCHEMA_OP_TAG, epDbUpdate::OP_ALTER);
                $ufm->setTag(epDbUpdate::SCHEMA_NAMED_TAG, $last_name);
                unset($ofm[$last_name]);
            }
            //needs to be added
            else{
                $ufm->setTag(epDbUpdate::SCHEMA_OP_TAG, epDbUpdate::OP_ADD);
            }
        }

        // at this point if any primitive old field map left
        // needs to be marked for drop
        foreach ($ofm as $fm){
            if($fm->isPrimitive()){
                //dummy field just to drop from table
                $f = epFieldMapFactory::make(
                        $fm->getName(), $fm->getType(), $fm->getTypeParams());
                $f->setTag(epDbUpdate::SCHEMA_OP_TAG, epDbUpdate::OP_DROP);
                $updCm->addField($f);
            }
        }

        // return modified class map
        return $updCm;
    }

    /**
     * Find differences and update schema. Can return queries refused to execute
     * due to its destructive nature (drop), but only if not forced to do it.
     *
     * If force is specified when altering the schema, drop fields/table will be executed
     *
     * @param array $outdCmaps array of epClassMap
     * @param epClassMap $ccm current class map to generate queries
     * @param bool $update whether to run/log the queries
     * @param bool $force whether to force to run destructive queries
     * @return boolean|array of sql refused to execute
     */
    protected function _updateSchema($outdCmaps, $ccm, $update = false, $force = false){

        $found = $this->findMatch($outdCmaps, $ccm);

        $annClassMap = null;
        if(!$found || $ccm->equal($found)){
            // not found fresh class or already equals
            return false;
        }

        // compare fields and annotate
        $annClassMap = $this->processClassMaps($found, $ccm, $force);

        // report processed class map
        $this->processed[] = $ccm;

        // get db object
        include_once(EP_SRC_DB.'/epDbObject.php');
        $dbo = epDbFactory::instance()->make($ccm->getDsn());
        if( !($dbo)){
            throw new epExceptionDbUpdate("Can not create db object.");
            return false;
        }

        // if was renamed the class
        if($old_class = $annClassMap->getTag(epDbUpdate::SCHEMA_NAMED_TAG)){
            // get all realtions fields where it is involved before
            $ofmaps = $this->ocmf->getRelationFields($old_class);
            // get all realtions fields where it is involved now
            $nfmaps = $this->ep_m->getClassMapFactory()->
                getRelationFields($annClassMap->getName());
            // remove old class class map factory repeated, but with different name
            //$this->ep_m->getClassMapFactory()->remove($found->getName());
        }

        // real alter table schema operation
        if(!$ret = $dbo->alter($annClassMap, $ofmaps, $nfmaps, $update, $force)){
            $this->log("Schema for class [".$annClassMap->getName()."] was not updated.",
                epLog::LOG_WARN);
            return false;
        }

        return $ret;
    }

    /**
     * Find match for class/field in list
     *
     * Do the work by looking
     *
     * @param array of epClassMap|epFieldMap $item_list
     * @param epClassMap|epFieldMap $item
     * @return epClassMap|epFieldMap|false
     */
    public function findMatch($item_list, $item){

        // prevent key'd by name arrays
        $item_list = array_values($item_list);

        // check fresh class maps to find its match in outdated
        $ret = null; $count = count($item_list);
        $found = false; $i = 0;
        while( !$found && ($i < $count) ){
            $oitem = $item_list[$i];
            $i++;
            // found class by name or by named custom tag?
            $found = ($item->getName() == $oitem->getName()) ||
                ($item->getTag(epDbUpdate::SCHEMA_UI_TAG) && $oitem->getTag(epDbUpdate::SCHEMA_UI_TAG) &&
                ($item->getTag(epDbUpdate::SCHEMA_UI_TAG) == $oitem->getTag(epDbUpdate::SCHEMA_UI_TAG)));
        }
        return $found ? $oitem : $found;
    }

    /**
     * Implements {@link epSingleton} interface
     * @return epDbUpdate (instance)
     * @access public
     * @static
     */
    static public function &instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self;
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
     * self instance
     * @var epManagerBase
     * @static
     */
    static protected $instance;

}
?>
