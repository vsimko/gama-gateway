<?php
/**
 * Syntactic sugar for matching a list of values.
 * Example:
 *   Instead of: ?x=1 || ?x=2 || ?x=3
 *   You can write: gama:inList(?x,1,2,3)
 * 
 * @param array $colName
 * @param array $_ optional variable arguments
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_inlist extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	public function execute()
	{
		$colName = $this->shiftParam(self::P_VAR);
		
		$values = '';
		while($this->hasMoreParameters())
		{
			$v = $this->shiftParam(self::P_URI + self::P_VAR)->getBindValue();
			GAMA_Utils::addStringDelimited($values, ',', $v);
		}
		
		return $colName->getBindValue()." in ($values)";
	}	
}
?>