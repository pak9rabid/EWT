<?php
	namespace elwood\database;
	use PDO;
	
	class PostgresDatabase extends Database
	{
		const DEFAULT_PORT = 5432;
		
		public function __construct(ConnectionConfig $config)
		{
			$port = $config->getPort();
			$port = empty($port) ? self::DEFAULT_PORT : $port;
			
			$this->dsn = "pgsql:host=" . $config->getHost() . ";port=" . $port . ";dbname=" . $config->getDatabase();
			$this->pdo = new PDO($this->dsn, $config->getUsername(), $config->getPassword());
		}
	}
?>