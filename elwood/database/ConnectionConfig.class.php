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