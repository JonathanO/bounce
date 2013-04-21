<?php
/**
 * @author Steve Storey <steves@moo.com>
 * @copyright Copyright (c) 2012, MOO Print Ltd.
 * @license ISC
 */

namespace MooDev\Bounce\Context;

use MooDev\Bounce\Config;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Sub-class of the XmlApplicationContext which caches the parsed configuration
 * objects into the APC cache.
 *
 * It keeps track of both modification times, and the inodes of all the files
 * which have been read in to create the configuration. If any files are newer
 * or have different inodes, then reload everything and re-cache it.
 *
 * @author steve
 *
 */
class ApcCachedXmlApplicationContext extends XmlApplicationContext implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface the logging instance
     */
    private $_log;
    /**
     * @var string => array map of stat info returned by stat calls keyed on
     * the filename
     */
    protected $_statInfoCache = array();

    /**
     * Creates the caching instance, setting up logging for delegating upwards
     * @param string $xmlFilePath
     * @param bool $shareBeanCache
     * @param ValueTagProvider[] $customNamespaces
     * @param array $logFactory Callback array to call to obtain a logger instance. Will be called with a single param (class name.)
     */
    public function __construct($xmlFilePath, $shareBeanCache = true, $customNamespaces = array(), $logFactory = array('\MooDev\Bounce\Logger\NullLogFactory', 'getLog'))
    {
        $logger = call_user_func($logFactory, get_class($this));
        if ($logger instanceof LoggerInterface) {
            $this->setLogger($logger);
        }
        parent::__construct($xmlFilePath, $shareBeanCache, $customNamespaces);
    }

    protected function _parseXmlFile($xmlFilePath)
    {
        //Do we have caching enabled?
        if (function_exists("apc_fetch")) {
            $this->_log->debug("APC caching is ENABLED");
            //Do we have a cache entry for this file path?
            $cacheKey = "bouncexml~$xmlFilePath";
            $cacheContentSerialized = apc_fetch($cacheKey);
            //Cache Content structure is:
            // array(
            //   "xmlFiles" => array(
            //      "/absolute/path/to/parent.xml" => array(
            //          "dev" => 5, //Device number from the underlying O/S
            //          "ino" => 1234567, //Inode number of the file on the device
            //          "mtime" => 124124681274 //Last modified time
            //      ),
            //      "/absolute/path/to/child1.xml" => array(
            //          "dev" => 5,
            //          "ino" => 345346364,
            //          "mtime" => 124124681274
            //      ),
            //      "/absolute/path/to/child2.xml" => array(
            //          "dev" => 5,
            //          "ino" => 345346364,
            //          "mtime" => 124124681274
            //      ),
            //   ),
            //   "context" => Config_Context
            // )
            if ($cacheContentSerialized) {
                $this->_log->debug("Found cached version on {xmlFilePath}", array("xmlFilePath" => $xmlFilePath));
                $cacheContent = unserialize($cacheContentSerialized);
                //Look at all the files from the cache content array to see
                //if any of them are different
                $filesToCheck = $cacheContent["xmlFiles"];
                $cacheUpToDate = true; //Be positive ...
                foreach ($filesToCheck as $fileName => $cachedStatInfo) {
                    //Stat the filename
                    $onDiskStatInfo = $this->_getStatInfo($fileName);
                    if ($onDiskStatInfo["mtime"] != $cachedStatInfo["mtime"] ||
                    $onDiskStatInfo["ino"] != $cachedStatInfo["ino"] ||
                    $onDiskStatInfo["dev"] != $cachedStatInfo["dev"]
                    ) {
                        $cacheUpToDate = false;
                        $this->_log->debug("Cache out of date: {fileName} has stat: {stat} but cached {cached}", array("fileName" => $fileName, "stat" => $onDiskStatInfo, "cached" => $cachedStatInfo));
                        break;
                    }
                }
                if ($cacheUpToDate) {
                    //Yes, so update the list of processed files for completeness, and
                    //return the cached context
                    /*foreach ($filesToCheck as $fileName => $cachedStatInfo) {
                        $this->_processedFiles[$fileName] = $cacheContent["context"];
                    }*/ //TODO We can't update this any more since it's a map of child contexts, which we don't have
                    $this->_log->debug("Cache is up to date. Returning cached context");
                    return $cacheContent["context"];
                }
            }
            $this->_log->debug("Cache item not found or invalid for {xmlFilePath}. Reloading ...", array("xmlFilePath" => $xmlFilePath));

            //If we get here, we're basically not up to date one way or another
            //so load up the context, and cache it.
            $context = parent::_parseXmlFile($xmlFilePath);
            //Now create the cache structure. NOTE! It's possible that for a
            //nested content structure we're going to include files from the
            //processed list that had been processed *BEFORE* we loaded this
            //particular XML file. However, we can't tell which ones might
            //also be dependencies of this child file, and using another
            //XmlApplicationContext instance is impractical as it means exposing
            //way more of the internals as public variables purely for the purposes
            //of the caching system.
            $cacheContent = array(
                "xmlFiles" => array(),
                "context" => $context
            );
            foreach (array_keys($this->_processedFiles) as $fileName) {
                $cacheContent["xmlFiles"][$fileName] = $this->_getStatInfo($fileName);
            }
            $cacheContentStr = serialize($cacheContent);
            apc_store($cacheKey, $cacheContentStr);
            $this->_log->debug("Cache item added for {$xmlFilePath}", array("xmlFilePath" => $xmlFilePath));
            //Return it
            return $context;
        } else {
            $this->_log->warning("APC caching is DISABLED");
            //No caching possible, so revert to the normal method of
            //reading from the filesystem all the time
            return parent::_parseXmlFile($xmlFilePath);
        }
    }

    /**
     * Returns the stat info for a given filename. This will make reading and
     * processing for putting into the cache rather more efficient.
     *
     * @param $fileName string the name of the file we want to get stat information
     *                  for
     * @return array result of the stat call for this filename
     */
    private function _getStatInfo($fileName)
    {
        if (!array_key_exists($fileName, $this->_statInfoCache)) {
            $this->_statInfoCache[$fileName] = stat($fileName);
        }
        return $this->_statInfoCache[$fileName];
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->_log = $logger;
    }
}
