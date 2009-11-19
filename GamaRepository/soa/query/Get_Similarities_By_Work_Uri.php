<?php
/**
 * Generates a list of similar manifestations for a given Work.
 */
class Get_Similarities_By_Work_Uri extends RPC_Service
{
	/**
	 * URI of the Work.
	 * @datatype uri
	 * @required
	 */
	static $PARAM_WORK_URI = 'uri';
	
	/**
	 * XML Document in the SPARQL XML result format containing
	 * the following output variables:
	 * - manif_uri
	 * - similar_manif
	 * - shotid
	 * - weight
	 * - bestmatch
	 */
	public function execute()
	{
		$workURI = $this->getParam(self::$PARAM_WORK_URI);
		
		$rpcclient = $this->getRpcClient();
		
		@header('Content-type: text/plain');
		
		echo $rpcclient->{'query/Query_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT ?manif_uri ?similar_manif ?shotid ?weight ?bestmatch
			WHERE {
			  FILTER gama:similar_media( ?manif_uri, ?shotid, ?similar_manif, ?weight, ?bestmatch )
			  ?work_uri a gama:Work; gama:has_manifestation ?manif_uri.
			  FILTER (?work_uri = '.GAMA_Utils::escapeSparqlUri($workURI).')
			}
		');
	}
}
?>