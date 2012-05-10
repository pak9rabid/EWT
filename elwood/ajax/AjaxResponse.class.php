<?php
	namespace elwood\ajax;
	
	class AjaxResponse
	{
		protected $data;
		protected $errors = array();
	
		public function __construct($data = "", array $errors = array())
		{
			$this->data = $data;
			$this->errors = $errors;
		}
	
		public function hasErrors()
		{
			return count($this->errors) > 0;
		}
	
		public function getResponseText()
		{
			return $this->data;
		}
	
		public function setErrors(array $errors)
		{
			$this->errors = $errors;
		}
	
		public function setData($data)
		{
			$this->data = $data;
		}
	
		public function toJson()
		{
			foreach ($this as $key => $value)
				@$json->$key = $value;
	
			return json_encode($json);
		}
	}
?>