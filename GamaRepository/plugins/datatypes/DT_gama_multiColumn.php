<?php

/**
 * This is a special datatype that may contain multiple columns depending
 * on the caching mechanism.
 * 
 * @author Viliam Simko
 */
class DT_gama_multiColumn extends GAMA_Datatype
{
	static public function getUri()
	{
		return 'http://gama-gateway.eu/schema/multiColumn';
	}
	
	public function getColumnDefinition()
	{
		return null;
	}
	
	public function getSortingIndexDefinition()
	{
		return null;
	}
	
	public function getIndexDefinition()
	{
		return null;
	}
	
	public function getValueDefinition($tabalias)
	{
		return "concat('Multivalue of subject:', $tabalias.subject)";
	}
	
	public function getSortingValueDefinition($tabalias)
	{
		return null;
	}
	
	public function getJoinDefinition($tabalias)
	{
		return null;
	}

	
	/**
	 * Expected value is a JSON string in the format (language=>value)
	 * @param RDFS_Literal $literal
	 */
	public function getParsedLiteral(RDFS_Literal $literal)
	{
		$result = json_decode($literal->value, true);
		if(!is_array($result))
		{
			throw new GAMA_Datatype_Value_Exception('Unsupported format of the value. Use JSON associative array encoded as language=>value.');
		}
		
		// just to be sure nobody is messing around the engine
		unset($result['object']);
		unset($result['g']);
		unset($result['subject']);
		
		return $result;
	}
}
?>