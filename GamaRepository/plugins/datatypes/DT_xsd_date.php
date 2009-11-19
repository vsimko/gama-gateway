<?php

/**
 * Gregorian calendar date as defined by the ISO 8601.
 * MySQL: YYYY-MM-DD format is supported
 */
class DT_xsd_date extends GAMA_Datatype
{
	static public function getUri()
	{
		return 'http://www.w3.org/2001/XMLSchema#date';
	}
	
	public function getColumnDefinition()
	{
		return array( 'object' => 'date' );
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
		try
		{
			$dateObject = new DateTime($literal->value);
			$dateStr = $dateObject->format('Y-m-d');
		} catch (Exception $e)
		{
			throw new GAMA_Datatype_Value_Exception($e->getMessage());
		}
		
		return array('object' => $dateStr);
	}
}
?>