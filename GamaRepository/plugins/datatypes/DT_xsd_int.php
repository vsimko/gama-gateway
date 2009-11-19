<?php

/**
 * Integer between -2147483648 and 2147483647.
 */
class DT_xsd_int extends DT_xsd_integer
{
	static public function getUri()
	{
		return 'http://www.w3.org/2001/XMLSchema#int';
	}
	
	public function getColumnDefinition()
	{
		return array('object' => 'int');
	}
}
?>