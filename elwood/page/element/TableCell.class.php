<?php
	namespace elwood\page\element;
	
	class TableCell extends Element
	{
		protected $content;
		protected $isHeading = false;
		
		public function __construct($name = "", $content = "", $isHeading = false)
		{
			$this->name = $name;
			$this->content = $content;
			$this->isHeading = (boolean)$isHeading;
		}
		
		public static function newCell($name = "", $content = "", $isHeading = false)
		{
			// convenience factory method
			return new self($name, $content, $isHeading);
		}
		
		// Override
		public function content()
		{
			$attributes = $this->attributesOut();
			$cellTag = $this->isHeading ? "th" : "td";
			
			return "<$cellTag" . (!empty($attributes) ? " $attributes" : "") . ">" . $this->content . "</$cellTag>";
		}
		
		public function isHeading()
		{
			return $this->isHeading;
		}
		
		public function setIsHeading($isHeading)
		{
			$this->isHeading = (boolean)$isHeading;
			return $this;
		}
	}
?>