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

	namespace elwood\log;
	
	use Exception;
	use elwood\config\Config;
	
	class Log
	{
		public static function getPath()
		{
			$configPath = Config::getInstance()->getSetting(Config::OPTION_LOG_PATH);
			
			/** no path set (can be the case when 'log.type = system', or if logging is disabled */
			if (empty($configPath))
				return false;
			
			if (preg_match("/^(\/|[A-Za-z]:\\\\)/", $configPath))
				/** absolute path */
				return $configPath;
			
			/** relative path */
			return implode(DIRECTORY_SEPARATOR, array(__DIR__, "..", "..", $configPath));
		}
		
		public static function write(Event $event)
		{
			$config = Config::getInstance();
			
			if (!$config->getSetting(Config::OPTION_LOG_ENABLED))
				return false;
			
			switch ($config->getSetting(Config::OPTION_LOG_TYPE))
			{
				case "system":
					openlog("ewt", LOG_ODELAY, LOG_SYSLOG);
					syslog($event->getEventType() === Event::EVENT_TYPE_INFO ? LOG_INFO :
							$event->getEventType() === Event::EVENT_TYPE_ALERT ? LOG_ALERT :
							$event->getEventType() === Event::EVENT_TYPE_ERROR ? LOG_ERR : LOG_NOTICE,
							$event);
					closelog();
					break;
					
				case "rotating":
					if (!$handle = @fopen(Log::getPath() . DIRECTORY_SEPARATOR . self::getRotatingLogFile(), "a"))
						return false;
					
					fwrite($handle, $event . "\n");
					fclose($handle);
					break;
					
				case "flat":
					if (!$handle = @fopen(Log::getPath(), "a"))
						return false;
					
					fwrite($handle, $event . "\n");
					fclose($handle);
					break;
					
				default:
					return false;
			}
			
			return $event->getTimestamp();
		}
		
		public static function getRotatingLogFile()
		{
			return "ewt-" . date("Ymd") . ".log";
		}
		
		public static function writeInfo($message)
		{
			return self::write(new Event(Event::EVENT_TYPE_INFO, $message));
		}
		
		public static function writeAlert($message)
		{
			return self::write(new Event(Event::EVENT_TYPE_ALERT, $message));
		}
		
		public static function writeError($message)
		{
			if ($message instanceof Exception)
				$message = $message->getMessage();
			
			return self::write(new Event(Event::EVENT_TYPE_ERROR, $message));
		}
	}
?>