<?php
	namespace elwood\page\element;
	
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
			$this->value = $value;
			return $this;
		}
		
		public function getLabel()
		{
			return $this->label;
		}
		
		public function getValue()
		{
			return $this->value;
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