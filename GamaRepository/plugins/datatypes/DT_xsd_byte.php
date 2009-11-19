<?php

/**
 * Signed integer between -128 and 127. Fits in a word of 8 bits.
 */
class DT_xsd_byte extends DT_xsd_integer
{
	static public function getUri()
	{
		return 'http://www.w3.org/2001/XMLSchema#byte';
	}

	public function getColumnDefinition()
	{
		return array('object' => 'tinyint');
	}
}
?>