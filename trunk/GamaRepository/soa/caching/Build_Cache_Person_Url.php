<?php
/**
 * There are some persons that have multiple person_urls.
 * These values will be aggregated into a single cell delimited by whitespace
 * since URLs in general cannot contain whitespaces.
 * 
 * @author Viliam Simko
 */
class Build_Cache_Person_Url extends RPC_Service
{
	/**
	 * Progress report as a simple text.
	 */
	public function execute()
	{
		ob_implicit_flush(1);
		@header('Content-type: text/plain');
		
		$harmonisation = new Harmonisation('http://gama-gateway.eu/schema/person_url');
		$cachePersonUrl = $harmonisation->fillEmptyValuesPerSubject('http://gama-gateway.eu/cache/person_url');
		
		// =========================================================================
		debug_time_measure(__METHOD__);

		GAMA_Store::singleton()->sql("
			SET session group_concat_max_len = @@max_allowed_packet;
			UPDATE
				{$cachePersonUrl->getStmtTab()} as target,
				({$harmonisation->sqlAggregatedHarmonisedValuesPerSubject('object')}) as source
			SET target.data = source.value
			WHERE target.subject = source.id;
		");

		$timeTaken = debug_time_measure(__METHOD__);
		echo "Caching took $timeTaken seconds\n";
		// =========================================================================
		
		
		echo "all done.\n";
	}
}
?>