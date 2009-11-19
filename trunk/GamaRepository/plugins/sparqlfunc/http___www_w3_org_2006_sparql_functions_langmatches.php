<?php
/**
 * In GAMA, this is equivalent to:FILTER ( lang(?x) = lang(?y) )
 * Example: FILTER langMatches(lang(?x), lang(?y))
 * 
 * @author Viliam Simko
 */
class http___www_w3_org_2006_sparql_functions_langmatches extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	public function execute()
	{
		// this is still nasty (accessing the parameters directly)
		$a = $this->engine->handleConstraint( $this->params[0] );
		$b = $this->engine->handleConstraint( $this->params[1] );
		return	$b == '"*"' ? "($a <> '' and not $a is null)" : $a.' = '.$b;
	}
}
?>