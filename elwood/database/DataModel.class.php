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
		/** allowed comparators for attributes.  as these are used as
		 * 	part of a regular expression in parseAttribute(), the order
		 * 	that they are specified here matters.
		 */
		const comparators = "=,!=,>=,<=,>,<,*?*,*?,?*";
		
		// attributes
		protected $attributes = array();
		protected $order = array();
		protected $tableRelationships = array();
		protected $selects = array();
		protected $updates = array();
		protected $db;
		
		public static function isValidComparator($comparator)
		{
			return in_array($comparator, explode(",", self::comparators));
		}
		
		public static function parseAttributeName($attributeName)
		{
			/** parses $attributeName and returns an object
			 * 	with the following properties:
			 * 		
			 * 		$attribute->table = <attribute table>
			 * 		$attribute->name = <attribute name>
			 */
			$parts = explode(".", $attributeName, 2);
			$attributeName = (object) (count($parts) > 1 ? array("table" => strtolower(trim($parts[0])), "name" => strtolower(trim($parts[1]))) : array("table" => "", "name" => strtolower(trim($parts[0]))));
			
			if (!empty($attributeName->table) && !Database::isValidIdentifier($attributeName->table))
				throw new Exception("Invalid table name specified: " . $attributeName->table);
			
			if (!Database::isValidIdentifier($attributeName->name))
				throw new Exception("Invalid attribute name specified: " . $attributeName->name);
			
			return $attributeName;
		}
		
		public static function parseAttribute($attributeString)
		{
			/** parses $attributeString in the form:
			 * 
			 * 		"<attribute name> <comparator> <attribute value>"	
			 * 
			 *	and returns an object with the following properties:
			 * 
			 * 		$attribute->table = <attribute table>
			 * 		$attribute->name = <attribute name>
			 * 		$attribute->comparator = <attribute comparator>
			 * 		$attribute->value = <attribute value>
			 */
			$parts = preg_split("/(" . preg_replace("/,/", "|", preg_quote(self::comparators)) . ")/", $attributeString, 3, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
			
			if (count($parts) != 3)
				throw new Exception("Invalid attribute string specified: " . $attributeString);
			
			$attribute = self::parseAttributeName($parts[0]);
			$attribute->comparator = $parts[1];
			$attribute->value = trim($parts[2]);
							
			if (!self::isValidComparator($attribute->comparator))
				throw new Exception("Invalid comparator specified: " . $attribute->comparator);
			
			return $attribute;
		}
		
		public function __construct($tables = "")
		{
			if (!is_array($tables))
				$tables = func_get_args();
			
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
		
		public function getTables()
		{
			return array_keys($this->attributes);
		}
		
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
		
		public function getOrder()
		{
			return $this->order;
		}
		
		public function removeOrder($attributeName)
		{
			$attribute = self::parseAttributeName($attributeName);
			$attribute->$table = $this->getExistingTable($attribute->$table, true);
			unset($this->order[$attribute->$table . "." . $attribute->name]);
			return $this;
		}
		
		public function clearOrder()
		{
			$this->order = array();
			return $this;
		}
		
		public function getAttribute($attributeName)
		{
			if (empty($attributeName))
				return null;
		
			$attribute = self::parseAttributeName($attributeName);
			$attribute->table = $this->getExistingTable($attribute->table);
			return isset($this->attributes[$attribute->table][$attribute->name]) ? $this->attributes[$attribute->table][$attribute->name]->value : null;
		}
		
		public function getAttributes($table = "", $useShortKeys = false)
		{
			/**
			 * WARNING:	if $useShortKeys is set to true and there are multiple tables that
			 *  		contain the same attribute name, the last entry will overwrite the
			 *  		previous entry in the returned array
			 */
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
				
		public function getAttributeKeys($table = "", $useShortKeys = false)
		{
			return array_keys($this->getAttributes($table, $useShortKeys));
		}
		
		public function setAttribute($attributeString)
		{
			$attribute = self::parseAttribute($attributeString);
			$attribute->table = $this->getExistingTable($attribute->table, true);
			
			if (!in_array($attribute->table, $this->getTables()))
				$this->setTable($attribute->table);
			
			$this->attributes[$attribute->table][$attribute->name] = (object) array("value" => $attribute->value, "comparator" => $attribute->comparator);
			return $this;
		}
		
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
		
		public function removeAttribute($attributeName)
		{
			$attribute = self::parseAttributeName($attributeName);
			$attribute->table = $this->getExistingTable($attribute->table, true);
			unset($this->attributes[$attribute->table][$attribute->name]);
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
			
			array_walk($this->attributes, function(&$attributes)
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
						->clearSelects()
						->clearUpdates();
		}
		
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
		
		public function executeUpdate(&$query = null)
		{
			if (!empty($this->db))
				$this->db->executeUpdate($this, $query);
			else
			{
				$db = Database::getInstance();
				$db->executeUpdate($this, $query);
			}
		}

		public function executeDelete(&$query = null)
		{			
			if (!empty($this->db))
				return $this->db->executeDelete($this, $query);
			else
			{
				$db = Database::getInstance();
				return $db->executeDelete($this, $query);
			}
		}
		
		public function setComparator($attributeName, $comparator)
		{
			$attribute = self::parseAttributeName($attributeName);
			$attribute->table = $this->getExistingTable($attribute->table, true);
		
			if (!isset($this->attributes[$attribute->table][$attribute->name]))
				return $this;
			
			$this->setAttribute($attributeName . " " . $comparator . " " . $this->getAttribute($attributeName));
			
			return $this;
		}
		
		public function getComparator($attributeName)
		{
			$attribute = self::parseAttributeName($attributeName);
			$attribute->table = $this->getExistingTable($attribute->table, true);
			return isset($this->attributes[$attribute->table][$attribute->name]) ? $this->attributes[$attribute->table][$attribute->name]->comparator : null;
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
		
		public function getTableRelationships()
		{
			return $this->tableRelationships;
		}
		
		public function clearTableRelationships()
		{
			$this->tableRelationships = array();
			return $this;
		}
		
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
		
		public function getSelects()
		{
			return $this->selects;
		}
		
		public function clearSelects()
		{
			$this->selects = array();
			return $this;
		}
		
		public function setUpdate($attributeName, $value)
		{
			$attribute = self::parseAttributeName($attributeName);
		
			if (!Database::isValidIdentifier($attribute->name))
				throw new Exception("Invalid attribute specified: " . $attribute->name);
		
			$attribute->table = $this->getExistingTable($attribute->table);
			$this->updates[$attribute->table . "." . $attribute->name] = $value;
			return $this;
		}
		
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
		
		public function removeUpdate($attribute)
		{
			unset($this->updates[$attribute]);
			return $this;
		}
		
		public function getUpdate($attribute)
		{
			return isset($this->updates[$attribute]) ? $this->updates[$attribute] : null;
		}
		
		public function getUpdates($table = "", $useShortKeys = false)
		{
			/**
			 * WARNING:	if $useShortKeys is set to true and there are multiple tables that
			 *  		contain the same attribute name, the last entry will override the
			 *  		previous entry in the returned array
			 */
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
		
		public function clearUpdates()
		{
			$this->updates = array();
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
				if (!Database::isValidIdentifier($table))
					throw new Exception("The table specified is invalid: " . $table);
				
				if (!$ignoreMissing && !isset($this->attributes[strtolower($table)]))
					throw new Exception("The table specified does not exist: " . $table);
			}
		
			return strtolower($table);
		}
	}
?>
