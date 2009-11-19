<?php

/**
 * XMLSchema definition: unsignedByte is ·derived· from unsignedShort by
 * setting the value of ·maxInclusive· to be 255. The ·base type· of
 * unsignedByte is unsignedShort.
 * 
 * MySQL: UNSIGNED TINYINT 0..255
 */
class DT_xsd_unsignedByte extends DT_xsd_integer
{
	static public function getUri()
	{
		return 'http://www.w3.org/2001/XMLSchema#unsignedByte';
	}
	
	public function getColumnDefinition()
	{
		return array('object' => 'tinyint');
	}
}
?>