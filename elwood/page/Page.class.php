<?php
	namespace elwood\page;
	use elwood\page\element\Element;
	
	abstract class Page
	{
		protected $elements = array();
		
		public function addElement(Element $element)
		{
			$this->elements[$element->getName()] = $element;
		}
		
		public function getElement($elementName)
		{
			if (isset($this->elements[$elementName]))
				return $this->elements[$elementName];
				
			return null;
		}
		
		public function getElements()
		{
			return $this->elements;
		}
		
		public function javascript(array $parameters)
		{
			if (empty($this->elements))
				return "";
				
			$out = array();
			
			foreach ($this->elements as $element)
				$out[] = $element->javascript();
			
			return implode("\n", $out);
		}
		
		abstract public function name(array $parameters);
		abstract public function head(array $parameters);
		abstract public function style(array $parameters);
		abstract public function content(array $parameters);
		abstract public function isRestricted(array $parameters);
	}
?>