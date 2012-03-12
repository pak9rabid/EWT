<?php
	namespace elwood\database;
	use PDO;
	
	class SqliteDatabase extends Database
	{
		public function __construct(ConnectionConfig $config)
		{
			$this->dsn = "sqlite:" . $config->getDatabase();
			$this->pdo = new PDO($this->dsn);
			$this->pdo->exec("PRAGMA foreign_keys = ON");
		}
	}
?>