<?php
	namespace elwood\database;
	
	use Exception;
	
	class SQLException extends Exception
	{
		protected $query;
		protected $errorCode;
		protected $errorMessage;
		
		public function __construct($message = "Error executing SQL query", $query = "", $errorCode = "", $errorMessage = "")
		{
			parent::__construct($message);
			$this->setQuery($query);
			$this->setErrorCode($errorCode);
			$this->setErrorMessage($errorMessage);
		}
		
		public function setQuery($query)
		{
			$this->query = $query;
		}
		
		public function setErrorCode($errorCode = "")
		{
			if (!empty($errorCode) && !preg_match("/^[0-9A-Z]{5}$/", $errorCode))
				throw new Exception("Invalid error code");
			
			$this->errorCode = $errorCode;
		}
		
		public function setErrorMessage($errorMessage = "")
		{
			$this->errorMessage = $errorMessage;
		}
		
		public function getQuery()
		{
			return $this->query;
		}
		
		public function getErrorCode()
		{
			return $this->errorCode;
		}
		
		public function getErrorMessage()
		{
			return $this->errorMessage;
		}
	}
?>