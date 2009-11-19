<?php

/**
 * Simple Load queries format:
 * <code>
 * SIMPLE LOAD
 *   http://some/resource/uri
 *   http://another/resource/uri
 *   ...
 * PROPERTIES
 *   http://some/property/uri
 *   http://another/property/uri
 *   ...
 * </code>
 */
class Query_Simple_Load extends RPC_Service
{
	/**
	 * Query string.
	 * @datatype text
	 */
	static $PARAM_QUERY_STRING = 'query';
	
	/**
	 * Temporarily: SPARQL XML Results format.
	 */
	public function execute()
	{
		$querystr = $this->getParam(self::$PARAM_QUERY_STRING);
		
		$engine = new Simple_Load_Engine;
		$engine->useQueryString($querystr);
		$engine->setResultHandler(new SPARQL_XML_Results_Renderer);

		@header('Content-type: application/rdf+xml');
		$engine->runQuery();
	}
}

?>