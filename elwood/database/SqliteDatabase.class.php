<?php
/**
 Copyright (c) 2014 Patrick Griffin

 Permission is hereby granted, free of charge, to any person obtaining
 a copy of this software and associated documentation files (the
 "Software"), to deal in the Software without restriction, including
 without limitation the rights to use, copy, modify, merge, publish,
 distribute, sublicense, and/or sell copies of the Software, and to
 permit persons to whom the Software is furnished to do so, subject to
 the following conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

	namespace elwood\database;
	use PDO;
	use Exception;
	use elwood\config\Config;
	
	/**
	 * A connection to an SQLite database
	 *
	 * The implementing class for accessing an SQLite database.
	 *
	 * @author pgriffin
	 */
	class SqliteDatabase extends Database
	{
		/**
		 * Constructor
		 *
		 * Creates a connection to an SQLite database.
		 *
		 * @param Config $config An EWT configuration object.
		 */
		public function __construct(Config $config)
		{
			parent::__construct($config);
			$this->dsn = "sqlite:" . $config->getSetting(Config::OPTION_DB_DATABASE);
			$this->pdo = new PDO($this->dsn);
			$this->pdo->exec("PRAGMA foreign_keys = ON");
		}
		
		/**
		 * Executes a SELECT database query
		 * 
		 * Generates and executes a SELECT database query based on properties set in $dm
		 * 
		 * @param DataModel $dm The DataModel object to build the query from
		 * @param string $query If not null, the generated query will be placed here
		 * @throws Exception If $dm has no tables set
		 * @return array An array of DataModel objects representing the result set of the query
		 */
		public function executeSelect(DataModel $dm, &$query = null)
		{
			$offset = $dm->getOffset();
			$limit = $dm->getLimit();
			
			if (empty($limit) && !empty($offset))
				throw new Exception("SQLite does not allow queries that contain OFFSET without LIMIT");
			
			return parent::executeSelect($dm, $query);
		}
	}
?>