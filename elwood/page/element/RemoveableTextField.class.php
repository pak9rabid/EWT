<?php
/**
 Copyright (c) 2012 Patrick Griffin

 Permission is hereby granted, free of charge, to any person obtaining
 a copy of this software and associated documentation files (the
 "Software"), to deal in the Software without restriction, including
 without limitation the rights to use, copy, modify, merge, publish,
 distribute, sublicense, and/or sell copies of the Software, and to
 permit persons to whom the Software is furnished to do so, subject to
 the following conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

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