<?php
/**
 * Filtering based on intersestion of date intervals.
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_dateintersect extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	public function execute()
	{
		$DTIURI = DT_gama_dateInterval::getUri();
		
		$d1 = $this->shiftParam(self::P_LITERAL + self::P_VAR)->getDatatypeBinding( $DTIURI );
		$d2 = $this->shiftParam(self::P_LITERAL + self::P_VAR)->getDatatypeBinding( $DTIURI );
		
		return "($d1[dfrom] < $d2[dto]) and ($d1[dto] > $d2[dfrom])";
	}
}
?>