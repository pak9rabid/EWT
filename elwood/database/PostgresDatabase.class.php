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

	namespace elwood\database;
	use PDO;
	use elwood\config\Config;
	
	class PostgresDatabase extends Database
	{
		const DEFAULT_PORT = 5432;
		
		public function __construct(Config $config)
		{
			parent::__construct($config);
			
			$dbSettings = array
			(
				"host" => $config->getSetting(Config::OPTION_DB_HOST),
				"port" => $config->getSetting(Config::OPTION_DB_PORT),
				"dbname" => $config->getSetting(Config::OPTION_DB_DATABASE),
				"user" => $config->getSetting(Config::OPTION_DB_USERNAME),
				"password" => $config->getSetting(Config::OPTION_DB_PASSWORD)
			);
			
			if (empty($dbSettings["host"]))
				$dbSettings["port"] = "";
			
			if (!empty($dbSettings["host"]) && empty($dbSettings["port"]))
				$dbSettings["port"] = self::DEFAULT_PORT;
			
			$this->dsn = "pgsql:" . http_build_query(array_filter($dbSettings), "", ";");
			$this->pdo = new PDO($this->dsn);
		}
	}
?>