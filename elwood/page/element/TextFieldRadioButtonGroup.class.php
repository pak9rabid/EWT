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
	
	class TextFieldRadioButtonGroup extends InputElement
	{
		protected $options;
		
		public function __construct($name = "", array $options = array())
		{
			$this->setName($name);			
			$this->setOptions($options);
			$this->setAttribute("type", "radio");
			$this->addClass("elwoodInput");
		}
		
		public function getOptions()
		{
			return $this->options;
		}
		
		public function setOptions(array $options)
		{
			foreach ($options as $option)
				$this->addOption($option);
			
			return $this;
		}
		
		// Override
		public function addOption(TextField $option)
		{
			$this->options[] = $option;
			return $this;
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
					for ($i=0 ; $i<count($this->getOptions()) ; $i++)
					{
						$id = $this->getName() . $i;
						$out[] = "$('#$id').bind('$event', $handler);\n";
					}
				}
			}
			
			foreach ($this->options as $textField)
			{
				foreach ($textField->getHandlers() as $event => $handlers)
				{
					foreach ($handlers as $handler)
						$out[] = "$('#" . $textField->getName() . "').bind('$event', $handler);\n";
				}
			}
			
			$out[] = "});\n";
			return implode("\n", $out);
		}
		
		// Override
		public function content()
		{
			$out = "";
			$index = 0;
			
			foreach ($this->getOptions() as $option)
			{
				$id = $this->getName() . $index;
				$attributes = $this->attributesOut();
				$attributes = preg_replace("/id=\"([^\"]*)\"/", "id=\"$id\"", $attributes);
				
				if (preg_match("/value=/", $attributes))
					$attributes = preg_replace("/value=\"([^\"]*)\"/", "value=\"$index\"", $attributes);
				else
					$attributes .= " value=\"" . $index . "\"";
				
				$value = $this->getValue();
					
				if (!empty($value))
				{
					if ($value == $option->getValue())
						$attributes .= " checked=\"checked\"";
				}
					
				$out .= "<div id=\"$id-container\"><input $attributes>&nbsp;$option</div>";
				
				$index++;
			}
			
			return $out;
		}
	}
?>