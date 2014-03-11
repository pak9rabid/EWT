<?php
/**
 *	Copyright (c) 2014 Patrick Griffin - All rights reserved
 */

	namespace elwood\test;
	
	use Closure;
	use Exception;
	use elwood\database\SQLException;
	
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
				catch (SQLException $ex)
				{
					$errors[] = "Exception running query: " . $ex->getQuery() . ": " . $ex->getErrorMessage();
				}
				catch (Exception $ex)
				{
					$errors[] = "Exception encountered: " . $ex->getMessage();
				}
			}
			
			return $errors;
		}
	}
?>