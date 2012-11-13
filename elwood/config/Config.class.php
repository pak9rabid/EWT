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
	
	final class Config
	{
		const CONFIG_FILE = "ewt.cfg";
		const APC_CACHE_CONFIG_KEY = "EWT_CONFIG";
		
		/** config file options */
		const OPTION_DB_TYPE = "db.type";
		const OPTION_DB_HOST = "db.host";
		const OPTION_DB_PORT = "db.port";
		const OPTION_DB_DATABASE = "db.database";
		const OPTION_DB_USERNAME = "db.username";
		const OPTION_DB_PASSWORD = "db.password";
		const OPTION_DB_DEBUG = "db.debug";
		const OPTION_LOG_ENABLED = "log.enabled";
		const OPTION_LOG_TYPE = "log.type";
		const OPTION_LOG_PATH = "log.path";
		
		private $config;
		private $checksum;
		
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
		
		public static function configFilePath()
		{		
			return implode(DIRECTORY_SEPARATOR, array(__DIR__, "..", "..", self::CONFIG_FILE));
		}
		
		private static function defaultConfig()
		{
			return array
			(
				/** database options */
				"db.type" => "",
				"db.host" => "localhost",
				"db.port" => "",
				"db.database" => "",
				"db.username" => "",
				"db.password" => "",
				"db.debug" => "false",
				
				/** logging options */
				"log.enabled" => "true",
				"log.type" => "rotating",
				"log.path" => "logs"
			);
		}
		
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
				
		private function __construct()
		{
			$this->config = self::readConfigFile();
			$this->checksum = sha1_file(self::configFilePath());
		}
		
		public function getConfig()
		{
			return $this->config;
		}
		
		public function getSetting($key)
		{
			return isset($this->config[$key]) ? $this->config[$key] : null;
		}
		
		public function getChecksum()
		{
			return $this->checksum;
		}
		
		public function __clone()
		{
			throw new Exception("This object does not support cloning");
		}
	}
?>