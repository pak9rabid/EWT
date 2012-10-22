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
	
	class Event
	{
		const EVENT_TYPE_INFO = 0;
		const EVENT_TYPE_ALERT = 1;
		const EVENT_TYPE_ERROR = 2;
		
		protected $eventType;
		protected $message;
		protected $timestamp;
		
		public static function isValidEventType($eventType)
		{
			return in_array($eventType, array(self::EVENT_TYPE_INFO, self::EVENT_TYPE_ALERT, self::EVENT_TYPE_ERROR));
		}
		
		public function __construct($eventType, $message, $timestamp = null)
		{
			$this->setEventType($eventType);
			$this->setMessage($message);
			$this->setTimestamp($timestamp);
		}
		
		public function setEventType($eventType)
		{
			if (!self::isValidEventType($eventType))
				throw new Exception("The specified event type does not exist: " . $eventType);
			
			$this->eventType = $eventType;
			return $this;
		}
		
		public function setMessage($message)
		{
			$message = trim($message);
			
			if (empty($message))
				throw new Exception("Event message must be a non-empty value");
			
			$this->message = $message;
			return $this;
		}
		
		public function setTimestamp($timestamp = null)
		{			
			if (!is_int($timestamp = empty($timestamp) ? time() : $timestamp))
				throw new Exception("Invalid timestamp specified: " . $timestamp);
			
			$this->timestamp = $timestamp;
			return $this;
		}
		
		public function getEventType()
		{
			return $this->eventType;
		}
		
		public function getMessage()
		{
			return $this->message;
		}
		
		public function getTimestamp()
		{
			return $this->timestamp;
		}
		
		public function __toString()
		{
			switch ($this->eventType)
			{
				case self::EVENT_TYPE_INFO:
					$eventCode = "I";
					break;
					
				case self::EVENT_TYPE_ALERT:
					$eventCode = "A";
					break;
					
				case self::EVENT_TYPE_ERROR:
					$eventCode = "E";
					break;
			}
			
			return "<" .$eventCode . "> [" . date("m-d-Y H:i:s", $this->timestamp) . "] " . $this->message;
		}
	}
?>