<?php
/**
 Copyright (c) 2014 Patrick Griffin

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
	
	class Config
	{
		/**
		 * CONFIG_FILE
		 * 
		 * Name of the EWT configuration file
		 * 
		 * @var string
		 */
		const CONFIG_FILE = "ewt.ini";
		
		/**
		 * APC_CACHE_CONFIG_KEY
		 * 
		 * Key used to cache the Config object with APC
		 * 
		 * @var string
		 */
		const APC_CACHE_CONFIG_KEY = "EWT_CONFIG";
		
		/** config file sections */
		
		/**
		 * SECTION_DATABASE
		 * 
		 * The database configuration section
		 * 
		 * @var string
		 */
		const SECTION_DATABASE = "database";
		
		/**
		 * SECTION_WEBSITE
		 * 
		 * The website configuration section
		 * 
		 * @var string
		 */
		const SECTION_WEBSITE = "website";
		
		/**
		 * SECTION_LOGGING
		 * 
		 * The logging configuration section
		 * 
		 * @var string
		 */
		const SECTION_LOGGING = "logging";
		
		/**
		 * SECTION_CUSTOM
		 * 
		 * The custom configuration section
		 * 
		 * @var string
		 */
		const SECTION_CUSTOM = "custom";
		
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
		 * OPTION_DB_PERSISTENT_CONNECTIONS
		 * 
		 * Convenience constant for the configuration option 'db.persistent_connections'
		 * 
		 * @var string
		 */
		const OPTION_DB_PERSISTENT_CONNECTIONS = "db.persistent_connections";
		
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
		 * OPTION_WEBSITE_DEFAULT_PAGE
		 * 
		 * Convenience constant for the configuration option 'website.default_page'
		 * 
		 * @var string
		 */
		const OPTION_WEBSITE_DEFAULT_PAGE = "website.default_page";
		
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
		
		protected $config;
		protected $checksum;
		
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
					
					if (!$config->hasConfigChanged())
						return $config;
				}
				
				$config = new static();
				apc_store(self::APC_CACHE_CONFIG_KEY, $config);
				return $config;
			}
		
			return new static();
		}
		
		public static function configDir()
		{
			return implode(DIRECTORY_SEPARATOR, array(__DIR__, "..", ".."));
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
			return static::configDir() . DIRECTORY_SEPARATOR . static::CONFIG_FILE;
		}
		
		/**
		 * Get the default EWT configuration
		 * 
		 * Gets the default EWT configuration
		 * 
		 * @return array Default configuration settings
		 */
		protected static function defaultConfig()
		{
			return array
			(
				self::SECTION_DATABASE => array
				(
					self::OPTION_DB_TYPE => "",
					self::OPTION_DB_PERSISTENT_CONNECTIONS => false,
					self::OPTION_DB_HOST => "",
					self::OPTION_DB_PORT => "",
					self::OPTION_DB_DATABASE => "",
					self::OPTION_DB_USERNAME => "",
					self::OPTION_DB_PASSWORD => "",
					self::OPTION_DB_DEBUG => false
				),
					
				self::SECTION_WEBSITE => array
				(
					self::OPTION_WEBSITE_TEMPLATE => "default.php",
					self::OPTION_WEBSITE_DEFAULT_PAGE => "Default"
				),
					
				self::SECTION_LOGGING => array
				(
					self::OPTION_LOG_ENABLED => true,
					self::OPTION_LOG_TYPE => "rotating",
					self::OPTION_LOG_PATH => "logs"
				)
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
		protected static function parseConfig()
		{
			$configFile = static::configFilePath();
						
			if (($userConfig = parse_ini_file($configFile, true)) === false)
				throw new Exception("Unable to parse EWT configuration file: " . $configFile);
			
			return array_replace_recursive(static::defaultConfig(), $userConfig);
		}
		
		protected static function configKeyToSection($key)
		{
			$keySegemnts = explode(".", $key);
			
			if (count($keySegemnts) < 2)
				return self::SECTION_CUSTOM;
			
			$prefix = reset($keySegemnts);
			
			switch ($prefix)
			{
				case "db":
					return self::SECTION_DATABASE;
					
				case "website":
					return self::SECTION_WEBSITE;
					
				case "log":
					return self::SECTION_LOGGING;
			}
			
			return self::SECTION_CUSTOM;
		}
		
		/**
		 * Constructor
		 * 
		 * Constructs a Config object based on the options specified
		 * in the EWT configuratino file.  A checksum of the configuration
		 * file is stored for caching purposes.
		 */
		protected function __construct()
		{
			$this->config = static::parseConfig();
			$this->checksum = sha1_file(static::configFilePath());
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
		 * @param string $section Optional. Use the specified $section instead of determining it automatically
		 * @return string A configuration setting, or null if the specified setting does not exist
		 */
		public function getSetting($key, $section = null)
		{
			$section = empty($section)
							? self::configKeyToSection($key)
							: $section;
			
			return isset($this->config[$section][$key])
						? $this->config[$section][$key]
						: null;
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
		
		public function hasConfigChanged()
		{
			return $this->checksum != sha1_file(static::configFilePath());
		}
	}
?>