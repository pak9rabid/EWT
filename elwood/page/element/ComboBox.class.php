<?php
	namespace elwood\page\element;
	
	class ComboBox extends InputElement
	{
		protected $options = array();
		
		public function __construct($name = "", array $options = array())
		{
			$this->setName($name);
			$this->setOptions($options);
			$this->addClass("elwoodInput");
		}
		
		public function getOptions()
		{
			return $this->options;
		}
	
		public function setOptions(array $options)
		{
			$this->options = $options;
			return $this;
		}
		
		public function addOption($label, $value)
		{
			$this->options[$label] = $value;
			return $this;
		}
		
		public function removeOption($label)
		{
			unset($this->options[$label]);
			return $this;
		}
		
		public function clearOptions()
		{
			$this->options = array();
			return $this;
		}
		
		// Override
		public function content()
		{
			$out = "<select " . $this->attributesOut() . ">";
			
			foreach ($this->options as $label => $value)
				$out .= "<option value=\"$value\"" . ($value == $this->getValue() ? " selected=\"selected\"" : "") . ">$label</option>";
				
			return $out . "</select>";
		}
	}
?>