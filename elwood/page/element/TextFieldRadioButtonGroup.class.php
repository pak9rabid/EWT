<?php
	namespace elwood\page\element;
	
	class TextFieldRadioButtonGroup extends RadioButtonGroup
	{
		public function __construct($name = "", array $options = array())
		{
			$this->setName($name);			
			$this->setOptions($options);
			$this->setAttribute("type", "radio");
			$this->addClass("elwoodInput");
		}
		
		// Override
		public function setOptions(array $options)
		{
			foreach ($options as $option)
			{
				if (!($option instanceof TextField))
					throw new Exception("Invalid type...must be of type TextField");
			}
			
			$this->options = $options;
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