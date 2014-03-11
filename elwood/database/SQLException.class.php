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