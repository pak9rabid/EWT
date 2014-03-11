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

	namespace elwood\ajax;
	
	use stdClass;
	
	/**
	 * Ajax response
	 * 
	 * A response from an ajax request handler
	 * 
	 * @author Patrick Griffin <pak9rabid@yahoo.com>
	 */
	class AjaxResponse
	{
		protected $data;
		protected $errors = array();
	
		/**
		 * Constructor
		 * 
		 * Constructs an ajax response
		 * 
		 * @param string $data Data to be encapsulated in the ajax response
		 * @param array $errors Errors to be encapsulated in the ajax response
		 */
		public function __construct($data = "", array $errors = array())
		{
			$this->data = $data;
			$this->errors = $errors;
		}
	
		/**
		 * Check for errors
		 * 
		 * Checks if the ajax response contains any errors.
		 * 
		 * @return boolean true if the response contains errors, false otherwise
		 */
		public function hasErrors()
		{
			return count($this->errors) > 0;
		}
	
		/**
		 * Get ajax response data
		 * 
		 * Gets the data associated with this ajax response.
		 * 
		 * @return mixed The response data associated with the ajax response
		 */
		public function getData()
		{
			return $this->data;
		}
		
		/**
		 * Get ajax response errors
		 * 
		 * Gets any errors associated with this ajax response.
		 * 
		 * @return array The errors associated with this ajax response
		 */
		public function getErrors()
		{
			return $this->errors;
		}
	
		/**
		 * JSON-encode the ajax response
		 * 
		 * Encodes the properties of the ajax response as a JSON object
		 * 
		 * @return string A JSON representation of the ajax response
		 */
		public function toJson()
		{
			$json = new stdClass();
			
			foreach ($this as $key => $value)
				@$json->$key = $value;
	
			return json_encode($json);
		}
	}
?>