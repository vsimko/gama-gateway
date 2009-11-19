<?php

/**
 * Provides the fulltext matching function from MySQL.
 * Example: FILTER gama:match("test", ?title, ?description)
 * - first parameter is the value
 * - other parameters are the variables to use in the fulltext matching
 */
class http___gama_gateway_eu_schema_match extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	function execute()
	{
		$ftMatchValue = $this->shiftParam(self::P_LITERAL);

		$sql = array();
		do
		{
			$ftMatchVar = $this->shiftParam(self::P_VAR);
			$sql[] = $ftMatchVar->getBindValue();
		} while($this->hasMoreParameters());
		
		return "MATCH(".implode(',', $sql).") AGAINST(".$ftMatchValue->getBindValue()." IN BOOLEAN MODE)";
	}
}
?>