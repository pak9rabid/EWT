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
		const SUPPORTED_DATABASE_TYPES = "mysql,pgsql,sqlite";
		const CONNECTION_CONFIG_FILE = "db.cfg";
		const MAX_IDENTIFIER_LENGTH = 128;
		
		protected $pdo;
		protected $dsn;
		protected $config;
		
		protected function __construct(ConnectionConfig $config)
		{
			$this->config = $config;
		}
		
		public static function getInstance(ConnectionConfig $config = null)
		{
			if (empty($config))
				$config = self::getConnectionConfig();
			
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
								"password" => "",
								"debug" => ""
							);
			
			foreach (file($configFile) as $line)
			{
				if (preg_match("/^(#|;)/", trim($line)))
					// line is commented out...skip
					continue;
					
				@list($key, $value) = explode("=", $line, 2);
				$config[trim($key)] = trim($value);
			}
			
			return new ConnectionConfig($config["databaseType"], $config["host"], $config["port"], $config["database"], $config["username"], $config["password"], $config["debug"]);
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
		
		public static function isValidIdentifier($identifier)
		{
			// database identifiers are names given to various named objects (tables, columns, constraints, indexes, etc)
			if (strlen($identifier) > self::MAX_IDENTIFIER_LENGTH)
				return false;
		
			if (!preg_match("/^[A-Za-z]{1}[A-Za-z0-9|_]*$/", $identifier))
				return false;
		
			return true;
		}
		
		public function dataModelToParamaterizedWhereClause(DataModel $dm, DbQueryPreper $prep = null, $table = "")
		{
			$conditions = array();
			$likeConditions = array();
			$conditionValues = array();
			$likeConditionValues = array();
			
			foreach ($dm->getAttributes($table) as $attribute => $value)
			{
				$comparator = $dm->getComparator($attribute);
			
				if (in_array($comparator, array("=", "!=", ">", "<", ">=", "<=")))
				{
					$conditions[] = "$attribute $comparator ?";
					$conditionValues[] = $value;
				}
				else
				{
					$likeConditions[] = "$attribute LIKE ?";
			
					switch ($comparator)
					{
						case "*?*":
							$likeConditionValues[] = "%" . $value . "%";
							break;
			
						case "*?":
							$likeConditionValues[] = "%" . $value;
							break;
			
						case "?*":
							$likeConditionValues[] = $value . "%";
					}
				}
			}
			
			$conditions = array_merge(empty($table) ? $dm->getTableRelationships() : array(), $conditions, $likeConditions);
			
			if (empty($conditions))
				return "";
			
			$sql = " WHERE " . implode(" AND ", $conditions);
			
			if ($prep != null)
			{
				$prep->addSql($sql);
				$prep->addVariablesNoPlaceholder(array_merge($conditionValues, $likeConditionValues));
			}
			
			return $sql;
		}
		
		public function executeQuery(DbQueryPreper $prep, $getNumRowsAffected = false)
		{
			if ($this->config->isDebugMode())
				echo "Query: " . $prep->getQueryDebug() . "\n";
				
			$results = array();
			
			try
			{				
				$stmt = $this->pdo->prepare($prep->getQuery());
				$stmt->execute($prep->getBindVars());
				
				if ($getNumRowsAffected)
					return $stmt->rowCount();
				
				return array_map(function($row)
				{
					$normalizedRow = array();
					
					array_walk($row, function($value, $attribute) use (&$normalizedRow)
					{
						$parts = explode(".", $attribute);
						
						if (count($parts) < 2)
							$attribute = "unknown_table." . $attribute;
						
						$normalizedRow[$attribute] = $value;
					});
					
					$dm = new DataModel();
					$dm->setAttributes($normalizedRow);
					return $dm;
					
				}, $stmt->fetchAll(PDO::FETCH_ASSOC));				
			}
			catch (Exception $ex)
			{
				$errorInfo = $this->pdo->errorInfo();
				$errorCode = $errorInfo[0];
				$errorMessage = $errorInfo[2];
				
				throw new SQLException("Error executing SQL Query", $prep->getQueryDebug(), $errorCode, $errorMessage);
			}
		}
		
		public function executeSelect(DataModel $dm, &$query = null)
		{
			$tables = $dm->getTables();
			
			if (empty($tables))
				throw new Exception("No table(s) specified");
			
			$selects = $dm->getSelects();
			
			$prep = new DbQueryPreper("SELECT " . (empty($selects) ? "*" : implode(", ", array_map(function($select)
			{
				return $select . " AS \"" . $select . "\"";
			}, $selects))) . " FROM " . implode(", ", $tables));
			
			$this->dataModelToParamaterizedWhereClause($dm, $prep);
			$order = $dm->getOrder();
			
			if (!empty($order))
			{
				$prep->addSql(" ORDER BY " . implode(", ", array_map(function($attribute, $direction)
				{
					return $attribute . " " . $direction;
				}, array_keys($order), $order)));
			}
			
			if (isset($query))
				$query = $prep->getQueryDebug();
			
			return $this->executeQuery($prep);
		}
		
		public function executeInsert(DataModel $dm, &$query = null)
		{
			/** iterates through all tables specified in $dm and inserts all set attributes
			 *	into the database, as a single transaction (if not already participating in one).
			 */
			
			if (!$alreadyInTransaction = $this->pdo->inTransaction())
				$this->pdo->beginTransaction();
			
			$db = $this;
			$queries = array();
			
			try
			{
				@array_walk($dm->getTables(), function($table, $key, DataModel $dm) use ($db, $query, &$queries)
				{
					$prep = new DbQueryPreper("INSERT INTO " . $table . " (");
					$prep->addSql(implode(",", $dm->getAttributeKeys($table, true)) . ") VALUES (");
					$prep->addVariables(array_values($dm->getAttributes($table)));
					$prep->addSql(")");
					
					if (isset($query))
					{
						$arr = explode("\n", $query);
						$query = implode("\n", explode("\n", $query));
					}
					
					if (isset($query))
						$queries[] = $prep->getQueryDebug();
					
					$db->executeQuery($prep);
				}, $dm);
				
				if (!$alreadyInTransaction)
					$this->pdo->commit();
			}
			catch (Exception $ex)
			{
				if (!$alreadyInTransaction)
					$this->pdo->rollBack();
				
				throw $ex;
			}
			
			if (isset($query))
				$query = implode("\n", $queries);
		}
		
		public function executeUpdate(DataModel $dm, &$query = null)
		{
			/** iterates through all tables in $dm and updates
			 * 	all set updates with the criterial specified in
			 * 	the set attributes
			 */
			if (!$alreadyInTransaction = $this->pdo->inTransaction())
				$this->pdo->beginTransaction();
			
			$db = $this;
			$queries = array();
			
			try
			{
				@array_walk($dm->getTables(), function($table, $key, DataModel $dm) use ($db, $query, &$queries)
				{
					$updates = $dm->getUpdates($table, true);
					$attributes = $dm->getAttributes($table, true);
					
					if (!empty($updates))
					{
						$prep = new DbQueryPreper("UPDATE " . $table . " SET " . implode(", ", array_map(function($attribute, $value)
						{
							return "$attribute = ?";
						}, array_keys($updates), $updates)));
						
						$prep->addVariablesNoPlaceholder(array_values($dm->getUpdates($table)));
						$db->dataModelToParamaterizedWhereClause($dm, $prep, $table);
						
						if (isset($query))
							$queries[] = $prep->getQueryDebug();
						
						$db->executeQuery($prep);
					}
				}, $dm);
				
				if (!$alreadyInTransaction)
					$this->pdo->commit();
			}
			catch (Exception $ex)
			{
				if (!$alreadyInTransaction)
					$this->pdo->rollBack();
				
				throw $ex;
			}
			
			if (isset($query))
				$query = implode("\n", $queries);
		}
		
		public function executeDelete(DataModel $dm, &$query = null)
		{
			/** iterates through all tables in $dm and removes
			 * 	all set attributes from the database, as a single
			 * 	transaction (if not already participating in one)
			 */
			if (!$alreadyInTransaction = $this->pdo->inTransaction())
				$this->pdo->beginTransaction();
			
			$db = $this;
			$queries = array();
			
			try
			{
				@array_walk($dm->getTables(), function($table, $key, DataModel $dm) use ($db, $query, &$queries)
				{
					$prep = new DbQueryPreper("DELETE FROM " . $table);
					$db->dataModelToParamaterizedWhereClause($dm, $prep, $table);
					
					if (isset($query))
						$queries[] = $prep->getQueryDebug();
					
					$db->executeQuery($prep);
				}, $dm);
				
				if (!$alreadyInTransaction)
					$this->pdo->commit();
			}
			catch (Exception $ex)
			{
				if (!$alreadyInTransaction)
					$this->pdo->rollBack();
				
				throw $ex;
			}
			
			if (isset($query))
				$query = implode("\n", $queries);
		}
		
		public function getPdo()
		{
			return $this->pdo;
		}
		
		public function getDsn()
		{
			return $this->dsn;
		}
		
		public function getConfig()
		{
			return $this->config;
		}
	}
?>
