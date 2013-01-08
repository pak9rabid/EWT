<?php
/**
 Copyright (c) 2012 Patrick Griffin

 Permission is hereby granted, free of charge, to any person obtaining
 a copy of this software and associated documentation files (the
 "Software"), to deal in the Software without restriction, including
 without limitation the rights to use, copy, modify, merge, publish,
 distribute, sublicense, and/or sell copies of the Software, and to
 permit persons to whom the Software is furnished to do so, subject to
 the following conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

	namespace elwood\config;
	
	use Exception;
	
	/**
	 * Access configuration settings
	 * 
	 * A class that represents configuration settings as specified in
	 * the EWT configuration file.
	 * 
	 * @author Patrick Griffin <pak9rabid@yahoo.com>
	 */
	
	final class Config
	{
		/**
		 * CONFIG_FILE
		 * 
		 * Name of the EWT configuration file
		 * 
		 * @var string
		 */
		const CONFIG_FILE = "ewt.cfg";
		
		/**
		 * APC_CACHE_CONFIG_KEY
		 * 
		 * Key used to cache the Config object with APC
		 * 
		 * @var string
		 */
		const APC_CACHE_CONFIG_KEY = "EWT_CONFIG";
		
		/** config file options */
		
		/**
		 * OPTION_DB_TYPE
		 * 
		 * Convenience constant for the configuration option 'db.type'
		 * 
		 * @var string
		 */
		const OPTION_DB_TYPE = "db.type";
		
		/**
		 * OPTION_DB_HOST
		 *
		 * Convenience constant for the configuration option 'db.host'
		 *
		 * @var string
		 */
		const OPTION_DB_HOST = "db.host";
		
		/**
		 * OPTION_DB_PORT
		 *
		 * Convenience constant for the configuration option 'db.port'
		 *
		 * @var string
		 */
		const OPTION_DB_PORT = "db.port";
		
		/**
		 * OPTION_DB_DATABASE
		 *
		 * Convenience constant for the configuration option 'db.database'
		 *
		 * @var string
		 */
		const OPTION_DB_DATABASE = "db.database";
		
		/**
		 * OPTION_DB_USERNAME
		 *
		 * Convenience constant for the configuration option 'db.username'
		 *
		 * @var string
		 */
		const OPTION_DB_USERNAME = "db.username";
		
		/**
		 * OPTION_DB_PASSWORD
		 *
		 * Convenience constant for the configuration option 'db.password'
		 *
		 * @var string
		 */
		const OPTION_DB_PASSWORD = "db.password";
		
		/**
		 * OPTION_DB_DEBUG
		 *
		 * Convenience constant for the configuration option 'db.debug'
		 *
		 * @var string
		 */
		const OPTION_DB_DEBUG = "db.debug";
		
		/**
		 * OPTION_WEBSITE_TEMPLATE
		 * 
		 * Convenience constant for the configuration option 'website.template'
		 * 
		 * @var string
		 */
		const OPTION_WEBSITE_TEMPLATE = "website.template";
		
		/**
		 * OPTION_LOG_ENABLED
		 *
		 * Convenience constant for the configuration option 'log.enabled'
		 *
		 * @var string
		 */
		const OPTION_LOG_ENABLED = "log.enabled";
		
		/**
		 * OPTION_LOG_TYPE
		 *
		 * Convenience constant for the configuration option 'log.type'
		 *
		 * @var string
		 */
		const OPTION_LOG_TYPE = "log.type";
		
		/**
		 * OPTION_LOG_PATH
		 *
		 * Convenience constant for the configuration option 'log.path'
		 *
		 * @var string
		 */
		const OPTION_LOG_PATH = "log.path";
		
		private $config;
		private $checksum;
		
		/**
		 * Get Config instance
		 * 
		 * Gets the current EWT configuration as set in the EWT configuration file. This method
		 * makes use of APC caching when available.
		 * 
		 * @return Config
		 */
		public static function getInstance()
		{
			if (function_exists("apc_exists") && function_exists("apc_fetch") && function_exists("apc_store"))
			{
				/** APC is installed...attempt to pull config from the APC cache */
				if (apc_exists(self::APC_CACHE_CONFIG_KEY))
				{
					$config = apc_fetch(self::APC_CACHE_CONFIG_KEY);
		
					if ($config->getChecksum() == sha1_file(self::configFilePath()))
						return $config;
				}
				
				$config = new Config();
				apc_store(self::APC_CACHE_CONFIG_KEY, $config);
				return $config;
			}
		
			return new Config();
		}
		
		/**
		 * Path to EWT configuration file
		 * 
		 * Gets the path to the EWT configuration file
		 * 
		 * @return string Path to the EWT configuration file
		 */
		public static function configFilePath()
		{		
			return implode(DIRECTORY_SEPARATOR, array(__DIR__, "..", "..", self::CONFIG_FILE));
		}
		
		/**
		 * Get the default EWT configuration
		 * 
		 * Gets the default EWT configuration
		 * 
		 * @return array Default configuration settings
		 */
		private static function defaultConfig()
		{
			return array
			(
				/** database options */
				self::OPTION_DB_TYPE => "",
				self::OPTION_DB_HOST => "",
				self::OPTION_DB_PORT => "",
				self::OPTION_DB_DATABASE => "",
				self::OPTION_DB_USERNAME => "",
				self::OPTION_DB_PASSWORD => "",
				self::OPTION_DB_DEBUG => "false",
				
				/** website options */
				self::OPTION_WEBSITE_TEMPLATE => "default.php",
				
				/** logging options */
				self::OPTION_LOG_ENABLED => "true",
				self::OPTION_LOG_TYPE => "rotating",
				self::OPTION_LOG_PATH => "logs"
			);
		}
		
		/**
		 * Reads the EWT configuration file
		 * 
		 * Reads the EWT configuration file.  For any EWT configuration options that
		 * are omitted, the default values are assumed.
		 * 
		 * @return array The EWT configuration
		 * 
		 * @throws Exception If the EWT configuration file doesn't exist or is not readable
		 */
		private static function readConfigFile()
		{
			$configFile = self::configFilePath();
			
			if (!is_readable($configFile))
				throw new Exception("The EWT config file ($configFile) does not exist or is not readable");
			
			$config = self::defaultConfig();
			
			foreach (file($configFile) as $line)
			{
				$line = trim($line);
				
				if (empty($line) || preg_match("/^(#|;)/", $line))
					/** line is empty or commented out...skip */
					continue;
				
				@list($key, $value) = explode("=", $line, 2);
				$config[trim($key)] = trim($value);
			}
			
			return $config;
		}
		
		/**
		 * Constructor
		 * 
		 * Constructs a Config object based on the options specified
		 * in the EWT configuratino file.  A checksum of the configuration
		 * file is stored for caching purposes.
		 */
		private function __construct()
		{
			$this->config = self::readConfigFile();
			$this->checksum = sha1_file(self::configFilePath());
		}
		
		/**
		 * Get the current EWT configuration
		 * 
		 * Gets the current EWT configuration, as specified in the EWT configufation file
		 * 
		 * @return array An associative array containing the current EWT configuration
		 */
		public function getConfig()
		{
			return $this->config;
		}
		
		/**
		 * Get a configuration setting
		 * 
		 * Gets the specified configuration setting
		 * 
		 * @param string $key The configuration setting to get
		 * @return string A configuration setting, or null if the specified setting does not exist
		 */
		public function getSetting($key)
		{
			return isset($this->config[$key]) ? $this->config[$key] : null;
		}
		
		/**
		 * Get configuration file checksum
		 * 
		 * Gets the checksum of the EWT configuration file at the time the Config object was created.  This
		 * is used when determining if a cached Config object is obsolete.
		 * 
		 * @return string Checksum of the EWT configuration file
		 */
		public function getChecksum()
		{
			return $this->checksum;
		}
	}
?>