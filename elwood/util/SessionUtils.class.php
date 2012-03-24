<?php
	namespace elwood\util;
	use elwood\database\DataHash;
	
	class SessionUtils
	{
		public static function putUser(DataHash $user)
		{
			$_SESSION['user'] = serialize($user);
		}
		
		public static function getUser()
		{
			if (isset($_SESSION['user']))
				return unserialize($_SESSION['user']);
			else
				return null;
		}
		
		public static function logout()
		{
			unset($_SESSION['user']);
		}
	}
?>