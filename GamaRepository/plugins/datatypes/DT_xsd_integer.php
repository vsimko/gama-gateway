<?php

/**
 * Signed integer with no restriction on range.
 * MySQL: BIGINT -9223372036854775808..9223372036854775807
 */
class DT_xsd_integer extends GAMA_Datatype
{
	static public function getUri()
	{
		return 'http://www.w3.org/2001/XMLSchema#integer';
	}

	public function getColumnDefinition()
	{
		return array('object' => 'bigint');
	}
	
	public function getSortingIndexDefinition()
	{
		return 'object';
	}
	
	public function getValueDefinition($tabalias)
	{
		return "$tabalias.object";
	}
	
	public function getSortingValueDefinition($tabalias)
	{
		return "$tabalias.object";
	}
	
	public function getJoinDefinition($tabalias)
	{
		return "$tabalias.object";
	}
	
	public function getParsedLiteral(RDFS_Literal $literal)
	{
		// non-numerical values will be silently converted to 0
		// if we threw an exception it whould cause problems during the ingest
		return array( 'object' => $literal->value );
	}
	
}
?>