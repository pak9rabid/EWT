<?php
	namespace elwood\page\element;
	
	class HiddenInput extends InputElement
	{
		public function __construct($name = "", $value = "")
		{
			$this->setName($name);
			$this->setValue($value);
			$this->setAttribute("type", "hidden");
		}
		
		// Override
		public function content()
		{			
			return "<input " . $this->attributesOut() . ">";
		}
	}
?>