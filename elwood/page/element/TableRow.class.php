<?php
	namespace elwood\page\element;
	
	class TableRow extends Element
	{
		protected $cells = array();
		
		public function __construct($name = "", array $cells = array())
		{
			$this->name = $name;
			$this->setCells($cells);
		}
		
		public static function newRow($name = "", array $cells = array())
		{
			// convenience factory method
			return new self($name, $cells);
		}
		
		// Override
		public function javascript()
		{
			$js = parent::javascript();
			
			foreach ($this->cells as $cell)
			{
				$tempJs = $cell->javascript();
				
				if (!empty($tempJs))
					$js .= $tempJs;
			}
			
			return $js;
		}
				
		// Override
		public function content()
		{
			$out = "<tr " . $this->attributesOut() . ">";
			
			foreach ($this->cells as $cell)
				$out .= $cell->content();
			
			return $out .= "</tr>";
		}
		
		public function getCellCount()
		{
			return count($this->cells);
		}
		
		public function isValidCellIndex($index)
		{
			return is_int($index) && $index >= 0 && $index < count($this->cells);
		}
		
		public function getCell($index)
		{
			if (!$this->isValidCellIndex($index))
				throw new Exception("Invalid cell index specified");
					
			return $this->cells[$index];
		}
		
		public function getCells()
		{
			return $this->cells;
		}
		
		public function setCells(array $cells)
		{
			foreach ($cells as $cell)
			{
				if (!($cell instanceof TableCell))
					throw new Exception("Invalid type...must be of type TableCell");
			}
			
			$this->cells = $cells;
			return $this;
		}
		
		public function addCell(TableCell $cell)
		{
			$this->cells[] = $cell;
			return $this;
		}
		
		public function removeCell($index)
		{
			if (!$this->isValidCellIndex($index))
				throw new Exception("Invalid cell index specified");
				
			unset($this->cells[$index]);
			$this->cells = array_values($this->cells);
			return $this;
		}
		
		public function clearCells()
		{
			$this->cells = array();
			return $this;
		}
	}
?>