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
	
	use stdClass;
	use Exception;

	/**
	 * An object representation of a database row
	 * 
	 * In it's simplest form, a DataModel object is an elaborate hashmap that
	 * maps column names from one or more database tables to values.  Once its
	 * attributes have been set, a DataModel object could then be used to
	 * execute queries against a database without having to write any SQL
	 * manually.
	 * 
	 * @author Patrick Griffin <pak9rabid@yahoo.com>
	 */
	
	class DataModel
	{
		/**
		 * Valid comparators
		 * 
		 * Valid comparators for set attributes.
		 */
		const comparators = "=,!=,>=,<=,>,<,*?*,*?,?*";	/** These are used as part of a regular
														 * expression in parseAttribute(), so the
														 * order that they are specified here matters.
														 */
		
		protected $attributes = array();
		protected $order = array();
		protected $tableRelationships = array();
		protected $selects = array();
		protected $updates = array();
		protected $db = null;
		
		/**
		 * Validates a comparator
		 * 
		 * @param string $comparator The comparator to validate
		 * @return boolean true if $comparator is valid, false otherwise
		 */
		public static function isValidComparator($comparator)
		{
			return in_array($comparator, explode(",", self::comparators));
		}
		
		/**
		 * Parses an attribute name
		 * 
		 * Parses an attribute name (either a standalone or fully-
		 * qualified name) and returns an object representation of it in the
		 * following form:
		 *    
		 *    $attribute->table = <table name>
		 *    $attribute->name = <attribute name>
		 *    
		 * @param string $attributeName A standalone or fully-qualified attribute name
		 * @throws Exception If the table or attribute name are invalid database identifiers
		 * @return stdClass Object representation of the attribute name
		 */
		public static function parseAttributeName($attributeName)
		{
			$parts = explode(".", $attributeName, 2);
			$attributeName = (object) (count($parts) > 1 ? array("table" => strtolower(trim($parts[0])), "name" => strtolower(trim($parts[1]))) : array("table" => "", "name" => strtolower(trim($parts[0]))));
			
			if (!empty($attributeName->table) && !Database::isValidIdentifier($attributeName->table))
				throw new Exception("Invalid table name specified: " . $attributeName->table);
			
			if (!Database::isValidIdentifier($attributeName->name))
				throw new Exception("Invalid attribute name specified: " . $attributeName->name);
			
			return $attributeName;
		}
		
		/**
		 * Parses an attribute key/value relationship
		 * 
		 * Parses $attributeString, which should be in the following form:
		 *    
		 *    <attribute name> <comparator> <attribute value>
		 *    
		 * and returns an object representation of it with the following properties:
		 * 
		 *    $attribute->table
		 *    $attribute->name
		 *    $attribute->comparator
		 *    $attribute->value
		 * 
		 * @param string $attributeString A key/value pair with a comparator, in the form: <attribute name> <comparator> <attribute value>
		 * @throws Exception If an invalid $attributeString is specified
		 * @return stdClass Object representation of the attributes table, name, comparator, and value
		 */
		public static function parseAttribute($attributeString)
		{
			$parts = preg_split("/(" . preg_replace("/,/", "|", preg_quote(self::comparators)) . ")+/", $attributeString, 2, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
			
			if (count($parts) != 3)
				throw new Exception("Invalid attribute string specified: " . $attributeString);
			
			$attribute = self::parseAttributeName($parts[0]);
			$attribute->comparator = $parts[1];
			$attribute->value = trim($parts[2]);
			
			if (!self::isValidComparator($attribute->comparator))
				throw new Exception("Invalid comparator specified: " . $attribute->comparator);
			
			return $attribute;
		}
		
		/**
		 * Constructor
		 * 
		 * Creates a new DataModel object, initializing it with any specified
		 * tables
		 * 
		 * @param mixed $tables A list of tables to initialize the DataModel with.
		 *    This can be an array containing a list of table names, or a
		 *    variable list of arguments, with each argument being a table
		 *    name.
		 */
		public function __construct($tables = "")
		{
			if (!is_array($tables))
				$tables = func_get_args();
			
			if (!empty($tables))
				$this->setTables($tables);
		}
		
		/**
		 * To String
		 * 
		 * Returns a string representation of this DataModel object
		 * 
		 * @return string A string representation of this DataModel object,
		 *    displaying all set attributes and associated comparators.
		 */
		public function __toString()
		{
			$dm = $this;
			
			return	"{" .
						implode
						(
							", ",
							array_map(function(array $attributes, $table) use ($dm)
							{
								return	"[" .
											implode
											(
												", ",
												array_map(function(stdClass $attribute, $attributeName)
												{
													return $attributeName . " " . $attribute->comparator . " " . $attribute->value;
												}, $attributes, $dm->getAttributeKeys($table))
											) .
										"]";
							}, $this->attributes, $this->getTables())
						) .
					"}";
		}
		
		/**
		 * JSON-encode
		 * 
		 * JSON-encode all set attributes
		 * 
		 * @return string JSON-encoded attributes
		 */
		public function toJson()
		{
			return json_encode($this->attributes);
		}
		
		/**
		 * Set database connection
		 * 
		 * Set a connection for this object to use when executing queries.
		 * This is useful for when one might want to run a series of queries
		 * as a single, atomic transaction.
		 * 
		 * @param Database $db
		 * @return DataModel $this (for method chaining)
		 */
		public function setConnection(Database $db)
		{
			$this->db = $db;
			return $this;
		}
		
		/**
		 * Get the database connection
		 * 
		 * Get the database connection set for this instance
		 * 
		 * @return Database The database connection, or null if none was set
		 */
		public function getConnection()
		{
			return $this->db;
		}
		
		/**
		 * Set and initialize a table
		 * 
		 * Creates an entry for a table, clearing any previously-set attributes
		 * 
		 * @param string $table A table name
		 * @throws Exception If an invalid table name is specified
		 * @return DataModel $this (for method chaining)
		 */
		public function setTable($table)
		{
			$table = trim($table);
			
			if (empty($table))
				return $this;
			
			if (!Database::isValidIdentifier($table))
				throw new Exception("Invalid table name specified: " . $table);
			
			$this->attributes[strtolower($table)] = array();
			return $this;
		}
		
		/**
		 * Set and initialize a list of tables
		 * 
		 * Creates an entry for a list of tables, clearing any previously-set
		 * attributes.
		 * 
		 * @param mixed $tables A list of tables to set.  This can be either an
		 *    array containing a list of table names, or a variable list of
		 *    arguments, with each argument being a table name.
		 *                      
		 * @throws Exception If an invalid table name is specified
		 * @return DataModel $this (for method chaining)
		 */
		public function setTables($tables = "")
		{
			if (!is_array($tables))
				$tables = func_get_args();
			
			$backup = $this->attributes;
			$this->clear();
			
			try
			{
				$f = array($this, "setTable");
				array_walk($tables, $f);
			}
			catch (Exception $ex)
			{
				$this->attributes = $backup;
				throw $ex;
			}
			
			return $this;
		}
		
		/**
		 * Remove a table
		 * 
		 * Removes a table and all associated attributes, table relationships,
		 * selects, orders, and updates.
		 * 
		 * @param string $table Name of the table to remove
		 * @return DataModel $this (for method chaining)
		 */
		public function removeTable($table)
		{
			unset($this->attributes[strtolower($table)]);
			
			$this->tableRelationships = array_filter($this->tableRelationships, function($relationship) use ($table)
			{
				$attributes = explode(" = ", $relationship);
				list($table1, $attribute1) = explode(".", $attributes[0]); 
				list($table2, $attribute2) = explode(".", $attributes[1]);
				
				return !in_array($table, array($table1, $table2));
			});
			
			$this->selects = array_filter($this->selects, function($selectAttribute) use ($table)
			{
				list($selectTable, $selectAttribute) = explode(".", $selectAttribute);
				return $selectTable != $table;
			});
			
			$f = function($attribute, $rmTable)
			{
				list($table, $attribute) = explode(".", $attribute);
				return !($table == $rmTable);
			};
			
			$this->order = array_diff_ukey($this->order, array_flip(array($table)), $f);
			$this->updates = array_diff_ukey($this->updates, array_flip(array($table)), $f);
			
			return $this;
		}
		
		/**
		 * Get table names
		 * 
		 * Gets a list of all set tables
		 * 
		 * @return array A list of all set table names
		 */
		public function getTables()
		{
			return array_keys($this->attributes);
		}
		
		/**
		 * Add a sort order
		 * 
		 * Adds a sort order for the specified attribute.  Sort orders are used
		 * to sort results when executeSelect() is called.
		 * 
		 * @param string $attributeName Attribute to order the results by
		 * @param string $direction Order direction.  Valid values are "ASC"
		 *    (for ascending order) and "DESC" (for descending order).  If
		 *    omitted, a direction of 'ASC' is assumed.
		 * @throws Exception If an invalid order direction is specified
		 * @return DataModel $this (for method chaining)
		 */
		public function addOrder($attributeName, $direction = "ASC")
		{
			$attribute = self::parseAttributeName($attributeName);
			
			if (empty($direction))
				$direction = "ASC";
			
			if (!in_array($direction = trim($direction), array("ASC", "DESC")))
				throw new Exception("Invalid order direction specified : " . $direction);
		
			$attribute->table = $this->getExistingTable($attribute->table);
		
			$this->order[$attribute->table . "." . $attribute->name] = $direction;
			return $this;
		}
		
		/**
		 * Set sort order
		 * 
		 * Sets the sort order, clearing any previously set ordering.
		 * 
		 * @param mixed $orders A list of attributes and sort directions to
		 *    order the results when executeSelect() is called.  This can be
		 *    an array containing a list of sort orders, or a variable list of
		 *    arguments, with each argument being a sort order.  Sort orders
		 *    are expected to be in the format:<br><br>
		 *    
		 *       attributeName [direction]<br><br>
		 *       
		 *    If ommitted, a direction of 'ASC' is assumed.
		 * @throws Exception If an invalid order direction is specified
		 * @return DataModel $this (for method chaining)
		 */
		public function setOrder($orders)
		{
			if (!is_array($orders))
				$orders = func_get_args();
			
			$backup = $this->order;
			$this->clearOrder();
		
			try
			{
				array_walk($orders, function($order, $index, DataModel $dm)
				{
					@list($attribute, $direction) = explode(" ", $order, 2);
					$dm->addOrder($attribute, $direction);
				}, $this);
			}
			catch (Exception $ex)
			{
				$this->order = $backup;
				throw $ex;
			}
		
			return $this;
		}
		
		/**
		 * Get order
		 * 
		 * Gets the set ordering.
		 * 
		 * @return array The order
		 */
		public function getOrder()
		{
			return $this->order;
		}
		
		/**
		 * Remove a sort order
		 * 
		 * Removes the specified sort order.
		 * 
		 * @param string $attributeName The name of the attribute to remove
		 *    sorting on
		 * @return DataModel $this (for method chaining)
		 */
		public function removeOrder($attributeName)
		{
			$attribute = self::parseAttributeName($attributeName);
			$attribute->$table = $this->getExistingTable($attribute->$table, true);
			unset($this->order[$attribute->$table . "." . $attribute->name]);
			return $this;
		}
		
		/**
		 * Clear ordering
		 * 
		 * Removes all set ordering
		 * 
		 * @return DataModel $this (for method chaining)
		 */
		public function clearOrder()
		{
			$this->order = array();
			return $this;
		}
		
		/**
		 * Get an attribute value
		 * 
		 * Gets the specified attribute value.
		 * 
		 * @param string $attributeName Attribute whose value to retreive
		 * @return The value of the specified attribute, or null of the
		 *    specified attribute is not set
		 */
		public function getAttribute($attributeName)
		{
			if (empty($attributeName))
				return null;
		
			$attribute = self::parseAttributeName($attributeName);
			$attribute->table = $this->getExistingTable($attribute->table);
			return isset($this->attributes[$attribute->table][$attribute->name]) ? $this->attributes[$attribute->table][$attribute->name]->value : null;
		}
		
		/**
		 * Get a list of attributes
		 * 
		 * Gets a list of set attributes, optionally limiting results to a
		 * specific table.
		 * 
		 * @param string $table If set, will only retreive attributes from the
		 *    specified table
		 * @param boolean $useShortKeys If true, the returned array will omit
		 *    the table name from the attribute key.
		 *    <br><br>
		 *    <b>WARNING:</b> If this is set to true and there are multiple
		 *    tables that contain the same attribute name, the last entry will
		 *    overwrite the previous entry in the returned array.
		 * @return array An array containing attribute names and values in the
		 *    format:<br><br>
		 *    
		 *    $attributes[attributeName] = attributeValue
		 */
		public function getAttributes($table = "", $useShortKeys = false)
		{
			$returnAttributes = array();
			
			$f = function(array $attributes, $table) use ($useShortKeys, &$returnAttributes)
			{
				/**
				 * given the table name $table, transform $attributes (a map of
				 * attributes keys to attribute objects for table $table)
				 * to a map of:
				 * 
				 * 		[<table>.<attribute name>] => <attribute value>
				 * 
				 * and store in $returnAttributes
				 * 
				 * if $useShortKeys is set to true, the array is returned with the table part of
				 * the key removed, so it would look like this instead:
				 * 
				 * 		[<attribute name>] => <attribute value>
				 */
				
				array_walk($attributes, function($attributeObject, $attributeKey) use ($table, $useShortKeys, &$returnAttributes)
				{
					$returnAttributes[$useShortKeys ? $attributeKey : $table . "." . $attributeKey] = $attributeObject->value;
				}, $returnAttributes);
			};
			
			if (!empty($table))
			{
				if (!isset($table, $this->attributes[$table]))
					return array();
				
				$f($this->attributes[$table], $table);
				return $returnAttributes;
			}
			
			array_walk($this->attributes, $f);
			return $returnAttributes;
		}
		
		/**
		 * Get a list of attribute names
		 * 
		 * Gets a list of set attribute names, optionally limiting results to a
		 * specific table.
		 * 
		 * @param string $table If set, will only retreive attribute names from
		 *    the specified table.
		 * @param boolean $useShortKeys If true, the returned array will omit
		 *    the table name from the attribute name.
		 * @return array An array containing the list of attribute names
		 */
		public function getAttributeKeys($table = "", $useShortKeys = false)
		{
			return array_keys($this->getAttributes($table, $useShortKeys));
		}
		
		/**
		 * Set an attribute
		 * 
		 * Sets an attribute's name, comparator, and value.
		 * 
		 * @param string $attributeString Attribute string in the following format:<br><br>
		 * 
		 *    '[tableName].attributeName comparator attributeValue'<br><br>
		 *    
		 *    'comparator' is one of: =, !=, >=, <=, >, <, \*?\*, \*?, ?\*
		 * @return DataModel $this (for method chaining)
		 */
		public function setAttribute($attributeString)
		{
			$attribute = self::parseAttribute($attributeString);
			$attribute->table = $this->getExistingTable($attribute->table, true);
			
			if (!in_array($attribute->table, $this->getTables()))
				$this->setTable($attribute->table);
			
			$this->attributes[$attribute->table][$attribute->name] = (object) array("value" => $attribute->value, "comparator" => $attribute->comparator);
			return $this;
		}
		
		/**
		 * Set a list of attributes
		 * 
		 * Sets a list of attributes at once.
		 * 
		 * @param mixed $attributes A list of attributes to set.  This can be 
		 *    an array containing a list of attributes, or a variable list of
		 *    arguments, wich each argument being an attribute string.  See
		 *    setAttribute() for details on how the attribute string should be
		 *    formatted.
		 * @return DataModel $this (for method chaining)
		 */
		public function setAttributes($attributes)
		{
			if (!is_array($attributes))
				$attributes = func_get_args();
		
			$backup = $this->attributes;
		
			try
			{
				array_walk($attributes, function($attribute, $index, DataModel $dm)
				{
					$dm->setAttribute($attribute);
				}, $this);
			}
			catch (Exception $ex)
			{
				$this->attributes = $backup;
				throw $ex;
			}
		
			return $this;
		}
		
		/**
		 * Remove a set attribute
		 * 
		 * Removes the specified attribute from the list of attributes
		 * 
		 * @param string $attributeName The attribute to remove
		 * @return DataModel $this (for method chaining)
		 */
		public function removeAttribute($attributeName)
		{
			$attribute = self::parseAttributeName($attributeName);
			$attribute->table = $this->getExistingTable($attribute->table, true);
			unset($this->attributes[$attribute->table][$attribute->name]);
			return $this;
		}
		/**
		 * Clear attributes
		 * 
		 * Clears attributes.  If $table is specified, only attribute contained
		 * within the specified table will be cleared, otherwsise all attributes
		 * are cleared.
		 * 
		 * @param string $table Optional.  If specified, only attributes in
		 *    the specified table will be cleared.
		 * @return DataModel $this (for method chaining)
		 */
		public function clearAttributes($table = "")
		{
			if (!empty($table))
			{
				$table = strtolower($table);
				
				if (!isset($this->attributes[$table]))
					return;

				$this->attributes[$table] = array();
				return $this;
			}
			
			array_walk($this->attributes, function(&$attributes)
			{
				$attributes = array();
			});
			
			return $this;
		}

		/**
		 * Clear all settings
		 * 
		 * Clears all set attributes, table relationships, orders,
		 * selects, and updates.
		 * 
		 * @return DataModel $this (for method chaining)
		 */
		public function clear()
		{
			return $this
						->clearAttributes()
						->clearTableRelationships()
						->clearOrder()
						->clearSelects()
						->clearUpdates();
		}
		
		/**
		 * Execute a SELECT query against a database
		 * 
		 * Executes a SELECT query based on set attributes and their associated
		 * comparators against a relational database.
		 * 
		 * @param string $query Optional.  If specified, a copy of the
		 *    generated SQL query will be placed here.  This is primarily used
		 *    for testing and debugging purposes.
		 * @return array The results of the SELECT query, as an array of
		 *    DataModel objects
		 */
		public function executeSelect(&$query = null)
		{
			if (!empty($this->db))
				return $this->db->executeSelect($this, $query);
			else
			{
				$db = Database::getInstance();
				return $db->executeSelect($this, $query);
			}
		}

		/**
		 * Execute an INSERT query against a database
		 * 
		 * Executes an INSERT query based on set attributes against a
		 * relational database.
		 * 
		 * @param string $query Optional.  If specified, a copy of the
		 *    generated SQL query will be placed here.  This is primarily used
		 *    for testing and debugging purposes.
		 * @return DataModel $this (for method chaining)
		 */
		public function executeInsert(&$query = null)
		{			
			if (!empty($this->db))
				$this->db->executeInsert($this, $query);
			else
			{
				$db = Database::getInstance();
				$db->executeInsert($this, $query);
			}
			
			return $this;
		}
		
		/**
		 * Execute an UPDATE query against a database
		 * 
		 * Executes an UPATE query based on any set attributes and updates
		 * against a relational database.  Any set updates are used for the new
		 * value and any set attributes are used for the WHERE clause.
		 * 
		 * @param string $query Optional.  If specified, a copy of the
		 *    generated SQL query will be placed here.  This is primarily used
		 *    for testing and debugging purposes.
		 * @return DataModel $this (for method chaining)
		 */
		public function executeUpdate(&$query = null)
		{
			if (!empty($this->db))
				$this->db->executeUpdate($this, $query);
			else
			{
				$db = Database::getInstance();
				$db->executeUpdate($this, $query);
			}
			
			// FIXED: return $this
			return $this;
		}

		/**
		 * Execute a DELETE query against a database
		 * 
		 * Executes a DELETE query based on any set attributes against a
		 * relational database.
		 * 
		 * @param string $query Optional.  If specified, a copy of the
		 *    generated SQL query will be placed here.  This is primarily used
		 *    for testing and debugging purposes.
		 * @return DataModel $this (for method chaining)
		 */
		public function executeDelete(&$query = null)
		{			
			if (!empty($this->db))
				$this->db->executeDelete($this, $query);
			else
			{
				$db = Database::getInstance();
				$db->executeDelete($this, $query);
			}
			
			// FIXED: return $this
			return $this;
		}
		
		/**
		 * Set a comparator
		 * 
		 * Sets a comparator for an existing attribute.
		 * 
		 * @param string $attributeName Attribute to set the comparator on.  If
		 *    the specified attribute does not exist, no changes are made.
		 * @param string $comparator The comparator to set.  For a list of
		 *    valid comparators, see the DataModel::comparators constant.
		 */
		public function setComparator($attributeName, $comparator)
		{
			$attribute = self::parseAttributeName($attributeName);
			$attribute->table = $this->getExistingTable($attribute->table, true);
		
			if (!isset($this->attributes[$attribute->table][$attribute->name]))
				return $this;
			
			$this->setAttribute($attributeName . " " . $comparator . " " . $this->getAttribute($attributeName));
			
			return $this;
		}
		
		/**
		 * Get a set comparator
		 * 
		 * Get's the value of a set attribute's comparator.
		 * 
		 * @param string $attributeName The name of the attribute who's
		 *    comparator to retrieve
		 * @return string The specified attribute's comparator, or null if the
		 *    specified attribute does not exist.
		 */
		public function getComparator($attributeName)
		{
			$attribute = self::parseAttributeName($attributeName);
			$attribute->table = $this->getExistingTable($attribute->table, true);
			return isset($this->attributes[$attribute->table][$attribute->name]) ? $this->attributes[$attribute->table][$attribute->name]->comparator : null;
		}
		
		/**
		 * Get a list of set comparators
		 * 
		 * Gets a list of any set comparators, optionally limited to a specific
		 * table.
		 * 
		 * @param string $table Optional.  The table for which to get the set
		 *    comparators.
		 * @return array An array containing any set comparators.  If $table is
		 *    specified, only comparators from the specified table are
		 *    included, otherwise all comparators are returned.  The returned
		 *    array is formatted as:
		 *       $comparators[<table name>.<attribute name>] = <comparator>
		 */
		public function getComparators($table = "")
		{
			$f = function(stdClass $attribute)
			{
				return $attribute->comparator;
			};
			
			if (!empty($table))
			{
				$table = strtolower($table);
				
				if (!isset($this->attributes[$table]))
					return array();
				
				$comparators = array_map($f, $this->attributes[$table]);
				
				return array_combine(array_map(function($key) use ($table)
				{
					return $table . "." . $key;
				}, array_keys($comparators)), $comparators);
			}
			
			$allComparators = array();
			
			array_walk($this->attributes, function(array $attributes, $table) use ($f, &$allComparators)
			{
				$tableComparators = array_map($f, $attributes);
				
				$tableComparators = array_combine(array_map(function($key) use ($table)
				{
					return $table . "." . $key;
				}, array_keys($tableComparators)), $tableComparators);
				
				$allComparators = array_merge($allComparators, $tableComparators);
			});
			
			return $allComparators;
		}
		
		/**
		 * Reset attribute comparators
		 * 
		 * Resets comparators to the default value (=).
		 * 
		 * @param string $table Optional.  If specified, resetting will be
		 *    limited to the specified table.
		 * @return DataModel $this (for method chaining)
		 */
		public function resetComparators($table = "")
		{
			$f = function(array $attributes, $table = "")
			{
				array_walk($attributes, function($attrObj, $attrName)
				{
					$attrObj->comparator = "=";
				});
			};
			
			if (!empty($table))
			{
				$table = strtolower($table);
				
				if (!isset($this->attributes[$table]))
					return $this;
				
				$f($this->attributes[$table]);
				return $this;
			}
			
			array_walk($this->attributes, $f);
			return $this;
		}
		
		/**
		 * Set a table relationship
		 * 
		 * Sets a relationship for the purpose of performing an SQL INNER JOIN
		 * between two tables.
		 * 
		 * @param string $relationship The table relationship, in the form:
		 *    <table name>.<attribute name> = <table name>.<attribute name>
		 * @throws Exception If the specifeid relationship is invalid
		 * @return DataModel $this (for method chaining)
		 */
		public function setTableRelationship($relationship)
		{
			list($left, $right) = explode("=", $relationship, 2);
			list($leftTable, $leftAttr) = explode(".", $left, 2);
			list($rightTable, $rightAttr) = explode(".", $right, 2);
			$a = array(&$leftTable, &$leftAttr, &$rightTable, &$rightAttr);
			
			array_walk($a, function(&$val, $key, $relationship)
			{
				$val = trim(strtolower($val));
			
				if (!Database::isValidIdentifier($val))
					throw new Exception("Invalid table relationship specified: " . $relationship);
			}, $relationship);
			
			if (!in_array($leftTable, $this->getTables()))
				$this->setTable($leftTable);
			
			if (!in_array($rightTable, $this->getTables()))
				$this->setTable($rightTable);
		
			$left = $leftTable . "." . $leftAttr;
			$right = $rightTable . "." . $rightAttr;
		
			$result = strcmp($left, $right);
		
			if ($result > 0)
			// $left > $right
				$relationship = $right . " = " . $left;
			else
			// $left < $right, or $left == $right
				$relationship = $left . " = " . $right;
		
			if (!in_array($relationship, $this->getTableRelationships()))
				$this->tableRelationships[] = $relationship;
		
			return $this;
		}
		
		/**
		 * Set one or more table relationships
		 * 
		 * Sets one or more table relationships for the purpose of performing
		 * an SQL INNER JOIN between two or more tables.
		 * 
		 * @param mixed $relationships A list of relationships to set.  This
		 *    be in the form of an array, or a variable list of relationship
		 *    parameters.  See the setTableRelationships($relationship) method
		 *    for how a relationsihp should be specified.
		 *    
		 * @throws Exception
		 * @return DataModel $this (for method chaining)
		 */
		public function setTableRelationships($relationships)
		{
			if (!is_array($relationships))
				$relationships = func_get_args();
			
			$backup = $this->tableRelationships;
			$this->clearTableRelationships();
			
			try
			{
				$f = array($this, "setTableRelationship");
				array_walk($relationships, $f);
			}
			catch (Exception $ex)
			{
				$this->tableRelationships = $backup;
				throw $ex;
			}
			
			return $this;
		}
		
		/**
		 * Get table relationships
		 * 
		 * Gets all set table relationships.  Table relationships are used for
		 * the purpose of conducting SQL INNER JOIN SELECT queries.
		 * 
		 * @return array A list of all table relationships
		 */
		public function getTableRelationships()
		{
			return $this->tableRelationships;
		}
		
		/**
		 * Clear table relationships
		 * 
		 * Clears all set table relationships.
		 * 
		 * @return DataModel $this (for method chaining)
		 */
		public function clearTableRelationships()
		{
			$this->tableRelationships = array();
			return $this;
		}
		
		/**
		 * Add a select attribute
		 * 
		 * Adds a select attribute.  Select attribute are used as part of the
		 * select criteria when executing a SELECT query.  If  no select
		 * attributes are specified, then '*' is assumed when executeSelect()
		 * is called.
		 * 
		 * @param string $attributeName The name of the attribute to select
		 * @throws Exception If the specified $attributeName is invalid
		 * @return DataModel $this (for method chaining)
		 */
		public function addSelect($attributeName)
		{
			$attribute = self::parseAttributeName($attributeName);
			$attribute->table = $this->getExistingTable($attribute->table, true);
		
			if (!in_array($attribute->table, $this->getTables()))
				$this->setTable($attribute->table);
		
			if (!Database::isValidIdentifier($attribute->name))
				throw new Exception("Invalid attribute specified: " . $attribute->name);
						
			$attributeName = $attribute->table . "." . $attribute->name;
			
			if (!in_array($attributeName, $this->selects))
				$this->selects[] = $attributeName;
		
			return $this;
		}
		
		/**
		 * Add one or more select attributes
		 * 
		 * Adds one or more select attributes
		 * 
		 * @param mixed $attributes This can be either an array containing
		 *    multiple selects to add, or a variable list of select parameters
		 * @return DataModel $this (for method chaining)
		 */
		public function addSelects($attributes)
		{
			if (!is_array($attributes))
				$attributes = func_get_args();
			
			$backup = $this->selects;
		
			try
			{
				$f = array($this, "addSelect");
				array_walk($attributes, $f);
			}
			catch (Exception $ex)
			{
				$this->selects = $backup;
				throw $ex;
			}
		
			return $this;
		}
		
		/**
		 * Set ont or more select attributes
		 * 
		 * Sets one or more select attributes, clearing any previously-set
		 * select attributes.
		 * 
		 * @param mixed $attributes This can be either an array containing
		 *    multiple selects to set, or a variable list of select parameters
		 * @throws Exception If any of the specified selects are invalid
		 * @return DataModel $this (for method chaining)
		 */
		public function setSelects($attributes)
		{
			if (!is_array($attributes))
				$attributes = func_get_args();
			
			$backup = $this->selects;
			$this->clearSelects();
		
			try
			{
				$this->addSelects($attributes);
			}
			catch (Exception $ex)
			{
				$this->selects = $backup;
				throw $ex;
			}
		
			return $this;
		}
		
		/**
		 * Get select attributes
		 * 
		 * Gets all set select attributes.
		 * 
		 * @return array An array containing all set select attributes
		 */
		public function getSelects()
		{
			return $this->selects;
		}
		
		/**
		 * Clear select attributes
		 * 
		 * Clears all set select attributes.
		 * 
		 * @return DataModel $this (for method chaining)
		 */
		public function clearSelects()
		{
			$this->selects = array();
			return $this;
		}
		
		/**
		 * Set an udpate attribute
		 * 
		 * Sets an update attribute.  Update attributes are used to define
		 * which columns are to be updated with what when the executeUpdate()
		 * method is called.
		 * 
		 * @param string $attributeName The name of the attribute to update
		 * @param unknown_type $value The new value to set the updated attribute to
		 * @throws Exception If the specified attribute name is invalid
		 * @return DataModel $this (for method chaining)
		 */
		public function setUpdate($attributeName, $value)
		{
			$attribute = self::parseAttributeName($attributeName);
		
			if (!Database::isValidIdentifier($attribute->name))
				throw new Exception("Invalid attribute specified: " . $attribute->name);
		
			$attribute->table = $this->getExistingTable($attribute->table);
			$this->updates[$attribute->table . "." . $attribute->name] = $value;
			return $this;
		}
		
		/**
		 * Set one or more update attributes
		 * 
		 * Sets one or more update attributes.
		 * 
		 * @param mixed $updates This can be either an array containing a list
		 *    of updates, or a variable list of update parameters.
		 * @throws Exception If one of the specified attribute names is invalid
		 * @return DataModel $this (for method chaining)
		 */
		public function setUpdates($updates)
		{
			if (!is_array($updates))
				$updates = func_get_args();
			
			$backup = $this->updates;
		
			try
			{
				array_walk($updates, function($update, $index, DataModel $dm)
				{
					@list($attribute, $value) = explode("=", $update, 2);
					$dm->setUpdate(trim($attribute), trim($value));
				}, $this);
			}
			catch (Exception $ex)
			{
				$this->updates = $backup;
				throw $ex;
			}
		
			return $this;
		}
		
		/**
		 * Remove a set update attribute
		 *
		 * Removes a set update attribute.
		 * 
		 * @param string $attribute The name of the set update attribute to
		 *    remove
		 * @return DataModel $this (for method chaining)
		 */
		public function removeUpdate($attribute)
		{
			unset($this->updates[$attribute]);
			return $this;
		}
		
		/**
		 * Get the value of a set update attribute
		 * 
		 * Gets the value of a set update attribute.
		 * 
		 * @param string $attribute The name of the udpate attribute to retreive
		 *    the value of.
		 * @return string The value of the named update attribute, or null if
		 *    the specified update attribute does not exist
		 */
		public function getUpdate($attribute)
		{
			return isset($this->updates[$attribute]) ? $this->updates[$attribute] : null;
		}
		
		/**
		 * Get set update attributes
		 * 
		 * Gets a list of all set update attributes, optionally limited to a
		 * specific table.
		 * 
		 * @param string $table Optional.  If specified, the returned updates
		 *    will be limitd to the specified table
		 * @param boolean $useShortKeys Optional.  If specified, the returned
		 *    array will only contain short keys.  That is, only the attribute
		 *    name will be used as keys, omitting the table name.
		 *    <br><br>
		 *    <b>WARNING:</b> If this is set to true and there are multiple
		 *    tables that contain the same attribute name, the last entry will
		 *    override the previous entry in the returned array.
		 * @return array An array containing the set update attributes,
		 *    formatted as:  $updates[<attribute name>] = <update value>
		 */
		public function getUpdates($table = "", $useShortKeys = false)
		{
			if (empty($table) && !$useShortKeys)
				return $this->updates;
		
			$returnArray = array();
		
			array_walk($this->updates, function($value, $attributeName) use ($table, $useShortKeys, &$returnArray)
			{
				$attribute = DataModel::parseAttributeName($attributeName);
		
				if ($useShortKeys)
					$attributeName = $attribute->name;
		
				if (!empty($table))
				{
					if ($table == $attribute->table)
						$returnArray[$attributeName] = $value;
				}
				else
					$returnArray[$attributeName] = $value;
		
			});
		
			return $returnArray;
		}
		
		/**
		 * Clear all set update attributes
		 * 
		 * Clears out all set update attributes.
		 * 
		 * @return DataModel $this (for method chaining)
		 */
		public function clearUpdates()
		{
			$this->updates = array();
			return $this;
		}
		
		/**
		 * Get an existing table and/or validate a table name
		 * 
		 * This is a convenience method that is used throughout the DataModel
		 * class for simplifying table name validation, as well as making it
		 * easier to work with single-table DataModel objects.  In the case of
		 * the latter, when this method is called with with no arguments it
		 * returns the name of the single set table.  This makes it possible to
		 * reference attributes without having to explicitly provide its
		 * table name as well, in the case of single-table DataModel objects.
		 * 
		 * @param string $table Optional.  The name of the table to retrieve
		 * @param boolean $ignoreMissing Optional.  If true, will not require
		 *    the specified table to exist and will simply check that the table
		 *    name is a valid identifier.
		 * @throws Exception If there are multiple set tables and no table name
		 *    is specified
		 * @throws Exception If an invalid table name is specified
		 * @throws Exception If $ignoreMissing is false and the specified table
		 *    does not exist
		 * @return A table name
		 */
		private final function getExistingTable($table = "", $ignoreMissing = false) //FIXED: added default value ("") for $table
		{
			$tables = $this->getTables();
		
			if (empty($table))
			{
				if (count($tables) == 1)
					$table = $tables[0];
				else
					throw new Exception("Ambiguous table...table must be specified");
			}
			else
			{
				if (!Database::isValidIdentifier($table))
					throw new Exception("The table specified is invalid: " . $table);
				
				if (!$ignoreMissing && !isset($this->attributes[strtolower($table)]))
					throw new Exception("The table specified does not exist: " . $table);
			}
		
			return strtolower($table);
		}
	}
?>
