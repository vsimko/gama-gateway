<?php
/**
 * Reqular expressions with the optional "i" modifier for
 * case-insensitive matching.
 * 
 * Example: FILTER regex(?x, "^Test.[0-9]")
 * Example: FILTER regex(?x, "test", "i")
 * 
 * @author Viliam Simko
 */
class http___www_w3_org_2006_sparql_functions_regex extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	public function execute()
	{
		$var = $this->shiftParam(self::P_VAR);
		$value = $this->shiftParam(self::P_VAR);
		
		if($this->hasMoreParameters())
		{
			$op = $this->shiftParam(self::P_LITERAL)->getRawValue() == 'i'
				? 'RLIKE' : 'RLIKE BINARY';
		} else
		{
			$op = 'RLIKE BINARY';
		}
		
		return $var->getOutValue()." $op ".$value->getOutValue();
	}	
}
?>