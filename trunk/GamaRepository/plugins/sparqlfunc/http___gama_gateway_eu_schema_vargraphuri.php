<?php
/**
 * Extracts the sorting hash from the labelType datatype.
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_vargraphuri extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	public function execute()
	{
		// take the first parameter given to the function
		$var = $this->shiftParam(self::P_VAR);
		
		// a nasty hack for detecting variables that represent the RESOURCE table.
		// because the RESOURCE table does not use the "g" column
		$varname = $var->getRawValue();
		if(preg_match('/\.uri$/', $this->engine->vars[$varname]['out']))
		{
			throw new SPARQL_Engine_Exception("Cannot use variable ?$varname in the gama:varGraphURI function");
		}
		
		// take only the "g" column of the table represented by the  sparql variable
		$vargbind = $var->getSpecialBinding('g');
		
		// generate extra variable that can be used for joining the GRAPH table
		static $genvarseq;
		$extraVarname = 'vargraphuri_'.(++$genvarseq);
		
		// now use the extra variable and add the GRAPH table
		$this->engine->updateVar2($extraVarname, 'GRAPH');
		
		// now join the "g" column with the "id" column
		$this->engine->joinStatement($extraVarname, 'var', $vargbind);

		// and return the URI from GRAPH as a literal
		return $this->engine->vars[$extraVarname]['tab'].'.uri';
	}	
}
?>