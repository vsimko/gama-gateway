<?php
/**
 * Test the variable content also taking into account the OPTIONAL clause.
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_isempty extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	public function execute()
	{
		$var = $this->shiftParam(self::P_VAR)->getBindValue();
		return "($var is null or $var = \"\")";
	}	
}
?>