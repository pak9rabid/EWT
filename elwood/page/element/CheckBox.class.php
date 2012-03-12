<?php
	namespace elwood\page\element;
	
	class CheckBox extends InputElement
	{	
		public function __construct($name = "", $isSelected = false)
		{
			$this->setName($name);
			$this->setSelected($isSelected);
			$this->setAttribute("type", "checkbox");
			$this->addClass("elwoodInput");
		}
		
		public function isSelected()
		{
			return array_key_exists("checked", $this->attributes);
		}
		
		public function setSelected($isSelected)
		{
			if ($isSelected)
				$this->setAttribute("checked", "checked");
			else
				$this->removeAttribute("checked");
				
			return $this;
		}
		
		// Override
		public function content()
		{
			return "<input " . $this->attributesOut() . ">";
		}
	}
?>