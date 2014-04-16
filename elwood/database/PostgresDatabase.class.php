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
	
	/**
	 * A connection to a PostgreSQL database
	 *
	 * The implementing class for accessing a PostgreSQL database.
	 *
	 * @author pgriffin
	 */
	class PostgresDatabase extends Database
	{
		/**
		 * Default PostgreSQL TCP port
		 *
		 * The default TCP port for PostgreSQL network connections.
		 *
		 * @var int
		 */
		const DEFAULT_PORT = 5432;
		
		/**
		 * Constructor
		 *
		 * Creates a connection to a PostgreSQL database.
		 *
		 * @param Config $config An EWT configuration object.
		 */
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
		
		/**
		 * Execute an INSERT database query
		 * 
		 * Generates and executes an INSERT database query based on the attributes set in $dm
		 * 
		 * @param DataModel $dm The DataModel object to build the query from
		 * @param string $query If not null, the generated query will be placed here
		 * @throws Exception If an error is encountered when executing the query
		 * @return array An array of DataModel objects if any insert returns
		 * are specified, otherwise an empty array
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
			$results = array();
		
			try
			{
				@array_walk($dm->getTables(), function($table, $key, DataModel $dm) use ($db, $query, &$queries, &$results)
				{
					$prep = new DbQueryPreper("INSERT INTO " . $table . " (");
					$prep->addSql(implode(",", $dm->getAttributeKeys($table, true)) . ") VALUES (");
					$prep->addVariables(array_values($dm->getAttributes($table)));
					$prep->addSql(")");
					
					if (count($dm->getInsertReturns()) > 0)
						$prep->addSql(" RETURNING " . implode(",", $dm->getInsertReturns()));
		
					if (isset($query))
					{
						$arr = explode("\n", $query);
						$query = implode("\n", explode("\n", $query));
					}
		
					if (isset($query))
						$queries[] = $prep->getQueryDebug();
		
					$results = array_merge($results, $db->executeQuery($prep));
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
			
			return $results;
		}
		
		/**
		 * Execute an UPDATE database query
		 *
		 * Generates and exectues an UPDATE database query based on the attributes set in $dm
		 *
		 * @param DataModel $dm The DataModel object to build the query from
		 * @param string $query If not null, the generated query will be placed here
		 * @throws Exception If an error is encountered when executing the query
		 * @return array An array of DataModel objects if any insert returns
		 * are specified, otherwise an empty array
		 */
		public function executeUpdate(DataModel $dm, &$query = null)
		{
			if (!$alreadyInTransaction = $this->pdo->inTransaction())
				$this->pdo->beginTransaction();
				
			$db = $this;
			$queries = array();
			$results = array();
				
			try
			{
				@array_walk($dm->getTables(), function($table, $key, DataModel $dm) use ($db, $query, &$queries, &$results)
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
			
						if (count($dm->getInsertReturns()) > 0)
							$prep->addSql(" RETURNING " . implode(",", $dm->getInsertReturns()));
						
						if (isset($query))
							$queries[] = $prep->getQueryDebug();
			
						$results = array_merge($results, $db->executeQuery($prep));
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
				
			return $results;
		}
		
		/**
		 * Execute a DELETE database query
		 *
		 * Generates and exectues a DELETE database query based on the attributes set in $dm
		 *
		 * @param DataModel $dm The DataModel object to build the query from
		 * @param string $query If not null, the generated query will be placed here
		 * @throws Exception If an error is encountered when executing the query
		 * @return array An array of DataModel objects if any insert returns
		 * are specified, otherwise an empty array
		 */
		public function executeDelete(&$query = null)
		{
			if (!$alreadyInTransaction = $this->pdo->inTransaction())
				$this->pdo->beginTransaction();
			
			$db = $this;
			$queries = array();
			$results = array();
				
			try
			{
				@array_walk($dm->getTables(), function($table, $key, DataModel $dm) use ($db, $query, &$queries, &$results)
				{
					$prep = new DbQueryPreper("DELETE FROM " . $table . " ");
					$db->dataModelToParamaterizedWhereClause($dm, $prep, $table);
					
					if (count($dm->getInsertReturns()) > 0)
						$prep->addSql(" RETURNING " . implode(",", $dm->getInsertReturns()));
					
					if (isset($query))
						$queries[] = $prep->getQueryDebug();
						
					$results = array_merge($results, $db->executeQuery($prep));
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
				
			return $results;
		}
	}
?>