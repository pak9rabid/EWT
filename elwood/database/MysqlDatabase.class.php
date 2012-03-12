<?php
	namespace elwood\database;
	use PDO;
	
	class MysqlDatabase extends Database
	{
		const DEFAULT_PORT = 3306;
		
		public function __construct(ConnectionConfig $config)
		{
			$port = $config->getPort();
			$port = empty($port) ? self::DEFAULT_PORT : $port;
			
			$this->dsn = "mysql:host=" . $config->getHost() . ";port=" . $port . ";dbname=" . $config->getDatabase();
			$this->pdo = new PDO($this->dsn, $config->getUsername(), $config->getPassword(), array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
		}
	}
?>