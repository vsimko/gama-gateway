<?php

/**
 * Matching of literals based on Levenshtein distance.
 * Our implementation could possibly work with following MySQL functions:
 * - levaggreg(x,y) returns string
 * - levenshtein(x,y) returns int
 * - levnocase(x,y) returns int
 * - damlevlim(x,y,l) returns int
 * - davlevlimnocase(x,y,l) returns int
 * - damlevlim256(x,y,l) returns int
 * 
 * @param array $firstWord
 * @param array $secondWord
 * @param array $distance (optional)
 * 
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_levenshtein extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	function execute()
	{
		$firstWord = $this->shiftParam(self::P_LITERAL + self::P_VAR)->getOutValue();
		$secondWord = $this->shiftParam(self::P_LITERAL + self::P_VAR)->getOutValue();
		
		debug('FIRST:'.$firstWord);
		debug('SECOND:'.$secondWord);
		
		// this is the version without ordering
		if(! $this->hasMoreParameters())
		{
			return "damlevlimnocase($firstWord, $secondWord, 100)";
		}
				
		// this is the version with implicit orderdering
		$distance = $this->shiftParam(self::P_LITERAL);

		if($distance->getRawValue() <= 0)
		{
			throw new SPARQL_Engine_Exception('Only positive integer can be used as distance limit');
		}
		
		$varname = $this->addHiddenVar("damlevlimnocase($firstWord, $secondWord, 100)");
		$this->addHavingCondition( $varname.' <= '.$distance->getBindValue() );
	}
}
?>