<?php

/**
 * Accepts true, false, and also 1 (for true) and 0 (for false).
 */
class DT_xsd_boolean extends DT_xsd_integer
{
	static public function getUri()
	{
		return 'http://www.w3.org/2001/XMLSchema#boolean';
	}

	public function getColumnDefinition()
	{
		return array( 'object' => 'bool not null default false' );
	}
	
	public function getParsedLiteral(RDFS_Literal $literal)
	{
		$booleanValue = $literal->value ? 1 : 0;
		
		return array( 'object' => $booleanValue );
	}
	
}
?>