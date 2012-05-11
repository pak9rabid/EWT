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
	
	abstract class Database
	{		
		const SUPPORTED_DATABASE_TYPES = "mysql,oci,pgsql,sqlite";
		const CONNECTION_CONFIG_FILE = "db.cfg";
		
		protected $pdo;
		protected $dsn;
				
		abstract public function __construct(ConnectionConfig $config);
		
		public static function getInstance(ConnectionConfig $config)
		{
			switch ($config->getDatabaseType())
			{
				case "mysql":
					$db = new MysqlDatabase($config);
					break;
					
				case "oci":
					$db = new OracleDatabase($config);
					break;
					
				case "pgsql":
					$db = new PostgresDatabase($config);
					break;
					
				case "sqlite":
					$db = new SqliteDatabase($config);
					break;
					
				default:
					throw new Exception("Unsupported database type specified");
			}
			
			$db->getPdo()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			return $db;
		}
		
		public static function getConnectionConfig()
		{
			$configFile = __DIR__ . DIRECTORY_SEPARATOR . self::CONNECTION_CONFIG_FILE;
			
			if (!is_readable($configFile))
				throw new Exception("Database config file ($configFile) doesn't exist or is not readable");
			
			// init config values
			$config = array	(
								"databaseType" => "",
								"host" => "",
								"port" => "",
								"database" => "",
								"username" => "",
								"password" => ""
							);
			
			foreach (file($configFile) as $line)
			{
				if (preg_match("/^(#|;)/", trim($line)))
					// line is commented out...skip
					continue;
					
				@list($key, $value) = explode("=", $line);
				$config[trim($key)] = trim($value);
			}
			
			return new ConnectionConfig($config["databaseType"], $config["host"], $config["port"], $config["database"], $config["username"], $config["password"]);
		}
		
		public static function getSupportedDatabaseTypes()
		{
			return explode(",", self::SUPPORTED_DATABASE_TYPES);
		}
		
		public static function isValidIanaPortNumber($port)
		{
			if (!preg_match("/^[0-9]{1,5}$/", $port))
				return false;
		
			if ($port < 1 || $port > 65535)
				return false;
		
			return true;
		}
		
		public function executeQuery(DbQueryPreper $prep)
		{
			$results = array();
			
			try
			{
				$stmt = $this->pdo->prepare($prep->getQuery());
				$stmt->execute($prep->getBindVars());
				
				foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
				{
					$resultHash = new DataHash();
					$resultHash->setAllAttributes($row);
					$results[] = $resultHash;
				}
			}
			catch (Exception $ex)
			{
				$errorInfo = $this->pdo->errorInfo();
				$errorCode = $errorInfo[0];
				$errorMessage = $errorInfo[2];
				
				throw new SQLException("Error executing SQL query", $prep->getQueryDebug(), $errorCode, $errorMessage);
			}
			
			return $results;
		}
		
		public function executeSelect(DataHash $data, $filterNullValues = false)
		{
			$classType = get_class($data);
			
			// Select rows from the database
			$prep = new DbQueryPreper("SELECT * FROM " . $data->getTable());
			
			if (count($data->getAttributeKeys()) > 0)
			{
				$prep->addSql(" WHERE ");
				$prep->addSql(implode(" AND ", array_map(array("self", "datahashToParamaterizedWhereClause"), $data->getAttributeKeys())));
				$prep->addVariablesNoPlaceholder($data->getAttributeValues());
			}
			
			$orderBy = $data->getOrderBy();
			
			if (!empty($orderBy))
				$prep->addSql(" ORDER BY " . implode(", ", $orderBy));
			
			return $this->executeQuery($prep);
		}
		
		public static function datahashToParamaterizedWhereClause($key)
		{
			return " $key = ? ";
		}

		public function executeInsert(DataHash $data)
		{
			// Insert new row into the database
			$prep = new DbQueryPreper("INSERT INTO " . $data->getTable() . " (");
			$prep->addSql(implode(",", $data->getAttributeKeys()) . ") VALUES (");
			$prep->addVariables($data->getAttributeValues());
			$prep->addSql(")");			
			$this->executeQuery($prep);
		}
		
		public function executeInserts(array $data)
		{
			$this->pdo->beginTransaction();
						
			try
			{
				foreach ($data as $row)
				{
					if (!($row instanceof DataHash))
						throw new Exception("Invalid type: must be of type DataHash");
						
					$this->executeInsert($row);
				}
			}
			catch (Exception $ex)
			{
				$this->pdo->rollBack();
				throw $ex;
			}
			
			$this->pdo->commit();
		}

		public function executeUpdate(DataHash $data)
		{
			// Update row in the database
			$primaryKey = $data->getPrimaryKey();
			$primaryKeyValue = $data->getAttribute($primaryKey);

			if (empty($primaryKey) || empty($primaryKeyValue))
				throw new Exception("Primary key not specified and/or set");

			$prep = new DbQueryPreper("UPDATE " . $data->getTable() . " SET");
			
			foreach ($data->getAttributeMap() as $key => $value)
			{
				if ($key != $primaryKey)
				{
					if (count($prep->getBindVars()) > 0)
						$prep->addSql(",");
					
					$prep->addSql(" $key = ");
					$prep->addVariable($value);
				}
			}
			
			$prep->addSql(" WHERE $primaryKey = ");
			$prep->addVariable($primaryKeyValue);			
			$this->executeQuery($prep);
		}

		public function executeDelete(DataHash $data, $isTemp = false)
		{
			// Deletes rows from the database based on the criteria specified in $data
			$prep = new DbQueryPreper("DELETE FROM " . $data->getTable());
			
			if (count($data->getAttributeKeys()) > 0)
			{
				$prep->addSql(" WHERE ");
				$prep->addSql(implode(" AND ", array_map(array("self", "datahashToParamaterizedWhereClause"), $data->getAttributeKeys())));
				$prep->addVariablesNoPlaceHolder($data->getAttributeValues());
			}
						
			$this->executeQuery($prep);
		}
		
		public function getPdo()
		{
			return $this->pdo;
		}
		
		public function getDsn()
		{
			return $this->dsn;
		}
	}
?>
