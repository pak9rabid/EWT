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
	
	class RadioButtonGroup extends ComboBox
	{
		public function __construct($name = "", array $options = array())
		{
			parent::__construct($name, $options);
			
			$this->setAttribute("type", "radio");
			$this->addClass("elwoodInput");
		}
		
		// Override
		public function content()
		{
			$out = "";
			
			foreach ($this->getOptions() as $label => $value)
			{
				// this ensures that each radio button input shares the same name, but has a unique id and its own value
				$attributes = $this->attributesOut();
				$attributes = preg_replace("/id=\"([^\"]*)\"/", "id=\"" . $this->getName() . "$value\"", $attributes);
				
				if (preg_match("/value=/", $attributes))
					$attributes = preg_replace("/value=\"([^\"]*)\"/", "value=\"$value\"", $attributes);
				else
					$attributes .= " value=\"$value\"";
				
				if ($this->getValue() == $value)
					$attributes .= "checked=\"checked\"";
				
				$out .= "<input $attributes>&nbsp;$label<br>";
			}
			
			return $out;
		}
		
		// Override
		public function javascript()
		{
			if (empty($this->eventHandlers))
				return "";
				
			$out = array("$(function(){");
			
			foreach ($this->eventHandlers as $event => $handlers)
			{
				foreach ($handlers as $handler)
				{
					foreach ($this->getOptions() as $label => $value)
					{
						$id = $this->getName() . $value;
						$out[] = "$('#$id').bind('$event', $handler);\n";
					}
				}
			}
			
			$out[] = "});\n";
			return implode("\n", $out);
		}
	}
?>