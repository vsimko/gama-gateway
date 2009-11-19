<?php
/**
 * Extracts the sorting hash from the labelType datatype.
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_getsorthash extends http___gama_gateway_eu_schema_dbalias
{
	/**
	 * (non-PHPdoc)
	 * @see plugins/sparqlfunc/http___gama_gateway_eu_schema_dbalias#execute()
	 */
	public function execute()
	{
		$this->params[] = array(
			'value'	=> 'value_sort',
			'type'	=> 'literal' );

		return parent::execute();
	}	
}
?>