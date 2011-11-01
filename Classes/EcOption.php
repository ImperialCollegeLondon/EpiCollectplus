<?php
	class EcOption{
		public $label;
		public $value;
		public $idx;
		
		public function fromArray($arr)
		{
			foreach(array_keys($arr) as $key)
			{
				$this->$key = $arr[$key];
			}
		}
	}
?>