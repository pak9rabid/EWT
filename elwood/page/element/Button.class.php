<?php
	namespace elwood\page\element;
	
	class Button extends InputElement
	{
		public function __construct($name = "", $value = "")
		{
			$this->setName($name);
			$this->setValue($value);
			
			$this->setAttribute("type", "button");
			$this->addClass("elwoodInput");
		}
		
		// Override
		public function content()
		{
			return	"<button " . $this->attributesOut() . ">" . $this->getValue() . "</button>";
		}
	}
?>