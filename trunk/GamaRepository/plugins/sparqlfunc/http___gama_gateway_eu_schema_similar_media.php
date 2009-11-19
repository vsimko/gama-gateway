<?php
/**
 * @param array $mainManif
 * @param array $shotId
 * @param array $similarManif
 * @param array $weight (optional)
 * @param array $bestMatch (optional)
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_similar_media extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	public function execute()
	{
		$mainManif = $this->shiftParam(self::P_VAR + self::P_URI);
		$shotId = $this->shiftParam(self::P_VAR);
		$similarManif = $this->shiftParam(self::P_VAR + self::P_URI);
		
		$tab = $this->engine->addTable('SIMILARITY', 'similarity match');
		
		$mainManif->bindWithTable( $tab.'.manif' );
		$similarManif->bindWithTable( $tab.'.smanif' );
		$shotId->bindWith( $tab.'.shotid' );

		if($this->hasMoreParameters())
		{
			// also bind the weight column
			$weight = $this->shiftParam(self::P_VAR);
			$weight->bindWith( $tab.'.weight' );
		}
		
		if($this->hasMoreParameters())
		{
			// also bind the bestmatch column
			$bestMatch = $this->shiftParam(self::P_VAR);
			$bestMatch->bindWith( $tab.'.bestmatch' );
		}
	}	
}
?>