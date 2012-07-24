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
		protected $selects = array();
		protected $db;
		
		public static function isValidComparator($comparator)
		{
			return in_array($comparator, array("=", "!=", ">", "<", ">=", "<=", "*?*", "*?", "?*"));
		}
		
		public static function parseAttribute($attribute)
		{
			$parts = explode(".", $attribute, 2);
			return count($parts) > 1 ? array("table" => strtolower(trim($parts[0])), "attribute" => strtolower(trim($parts[1]))) : array("table" => "", "attribute" => strtolower(trim($parts[0])));
		}
		
		public function __construct($tables = "")
		{
			if (!empty($tables))
				$this->setTables($tables);
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
		
		public function toJson()
		{
			return json_encode($this->attributes);
		}
		
		public function setConnection(Database $db)
		{
			$this->db = $db;
			return $this;
		}
		
		public function getConnection()
		{
			return $this->db;
		}
		
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
		
		public function setTables($tables)
		{
			$backup = $this->attributes;
			$this->clear();
			
			try
			{
				array_walk(explode(",", $tables), function($table, $key, DataModel $dm)
				{
					$dm->setTable($table);
				}, $this);
			}
			catch (Exception $ex)
			{
				$this->attributes = $backup;
				throw $ex;
			}
			
			return $this;
		}
			
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
			
			$this->order = array_filter($this->order, function($order) use ($table)
			{
				list($orderTable, $attribute) = explode(".", $order);
				return $orderTable != $table;
			});
			
			$this->selects = array_filter($this->selects, function($selectAttribute) use ($table)
			{
				list($selectTable, $selectAttribute) = explode(".", $selectAttribute);
				return $selectTable != $table;
			});
			
			return $this;
		}
		
		public function getTables()
		{
			return array_keys($this->attributes);
		}
		
		public function addOrder($attribute, $direction = "ASC")
		{
			list($table, $attribute) = array_values(self::parseAttribute($attribute));
			
			if (!Database::isValidIdentifier($attribute))
				throw new Exception("The attribute specified is invalid: " . $attribute);
			
			if (!in_array($direction = trim($direction), array("ASC", "DESC")))
				throw new Exception("Invalid order direction specified : " . $direction);
			
			$table = $this->getExistingTable($table);
			
			$this->order[$table . "." . $attribute] = $direction;
			return $this;
		}
		
		public function setOrder($attributes, $direction = "ASC")
		{
			$backup = $this->order;
			$this->clearOrder();
			
			try
			{
				array_walk(explode(",", $attributes), function($attribute, $key, DataModel $dm) use ($direction)
				{
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
		
		public function getOrder()
		{
			return $this->order;
		}
		
		public function removeOrder($attribute)
		{
			list($table, $attribute) = array_values(self::parseAttribute($attribute));
			$table = $this->getExistingTable($table, true);
			unset($this->order[$table . "." . $attribute]);
			return $this;
		}
		
		public function clearOrder()
		{
			$this->order = array();
			return $this;
		}
		
		public function getAttribute($attribute)
		{
			if (empty($attribute))
				return null;
			
			list($table, $attribute) = array_values(self::parseAttribute($attribute));
			$table = $this->getExistingTable($table);
			return isset($this->attributes[$table][$attribute]) ? $this->attributes[$table][$attribute]->value : null;
		}
		
		public function getAttributes($table = "", $useShortKeys = false)
		{
			/**
			 * WARNING:	if $useShortKeys is set to true and there are multiple tables that
			 *  		contain the same attribute name, the last entry will override the
			 *  		previous entry in the returned array
			 */
			$returnAttributes = array();
			
			$f = function(array $attributes, $table, array &$returnAttributes) use ($useShortKeys)
			{
				/**
				 * given the table name $table, transform $attributes (a map of
				 * attributes keys to attribute objects for table $table)
				 * to a map of:
				 * 		[<table> . <attribute name>] => <attribute value>
				 * 
				 * and store in $returnAttributes
				 * 
				 * if $useShortKeys is set to true, the array is returned with the table part of
				 * the key removed, so it would look like this instead:
				 * 
				 * 		[<attribute name>] => <attribute value>
				 */
				array_walk($attributes, function($attributeObject, $attributeKey, $returnAttributes) use ($table, $useShortKeys)
				{
					$returnAttributes[$useShortKeys ? $attributeKey : $table . "." . $attributeKey] = $attributeObject->value;
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
				
		public function getAttributeKeys($table = "", $useShortKeys = false)
		{
			return array_keys($this->getAttributes($table, $useShortKeys));
		}
		
		public function setAttribute($attribute, $value, $comparator = "=")
		{
			list($table, $attribute) = array_values(self::parseAttribute($attribute));
			
			if (!Database::isValidIdentifier($attribute))
				throw new Exception("Invalid attribute specified: " . $attribute);
			
			if (!self::isValidComparator($comparator))
				throw new Exception("Invalid comparator specified: " . $comparator);
						
			$table = $this->getExistingTable($table, true);
			
			if (!in_array($table, $this->getTables()))
				$this->setTable($table);
			
			$this->attributes[$table][strtolower($attribute)] = (object) array("value" => $value, "comparator" => $comparator);
			return $this;
		}

		public function setAttributes(array $attributes)
		{
			/** sets attributes as specified in $attributes, creating new
			 * 	tables as needed.  $attributes is expected to be an
			 * 	associative array in the following form:
			 * 
			 * 		$attributes[<attribute name>] = <attribute value>
			 */
			$backup = $this->attributes;
			
			try
			{
				array_walk($attributes, function($value, $attribute, DataModel $dm)
				{
					$dm->setAttribute($attribute, $value);
					
				}, $this);
			}
			catch (Exception $ex)
			{
				$this->attributes = $backup;
				throw $ex;
			}
			
			return $this;
		}
		
		public function removeAttribute($attribute)
		{
			list($table, $attribute) = array_values(self::parseAttribute($attribute));
			$table = $this->getExistingTable($table, true);
			unset($this->attributes[$table][$attribute]);
			return $this;
		}
		
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
						->clearSelectAttributes();
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
		
		public function setComparator($attribute, $comparator)
		{
			list($table, $name) = array_values(self::parseAttribute($attribute));
			$table = $this->getExistingTable($table, true);
			
			if (!isset($this->attributes[$table][$name]))
				return $this;
			
			$this->setAttribute($attribute, $this->getAttribute($attribute), $comparator);
			return $this;
		}
		
		public function getComparator($attribute)
		{			
			list($table, $attribute) = array_values(self::parseAttribute($attribute));			
			$table = $this->getExistingTable($table, true);
			return isset($this->attributes[$table][$attribute]) ? $this->attributes[$table][$attribute]->comparator : null;
		}
		
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
				$table = strtolower($table);
				
				if (!isset($this->attributes[$table]))
					return $this;
				
				$f($this->attributes[$table]);
				return $this;
			}
			
			array_walk($this->attributes, $f);
			return $this;
		}
		
		public function setTableRelationship($relationship)
		{
			list($left, $right) = explode("=", $relationship, 2);
			list($leftTable, $leftAttr) = explode(".", $left, 2);
			list($rightTable, $rightAttr) = explode(".", $right, 2);
		
			array_walk($a = array(&$leftTable, &$leftAttr, &$rightTable, &$rightAttr), function(&$val, $key, $relationship)
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
		
		public function setTableRelationships($relationships)
		{
			$backup = $this->tableRelationships;
			$this->clearTableRelationships();
			
			try
			{
				array_walk(explode(",", $relationships), function($relationship, $key, DataModel $dm)
				{
					$dm->setTableRelationship($relationship);
				}, $this);
			}
			catch (Exception $ex)
			{
				$this->tableRelationships = $backup;
				throw $ex;
			}
			
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
		
		public function addSelect($attribute)
		{
			list($table, $attribute) = array_values(self::parseAttribute($attribute));
			$table = $this->getExistingTable($table, true);
			
			if (!in_array($table, $this->getTables()))
				$this->setTable($table);
		
			if (!Database::isValidIdentifier($attribute))
				throw new Exception("Invalid attribute specified: " . $attribute);
		
			$attribute = strtolower($table . "." . $attribute);
		
			if (!in_array($attribute, $this->selects))
				$this->selects[] = $attribute;
		
			return $this;
		}
		
		public function addSelects($attributes)
		{
			$backup = $this->selects;
			
			try
			{
				array_walk(explode(",", $attributes), function($attribute, $key, DataModel $dm)
				{
					$dm->addSelect($attribute);
				}, $this);
			}
			catch (Exception $ex)
			{
				$this->selects = $backup;
				throw $ex;
			}
			
			return $this;
		}
		
		public function setSelects($attributes)
		{
			$backup = $this->selects;
			$this->clearSelectAttributes();
			
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
		
		public function getSelects()
		{
			return $this->selects;
		}
		
		public function clearSelectAttributes()
		{
			$this->selects = array();
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
					throw new Exception("Ambiguous table...table must be specified");
			}
			else
			{
				if (!$ignoreMissing && !isset($this->attributes[strtolower($table)]))
					throw new Exception("The table specified does not exist: " . $table);
			}
		
			return strtolower($table);
		}
	}
?>
