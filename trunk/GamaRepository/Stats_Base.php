<?php
abstract class Stats_Base
{
	abstract public function reloadStats();
	
	public function __construct()
	{
		$this->reloadStats();
	}
	
	public function cleanupStats()
	{
		foreach($this as $propertyName => $propertyValue)
		{
			$this->$propertyName = array();
		}
	}
}
?>