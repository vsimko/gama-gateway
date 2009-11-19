<?php

/**
 * The size of the value is reatricted by the PHP's memory limit.
 */
class DT_gama_binary extends GAMA_Datatype
{
	static public function getUri()
	{
		return 'http://gama-gateway.eu/schema/binary';
	}

	public function getColumnDefinition()
	{
		return array(	'object'	=> 'binary(16) not null',
						'data'		=> 'blob' );
	}
	
	public function getSortingIndexDefinition()
	{
		return 'data(20)';
	}
		
	public function getValueDefinition($tabalias)
	{
		return "$tabalias.data";
	}

	public function getSortingValueDefinition($tabalias)
	{
		return "$tabalias.data";
	}
	
	public function getJoinDefinition($tabalias)
	{
		return "$tabalias.object";
	}
	
	public function getLangJoinDefinition($tabalias)
	{
		return "$tabalias.lang";
	}

	public function getParsedLiteral(RDFS_Literal $literal)
	{
		return array(	'object'	=> hash('md5', $literal->value, true),
						'lang'		=> $literal->lang,
						'data'		=> &$literal->value);
	}
}

?>