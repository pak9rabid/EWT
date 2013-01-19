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

	namespace elwood\ajax;
	
	use ErrorException;
	use Exception;
	
	/**
	 * Top-level class for all ajax request handlers.
	 * 
	 * All ajax request handlers must extend this class.
	 * 
	 * @author Patrick Griffin <pak9rabid@yahoo.com>
	 */
	abstract class AjaxRequestHandler
	{
		protected $parameters = array();
		
		/**
		 * Constructor
		 * 
		 * Creates a new AjaxRequestHandler object.
		 * 
		 * @param array $parameters The HTTP request parameters
		 */
		abstract public function __construct(array &$parameters);
		
		/**
		 * Process the ajax request
		 * 
		 * Processes the incoming ajax request.
		 * 
		 * @param array $parameters A copy of the $_REQUEST superglobal array
		 * @return AjaxResponse The ajax response
		 */
		abstract public function processRequest();
		
		/**
		 * Handle an ajax request
		 * 
		 * This is the entry method for handling ajax HTTP requests.
		 * 
		 * @param array $parameters HTTP request parameters
		 * @return AjaxResponse The ajax response
		 */
		public static function handle(array $parameters = array())
		{
			set_error_handler(function($errno, $errstr, $errfile, $errline)
			{
				if ($errno === E_RECOVERABLE_ERROR)
					throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
			});
			
			try
			{
				if (!isset($parameters['handler']))
					throw new Exception("No ajax request handler specified");
				
				$requestHandler = trim($parameters['handler']);
				
				if (empty($requestHandler))
					throw new Exception("No ajax request handler specified");
				
				$requestHandlerClass = "elwood\\ajax\\" . $requestHandler . "AjaxRequestHandler";
				$requestHandlerObj = new $requestHandlerClass($parameters);
				
				if (!($requestHandlerObj instanceof AjaxRequestHandler))
					throw new Exception("The specified ajax request handler ($requestHandler) does not extend the AjaxRequestHandler class");
				
				if ($requestHandlerObj->isRestricted())
					throw new Exception("The requested ajax request handler is restricted");
				
				$response = $requestHandlerObj->processRequest();
				
				if (!($response instanceof AjaxResponse))
					throw new Exception("The specified ajax request handler ($requestHandler) did not return a valid ajax response");
				
				return $response;
			}
			catch (Exception $ex)
			{
				$response = new AjaxResponse("", array("Exception: " . $ex->getMessage()));
				return $response;
			}
		}
		
		/**
		 * Restricts the ajax request handler
		 * 
		 * A restricted ajax request handler can only be executed by a user that has an active session
		 * 
		 * @return boolean true if the ajax request handler is restricted, false otherwise
		 */
		public function isRestricted()
		{
			return false;
		}
		
		/**
		 * Get HTTP request parameters
		 * 
		 * Gets the HTTP request parameters associated with this
		 * AjaxRequestHandler
		 * 
		 * @return array The HTTP request parameters
		 */
		public function getParameters()
		{
			return $this->parameters;
		}
	}
?>