<?php
/**
 * Builds the content of the cache:fulltext_works property.
 */
class Build_Cache_Fulltext_Works extends RPC_Service
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
			'http://gama-gateway.eu/cache/fulltext_works' );

		$caching->startCaching();
		
		$listLanguages = preg_split('/,\s*/', Config::get('dt.multilangstring.languages')); 
		
		// Harmonised work title in multiple languages
		// =====================================================================
		foreach($listLanguages  as $lang)
		{
			$caching->buildTextColumn("title_$lang", '
				PREFIX gama: <http://gama-gateway.eu/schema/>
				PREFIX cache: <http://gama-gateway.eu/cache/>
				select ?uri ?value
				{
					?uri cache:work_title ?cache.
					FILTER gama:dbalias(?cache, "'.$lang.'", ?value).
				}
			');
		}
		
		// Harmonised work description in multiple languages
		// =====================================================================
		foreach($listLanguages  as $lang)
		{
			$caching->buildTextColumn("descr_$lang", '
				PREFIX gama: <http://gama-gateway.eu/schema/>
				PREFIX cache: <http://gama-gateway.eu/cache/>
				select ?uri ?value
				{
					?uri cache:work_description ?cache.
					FILTER gama:dbalias(?cache, "'.$lang.'", ?value).
				}
			');
		}
		
		// gama:has_creator.gama:person_name
		// =========================================================================
		$caching->buildTextColumn('cname', '
			PREFIX gama: <http://gama-gateway.eu/schema/>
			PREFIX cache: <http://gama-gateway.eu/cache/>
			select * {
				_:p gama:is_creator ?uri.
				_:p cache:person_name ?cache.
				FILTER gama:dbalias(?cache, "value", ?value).
			}
		');

		// gama:has_contributor.gama:person_name
		// =========================================================================
		$caching->appendToTextColumn('cname', '
			PREFIX gama: <http://gama-gateway.eu/schema/>
			PREFIX cache: <http://gama-gateway.eu/cache/>
			select * {
				_:p gama:is_contributor ?uri.
				_:p cache:person_name ?cache.
				FILTER gama:dbalias(?cache, "value", ?value).
			}
		');
		
		// Indexing keywords of main manifestations
		// =========================================================================
		$caching->buildTextColumn('kw_ocr', '
			PREFIX gama: <http://gama-gateway.eu/schema/>
			PREFIX mysql: <http://www.mysql.com/>
			select ?uri ?value where {
				?uri a gama:Work.
				?uri gama:has_manifestation ?manif.
				?manif gama:idx_main "1".
				?manif gama:idx_keyword ?kw.
				FILTER gama:dbalias(?kw, "kwtype", "ocr")
				FILTER (?value = mysql:groupConcat("distinct", "prefix:o_", ?kw, "separator: "))
			} group by ?uri
		');
		
		$caching->buildTextColumn('kw_asr', '
			PREFIX gama: <http://gama-gateway.eu/schema/>
			PREFIX mysql: <http://www.mysql.com/>
			select ?uri ?value where {
				?uri a gama:Work.
				?uri gama:has_manifestation ?manif.
				?manif gama:idx_main "1".
				?manif gama:idx_keyword ?kw.
				FILTER gama:dbalias(?kw, "kwtype", "asr")
				FILTER (?value = mysql:groupConcat("distinct", "prefix:a_", ?kw, "separator: "))
			} group by ?uri
		');
		
		// fulltext index on columns
		// =========================================================================
		foreach($listLanguages  as $lang)
		{
			$caching->buildFulltextIndex("title_$lang", "descr_$lang", 'cname', 'kw_ocr', 'kw_asr');
		}
		
		echo "Building took ".debug_time_measure(__METHOD__)." seconds\n";
		
		echo "all done.\n";
	}
}
?>