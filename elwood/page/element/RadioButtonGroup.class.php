<?php
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