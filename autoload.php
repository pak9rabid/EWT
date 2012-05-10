<?php
	use elwood\util\AutoloadClassException;
	
	spl_autoload_register(function($class)
	{
		$ds = DIRECTORY_SEPARATOR;
		$classPath = __DIR__ . $ds . str_replace("\\", $ds, $class) . ".class.php";
						
		if (!is_readable($classPath))
			throw new AutoloadClassException("The class to autoload couldn't be read: $classPath");
		
		require_once $classPath;
	});
?>