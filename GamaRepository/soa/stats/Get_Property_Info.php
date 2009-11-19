<?php
/**
 * Describes the given property..
 */
class Get_Property_Info extends RPC_Service
{
	/**
	 * URI of the property
	 * @datatype uri
	 * @required
	 */
	static $PARAM_PROPERTY_URI = 'uri';
	
	/**
	 * JSON format
	 * <table>
	 *   <tr><th>type</th><td>array of uris</td></tr>
	 *   <tr><th>domain</th><td>array of uris</td></tr>
	 *   <tr><th>range</th><td>array of uris</td></tr>
	 * </table>
	 */
	public function execute()
	{
		$propertyUri = $this->getParam(self::$PARAM_PROPERTY_URI, self::REQUIRED);
		
		@header('Content-type: text/plain');
		echo $this->getRpcClient()->{'query/Aggregated_Sparql'}('
		
		AGGREGATE type => ? => ?type
		SELECT * { '.GAMA_Utils::escapeSparqlUri($propertyUri).' a ?type } 
		
		AGGREGATE domain => ? => ?dom
		SELECT * { '.GAMA_Utils::escapeSparqlUri($propertyUri).' rdfs:domain ?dom } 

		AGGREGATE range => ? => ?rng
		SELECT * { '.GAMA_Utils::escapeSparqlUri($propertyUri).' rdfs:range ?rng }
		');
	}
}

?>