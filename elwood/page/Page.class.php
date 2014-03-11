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

	namespace elwood\page;
	
	use elwood\page\element\Element;
	use elwood\config\Config;
	use elwood\log\Log;
	use elwood\usr\page\DefaultPage;
	use Exception;
	use ErrorException;
	
	abstract class Page
	{
		protected $elements = array();
		protected $parameters = array();
		
		abstract public function __construct(array &$parameters);
		abstract public function id();
		abstract public function name();
				
		public static function render(array $parameters = array())
		{
			set_error_handler(function($errno, $errstr, $errfile, $errline)
			{
				if ($errno === E_RECOVERABLE_ERROR)
					throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
			});
			
			$config = Config::getInstance();
			$template = $config->getSetting(Config::OPTION_WEBSITE_TEMPLATE);
			$templatePath = implode(DIRECTORY_SEPARATOR, array(__DIR__, "..", "..", "templates", $template));
			
			if (!is_readable($templatePath))
				throw new Exception("Website template does not exist: " . $template);
			
			$requestedPage = isset($parameters['page']) ? $parameters['page'] : $config->getSetting(Config::OPTION_WEBSITE_DEFAULT_PAGE);
			$pageClass = $requestedPage . "Page";
			$page = self::loadPage($pageClass, $parameters);
			
			ob_start();
			include $templatePath;
			return ob_get_clean();
		}
		
		private static function loadPage($pageClass, array &$parameters)
		{
			$pageClass = "elwood\\usr\\page\\" . $pageClass;
			
			try
			{
				$page = new $pageClass($parameters);
			}
			catch (Exception $ex)
			{
				Log::writeAlert("Could not load page class (" . $pageClass . "): " . $ex->getMessage() . "...loading the default page instead");
				$page = self::loadDefaultPage($parameters);
			}
			
			if (!($page instanceof Page))
			{
				Log::writeAlert("Could not load page class (" . $pageClass . "): The object is not a Page type...loading the default page instead");
				$page = self::loadDefaultPage($parameters);
			}
			
			if (!$result = $page->isRestricted())
				return $page;
			
			return self::loadPage($result, $parameters);
		}
		
		private static function loadDefaultPage(array &$parameters)
		{
			$pageClass = "elwood\\usr\\page\\" . Config::getInstance()->getSetting(Config::OPTION_WEBSITE_DEFAULT_PAGE) . "Page";
			return new $pageClass($parameters);
		}
		
		public function head()
		{
		}
		
		public function style()
		{
		}
		
		public function content()
		{
		}
		
		public function isAccessible()
		{
			return false;
		}
		
		public function isRestricted()
		{
			return false;
		}
		
		public function javascript()
		{
			if (empty($this->elements))
				return "";
		
			$out = array();
		
			foreach ($this->elements as $element)
				$out[] = $element->javascript();
		
			return implode("\n", $out);
		}
						
		public function addElement(Element $element)
		{
			$this->elements[$element->getName()] = $element;
		}
		
		public function getElement($elementName)
		{
			if (isset($this->elements[$elementName]))
				return $this->elements[$elementName];
				
			return null;
		}
		
		public function getElements()
		{
			return $this->elements;
		}
		
		public function getParameters()
		{
			return $this->parameters;
		}
	}
?>