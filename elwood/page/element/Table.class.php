<?php
	namespace elwood\page\element;
	
	class Table extends Element
	{
		protected $rows = array();
		
		public function __construct($name = "", array $rows = array())
		{
			$this->setName($name);
			$this->setRows($rows);
		}
		
		// Override
		public function javascript()
		{
			$js = parent::javascript();
			
			foreach ($this->getRows() as $row)
			{
				$tempJs = $row->javascript();
				
				if (!empty($tempJs))
					$js .= $tempJs;
			}
			
			return $js;
		}
		
		// Override
		public function content()
		{
			$out = "<table " . $this->attributesOut() . "><tbody>";
			
			foreach ($this->rows as $row)
				$out .= $row->content();
			
			return $out .= "</tbody></table>";
		}
		
		public function getRowCount()
		{
			return count($this->rows);
		}
		
		public function isValidRowIndex($index)
		{
			return is_int($index) && $index >= 0 && $index < count($this->rows);
		}
		
		public function getRow($index)
		{
			if (!$this->isValidRowIndex($index))
				throw new Exception("Invalid row index specified");
				
			return $this->rows[$index];
		}
		
		public function getRows()
		{
			return $this->rows;
		}
		
		public function setRows(array $rows)
		{
			foreach ($rows as $row)
			{
				if (!($row instanceof TableRow))
					throw new Exception("Invalid type...must be of type TableRow");
			}
			
			$this->rows = $rows;
			return $this;
		}
		
		public function addRow(TableRow $row)
		{
			$this->rows[] = $row;
			return $this;
		}
		
		public function removeRow($index)
		{
			if (!$this->isValidRowIndex($index))
				throw new Exception("Invalid row index specified");
				
			unset($this->rows[$index]);
			$this->rows = array_values($this->rows);
			return $this;
		}
		
		public function clearRows()
		{
			$this->rows = array();
			return $this;
		}
	}
?>