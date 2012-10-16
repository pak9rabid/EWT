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
	
	use Exception;
	use PDO;
	
	class MysqlDatabase extends Database
	{
		const DEFAULT_PORT = 3306;
		
		public function __construct(ConnectionConfig $config)
		{
			parent::__construct($config);
			$port = $config->getPort();
			$port = empty($port) ? self::DEFAULT_PORT : $port;
			
			$this->dsn = "mysql:host=" . $config->getHost() . ";port=" . $port . ";dbname=" . $config->getDatabase();
			$this->pdo = new PDO($this->dsn, $config->getUsername(), $config->getPassword(), array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
		}
		
		// Override
		public function executeQuery(DbQueryPreper $prep, $getNumRowsAffected = false)
		{
			if (preg_match("/^SELECT/i", $prep->getQuery()))
				return parent::executeQuery($prep, $getNumRowsAffected);
			
			// the MySQL PDO implementation throws a fit when $stmt->fetchAll()
			// is called on any prepared statement that isn't a select, so we'll
			// work around it
			if ($this->config->isDebugMode())
				echo "Query: " . $prep->getQueryDebug() . "\n";
			
			try
			{
				$stmt = $this->pdo->prepare($prep->getQuery());
				$stmt->execute($prep->getBindVars());
			}
			catch (Exception $ex)
			{
				$errorInfo = $this->pdo->errorInfo();
				$errorCode = $errorInfo[0];
				$errorMessage = $errorInfo[2];
				
				throw new SQLException("Error executing SQL query", $prep->getQueryDebug(), $errorCode, $errorMessage);
			}
		}
	}
?>