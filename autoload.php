<?php
	use elwood\util\AutoloadClassException;
	
	spl_autoload_register(function($class)
	{
		$classPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $class) . ".class.php";
						
		if (!is_readable($classPath))
			throw new AutoloadClassException("The class to autoload couldn't be read: $classPath");
		
		require_once $classPath;
	});
?>