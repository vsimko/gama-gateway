<?php

/**
 * Aggreagted SPARQL queries.
 * 
 * <b>Example:</b>
 * <code>
 * PREFIX gama: <http://gama-gateway.eu/schema/>
 * AGGREGATE ?archive => archive_name => ?name + ", "
 * SELECT * { ?archive gama:archive_name ?name }
 * AGGREGATE ?archive => homepage => ?homepage + \n
 * SELECT * { ?archive gama:archive_homepage ?homepage }
 * </code>
 * 
 * <b>Simplified EBNF definition:</b>
 * <code>
 * AggregatedQuery  ::= Preamble? QueryBody+
 * Preamble         ::= SparqlPrefixClause | SparqlBaseClause
 * QueryBody        ::= AggregateClause SparqlSelectClause
 * AggreagteClause  ::= WhiteSpace* "AGGREGATE:" AggregPattern
 * AggregPattern    ::= AggregPath* AggregValue "+" Delimiter
 * AggregPath       ::= AggregValue "=>"
 * AggregValue      ::= VariableDef | StringDef
 * VariableDef      ::= ("$" | "?") RestrictedChar+
 * StringDef        ::= '"' Char+ '"' | Char+
 * Delimiter        ::= StringDef
 * </code>
 */
class Aggregated_Sparql extends RPC_Service implements SPARQL_Result_Handler_Interface
{
	/**
	 * Query string.
	 * @datatype text
	 * @required
	 */
	static $PARAM_QUERY_STRING = 'query';
	
	/**
	 * Enable faster JSON implementation which avoids unnecessary white spaces.
	 * @datatype boolean
	 * @optional
	 */
	static $PARAM_COMPACT_JSON = 'compact';
	
	/**
	 * JSON format depending on the aggregation patterns
	 */
	public function execute()
	{
		$querystr = $this->getParam(self::$PARAM_QUERY_STRING);
		
		$allParsed = $this->splitAggregates($querystr);
		
		$engine = new SPARQL_Engine;
		$engine->setResultHandler($this);
		
		@header('Content-type: text/plain');
		foreach($allParsed as $singleParsed)
		{
			$engine->useSparql($singleParsed['query']);
			$this->currentAggregPattern = $singleParsed['aggreg'];
			$engine->runQuery();
		}
		
		if($this->getParam(self::$PARAM_COMPACT_JSON, self::OPTIONAL))
		{
			//debug($this->getParam(self::$PARAM_FAST_JSON, self::OPTIONAL));
			echo json_encode($this->aggregatedResult);
		} else
		{
			echo GAMA_Utils::jsonPrettyEncode($this->aggregatedResult);
		}
	}
	
	private function splitAggregates($querystr)
	{
		// remove comments
		$querystr = preg_replace('/^\s*(#|\/\/).*/m', '', $querystr);

		$split = preg_split('/^\s*AGGREGATE/mi', $querystr);
		$first = array_shift($split);
				
		$result = array();
		foreach($split as $singleQueryStr)
		{
			$lines = explode("\n", $singleQueryStr);
			$aggPattern = array_shift($lines);
			
			$result[] = array(
				'aggreg'	=> $this->parseAggregPattern($aggPattern), 
				'query'		=> $first."\n".implode("\n", $lines)
				);
		}
		
		return $result;
	}
	
	private function parseAggregPattern($string)
	{
		$result = array();
		foreach(explode("=>", $string) as $chunk)
		{
			// identifies variable string and delimiter
			if(preg_match('/^\s*(([\?\$])?([^\+]+)\s*(\+\s*(.*))?)\s*$/', $chunk, $match))
			{
				$x = & $result[];
				
				$x['isvar'] = !empty($match[2]);
				$x['val'] = stripcslashes(preg_replace('/"(.*)"/', '$1',trim($match[3])));
				
				if(isset($match[5]))
				{
					$x['delim'] = stripcslashes(preg_replace('/"(.*)"/', '$1', $match[5]));
				}
			}
		}
		return $result;
	}
	
	private $currentAggregPattern;
	private $aggregatedResult;
	
	/**
	 * (non-PHPdoc)
	 * @see interfaces/SPARQL_Result_Handler_Interface#onFoundResult($caller, $record)
	 */
	function onFoundResult($caller, array $record)
	{
		$ref = & $this->aggregatedResult;
		
		// process everything except the last element
		for($i=0; $i<count($this->currentAggregPattern)-1;++$i)
		{
			$aggreg = $this->currentAggregPattern[$i];
			if($aggreg['isvar']) // use the item as an index of the array
			{
				if(empty($aggreg['val']))
				{
					$ref = & $ref[];
				} else
				{
					$idx = $record[ $aggreg['val'] ];
					$ref = & $ref[ $idx ];
				}
			} else // use the item as a value of the array
			{
				$idx = $aggreg['val'];
				$ref = & $ref[ $idx ];
			}
		}
		
		// process the last element
		$aggreg = end($this->currentAggregPattern);
		
		if(isset($aggreg['delim']))
		{
			GAMA_Utils::addStringDelimited($ref, $aggreg['delim'], $record[ $aggreg['val'] ] );
		} else
		{
			$ref = $record[ $aggreg['val'] ];
		}
	}
	
	function onBeginResults($caller, array $outputVariables, array $debugString = array()){} // nothing
	function onEndResults($caller){} // nothing
	function onComment($commentString){} // nothing
}

?>