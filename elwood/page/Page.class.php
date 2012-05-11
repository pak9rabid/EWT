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

	namespace elwood\page;
	
	use elwood\page\element\Element;
	
	abstract class Page
	{
		protected $elements = array();
		
		abstract public function name(array $parameters);
		abstract public function head(array $parameters);
		abstract public function style(array $parameters);
		abstract public function content(array $parameters);
		abstract public function isRestricted(array $parameters);
				
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
		
		public function javascript(array $parameters)
		{
			if (empty($this->elements))
				return "";
				
			$out = array();
			
			foreach ($this->elements as $element)
				$out[] = $element->javascript();
			
			return implode("\n", $out);
		}
	}
?>