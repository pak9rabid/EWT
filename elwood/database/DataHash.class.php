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

	class DataHash
	{
		// Attributes
		protected $table = "";
		protected $primaryKey = "id";
		protected $orderBy = array();
		protected $hashMap = array();
		protected $conn;

		// Constructors
		public function __construct($table = "")
		{
			$this->table = $table;
		}

		// Methods
		public function __toString()
		{
			$elements = array();

			foreach ($this->hashMap as $key => $value)
				$elements[] = "$key=$value";

			return "{" . implode(", ", $elements) . "}";
		}
		
		public function toJson()
		{
			return json_encode($this->hashMap);
		}
		
		public function setConnection(Database $conn)
		{
			$this->conn = $conn;
		}

		public function setTable($table)
		{
			$this->table = $table;
		}

		public function getTable()
		{
			return $this->table;
		}

		public function setPrimaryKey($primaryKey)
		{
			$this->primaryKey = $primaryKey;
		}
		
		public function setOrderBy(array $orderByList)
		{			
			$this->orderBy = $orderByList;
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
			if (isset($this->hashMap[$key]))
				return $this->hashMap[$key];
				
			return null;
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

		public function setAttribute($key, $value)
		{
			$this->hashMap[$key] = $value;
		}

		public function setAllAttributes($hashMap)
		{
			$this->hashMap = $hashMap;
		}

		public function removeAttribute($key)
		{
			unset($this->hashMap[$key]);
		}

		public function clear()
		{
			$this->hashMap = array();
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
		}

		public function executeDelete()
		{			
			if (!empty($this->conn))
				$this->conn->executeDelete($this);
			else
			{
				$db = Database::getInstance(Database::getConnectionConfig());
				$db->executeDelete($this);
			}
		}
		
		public function getAttributeDisp($attribute)
		{
			return $this->getAttribute($attribute) == null ? "*" : $this->getAttribute($attribute);
		}
	}
?>
