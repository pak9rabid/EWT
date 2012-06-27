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

	class DataHash
	{
		// Attributes
		protected $primaryKey = "id";
		protected $tables = array();
		protected $orderBy = array();
		protected $hashMap = array();
		protected $comparatorMap = array();
		protected $conn;
				
		public static function isValidComparator($comparator)
		{
			return in_array($comparator, array("=", "!=", ">", "<", ">=", "<=", "*?*", "*?", "?*"));
		}

		// Constructors
		public function __construct($tables = "")
		{
			if (gettype($tables) == "array")
				$this->setTables($tables);
			else
				$this->setTable($tables);
		}

		// Methods
		public function __toString()
		{
			$dh = $this;
			
			return	"{" .
						implode
						(", ",
							array_map
							(
								// closures ftw
								function($key, $value) use ($dh)
								{
									return "$key " . $dh->getComparator($key) . " $value";
								},
								array_keys($this->hashMap),
								array_values($this->hashMap)
							)
						) .
					"}";
		}
		
		public function toJson()
		{
			return json_encode($this->hashMap);
		}
		
		public function setConnection(Database $conn)
		{
			$this->conn = $conn;
			return $this;
		}
		
		public function setTable($table)
		{
			$this->setTables(array($table));
			return $this;
		}
		
		public function setTables(array $tables)
		{
			if (empty($tables))
				throw new Exception("No table(s) specified");
			
			array_walk($tables, function($table, $key)
			{
				if (!Database::isValidIdentifier($table))
					throw new Exception("Invalid table name specified");
			});
			
			$this->tables = $tables;
			return $this;
		}
		
		public function addTable($table)
		{
			if (!Database::isValidIdentifier($table))
				throw new Exception("Invalid table name specified");
			
			if (in_array($table, $this->getTables()))
				throw new Exception("The specified table already exists");
			
			$this->tables[] = $table;
			return $this;
		}
		
		public function addTables(array $tables)
		{
			$result = array_intersect($tables, $this->getTables());
			
			if (!empty($result))
				throw new Exception("One or more of the tables specified already exists");
			
			$this->setTables(array_merge($this->getTables(), $tables));
			return $this;
		}
		
		public function removeTable($removeTable)
		{
			$this->setTables(array_filter($this->getTables(), function($table) use ($removeTable)
			{
				return $table != $removeTable;
			}));
			
			return $this;
		}
		
		public function getTables()
		{
			return $this->tables;
		}

		public function setPrimaryKey($primaryKey)
		{
			$this->primaryKey = $primaryKey;
			return $this;
		}
		
		public function setOrderBy(array $orderByList)
		{			
			$this->orderBy = $orderByList;
			return $this;
		}
		
		public function getOrderBy()
		{
			return $this->orderBy;
		}
	
		public function getPrimaryKey()
		{
			return $this->primaryKey;
		}

		public function getAttribute($key)
		{
			return isset($this->hashMap[$key]) ? $this->hashMap[$key] : null;
		}

		public function getAttributeMap()
		{
			return $this->hashMap;
		}

		public function getAttributeKeys()
		{
			return array_keys($this->hashMap);
		}

		public function getAttributeValues()
		{
			return array_values($this->hashMap);
		}

		public function setAttribute($key, $value, $comparator = "=")
		{
			$this->hashMap[$key] = $value;
			$this->setComparator($key, $comparator);
			return $this;
		}

		public function setAllAttributes(array $hashMap)
		{
			$this->hashMap = $hashMap;
			
			if (empty($hashMap))
				$this->clearComparators();
			else
			{				
				array_walk($this->hashMap, function($value, $key, DataHash $dh)
				{
					$dh->setComparator($key, "=");
				}, $this);
			}
			
			return $this;
		}

		public function removeAttribute($key)
		{
			unset($this->hashMap[$key]);
			return $this;
		}

		public function clear()
		{
			$this->hashMap = array();
			$this->clearComparators();
			return $this;
		}
		
		public function executeSelect($filterNullValues = false)
		{
			if (!empty($this->conn))
				return $this->conn->executeSelect($this, $filterNullValues);
			else
			{
				$db = Database::getInstance(Database::getConnectionConfig());
				return $db->executeSelect($this, $filterNullValues);
			}
		}

		public function executeInsert()
		{			
			if (!empty($this->conn))
				$this->conn->executeInsert($this);
			else
			{
				$db = Database::getInstance(Database::getConnectionConfig());
				$db->executeInsert($this);
			}
			
			return $this;
		}

		public function executeUpdate()
		{			
			if (!empty($this->conn))
				$this->conn->executeUpdate($this);
			else
			{
				$db = Database::getInstance(Database::getConnectionConfig());
				$db->executeUpdate($this);
			}
			
			return $this;
		}

		public function executeDelete()
		{			
			if (!empty($this->conn))
				return $this->conn->executeDelete($this);
			else
			{
				$db = Database::getInstance(Database::getConnectionConfig());
				return $db->executeDelete($this);
			}
		}
		
		public function setComparator($attribute, $comparator)
		{
			if (!self::isValidComparator($comparator))
				throw new Exception("Invalid comparator specified");
			
			if (!in_array($attribute, $this->getAttributeKeys()))
				throw new Exception("Invalid attribute specified to apply comparator to");
			
			$this->comparatorMap[$attribute] = $comparator;
			return $this;
		}
		
		public function getComparator($attribute)
		{
			return isset($this->comparatorMap[$attribute]) ? $this->comparatorMap[$attribute] : null;
		}
		
		public function getComparatorMap()
		{
			return $this->comparatorMap;
		}
		
		public function getComparatorKeys()
		{
			return array_keys($this->comparatorMap);
		}
		
		public function getComparatorValues()
		{
			return array_values($this->comparatorMap);
		}
		
		public function clearComparators()
		{
			// resets all attribute comparators back to '='
			$this->comparatorMap = array();
			
			array_walk($this->hashMap, function($value, $key, DataHash $dh)
			{
				$dh->setComparator($key, "=");
			}, $this);
			
			return $this;
		}
	}
?>
