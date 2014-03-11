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
	
	class DbQueryPreper
	{
		// Attributes
		protected $query;
		protected $bindVars = array();
		
		// Constructors
		public function __construct($sql = "")
		{
			$this->query = $sql;
		}
				
		public function addSql($sql)
		{
			$this->query .= $sql;
			
			return $this;
		}
		
		public function addPrep(DbQueryPreper $prep)
		{
			$this->query .= $prep->getQuery();
			$this->bindVars = array_merge($this->bindVars, $prep->getBindVars());
		}
		
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
		
		public function addVariables(array $bindVars, $delimiter = ",")
		{
			$this->query .= implode($delimiter, array_pad(array(), count($bindVars), "?"));
			$this->addVariablesNoPlaceholder($bindVars);
			return $this;
		}
		
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
		
		public function getQuery()
		{
			return $this->query;
		}
		
		public function getQueryDebug()
		{
			$debugQuery = $this->query;
			
			foreach ($this->bindVars as $bindVar)
				$debugQuery = preg_replace("/\?/", ($bindVar->type == PDO::PARAM_STR ? "'" . $bindVar->value . "'" : $bindVar->value), $debugQuery, 1);
			
			return $debugQuery;
		}
		
		public function getBindVars()
		{
			return $this->bindVars;
		}
		
		public function execute(Database $conn = null)
		{
			if (!empty($conn))
				return $conn->executeQuery($this);
			
			$db = Database::getInstance();
			return $db->executeQuery($this);
		}
	}
?>