<?php
/**
 *	Copyright (c) 2012 Patrick Griffin - All rights reserved
 */

	namespace elwood\test;
	
	use Closure;
	use Exception;
	
	class Test
	{
		public static function runTests(array $tests)
		{
			$errors = array();
			
			foreach ($tests as $test)
			{
				try
				{
					if (!($test instanceof Closure))
						continue;
					
					$test();
				}
				catch (TestFailedException $ex)
				{
					$errors[] = "Test failed: " . $ex->getTestName() . ": " . $ex->getMessage();
				}
				catch (Exception $ex)
				{
					$errors[] = "Exception at " . $ex->getFile() . ", line " . $ex->getLine() . ": " . $ex->getMessage();
				}
			}
			
			return $errors;
		}
	}
?>