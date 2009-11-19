<?php

/**
 * Faster version of regex function suitable for searching by prefix.
 * 
 * Example:
 * FILTER gama:prefixMatch("A", ?value)
 * 
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_prefixmatch extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	function execute()
	{
		$prefixMatchValue = $this->shiftParam(self::P_LITERAL);
		$prefixMatchVar = $this->shiftParam(self::P_VAR);
		
		// also replaces the mysql_escape_string function
		$value = str_replace(
			array('%',   '_',   "'" ),
			array('\\%', '\\_', "\\'"),
			$prefixMatchValue->getRawValue() );
		
		return $prefixMatchVar->getBindValue()." LIKE '$value%'";
	}
}
?>