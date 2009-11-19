<?php

/**
 * Year from the beggining of the gama:life_span interval - single harmonised
 * instance for every person.
 * @author Viliam Simko
 */
class Build_Cache_Person_Birth_Year extends RPC_Service
{
	/**
	 * Progress report
	 */
	public function execute()
	{
		ob_implicit_flush(1);
		@header('Content-type: text/plain');
		
		$harmonisation = new Harmonisation('http://gama-gateway.eu/schema/life_span');
		$personBirthDate = $harmonisation->fillEmptyValuesPerSubject('http://gama-gateway.eu/cache/person_birth_year');
		
		// fill the cache using teporary table operations
		$sql = "
			UPDATE
				{$personBirthDate->getStmtTab()} as target,
				({$harmonisation->sqlSingleHarmonisedValuePerSubject()}) as source
			SET target.object = source.dfrom
			WHERE target.subject = source.id;
		";
		
		// =========================================================================
		debug_time_measure(__METHOD__);
		
		GAMA_Store::singleton()->sql($sql);

		$timeTaken = debug_time_measure(__METHOD__);
		echo "Caching took $timeTaken seconds\n";
		// =========================================================================
		
		echo "all done.\n";
	}
}
?>