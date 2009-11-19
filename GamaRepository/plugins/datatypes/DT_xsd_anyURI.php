<?php

/**
 * The URIs defined by RFCs 2396 and 2732
 */
class DT_xsd_anyURI extends GAMA_Datatype
{
	static public function getUri()
	{
		return 'http://www.w3.org/2001/XMLSchema#anyURI';
	}
	
	public function getColumnDefinition()
	{
		return array( 'object' => 'varchar(512) null default null' );
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
		// fix value from DB-Adapter containing spaces 
		$url = str_replace(' ', '%20', trim($literal->value) );

		return array('object' => $url);
	}
}
?>