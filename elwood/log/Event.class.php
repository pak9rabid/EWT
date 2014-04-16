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
	
	class Event
	{
		/**
		 * EVENT_TYPE_INFO
		 * 
		 * Informational events
		 * 
		 * @var int
		 */
		const EVENT_TYPE_INFO = 0;
		
		/**
		 * EVENT_TYPE_ALERT
		 * 
		 * Alert events
		 * 
		 * @var int
		 */
		const EVENT_TYPE_ALERT = 1;
		
		/**
		 * EVENT_TYPE_ERROR
		 * 
		 * Error events.
		 * 
		 * @var int
		 */
		const EVENT_TYPE_ERROR = 2;
		
		protected $eventType;
		protected $message;
		protected $timestamp;
		
		/**
		 * Validate event type
		 * 
		 * Validates the specified event type.
		 * 
		 * @param int $eventType The type of event
		 * @return boolean If the event type if valid, false otherwise
		 */
		public static function isValidEventType($eventType)
		{
			return in_array($eventType, array(self::EVENT_TYPE_INFO, self::EVENT_TYPE_ALERT, self::EVENT_TYPE_ERROR));
		}
		
		/**
		 * Constructor
		 * 
		 * Creates an event instance.
		 * 
		 * @param int $eventType The type of event (informational, alert, or error)
		 * @param string $message The message associated with the event
		 * @param string $timestamp Optional. The time associated with the
		 * event.  If empty, the current time will be assumed.
		 */
		public function __construct($eventType, $message, $timestamp = null)
		{
			$this->setEventType($eventType);
			$this->setMessage($message);
			$this->setTimestamp($timestamp);
		}
		
		/**
		 * Set the event type
		 * 
		 * Sets the event type as either informational, alert, or an error event.
		 * 
		 * @param int $eventType
		 * @throws Exception If the event type is invalid
		 * @return Event $this (for method chaining)
		 */
		public function setEventType($eventType)
		{
			if (!self::isValidEventType($eventType))
				throw new Exception("The specified event type does not exist: " . $eventType);
			
			$this->eventType = $eventType;
			return $this;
		}
		
		/**
		 * Set the event message
		 * 
		 * Sets the message associated with the event.
		 * 
		 * @param string $message The event message
		 * @throws Exception If the message is empty
		 * @return Event $this (for method chaining)
		 */
		public function setMessage($message)
		{
			$message = trim($message);
			
			if (empty($message))
				throw new Exception("Event message must be a non-empty value");
			
			$this->message = $message;
			return $this;
		}
		
		/**
		 * Set the event timestamp
		 * 
		 * Sets the timestamp associated with the event.
		 * 
		 * @param int $timestamp The timestamp in the form of a Unix timestamp
		 * (number of seconds since the Unix Epoch)
		 * @throws Exception If an invalid timestamp is specified
		 * @return Event $this (for method chaining)
		 */
		public function setTimestamp($timestamp = null)
		{			
			if (!is_int($timestamp = empty($timestamp) ? time() : $timestamp))
				throw new Exception("Invalid timestamp specified: " . $timestamp);
			
			$this->timestamp = $timestamp;
			return $this;
		}
		
		/**
		 * Get the event type
		 * 
		 * Gets the type of event associated with this event.
		 * 
		 * @return int The event type
		 */
		public function getEventType()
		{
			return $this->eventType;
		}
		
		/**
		 * Get the event message
		 * 
		 * Gets the message associated with this event.
		 * 
		 * @return string The event message
		 */
		public function getMessage()
		{
			return $this->message;
		}
		
		/**
		 * Get the event timestamp
		 * 
		 * Gets the timestamp associated with this event.
		 * 
		 * @return int The event timestamp in the form of a Unix timestamp
		 * (number of seconds since the Unix Epoch)
		 */
		public function getTimestamp()
		{
			return $this->timestamp;
		}
		
		/**
		 * Event string representation
		 *
		 * A string representation of the event.
		 *
		 * @return string The event represented as a string
		 */
		public function toString()
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
				
			return "<" . $eventCode . "> [" . date("m-d-Y H:i:s", $this->timestamp) . "] " . $this->message;
		}
		
		/**
		 * String conversion magic method
		 * 
		 * Converts the event to a string whenever a string transformation of
		 * it is expected.
		 * 
		 * @return string The event represented as a string
		 */
		public function __toString()
		{
			return $this->toString();
		}
	}
?>