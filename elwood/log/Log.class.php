<?php
/**
 Copyright (c) 2012 Patrick Griffin

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
	
	class Log
	{
		public static function getFilePath()
		{
			return __DIR__ . DIRECTORY_SEPARATOR . "ewt-" . date("Ymd") . ".log";
		}
		
		public static function write(Event $event)
		{
			$filePath = self::getFilePath();
			
			if (!$handle = fopen($filePath, "a"))
				throw new Exception("Unable to open log file for writing: " . $filePath);
			
			fwrite($handle, $event . "\n");
			fclose($handle);
			
			return $event->getTimestamp();
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