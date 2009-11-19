<?php
/**
 * @param array $mainManif
 * @param array $shotid
 * 
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_similar_shots extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	public function execute()
	{
		// The SQL subselect doesn't use indexes. It always performs the full table scan which is bad
		$tab = $this->engine->addTable('(select distinct manif, shotid from SIMILARITY)', 'just shots of manifestations');

		$mainManif = $this->shiftParam(self::P_VAR + self::P_URI);
		$shotId = $this->shiftParam(self::P_VAR);
		
		$mainManif->bindWithTable( $tab.'.manif' );
		$shotId->bindWith( $tab.'.shotid' );
	}	
}
?>