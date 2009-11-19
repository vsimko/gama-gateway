<?php
/**
 * Extracts an arbitrary column from the property table
 * @param $var this variable represents the database table
 * @param $columnName the column to be extracted
 * @param $var2 (optional)
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_dbalias extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	public function execute()
	{
		$var = $this->shiftParam(self::P_VAR);
		$columnName  = $this->shiftParam(self::P_LITERAL)->getRawValue();

		$bind = $var->getSpecialBinding( $columnName );
		
		if(! $this->hasMoreParameters())
		{
			return $var->getSpecialBinding( $columnName );
		}
		
		$this->shiftParam(self::P_VAR)->bindWith( $bind );
	}	
}
?>