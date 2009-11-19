<?php
/**
 * Compiles a SPARQL query to SQL query.
 * TODO: unstable service
 */
class Sparql_To_Sql extends RPC_Service
{
	/**
	 * SPARQL query string.
	 * @datatype text
	 * @required
	 */
	static $PARAM_QUERY_STRING = 'query';
	
	/**
	 * SQL query string compatible with the current GAMA RDF Repository.
	 */
	public function execute()
	{
		$querystr = $this->getParam(self::$PARAM_QUERY_STRING);
		
		$engine = new SPARQL_Engine;
		$engine->useSparql($querystr);

		@header('Content-type: text/plain');
		echo $engine->getSql();
	}
}

?>