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

	namespace elwood\page\element;
	
	class ComboBox extends InputElement
	{
		protected $options = array();
		
		public function __construct($name = "", array $options = array())
		{
			$this->setName($name);
			$this->setOptions($options);
			$this->addClass("elwoodInput");
		}
		
		public function getOptions()
		{
			return $this->options;
		}
	
		public function setOptions(array $options)
		{
			$this->options = $options;
			return $this;
		}
		
		public function addOption($label, $value)
		{
			$this->options[$label] = $value;
			return $this;
		}
		
		public function removeOption($label)
		{
			unset($this->options[$label]);
			return $this;
		}
		
		public function clearOptions()
		{
			$this->options = array();
			return $this;
		}
		
		// Override
		public function content()
		{
			$out = "<select " . $this->attributesOut() . ">";
			
			foreach ($this->options as $label => $value)
				$out .= "<option value=\"$value\"" . ($value == $this->getValue() ? " selected=\"selected\"" : "") . ">$label</option>";
				
			return $out . "</select>";
		}
	}
?>