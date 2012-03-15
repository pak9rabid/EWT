<?php
	namespace elwood\util;
	
	class SessionUtils
	{
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