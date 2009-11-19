<?php
/**
 * NASTY HACK: Removes duplicates from equivalent property.
 * 
 * - First parameter is the SPARQL variable
 * - Second (optional) paramter is the index of a join statement. This is
 *   useful when the variable has been used in multiple statements.
 * 
 * Example snippet:
 * 
 * 	?p gama:person_name ?name.
 * 	?p owl:sameAs ?p2.
 * 	FILTER gama:removeEqDups(?p,1)
 * 	FILTER gama:match("Woody", ?name)
 * 
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_removeeqdups extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	function execute()
	{
		$var = $this->shiftParam(self::P_VAR);
		
		// optional parameter is the join index
		if($this->hasMoreParameters())
		{
			$joinidx = $this->shiftParam(self::P_LITERAL);
			$joinidx = $joinidx->getRawValue();
		} else
		{
			$joinidx = 0;
		}
		
		$log = $var->getJoinLog();
		
		$bindval = $var->getBindValue();
		
		if(!isset($log[$joinidx]))
		{
			throw new SPARQL_Engine_Exception(
				"This join index does not exists: $joinidx\n".
				"You can, however, try one of the following indexes:\n".
				print_r($log, true)
			);
		}
		
		// table name of the first item in the log
		$rtab = preg_replace('/\..*/', '', $log[$joinidx]);
		
		return "($rtab.object is null or $rtab.object = $bindval) /* join index: $joinidx */";
	}
}
?>