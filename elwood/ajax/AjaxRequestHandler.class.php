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
	
	/**
	 * Top-level interface for all ajax request handlers.
	 * 
	 * All ajax request handlers must implement this interface.
	 * 
	 * @author Patrick Griffin <pak9rabid@yahoo.com>
	 */
	interface AjaxRequestHandler
	{
		/**
		 * Process the ajax request
		 * 
		 * Processes the incoming ajax request.
		 * 
		 * @param array $parameters A copy of the $_REQUEST superglobal array
		 * @return AjaxResponse The ajax response
		 */
		public function processRequest(array $parameters);
		
		/**
		 * Restricts the ajax request handler
		 * 
		 * A restricted ajax request handler can only be executed by a user that has an active session
		 * 
		 * @param array $parameters A copy of the $_REQUEST superglobal array
		 * @return boolean true if the ajax request handler is restricted, false otherwise
		 */
		public function isRestricted(array $parameters);
	}
?>