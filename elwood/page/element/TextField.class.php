<?php
	namespace elwood\page\element;
	
	class TextField extends InputElement
	{		
		public function __construct($name = "", $value = "")
		{
			$this->setName($name);
			$this->setValue($value);
			
			$this->addClass("elwoodInput");
			$this->addClass("textfield");
			$this->setAttribute("type", "text");
			$this->setAttribute("size", "20");
		}
				
		// Override
		public function content()
		{			
			return "<input " . $this->attributesOut() . ">";
		}
	}
?>