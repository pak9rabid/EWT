<?php
	namespace elwood\database;
	
	class DbQueryPreper
	{
		// Attribute
		protected $query;
		protected $bindVars = array();
		
		// Constructors
		public function __construct($sql)
		{
			$this->query = $sql;
		}
				
		public function addSql($sql)
		{
			$this->query .= $sql;
		}
		
		public function addVariable($bindVar)
		{
			$this->query .= "?";
			$this->bindVars[] = $bindVar;
		}
		
		public function addVariables(array $bindVars, $delimiter = ",")
		{
			$this->query .= implode($delimiter, array_pad(array(), count($bindVars), "?"));
			$this->addVariablesNoPlaceholder($bindVars);
		}
		
		public function addVariablesNoPlaceholder(array $bindVars)
		{
			$this->bindVars = array_merge($this->bindVars, $bindVars);
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
			
			$db = Database::getInstance(Database::getConnectionConfig());
			return $db->executeQuery($this);
		}
	}
?>