<?php
/**
 * Build sorting sequences for works based on multiple rules
 * @author Viliam Simko
 */
class Build_Cache_Work_Sorting extends RPC_Service
{
	/**
	 *  The caching progress will be reported progressively.
	 */
	public function execute()
	{
		ob_implicit_flush(1);
		@header('Content-type: text/plain');
		
		debug_time_measure(__METHOD__);
		
		$caching = new Caching_Helper_Column_From_Sparql(
			'http://gama-gateway.eu/cache/work_sorting' );

		$caching->startCaching();
		
		$listLanguages = preg_split('/,\s*/', Config::get('dt.multilangstring.languages')); 
		
		// sequence sorted by work_title, creator's name
		// =========================================================================
		foreach($listLanguages  as $this->lang)
		{
			$caching->buildSortingColumn("title_$this->lang",
				$this->getSparqlQuery(
					array("title", "cname"),
					array("title", "cname") )
			);
		}
		
		// sequence sorted by creator's name, work_title
		// =========================================================================
		foreach($listLanguages  as $this->lang)
		{
			$caching->buildSortingColumn("cname_$this->lang",
				$this->getSparqlQuery(
					array("title", "cname"),
					array("cname", "title") )
			);
		}

		// sequence sorted by work_created, creator's name, work_title
		// =========================================================================
		foreach($listLanguages  as $this->lang)
		{
			$caching->buildSortingColumn("created_$this->lang",
				$this->getSparqlQuery(
					array("title", "cname", "created"),
					array("created", "title") )
			);
		}

		// sequence sorted by archive's name, creator's name, work_title
		// =========================================================================
		foreach($listLanguages  as $this->lang)
		{
			$caching->buildSortingColumn("archive_$this->lang",
				$this->getSparqlQuery(
					array("title", "cname", "archive"),
					array("archive", "cname", "title") )
			);
		}
		
		echo "Building took ".debug_time_measure(__METHOD__)." seconds\n";
		
		echo "all done.\n";
	}
	
	/**
	 * The current language.
	 * Set in the foreach loop.
	 * @var string
	 */
	private $lang;

	/**
	 * Prepare the SPARQL query.
	 * @param array $listPatterns which patterns should be included
	 * @param array $listOrderBy variables to order
	 * @return string
	 */
	private function getSparqlQuery(array $listPatterns, array $listOrderBy)
	{
		$patterns = '';
		
		foreach($listPatterns as $patternName)
		{
			if($patternName == "title")
			{
				$patterns .= "
					# precached work title
					OPTIONAL {
						?uri cache:work_title ?wtcache.
						FILTER gama:dbalias(?wtcache, '$this->lang', ?title).
					}
				";
			} elseif($patternName == "cname")
			{
				$patterns .= "
					# precached creator's name
					OPTIONAL {
						?uri cache:work_creator ?creator.
						?creator cache:person_name ?cncache.
						FILTER gama:dbalias(?cncache, 'value', ?cname).
					}
				";
			} elseif($patternName == "created")
			{
				$patterns .= "
					# work created (beginnig of the interval)
					OPTIONAL {
						?uri gama:work_created ?cinterval.
						FILTER (?created = gama:dateIntervalBegin(?cinterval)).
					}
				";
			} elseif($patternName == "archive")
			{
				$patterns .= "
					# archive name
					OPTIONAL {
					    ?uri gama:provided_by ?archuri.
						?archuri gama:archive_name ?archive.
					}
				";
			}
		}
		
		// use ASC(item) for every item
		foreach($listOrderBy as $key => $value)
		{
			$listOrderBy[$key] = "ASC(?$value)";
		}
		
		return "
			PREFIX gama: <http://gama-gateway.eu/schema/>
			PREFIX cache: <http://gama-gateway.eu/cache/>
			SELECT DISTINCT ?uri {
				?uri a gama:Work.
				$patterns
			} ORDER BY ". implode(" ", $listOrderBy);
	}
	
}
?>