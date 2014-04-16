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
	
	/**
	 * The SQLException class
	 * 
	 * An Exception to be thrown when problems arise accessing a database.
	 * 
	 * @author pgriffin
	 */
	class SQLException extends Exception
	{
		protected $query;
		protected $errorCode;
		protected $errorMessage;
		
		/**
		 * Constructor
		 * 
		 * Creates an SQLException instance.
		 * 
		 * @param string $message Optional. A short summary message of the
		 * exception
		 * @param string $query Optional. The offending SQL query that caused
		 * the database error
		 * @param string $errorCode Optional. The SQLSTATE error code (a five
		 * characters alphanumeric identifier defined in the ANSI SQL standard)
		 * @param string $errorMessage Optional. The PDO driver-specific error
		 * message 
		 */
		public function __construct($message = "Error executing SQL query", $query = "", $errorCode = "", $errorMessage = "")
		{
			parent::__construct($message);
			$this->setQuery($query);
			$this->setErrorCode($errorCode);
			$this->setErrorMessage($errorMessage);
		}
		
		/**
		 * Set the query
		 * 
		 * Sets the SQL query associated with the exception
		 * 
		 * @param string $query The SQL query
		 */
		public function setQuery($query)
		{
			$this->query = $query;
		}
		
		/**
		 * Set the error code
		 * 
		 * Sets the SQLSTAT error code (a five characters alphanumeric
		 * identifier defined in the ANSI SQL standard).
		 * 
		 * @param string The SQLSTAT error code
		 */
		public function setErrorCode($errorCode = "")
		{
			$this->errorCode = preg_match("/^[0-9A-Z]{5}$/", $errorCode)
				? $errorCode
				: "";
		}
		
		/**
		 * Set the error message
		 * 
		 * Sets the PDO driver-specific error message.
		 * 
		 * @param string $errorMessage The PDO driver-specific error message
		 */
		public function setErrorMessage($errorMessage = "")
		{
			$this->errorMessage = $errorMessage;
		}
		
		/**
		 * Get the debug query
		 * 
		 * Gets the debug query of the offending SQL query.
		 * 
		 * @return string The debug SQL query
		 */
		public function getQuery()
		{
			return $this->query;
		}
		
		/**
		 * Get the error code
		 * 
		 * Gets the SQLSTAT error code (a five characters alphanumeric 
		 * identifier defined in the ANSI SQL standard)
		 * 
		 * @return string The error code
		 */
		public function getErrorCode()
		{
			return $this->errorCode;
		}
		
		/**
		 * Get the error message
		 * 
		 * Gets the PDO driver-specific error message.
		 * 
		 * @return string The error message
		 */
		public function getErrorMessage()
		{
			return $this->errorMessage;
		}
	}
?>