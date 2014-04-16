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

	namespace elwood\database;
	
	use Exception;
	use PDO;
	use elwood\config\Config;
	use elwood\log\Log;
	
	/**
	 * A connection to a database
	 * 
	 * This is an abstract class that provides common functionality for all of
	 * the supported database systems.  All supported database systems must subclass
	 * this class
	 * 
	 * @author Patrick Griffin <pak9rabid@yahoo.com>
	 */
	
	abstract class Database
	{
		/**
		 * SUPPORTED_DATABASE_TYPES
		 * 
		 * Contains a comma-separeted list of supported database systems
		 * 
		 * @var string
		 */
		const SUPPORTED_DATABASE_TYPES = "mysql,pgsql,sqlite";
		
		/**
		 * MAX_IDENTIFIER_LENGTH
		 * 
		 * Contains the maximum allowed character length that a database
		 * system can use for various identifiers (table names, indexes, constraints, etc).
		 * 
		 * @var int
		 */
		const MAX_IDENTIFIER_LENGTH = 128;
		
		protected $pdo;
		protected $dsn;
		protected $config;
		
		/**
		 * Constructor
		 * 
		 * This is generally called by extending classes to perform initialization common to
		 * all Database-derived classes
		 * 
		 * @param Config $config EWT configuration
		 */
		protected function __construct(Config $config)
		{
			$this->config = $config;
		}
		
		/**
		 * Gets a database connection
		 * 
		 * This gets an instance of a database connection of the database specified in the EWT
		 * configuration file
		 * 
		 * @return Database A connection to a specific database type
		 * @throws Exception If an unsupported database type is specified in the EWT configuration file
		 */
		public static function getInstance()
		{
			$config = Config::getInstance();
		
			switch ($config->getSetting(Config::OPTION_DB_TYPE))
			{
				case "mysql":
					$db = new MysqlDatabase($config);
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
		
		/**
		 * Gets a list of supported database types
		 * 
		 * @return array A list of supported database types
		 */
		public static function getSupportedDatabaseTypes()
		{
			return explode(",", self::SUPPORTED_DATABASE_TYPES);
		}
		
		/**
		 * Verifies a port number
		 * 
		 * Verifies if $port is a valid TCP/UDP port number
		 * 
		 * @param int $port The port number to verify
		 * @return boolean true if $port is a valid port number, false otherwise
		 */
		public static function isValidIanaPortNumber($port)
		{
			if (!preg_match("/^[0-9]{1,5}$/", $port))
				return false;
		
			if ($port < 1 || $port > 65535)
				return false;
		
			return true;
		}
		
		/**
		 * Verifies a database identifier is valid
		 * 
		 * Verifies if $identifier is a valid database identifier.  Database identifiers are names
		 * for database objects (tables, columns, indexes, constraints, etc).
		 * 
		 * @param string $identifier Identifier to validate
		 * @return boolean true if $identifier is valid, false otherwise
		 */
		public static function isValidIdentifier($identifier)
		{
			// database identifiers are names given to various named objects (tables, columns, constraints, indexes, etc)
			if (strlen($identifier) > self::MAX_IDENTIFIER_LENGTH)
				return false;
		
			if (!preg_match("/^[A-Z|_]{1}[A-Z0-9|_]*$/i", $identifier))
				return false;
		
			return true;
		}
		
		public static function hasOuterJoinIndicator($attribute)
		{
			return $attribute != "" && substr($attribute, -strlen(DataModel::OUTER_JOIN_INDICATOR)) == DataModel::OUTER_JOIN_INDICATOR;
		}
		
		/**
		 * Generates a prepared WHERE clause
		 * 
		 * Given the specified $dm, this method will generate a prepared SQL WHERE clause
		 * based on its set attributes and optionally append it to $prep (if specified).
		 * 
		 * @param DataModel $dm DataModel object to generate SQL WHERE clause from
		 * @param DbQueryPreper $prep Optional.  If specified, it will append the generated WHERE clause to it
		 * @param string $table Optional.  If specified, the generated WHERE clause will be limited to the specified table's attributes
		 * @return string SQL containing the prepared WHERE clause
		 */
		public static function dataModelToParamaterizedWhereClause(DataModel $dm, DbQueryPreper $prep = null, $table = "")
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
					$likeConditions[] = !$dm->isCaseSensitive() ? "UPPER($attribute) LIKE UPPER(?)" : "$attribute LIKE ?";
						
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
						
			$conditions = array_merge($conditions, $likeConditions);
			
			if (empty($conditions))
				return "";
			
			$sql = "WHERE " . implode(" AND ", $conditions);
			
			if ($prep != null)
			{
				$prep->addSql($sql);
				$prep->addVariablesNoPlaceholder(array_merge($conditionValues, $likeConditionValues));
			}
			
			return $sql;
		}
		
		public static function dataModelToJoinClause(DataModel $dm)
		{
			$tableRelationships = $dm->getTableRelationships();
			
			if (empty($tableRelationships))
				return implode(",", $dm->getTables());
			
			$includeLeftAndRightTables = true;
			
			return implode(" ", array_map(function($tableRelationship) use (&$includeLeftAndRightTables)
			{
				list($left, $right) = explode("=", $tableRelationship);
				
				$left = trim($left);
				$right = trim($right);
				
				list($leftTable, $leftAttr) = explode(".", $left);
				list($rightTable, $rightAttr) = explode(".", $right);
				
				$joinType = !Database::hasOuterJoinIndicator($leftAttr) && !Database::hasOuterJoinIndicator($rightAttr)
								? "INNER JOIN"
								: (Database::hasOuterJoinIndicator($leftAttr) && Database::hasOuterJoinIndicator($rightAttr)
									? "FULL OUTER JOIN"
									: (Database::hasOuterJoinIndicator($leftAttr)
										? "LEFT OUTER JOIN"
										: "RIGHT OUTER JOIN"));
				
				$joinStatement = array();
				
				if ($includeLeftAndRightTables)
				{
					$joinStatement[] = $leftTable;
					$includeLeftAndRightTables = false;
				}
				
				$regex = "/\\" . DataModel::OUTER_JOIN_INDICATOR . "$/";
				return implode(" ", array_merge($joinStatement, array($joinType, $rightTable, "ON", preg_replace($regex, "", $left), "=", preg_replace($regex, "", $right))));
				
			}, $tableRelationships));
		}
		
		/**
		 * Execute a prepared SQL query
		 * 
		 * Executes $prep as a prepared SQL statement, binding any set variables
		 * during query execution
		 * 
		 * @param DbQueryPreper $prep The prepared SQL query
		 * @param boolean $getNumRowsAffected If set to true, will return a count of the rows affected by the executed query
		 * @throws SQLException If execution of the query fails
		 * @return int|array A count of affected rows if $getNumRowsAffected is true, otherwise an array of DataModel objects representing the result set of the query
		 */
		public function executeQuery(DbQueryPreper $prep, $getNumRowsAffected = false)
		{
			if ($this->config->getSetting(Config::OPTION_DB_DEBUG))
				Log::writeInfo("Query: " . $prep->getQueryDebug());
				
			$results = array();
			
			try
			{
				$stmt = $this->pdo->prepare($prep->getQuery());
				
				foreach ($prep->getBindVars() as $key => $bindParam)
					$stmt->bindParam($key + 1, $bindParam->value, $bindParam->type);
				
				$stmt->execute();
				
				if ($getNumRowsAffected)
					return $stmt->rowCount();
				
				return array_map(function($row) use ($prep)
				{
					$normalizedRow = array();
					
					array_walk($row, function($value, $attribute) use (&$normalizedRow, $prep)
					{
						$parts = explode(".", $attribute);
						
						if (count($parts) < 2)
						{
							// no table name...see if we can figure out the
							// table name from the query							
							$matches = array();
							
							if
							(
								preg_match("/^SELECT\b.*\bFROM\b(.*)\bWHERE\b/i", $prep->getQuery(), $matches) &&
								count($results = (preg_split("/[^A-Z0-9_]/i", trim($matches[1])))) == 1
							)
								$attribute = $results[0] . "." . $attribute;
							else
								$attribute = "unknown_table." . $attribute;
						}
						
						$normalizedRow[] = $attribute . " = " . $value;
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
				$sqlEx = new SQLException("Error executing SQL Query", $prep->getQueryDebug(), $errorCode, $errorMessage);
				Log::writeError($sqlEx->getMessage() . ": " . $sqlEx->getQuery() . ": " . $sqlEx->getErrorMessage());
				throw $sqlEx;
			}
		}
		
		/**
		 * Executes a SELECT database query
		 * 
		 * Generates and executes a SELECT database query based on properties set in $dm
		 * 
		 * @param DataModel $dm The DataModel object to build the query from
		 * @param string $query If not null, the generated query will be placed here
		 * @throws Exception If $dm has no tables set
		 * @return array An array of DataModel objects representing the result set of the query
		 */
		public function executeSelect(DataModel $dm, &$query = null)
		{
			$tables = $dm->getTables();
				
			if (empty($tables))
				throw new Exception("No table(s) specified");
				
			$selects = $dm->getSelects();
						
			$prep = new DbQueryPreper("SELECT " . (empty($selects) ? "*" : implode(", ", array_map(function($select)
			{
				return $select . " AS \"" . $select . "\"";
			}, $selects))) . " FROM " . static::dataModelToJoinClause($dm) . " ");
			
			static::dataModelToParamaterizedWhereClause($dm, $prep);
			$order = $dm->getOrder();
				
			if (!empty($order))
			{
				$prep->addSql(" ORDER BY " . implode(", ", array_map(function($attribute, $direction)
				{
					return $attribute . " " . $direction;
				}, array_keys($order), $order)));
			}
				
			if ($dm->getLimit() != 0)
				$prep->addSql(" LIMIT ")->addVariable($dm->getLimit());
				
			if ($dm->getOffset() != 0)
				$prep->addSql(" OFFSET ")->addVariable($dm->getOffset());
				
			if (isset($query))
				$query = $prep->getQueryDebug();
				
			return $this->executeQuery($prep);
		}
		
		/**
		 * Execute an INSERT database query
		 * 
		 * Generates and executes an INSERT database query based on the attributes set in $dm
		 * 
		 * @param DataModel $dm The DataModel object to build the query from
		 * @param string $query If not null, the generated query will be placed here
		 * @throws Exception If an error is encountered when executing the query
		 * @return array An empty array
		 */
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
			
			return array();
		}
		
		/**
		 * Execute an UPDATE database query
		 * 
		 * Generates and exectues an UPDATE database query based on the attributes set in $dm
		 * 
		 * @param DataModel $dm The DataModel object to build the query from
		 * @param string $query If not null, the generated query will be placed here
		 * @throws Exception If an error is encountered when executing the query
		 * @return @return array An empty array
		 */
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
						}, array_keys($updates), $updates)) . " ");
						
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
			
			return array();
		}
		
		/**
		 * Execute a DELETE database query
		 *
		 * Generates and exectues a DELETE database query based on the attributes set in $dm
		 *
		 * @param DataModel $dm The DataModel object to build the query from
		 * @param string $query If not null, the generated query will be placed here
		 * @throws Exception If an error is encountered when executing the query
		 * @return array An empty array
		 */
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
					$prep = new DbQueryPreper("DELETE FROM " . $table . " ");
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
			
			return array();
		}
		
		/**
		 * Get PDO object
		 * 
		 * Gets the PDO object associated with this database connection
		 * 
		 * @return PDO The PDO object associated with this database connection
		 */
		public function getPdo()
		{
			return $this->pdo;
		}
		
		/**
		 * Get the Data Source Name (DSN)
		 * 
		 * Gets the DSN string associated with this database connection
		 * 
		 * @return string The DSN associated with this database connection
		 */
		public function getDsn()
		{
			return $this->dsn;
		}
	}
?>
