<?php
	namespace elwood\page\element;
	
	class SaveButton extends Button
	{
		public function __construct($name = "", $value = "")
		{
			parent::__construct($name, $value);
			
			if (empty($value))
				$this->setValue("Save");
		}
		
		// Override
		public function javascript()
		{
			$js = <<<END
			
			$(function()
			{
				$('#{$this->getName()}').click(function()
				{
					$(this)
						.html("Saving...&nbsp;<img src='images/loading.gif'>")
						.attr("disabled", "disabled");
				});
			});
END;
			return $js . parent::javascript();
		}
	}
?>