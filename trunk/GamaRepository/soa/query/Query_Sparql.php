<?php

/**
 * SPARQL queries.
 */
class Query_Sparql extends RPC_Service
{
	/**
	 * Query string.
	 * @datatype text
	 * @required
	 */
	static $PARAM_QUERY_STRING = 'query';
	
	/**
	 * SPARQL XML Results format, see
	 * <a href="http://www.w3.org/TR/rdf-sparql-XMLres/">http://www.w3.org/TR/rdf-sparql-XMLres/</a>
	 */
	public function execute()
	{
		$querystr = $this->getParam(self::$PARAM_QUERY_STRING);
		
		$engine = new SPARQL_Engine;
		$engine->useSparql($querystr);
		$engine->setResultHandler(new SPARQL_XML_Results_Renderer);
		
		@header('Content-type: application/rdf+xml');
		$engine->runQuery();
	}
}

?>