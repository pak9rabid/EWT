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
	
	/**
	 * The EWT log class
	 * 
	 * Logs events to the EWT log.
	 * 
	 * @author pgriffin
	 */
	class Log
	{
		/**
		 * DEFAULT_LOG_FILE_PREFIX
		 * 
		 * The default log file prefix that gets prepended to rotating log
		 * files when none is specified.
		 * 
		 * @var string
		 */
		const DEFAULT_LOG_FILE_PREFIX = "ewt";
		
		/**
		 * Convert an Exception into a string
		 * 
		 * Converts the specified exception into a loggable event message.
		 * The exception message, file and line where the Exception was thrown,
		 * and a full stack trace is represented into the generated message.
		 * 
		 * @param Exception $ex The Exception to convert
		 * @return string A string representation of the Exception for logging
		 * purposes
		 */
		public static function exceptionToMessage(Exception $ex)
		{
			return
				"Exception (" . get_class($ex) . ") encountered at: " . $ex->getFile() . ":" . $ex->getLine() . ": " . $ex->getMessage() . "\n" .
				"Stack trace:\n" .
				$ex->getTraceAsString();
		}
		
		/**
		 * Get the log file path
		 * 
		 * Gets the path to the log file directory, as specified in the EWT
		 * configuration file.
		 * 
		 * @param string $subdir Optional. If specified, the returned path will
		 * append the specified sub-directory
		 * @return string The log file path, or false if the log path is not
		 * specified or irrelevent (as is the case when the log typs is set
		 * to 'system')
		 */
		public static function getPath($subdir = null)
		{
			$configPath = Config::getInstance()->getSetting(Config::OPTION_LOG_PATH);
			
			// no path set (can be the case when 'log.type = system', or if logging is disabled
			if (empty($configPath))
				return false;
			
			if (preg_match("/^(\/|[A-Za-z]:\\\\)/", $configPath))
				// absolute path
				return empty($subdir)
					? $configPath
					: $configPath . DIRECTORY_SEPARATOR . $subdir;
			
			// relative path
			return Config::configDir() . DIRECTORY_SEPARATOR . (empty($subdir) ? $configPath : $configPath . DIRECTORY_SEPARATOR . $subdir);
		}
		
		/**
		 * Write the event to the log
		 * 
		 * Writes the specified Event to the EWT log
		 * 
		 * @param Event $event The event to write tot he log
		 * @param string $prefix Optional. If specified, the event will be written
		 * to the log file with the specified prefix, otherwise the default prefix
		 * is assumed
		 * @param string $subdir Optional. If specified, the event will be written
		 * to a log file in the specified sub-directory
		 * @return int|bool The timestamp of the time the log entry was written,array
		 * or false if writing to the log failed 
		 */
		public static function write(Event $event, $prefix = self::DEFAULT_LOG_FILE_PREFIX, $subdir = null)
		{
			$config = Config::getInstance();
			
			if (!$config->getSetting(Config::OPTION_LOG_ENABLED))
				return false;
			
			switch ($config->getSetting(Config::OPTION_LOG_TYPE))
			{
				case "system":
					openlog($prefix, LOG_ODELAY, LOG_SYSLOG);
					syslog($event->getEventType() === Event::EVENT_TYPE_INFO ? LOG_INFO :
							$event->getEventType() === Event::EVENT_TYPE_ALERT ? LOG_ALERT :
							$event->getEventType() === Event::EVENT_TYPE_ERROR ? LOG_ERR : LOG_NOTICE,
							$event);
					closelog();
					break;
					
				case "rotating":
					if (!$handle = @fopen(Log::getPath($subdir) . DIRECTORY_SEPARATOR . self::getRotatingLogFile($prefix), "a"))
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
		
		/**
		 * Get the rotating log file
		 * 
		 * Gets the rotating log file.
		 * 
		 * @param string $prefix Optional. If specified, the log file will be
		 * returned with the specified prefix, otherwise the default prefix is
		 * assumed.
		 * @return string The name of the rotating log file
		 */
		public static function getRotatingLogFile($prefix = self::DEFAULT_LOG_FILE_PREFIX)
		{
			return $prefix . "-" . date("Ymd") . ".log";
		}
		
		/**
		 * Write an informational event to the log
		 * 
		 * Writes an informational event to the log file.
		 * 
		 * @param Exception|string $message The log message.  If message is
		 * an Exception then the full stack trace will be logged, as well as the
		 * encapsulated message.
		 * @param string $prefix Optional. If specified, the event will be written
		 * to the log file with the specified prefix, otherwise the default prefix
		 * is assumed
		 * @param string $subdir Optional. If specified, the event will be written
		 * to a log file in the specified sub-directory
		 * @return int|bool The timestamp of the log entry, or false if writing
		 * to the log failed
		 */
		public static function writeInfo($message, $prefix = self::DEFAULT_LOG_FILE_PREFIX, $subdir = null)
		{
			if ($message instanceof Exception)
				$message = self::exceptionToMessage($message);
			
			return self::write(new Event(Event::EVENT_TYPE_INFO, $message), $prefix, $subdir);
		}
		
		/**
		 * Write an alert event to the log
		 *
		 * Writes an alert event to the log file.
		 *
		 * @param Exception|string $message The log message.  If message is
		 * an Exception then the full stack trace will be logged, as well as the
		 * encapsulated message
		 * @param string $prefix Optional. If specified, the event will be written
		 * to the log file with the specified prefix, otherwise the default prefix
		 * is assumed
		 * @param string $subdir Optional. If specified, the event will be written
		 * to a log file in the specified sub-directory
		 * @return int|bool The timestamp of the log entry, or false if writing
		 * to the log failed
		 */
		public static function writeAlert($message, $prefix = self::DEFAULT_LOG_FILE_PREFIX, $subdir = null)
		{
			if ($message instanceof Exception)
				$message = self::exceptionToMessage($message);
				
			return self::write(new Event(Event::EVENT_TYPE_ALERT, $message), $prefix, $subdir);
		}
		
		/**
		 * Write an error event to the log
		 *
		 * Writes an error event to the log file.
		 *
		 * @param Exception|string $message The log message.  If message is
		 * an Exception then the full stack trace will be logged, as well as the
		 * encapsulated message
		 * @param string $prefix Optional. If specified, the event will be written
		 * to the log file with the specified prefix, otherwise the default prefix
		 * is assumed
		 * @param string $subdir Optional. If specified, the event will be written
		 * to a log file in the specified sub-directory
		 * @return int|bool The timestamp of the log entry, or false if writing
		 * to the log failed
		 */
		public static function writeError($message, $prefix = self::DEFAULT_LOG_FILE_PREFIX, $subdir = null)
		{
			if ($message instanceof Exception)
				$message = self::exceptionToMessage($message);
				
			return self::write(new Event(Event::EVENT_TYPE_ERROR, $message), $prefix, $subdir);
		}
	}
?>