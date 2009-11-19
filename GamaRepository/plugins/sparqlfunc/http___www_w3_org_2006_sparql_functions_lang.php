<?php
/**
 * Use this builtin call if you want to filter a specific language
 * inside the SPARQL.
 * 
 * @author Viliam Simko
 */
class http___www_w3_org_2006_sparql_functions_lang extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	public function execute()
	{
		return $this->shiftParam(self::P_VAR)->getLangValue();
	}	
}
?>