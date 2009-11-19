<?php

/**
 * Return a single number value as a result of a SPARQL query.
 */
class Single_Value_From_Sparql extends RPC_Service
{
	/**
	 * Query string.
	 * @datatype text
	 * @required
	 */
	static $PARAM_QUERY_STRING = 'query';
	
	/**
	 * Single number.
	 */
	public function execute()
	{
		$queryString = $this->getParam(self::$PARAM_QUERY_STRING, self::REQUIRED);
				
		$xmlresult = $this->getRpcClient()->{'query/Query_Sparql'}($queryString);
		
		$parser = new SPARQL_XML_Results_Parser;
		$parser->setResultHandler($this, 'onResult');
		
		$this->resultBufffer = array();
		$parser->parseFromString($xmlresult);
		
		if(count($this->resultBufffer) < 1)
		{
			throw new Exception('There was no result from the given SPARQL');
		}
		
		@header('Content-type: text/plain');
		echo array_shift($this->resultBufffer[0]);
	}
	
	private $resultBufffer;
	public function onResult($parser, $record)
	{
		$this->resultBufffer[] = $record;
	}
}

?>