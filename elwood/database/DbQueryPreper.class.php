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
	
	class DbQueryPreper
	{
		// Attribute
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
		
		public function addVariable($bindVar)
		{
			$this->query .= "?";
			$this->bindVars[] = $bindVar;
			
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
			$this->bindVars = array_merge($this->bindVars, $bindVars);
			
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
			{
				$debugQuery = preg_replace("/\?/", "'$bindVar'", $debugQuery, 1);
			}
			
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