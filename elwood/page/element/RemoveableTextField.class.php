<?php
	namespace elwood\page\element;
	
	class RemoveableTextField extends TextField
	{
		protected $rmButton;
		protected $mouseoverColor = "#EEE";
		
		public function __construct($name = "", $value = "")
		{
			parent::__construct($name, $value);
			$this->rmButton = new Button($name . "RmButton", "-");
			$this->rmButton->addClass("removeBtn");
		}
		
		// Override
		public function content()
		{
			$superOut = parent::content();
			$name = $this->getName();			
			
			return <<<END
			
			<div class="removeable" id="{$name}Container">
				$superOut<div style="width: 35px; display: inline-block;">&nbsp;{$this->rmButton}</div>
			</div>
END;
		}
		
		// Override
		public function javascript()
		{
			$name = $this->getName();
			$container = $name . "Container";
			$rmButton = $name . "RmButton";
			
			return parent::javascript() . $this->rmButton->javascript() . <<<END
			
			$(function()
			{
				$("#$container").mouseover(function()
				{
					if (!$("#$name").is(":disabled"))
					{
						$("#$container").css("background", "{$this->mouseoverColor}");
						$("#$rmButton").show();
					}
				});
				
				$("#$container").mouseout(function()
				{
					if (!$("#$name").is(":disabled"))
					{
						$("#$container").css("background", "");
						$("#$rmButton").hide();
					}
				});
				
				$("#$rmButton").click(function()
				{
					$("#$container").remove();
				});
			});
END;
		}
		
		public function getRmButton()
		{
			return $this->rmButton;
		}
		
		public function getMouseoverColor()
		{
			return $this->mouseoverColor;
		}
		
		public function setMouseoverColor($color = "#EEE")
		{
			$this->mouseoverColor = $color;
			return $this;
		}
	}
?>