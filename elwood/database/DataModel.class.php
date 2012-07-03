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

	class DataModel
	{
		// attributes
		protected $attributes = array();
		protected $order = array();
		protected $tableRelationships = array();
		protected $orderDirection = "ASC";
		protected $db;
		
		public static function isValidComparator($comparator)
		{
			return in_array($comparator, array("=", "!=", ">", "<", ">=", "<=", "*?*", "*?", "?*"));
		}

		// constructor
		public function __construct($tables = "")
		{
			if (gettype($tables) == "array")
				$this->setTables($tables);
			else
				$this->setTable($tables);
		}

		// methods
		public function __toString()
		{
			$dm = $this;
			
			return	"{" .
						implode
						(
							", ",
							array_map(function(array $attributes, $table) use ($dm)
							{
								return	$table .
									"[" .
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
		
		public function toJson()
		{
			return json_encode($this->attributes);
		}
		
		public function setConnection(Database $db)
		{
			$this->db = $db;
			return $this;
		}
		
		public function setTable($table)
		{
			if (!Database::isValidIdentifier($table))
				throw new Exception("Invalid table name specified: " . $table);
			
			$this->attributes[$table] = array();
			return $this;
		}
		
		public function setTables(array $tables)
		{
			if (empty($tables))
				throw new Exception("At least on table must be specified");
			
			$attributes = array();
			
			array_walk($tables, function($table, $key, array $attributes)
			{
				if (!Database::isValidIdentifier($table))
					throw new Exception("Invalid table name specified: " . $table);
				
				$attributes[$table] = array();
			}, &$attributes);
			
			$this->attributes = $attributes;
			return $this;
		}
		
		public function addTable($table)
		{
			if (isset($this->attributes[$table]))
				throw new Exception("The table specified already exists: " . $table);
			
			$this->setTable($table);
			return $this;
		}
		
		public function addTables(array $tables)
		{
			$conflicts = array_intersect($tables, $this->getTables());
			
			if (!empty($conflicts))
				throw new Exception("The table(s) specified already exist: " . implode(", ", $conflicts));
			
			array_walk($tables, function($table, $key, DataModel $dm)
			{
				$dm->addTable($table);
			}, $this);
			
			return $this;
		}
		
		public function removeTable($table)
		{
			if (count($this->getTables()) == 1)
				throw new Exception("Unable to remove the last remaining table: " . $table);
			
			unset($this->attributes[$table]);
			return $this;
		}
		
		public function getTables()
		{
			return array_keys($this->attributes);
		}
		
		public function addOrder($attribute, $table = "")
		{
			if (!Database::isValidIdentifier($attribute))
				throw new Exception("The attribute specified is invalid: " . $attribute);
						
			$table = $this->getExistingTable($table);
			$this->order[] = $table . "." . $attribute;
			return $this;
		}
		
		public function setOrder(array $order)
		{
			// $order keys represent tables, and values represent attribute names
			// example: $order[$table] = $attribute
			$orderBackup = $this->order;
			$this->order = array();
			
			try
			{
				array_walk($order, function($attribute, $table, DataModel $dm)
				{
					$dm->addOrder($attribute, $table);
				}, $this);
			}
			catch (Exception $ex)
			{
				$this->order = $orderBackup;
				throw $ex;
			}
			
			return $this;
		}
		
		public function getOrder()
		{
			return $this->order;
		}
		
		public function clearOrder()
		{
			$this->order = array();
			return $this;
		}
		
		public function setOrderDirection($direction = "ASC")
		{
			if (!in_array($direction, array("ASC", "DESC")))
				throw new Exception("Invalid order direction specified");
			
			$this->orderDirection = $direction;
			return $this;
		}
		
		public function getOrderDirection()
		{
			return $this->orderDirection;
		}
		
		public function getAttribute($name, $table = "")
		{
			if (empty($name))
				return null;
			
			$table = $this->getExistingTable($table);
			return isset($this->attributes[$table][$name]) ? $this->attributes[$table][$name]->value : null;
		}
		
		public function getAttributes($table = "")
		{
			$returnAttributes = array();
			
			$f = function(array $attributes, $table, array &$returnAttributes)
			{
				/**
				 * given the table name $table, transform $attributes (a map of
				 * attributes keys to attribute objects for table $table)
				 * to a map of [$table . $attributeKey] => $attributeObject->value, and
				 * store in $returnAttributes
				 */
				array_walk($attributes, function($attributeObject, $attributeKey, $returnAttributes) use ($table)
				{
					$returnAttributes[$table . "." . $attributeKey] = $attributeObject->value;
				}, &$returnAttributes);
			};
			
			if (!empty($table))
			{
				if (!isset($table, $this->attributes[$table]))
					return array();
				
				$f($this->attributes[$table], $table, $returnAttributes);
				return $returnAttributes;
			}
			
			array_walk($this->attributes, $f, &$returnAttributes);
			return $returnAttributes;
		}
				
		public function getAttributeKeys($table = "")
		{
			return array_keys($this->getAttributes($table));
		}
		
		public function setAttribute($key, $value, $table = "", $comparator = "=")
		{
			if (!Database::isValidIdentifier($key))
				throw new Exception("Invalid attribute specified: " . $key);
			
			if (!self::isValidComparator($comparator))
				throw new Exception("Invalid comparator specified: " . $comparator);
			
			$table = $this->getExistingTable($table);
						
			$this->attributes[$table][$key] = (object) array("value" => $value, "comparator" => $comparator);
			return $this;
		}

		public function setAttributes(array $attributes, $table = "")
		{
			$backup = $this->attributes;
			
			try
			{
				array_walk($attributes, function($value, $key, DataModel $dm) use ($table)
				{
					$dm->setAttribute($key, $value, $table, "=");
				}, $this);
			}
			catch (Exception $ex)
			{
				$this->attributes = $backup;
				throw $ex;
			}
			
			return $this;
		}

		public function removeAttribute($key, $table = "")
		{
			$table = $this->getExistingTable($table, true);
			unset($this->attributes[$table][$key]);
			return $this;
		}
		
		public function clearAttributes($table = "")
		{
			if (!empty($table))
			{
				if (!isset($this->attributes[$table]))
					return;

				$this->attributes[$table] = array();
				return $this;
			}
			
			array_walk($this->attributes, function(&$attributes, $table)
			{
				$attributes = array();
			});
			
			return $this;
		}

		public function clear()
		{
			return $this
						->clearAttributes()
						->clearTableRelationships()
						->clearOrder()
						->setOrderDirection();
		}
		
		public function executeSelect($filterNullValues = false)
		{
			if (!empty($this->db))
				return $this->db->executeSelect($this, $filterNullValues);
			else
			{
				$db = Database::getInstance(Database::getConnectionConfig());
				return $db->executeSelect($this, $filterNullValues);
			}
		}

		public function executeInsert()
		{			
			if (!empty($this->db))
				$this->db->executeInsert($this);
			else
			{
				$db = Database::getInstance(Database::getConnectionConfig());
				$db->executeInsert($this);
			}
			
			return $this;
		}
		
		public function executeUpdate(DataModel $criteria)
		{
			if (!empty($this->db))
				$this->db->executeUpdate($this, $criteria);
			else
			{
				$db = Database::getInstance(Database::getConnectionConfig());
				$db->executeUpdate($this, $criteria);
			}
		}

		public function executeDelete()
		{			
			if (!empty($this->db))
				return $this->db->executeDelete($this);
			else
			{
				$db = Database::getInstance(Database::getConnectionConfig());
				return $db->executeDelete($this);
			}
		}
		
		public function setComparator($attribute, $comparator, $table = "")
		{
			$table = $this->getExistingTable($table);
			
			if (!isset($this->attributes[$table][$attribute]))
				throw new Exception("The attribute specified does not exist: " . $table . "." . $attribute);
			
			$this->setAttribute($attribute, $this->getAttribute($attribute, $table), $table, $comparator);
			return $this;
		}
		
		public function getComparator($attribute, $table = "")
		{
			if (empty($attribute))
				return null;
			
			$table = $this->getExistingTable($table);
			
			if (!isset($this->attributes[$table][$attribute]))
				return null;
			
			return $this->attributes[$table][$attribute]->comparator;
		}
		
		public function getComparators($table = "")
		{
			$f = function(stdClass $attribute)
			{
				return $attribute->comparator;
			};
			
			if (!empty($table))
			{
				if (!isset($this->attributes[$table]))
					return array();
				
				$comparators = array_map($f, $this->attributes[$table]);
				
				return array_combine(array_map(function($key) use ($table)
				{
					return $table . "." . $key;
				}, array_keys($comparators)), $comparators);
			}
			
			$allComparators = array();
			
			array_walk($this->attributes, function(array $attributes, $table, $allComparators) use ($f)
			{
				$tableComparators = array_map($f, $attributes);
				
				$tableComparators = array_combine(array_map(function($key) use ($table)
				{
					return $table . "." . $key;
				}, array_keys($tableComparators)), $tableComparators);
				
				$allComparators = array_merge($allComparators, $tableComparators);
			}, &$allComparators);
			
			return $allComparators;
		}
		
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
				if (!isset($this->attributes[$table]))
					throw new Exception("The table specified does not exist: " . $table);
				
				$f($this->attributes[$table]);
				return $this;
			}
			
			array_walk($this->attributes, $f);
			return $this;
		}
		
		public function setTableRelationship($table1 = "", $attribute1 = "", $table2 = "", $attribute2 = "")
		{
			if (!isset($this->attributes[$table1]))
				throw new Exception("Invalid table specified: " . $table1);
			
			if (!isset($this->attributes[$table2]))
				throw new Exception("Invalid table specified: " . $table2);
			
			array_walk($params = array("\$table1" => $table1, "\$attribute1" => $attribute1, "\$table2" => $table2, "\$attribute2" => $attribute2), function($value, $key)
			{
				if (empty($value))
					throw new Exception("Required parameter missing: " . $key);
				
				if (!Database::isValidIdentifier($value))
					throw new Exception("The identifier specified is invalid: " . $value);
			});
			
			$this->tableRelationships[$table1 . "." . $attribute1] = $table2 . "." . $attribute2;
			return $this;
		}
		
		public function getTableRelationships()
		{
			return $this->tableRelationships;
		}
		
		public function clearTableRelationships()
		{
			$this->tableRelationships = array();
			return $this;
		}
		
		private final function getExistingTable($table, $ignoreMissing = false)
		{
			$tables = $this->getTables();
			
			if (empty($table))
			{
				if (count($tables) == 1)
					$table = $tables[0];
				else
					throw new Exception("Multiple tables present...table must be specified");
			}
			else
			{
				if (!$ignoreMissing && !isset($this->attributes[$table]))
					throw new Exception("The table specified does not exist: " . $table);
			}
			
			return $table;
		}
	}
?>
