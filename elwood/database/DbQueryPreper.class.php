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
	
	/**
	 * A prepared statement query preper class
	 * 
	 * An instance of this class makes it possible to create and execute
	 * prepared SQL statements in an easy an straightforward way.
	 * 
	 * @author pgriffin
	 */
	class DbQueryPreper
	{
		// Attributes
		protected $query;
		protected $bindVars = array();
		protected $conn;
		
		/**
		 * Constructor
		 * 
		 * Creates a new DbQueryPreper object, optionally set with some initial
		 * SQL.
		 * 
		 * @param string $sql Optional. The initial SQL to initialize the
		 * instance with
		 */
		public function __construct($sql = "")
		{
			$this->query = $sql;
		}
		
		/**
		 * Append SQL
		 * 
		 * Appends the specified SQL to the instance.
		 * 
		 * @param string $sql The SQL to append
		 * @return DbQueryPreper $this (for method chaining)
		 */
		public function addSql($sql)
		{
			$this->query .= $sql;
			
			return $this;
		}
		
		/**
		 * Append DbQueryPreper
		 * 
		 * Appends the SQL and bind variables contained within the incoming
		 * DbQueryPreper.
		 * 
		 * @param DbQueryPreper $prep The DbQueryPreper to append to this
		 * instance
		 * @return DbQueryPreper $this (for method chaining)
		 */
		public function addPrep(DbQueryPreper $prep)
		{
			$this->query .= $prep->getQuery();
			$this->bindVars = array_merge($this->bindVars, $prep->getBindVars());
		}
		
		/**
		 * Append a bind variable to the query
		 * 
		 * Adds the specified $bindVar as a bind variable to the query,
		 * preserving the order in which it was specified.
		 * 
		 * @param mixed $bindVar The bind variable
		 * @param string $type Optional. The type of the bind variable.  If not
		 * specified the type will attempt to the determined automatically.
		 * @return DbQueryPreper $this (for method chaining)
		 */
		public function addVariable($bindVar, $type = null)
		{
			$this->query .= "?";
			$this->bindVars[] = (object) array
			(
				"value" => $bindVar === null ? "NULL" : $bindVar,
				"type" => !empty($type)
							? $type
							: ($bindVar == null
								? PDO::PARAM_NULL
								: (is_int($bindVar) || is_float($bindVar)
									? PDO::PARAM_INT
									: PDO::PARAM_STR))
							
			);
			
			return $this;
		}
		
		/**
		 * Append multiple bind variables to the query
		 * 
		 * Appends many bind variables to the query, preserving the order in
		 * which they're specified.
		 * 
		 * @param array $bindVars An array containing the bind values to append
		 * @param string $delimiter Optional. The delimiter used to join the
		 * values together
		 * @return DbQueryPreper $this (for method chaining)
		 */
		public function addVariables(array $bindVars, $delimiter = ",")
		{
			$this->query .= implode($delimiter, array_pad(array(), count($bindVars), "?"));
			$this->addVariablesNoPlaceholder($bindVars);
			return $this;
		}
		
		/**
		 * Append multiple bind variables without a placeholder
		 * 
		 * Appends many bind variables to the instance without any corresponding
		 * placeholders in the SQL.
		 * 
		 * @param array $bindVars The bind variables to append
		 * @return \elwood\database\DbQueryPreper
		 */
		public function addVariablesNoPlaceholder(array $bindVars)
		{
			foreach ($bindVars as $bindVar)
			{
				$this->bindVars[] = (object) array
				(
					"value" => $bindVar == null ? "NULL" : $bindVar,
					"type" => $bindVar == null
								? PDO::PARAM_NULL
								: (is_int($bindVar) || is_float($bindVar)
									? PDO::PARAM_INT
									: PDO::PARAM_STR)
				);
			}
			
			return $this;
		}
		
		/**
		 * Get the SQL query
		 * 
		 * Gets the SQL query set in the instance, with placeholders.
		 * 
		 * @return string The SQL query
		 */
		public function getQuery()
		{
			return $this->query;
		}
		
		/**
		 * Get the debug SQL query
		 * 
		 * Gets the SQL query set in the instance, with placeholders replaced
		 * with bind variable values.
		 * 
		 * @return string The debug SQL query
		 */
		public function getQueryDebug()
		{
			$debugQuery = $this->query;
			
			foreach ($this->bindVars as $bindVar)
				$debugQuery = preg_replace("/\?/", ($bindVar->type == PDO::PARAM_STR ? "'" . $bindVar->value . "'" : $bindVar->value), $debugQuery, 1);
			
			return $debugQuery;
		}
		
		/**
		 * Get bind variables
		 * 
		 * Gets all set bind variables on the instance.
		 * 
		 * @return array An array containing all set bind variables.
		 */
		public function getBindVars()
		{
			return $this->bindVars;
		}
		
		/**
		 * Set the database connection
		 * 
		 * Sets the database connection to execute the query against.
		 * 
		 * @param Database $conn The database connection
		 */
		public function setConnection(Database $conn)
		{
			$this->conn = $conn;
		}
		
		/**
		 * Get the set database connection
		 * 
		 * Gets the database connection associated with the instance.
		 * 
		 * @return Database The database connection
		 */
		public function getConnection()
		{
			if (empty($this->conn))
				$this->conn = Database::getInstance();
			
			return $this->conn;
		}
		
		/**
		 * Execute the query
		 * 
		 * Executes the set SQL query against a database.
		 * 
		 * @param Database $conn Optional. The database connection to execute
		 * the query against.
		 * @return array The results of the executed query, as an array of
		 * DataModel objects
		 */
		public function execute(Database $conn = null)
		{
			if (!empty($conn))
				return $conn->executeQuery($this);
			
			return $this->getConnection()->executeQuery($this);
		}
	}
?>