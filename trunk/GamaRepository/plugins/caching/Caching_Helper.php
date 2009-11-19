<?php
abstract class Caching_Helper
{
	const HARMONISATION_GRAPH_ID = 'http://gama-gateway.eu/harmonisation/';
	const CACHING_GRAPH_ID = 'http://gama-gateway.eu/cache/';
	
	protected $sourceProperty;
	protected $targetProperty;
	protected $owlSameAs;
	protected $harmonisationGraphId;
	
	/**
	 * @param $sourcePropertyUri
	 * @param $targetPropertyUri
	 */
	public function __construct($sourcePropertyUri, $targetPropertyUri)
	{
		$resmgr = Resource_Manager::singleton();
		$store = GAMA_Store::singleton();
		
		$this->sourceProperty = $resmgr->getResourceByUri($sourcePropertyUri);
		$this->targetProperty = $resmgr->getResourceByUri($targetPropertyUri);
		$this->owlSameAs = $resmgr->getResourceByUri(GAMA_Store::OWL_SAME_AS_URI);
		
		// get graph ID of harmonisation
		$store->setGraph(self::HARMONISATION_GRAPH_ID);
		$this->harmonisationGraphId = $store->getGraphID();
		
		// use this graph ID when updating statement tables
		$store->setGraph(self::CACHING_GRAPH_ID);
	}
	
	/**
	 * The function performs following operations:
	 * - drops all columns except "g" and "subject"
	 * - fills the subject table with distinct IDs from the domain of the target property
	 */
	public function startCaching()
	{
		$store = GAMA_Store::singleton();
		 
		// ===============
		app_lock(LOCK_EX);
		// ===============
		try
		{
			// -----------------------------------------------------------------
			$this->report("Dropping old data and columns from table '{$this->targetProperty->getUri()}' ({$this->targetProperty->getStmtTab()})\n");
			debug_time_measure(__METHOD__);
			
			$store->sql("delete from {$this->targetProperty->getStmtTab()}");
			
			// get all property-specific columns
			$oldColNames = $store->sqlFetchColumn('column_name', '
					SELECT column_name
					FROM INFORMATION_SCHEMA.COLUMNS
					WHERE table_schema = ?
						and table_name = ?
						and not column_name in ("g", "subject")
			', $store->getDatabaseName(), $this->targetProperty->getStmtTab() );
			
			// drop old columns
			foreach($oldColNames  as $columnName)
			{
				$this->report("  Dropping old column: $columnName\n");
				$store->sql("alter table {$this->targetProperty->getStmtTab()} drop column $columnName");
			}
			
			$this->report("Dropping took ".debug_time_measure(__METHOD__)."s\n");
			
			// -----------------------------------------------------------------
			$this->report("\nFilling empty values for property '{$this->targetProperty->getUri()}' ({$this->targetProperty->getStmtTab()}) ... ");
			debug_time_measure(__METHOD__);
			
			$domainId = $store->sqlFetchValue(
				'select dom from PROPERTY where uri = ?',
				$this->targetProperty->getUri() );
				
			$store->sql("
				insert into {$this->targetProperty->getStmtTab()} (g,subject)
				select ?, id from RESOURCE where type=?",
				$store->getGraphID(), $domainId );
			
			$this->report(debug_time_measure(__METHOD__)."s\n");
			
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
		// ==========
		app_unlock();
		// ==========
	}
	
	protected function report($text)
	{
		echo "$text";
	}	
}
?>