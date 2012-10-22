<?php
/**
 *	Copyright (c) 2012 Patrick Griffin - All rights reserved
 */

	namespace elwood\test;
	
	use Exception;

	class TestFailedException extends Exception
	{
		private $testName;

		public function __construct($message, $testName)
		{
			parent::__construct($message);
			$this->testName = $testName;
		}

		public function getTestName()
		{
			return $this->testName;
		}
	}
?>