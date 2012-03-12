<?php 
	namespace elwood\database;
	use \Exception;
	
	class ConnectionConfig
	{
		private $databaseType;
		private $host;
		private $port;
		private $database;
		private $username;
		private $password;
		
		public function __construct($databaseType, $host = "", $port = "", $database = "", $username = "", $password = "")
		{			
			$this->setDatabaseType($databaseType);
			$this->setHost($host);
			$this->setPort($port);
			$this->setDatabase($database);
			$this->setUsername($username);
			$this->setPassword($password);
		}
				
		public function getDatabaseType()
		{
			return $this->databaseType;
		}
		
		public function getHost()
		{
			return $this->host;
		}
		
		public function getPort()
		{
			return $this->port;
		}
		
		public function getDatabase()
		{
			return $this->database;
		}
		
		public function getUsername()
		{
			return $this->username;
		}
		
		public function getPassword()
		{
			return $this->password;
		}
		
		public function setDatabaseType($databaseType)
		{
			if (!in_array($databaseType, Database::getSupportedDatabaseTypes()))
				throw new Exception("Unsupported database type specified");
			
			$this->databaseType = $databaseType;
		}
		
		public function setHost($host = "")
		{
			$this->host = $host;
		}
		
		public function setPort($port = "")
		{			
			if (!empty($port) && !Database::isValidIanaPortNumber($port))
				throw new Exception("Invalid port number specified");
			
			$this->port = $port;
		}
		
		public function setDatabase($database = "")
		{
			$this->database = $database;
		}
		
		public function setUsername($username = "")
		{
			$this->username = $username;
		}
		
		public function setPassword($password = "")
		{
			$this->password = $password;
		}
	}
?>