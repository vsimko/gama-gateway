<?php
/**
 * Extracts only the beginning date from the dateInterval datatype.
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_dateintervalbegin extends http___gama_gateway_eu_schema_dbalias
{
	/**
	 * (non-PHPdoc)
	 * @see plugins/sparqlfunc/http___gama_gateway_eu_schema_dbalias#execute()
	 */
	public function execute()
	{
		$this->params[] = array('value'=>'dfrom', 'type'=>'literal');
		return parent::execute();
	}	
}
?>