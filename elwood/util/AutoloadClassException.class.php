<?php
	namespace elwood\util;
	use Exception;
	
	class AutoloadClassException extends Exception
	{
		public function __construct($message = "Failed to autoload class")
		{
			parent::__construct($message);
		}
	}
?>