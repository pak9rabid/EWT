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
	
	use Exception;
	
	abstract class InputElement extends Element
	{
		protected $label;
		protected $value;
		
		public function setLabel($label)
		{
			$this->label = $label;
			return $this;
		}
		
		public function setValue($value)
		{
			if (!$this->isValid($value))
				throw new Exception("Invalid value specified: $value");
			
			$this->value = $value;
			return $this;
		}
		
		public function clearValue()
		{
			unset($this->value);
			return $this;
		}
		
		public function getLabel()
		{
			return $this->label;
		}
		
		public function getValue()
		{
			return isset($this->value) ? $this->value : null;
		}
		
		public function isValid($input)
		{
			return true;
		}
		
		// Override
		protected function attributesOut()
		{
			$out = explode(" ", parent::attributesOut());
			
			if (!empty($this->name))
				array_unshift($out, "name=\"" . $this->getName() . "\"");
			
			if (!empty($this->value))
				$out[] = "value=\"" . $this->getValue() . "\"";
				
			return implode(" ", $out);
		}
	}
?>