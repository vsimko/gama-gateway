<?php

/**
 * Number of works.
 */
class Stat_Num_Works extends RPC_Service
{
	const EMPTY_PLACEHOLDER = '-';
	
	/**
	 * Filter on archive URI.
	 * Leave empty if you don't want to filter on archive URI.
	 * The "-" value represents empty archive URI.
	 * @datatype uri
	 * @optional
	 */
	static $PARAM_ARCHIVE_URI = 'archive_uri';
	
	/**
	 * Restrict on work_type property.
	 * Leave empty if you don't want to filter on work type.
	 * The "-" value represents empty work type.
	 * @datatype uri
	 * @optional
	 */
	static $PARAM_WORK_TYPE = 'work_type';
	
	/**
	 * @param $filter
	 * @return string
	 */
	protected function constructQuery($filter)
	{
		return "
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?work) as ?num {
				?work a gama:Work.
				$filter
			}";
	}

	/**
	 * @param $workTypeUri
	 * @return string
	 */
	protected function getWorkTypeFilter($workTypeUri)
	{
		if($workTypeUri == self::EMPTY_PLACEHOLDER)
		{
			return 'OPTIONAL {?work gama:work_type ?wt}.FILTER gama:isEmpty(?wt)';
		} elseif(!empty($workTypeUri))
		{
			return '?work gama:work_type '.GAMA_Utils::escapeSparqlUri($workTypeUri);
		}
	}
	
	/**
	 * @param $archiveUri
	 * @return string
	 */
	protected function getArchiveFilter($archiveUri)
	{
		if($archiveUri == self::EMPTY_PLACEHOLDER)
		{
			return 'OPTIONAL {?work gama:provided_by ?arch}.FILTER gama:isEmpty(?arch)';
		} elseif(!empty($archiveUri))
		{
			return '?work gama:provided_by '.GAMA_Utils::escapeSparqlUri($archiveUri);
		}
	}

	/**
	 * @return array
	 */
	protected function getFilters()
	{
		$filters = array(
			$this->getWorkTypeFilter($this->getParam(self::$PARAM_WORK_TYPE, self::OPTIONAL)),
			$this->getArchiveFilter($this->getParam(self::$PARAM_ARCHIVE_URI, self::OPTIONAL)),
			);
		
		// remove empty items
		return array_filter($filters);
	}
	
	/**
	 * A single number on the first line representing the spreadsheet cell.
	 * The other lines might contain some debugging-related output.
	 * 
	 */
	public function execute()
	{
		$filterstr = implode(".\n", $this->getFilters());
		$sparqlQuery = $this->constructQuery( $filterstr );
		
		@header('Content-type: text/plain');
		
		$engine = new SPARQL_Engine;
		$engine->useSparql($sparqlQuery);
		$engine->setResultHandler(new Single_Value_Extractor);
		
		try
		{
			$engine->runQuery();
		} catch(Single_Value_Extractor $e)
		{
			echo array_shift($e->getFoundValue());
			echo "\n".preg_replace('/\t\t\t/', '', $sparqlQuery)."\n";
			return;
		}
		
		throw new Exception('There was no result from the given SPARQL');
	}
}
?>
