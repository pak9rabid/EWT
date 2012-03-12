<?php
	namespace elwood\database;
	use PDO;
	
	class OracleDatabase extends Database
	{
		const DEFAULT_PORT = 1521;
		protected function __construct(ConnectionConfig $config)
		{
			$port = $config->getPort();
			$port =  empty($port) ? self::DEFAULT_PORT : $port;
			
			$this->dsn = "oci:dbname=//" . $config->getHost() . ":" . $port . "/" . $config->getDatabase() . ";charset=utf8";
			$this->pdo = new PDO($this->dsn, $config->getUsername(), $config->getPassword());
		}
	}
?>